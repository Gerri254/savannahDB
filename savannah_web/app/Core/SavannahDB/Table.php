<?php
declare(strict_types=1);

namespace App\Core\SavannahDB;

use Generator;
use RuntimeException;
use InvalidArgumentException;

class Table
{
    public string $name;
    public string $file;
    public string $primaryKey;
    private array $index = [];

    public function __construct(string $tableName, string $primaryKey = 'id')
    {
        if (empty($tableName)) {
            throw new InvalidArgumentException("Table name cannot be empty.");
        }

        $this->name = $tableName;
        $this->primaryKey = $primaryKey;
        // Assuming data directory is at the project root relative to src/
        $this->file = __DIR__ . '/../data/' . $tableName . '.ndjson';

        $this->ensureFileExists();
        $this->rebuildIndex();
    }

    private function ensureFileExists(): void
    {
        $directory = dirname($this->file);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                 throw new RuntimeException("Could not create directory: $directory");
            }
        }

        if (!file_exists($this->file)) {
            if (file_put_contents($this->file, '') === false) {
                throw new RuntimeException("Could not create table file: {$this->file}");
            }
        }
    }

    private function rebuildIndex(): void
    {
        $this->index = [];
        $handle = fopen($this->file, 'r');
        if ($handle === false) return;

        // Use shared lock just to be safe while reading
        if (flock($handle, LOCK_SH)) {
            try {
                $offset = 0;
                while (($line = fgets($handle)) !== false) {
                    $trimmedLine = trim($line);
                    // Calculate length of the line as read (including newline) for next offset
                    $lineLength = strlen($line);
                    
                    if ($trimmedLine !== '') {
                        $row = json_decode($trimmedLine, true);
                        if (is_array($row) && isset($row[$this->primaryKey])) {
                            $this->index[$row[$this->primaryKey]] = $offset;
                        }
                    }
                    $offset += $lineLength;
                }
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        } else {
            fclose($handle);
        }
    }

    public function insert(array $data): array
    {
        $handle = fopen($this->file, 'c+');
        if ($handle === false) {
            throw new RuntimeException("Could not open file: {$this->file}");
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new RuntimeException("Could not acquire exclusive lock for table: {$this->name}");
        }

        try {
            // Determine the next ID if not provided
            if (!isset($data[$this->primaryKey])) {
                // Since we have an index, finding max ID is easy if keys are numeric
                // Or we can just count(), but max() is safer for auto-increment behavior
                $maxId = 0;
                foreach (array_keys($this->index) as $id) {
                    if (is_int($id) && $id > $maxId) {
                        $maxId = $id;
                    }
                }
                $data[$this->primaryKey] = $maxId + 1;
            }

            $id = $data[$this->primaryKey];
            $jsonLine = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . PHP_EOL;

            // Get current size (offset for new line)
            fseek($handle, 0, SEEK_END);
            $offset = ftell($handle);
            
            fwrite($handle, $jsonLine);

            // Update Index
            $this->index[$id] = $offset;

            return $data;

        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function selectAll(): Generator
    {
        $handle = fopen($this->file, 'r');
        if ($handle === false) {
             throw new RuntimeException("Could not open file for reading: {$this->file}");
        }

        if (flock($handle, LOCK_SH)) {
            try {
                // Scan the file
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if ($line === '') continue;

                    $row = json_decode($line, true);
                    if (is_array($row) && isset($row[$this->primaryKey])) {
                        // Check if this ID is in the active index (not deleted)
                        if (isset($this->index[$row[$this->primaryKey]])) {
                            yield $row;
                        }
                    }
                }
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        } else {
            fclose($handle);
            throw new RuntimeException("Could not acquire shared lock for table: {$this->name}");
        }
    }

    public function findById(int|string $id): ?array
    {
        if (!isset($this->index[$id])) {
            return null;
        }

        $offset = $this->index[$id];
        $handle = fopen($this->file, 'r');
        
        if ($handle === false) {
             throw new RuntimeException("Could not open file for reading: {$this->file}");
        }

        try {
            if (flock($handle, LOCK_SH)) {
                fseek($handle, $offset);
                $line = fgets($handle);
                if ($line === false) return null;

                $row = json_decode(trim($line), true);
                if (is_array($row) && isset($row[$this->primaryKey]) && $row[$this->primaryKey] == $id) {
                    return $row;
                }
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return null;
    }

    public function update(int|string $id, array $data): bool
    {
        // NOTE: This performs a full rewrite, which is O(N).
        // It then rebuilds the index to stay consistent.
        if (!isset($this->index[$id])) {
            return false;
        }

        $updated = false;

        $this->atomicProcess(function (array $row) use ($id, $data, &$updated) {
            if (isset($row[$this->primaryKey]) && $row[$this->primaryKey] == $id) {
                $updated = true;
                return array_merge($row, $data);
            }
            // Filter out deleted items during rewrite (compaction)
            if (!isset($this->index[$row[$this->primaryKey]])) {
                return null; 
            }
            return $row;
        });

        if ($updated) {
            $this->rebuildIndex();
        }

        return $updated;
    }

    public function delete(int|string $id): bool
    {
        if (!isset($this->index[$id])) {
            return false;
        }

        $deleted = false;

        $this->atomicProcess(function (array $row) use ($id, &$deleted) {
            if (isset($row[$this->primaryKey]) && $row[$this->primaryKey] == $id) {
                $deleted = true;
                return null; // Return null to remove the row from the file
            }
            // Filter out other already soft-deleted items if any exist in index mismatch
            if (!isset($this->index[$row[$this->primaryKey]]) && $row[$this->primaryKey] != $id) {
                 return null;
            }
            return $row;
        });

        if ($deleted) {
            $this->rebuildIndex();
            return true;
        }
        return false;
    }

    public function compact(): void
    {
        $handle = fopen($this->file, 'r'); // Read original
        if ($handle === false) {
             throw new RuntimeException("Could not open file for compaction: {$this->file}");
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new RuntimeException("Could not acquire lock for compaction.");
        }

        $tempPath = $this->file . '.compact';
        $tempHandle = fopen($tempPath, 'w+');

        try {
            // Iterate strictly over the INDEX, which represents the source of truth for "active" rows.
            // (selectAll also filters by index, so we could use that, but iterating index keys is more direct if we want to preserve order or just dump)
            // However, selectAll reads strictly sequentially. Random seeking via index for every row might be slower than linear scan + filter.
            // Let's use a linear scan of the FILE, and check against the INDEX. This is O(N) IO but sequential.
            
            rewind($handle);
            while (($line = fgets($handle)) !== false) {
                $trim = trim($line);
                if ($trim === '') continue;

                $row = json_decode($trim, true);
                // If row is valid AND its ID is in our active index...
                if (is_array($row) && isset($row[$this->primaryKey])) {
                    if (isset($this->index[$row[$this->primaryKey]])) {
                        // Write to new file
                        fwrite($tempHandle, json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . PHP_EOL);
                    }
                }
            }

            // Atomic Swap
            // We need to release the lock on the original handle before replacing it on Windows, 
            // but on Linux we can rename over.
            // To be safe and portable:
            flock($handle, LOCK_UN); 
            fclose($handle);
            fclose($tempHandle);

            if (!rename($tempPath, $this->file)) {
                 throw new RuntimeException("Failed to swap compacted file.");
            }

            // Rebuild index to get new offsets
            $this->rebuildIndex();

        } catch (\Throwable $e) {
            // Cleanup on failure
            if (is_resource($handle)) fclose($handle);
            if (is_resource($tempHandle)) fclose($tempHandle);
            if (file_exists($tempPath)) unlink($tempPath);
            throw $e;
        }
    }

    public function truncate(): void
    {
        $handle = fopen($this->file, 'c+');
        if (flock($handle, LOCK_EX)) {
            ftruncate($handle, 0);
            flock($handle, LOCK_UN);
        }
        fclose($handle);
        $this->index = [];
    }

    private function atomicProcess(callable $callback): void
    {
        $handle = fopen($this->file, 'r+');
        if ($handle === false) {
            throw new RuntimeException("Could not open file for writing: {$this->file}");
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new RuntimeException("Could not acquire exclusive lock.");
        }

        $tempPath = $this->file . '.tmp';
        $tempHandle = fopen($tempPath, 'w+');

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') continue;

                $row = json_decode($line, true);
                if (!is_array($row)) continue;

                $processedRow = $callback($row);

                if ($processedRow !== null) {
                    fwrite($tempHandle, json_encode($processedRow, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . PHP_EOL);
                }
            }

            rewind($handle);
            ftruncate($handle, 0);
            rewind($tempHandle);
            stream_copy_to_stream($tempHandle, $handle);

        } finally {
            fclose($tempHandle);
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}