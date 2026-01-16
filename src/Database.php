<?php
declare(strict_types=1);

namespace SavannahDB;

use RuntimeException;

class Database
{
    private array $tables = [];

    public function execute(string $sql): array|string
    {
        $sql = trim($sql);

        // A. CREATE TABLE users
        if (preg_match('/^CREATE\s+TABLE\s+(\w+)$/i', $sql, $matches)) {
            return $this->handleCreate($matches[1]);
        }

        // B. INSERT INTO users (name, age) VALUES ('Gerald', 25)
        if (preg_match('/^INSERT\s+INTO\s+(\w+)\s+\((.+?)\)\s+VALUES\s+\((.+?)\)$/i', $sql, $matches)) {
            return $this->handleInsert($matches[1], $matches[2], $matches[3]);
        }

        // C. SELECT ... (Unified Handler)
        if (str_starts_with(strtoupper($sql), 'SELECT')) {
            return $this->handleSelect($sql);
        }

        // D. DELETE FROM users WHERE id = 1
        if (preg_match('/^DELETE\s+FROM\s+(\w+)\s+WHERE\s+id\s*=\s*(.+)$/i', $sql, $matches)) {
            return $this->handleDelete($matches[1], $matches[2]);
        }

        // E. VACUUM users
        if (preg_match('/^VACUUM\s+(\w+)$/i', $sql, $matches)) {
            return $this->handleVacuum($matches[1]);
        }

        throw new RuntimeException("Syntax Error or Unsupported Command: $sql");
    }

    private function handleSelect(string $sql): array
    {
        // 1. Extract ORDER BY (Optional, at the end)
        $orderByCol = null;
        $orderDir = 'ASC';
        
        if (preg_match('/\s+ORDER\s+BY\s+([a-zA-Z0-9_]+)(?:\s+(ASC|DESC))?$/i', $sql, $matches)) {
            $orderByCol = $matches[1];
            $orderDir = strtoupper($matches[2] ?? 'ASC');
            // Remove ORDER BY from SQL to simplify further parsing
            $sql = preg_replace('/\s+ORDER\s+BY\s+([a-zA-Z0-9_]+)(?:\s+(ASC|DESC))?$/i', '', $sql);
        }

        // 2. Extract WHERE (Optional)
        // Note: For now, we only support WHERE on the Simple Select path, or if explicitly handled.
        // We will extract it if present to separate the core command.
        $whereClause = null;
        if (preg_match('/\s+WHERE\s+(.+)$/i', $sql, $matches)) {
            $whereClause = trim($matches[1]);
            // Remove WHERE from SQL
            $sql = preg_replace('/\s+WHERE\s+(.+)$/i', '', $sql);
        }

        $sql = trim($sql);
        $results = [];

        // 3. Check for JOIN
        // Syntax: SELECT (.*) FROM ([a-z_]+) JOIN ([a-z_]+) ON ([a-z_\.]+)\s*=\s*([a-z_\.]+)
        if (preg_match('/^SELECT\s+(.+?)\s+FROM\s+([a-z_]+)\s+JOIN\s+([a-z_]+)\s+ON\s+([a-z_\.]+)\s*=\s*([a-z_\.]+)$/i', $sql, $matches)) {
             // JOIN Logic
             $cols = $matches[1];
             $tableAName = $matches[2];
             $tableBName = $matches[3];
             $onLeft = $matches[4]; // e.g. users.id
             $onRight = $matches[5]; // e.g. grades.user_id

             $tableA = $this->getTable($tableAName);
             $tableB = $this->getTable($tableBName);

             // Prepare Data
             $rowsA = iterator_to_array($tableA->selectAll());
             $rowsB = iterator_to_array($tableB->selectAll());

             // Parse ON conditions to get column names
             // format: tableName.columnName
             $leftParts = explode('.', $onLeft);
             $rightParts = explode('.', $onRight);
             
             $leftCol = end($leftParts);
             $rightCol = end($rightParts);

             // Nested Loop Join
             foreach ($rowsA as $rowA) {
                 foreach ($rowsB as $rowB) {
                     // Check condition (loose comparison for now to handle string/int mismatch)
                     if (isset($rowA[$leftCol]) && isset($rowB[$rightCol]) && $rowA[$leftCol] == $rowB[$rightCol]) {
                         // Merge rows. Keys might collide (e.g. 'id').
                         // In a real DB we'd use aliases. Here we just merge (Right overwrites Left).
                         $results[] = array_merge($rowA, $rowB);
                     }
                 }
             }

        } elseif (preg_match('/^SELECT\s+(.+?)\s+FROM\s+([a-z_]+)$/i', $sql, $matches)) {
            // Simple Select Logic
            $tableName = $matches[2];
            $table = $this->getTable($tableName);

            // Handle Optimized ID Lookup
            if ($whereClause && preg_match('/^id\s*=\s*(.+)$/', $whereClause, $wMatches)) {
                 $val = trim($wMatches[1]);
                 if ((str_starts_with($val, "'") && str_ends_with($val, "'")) || (str_starts_with($val, '"') && str_ends_with($val, '"'))) {
                     $val = substr($val, 1, -1);
                 } elseif (is_numeric($val)) {
                     $val = $val + 0;
                 }
                 
                 $row = $table->findById($val);
                 if ($row) {
                     $results[] = $row;
                 }
            } elseif ($whereClause) {
                // Scan + Filter
                // Parse simple "col = val"
                if (preg_match('/^(\w+)\s*=\s*(.+)$/', $whereClause, $wMatches)) {
                    $col = $wMatches[1];
                    $val = trim($wMatches[2]);
                     if ((str_starts_with($val, "'") && str_ends_with($val, "'")) || (str_starts_with($val, '"') && str_ends_with($val, '"'))) {
                         $val = substr($val, 1, -1);
                     } elseif (is_numeric($val)) {
                         $val = $val + 0;
                     }

                     foreach ($table->selectAll() as $row) {
                         if (isset($row[$col]) && $row[$col] == $val) {
                             $results[] = $row;
                         }
                     }
                } else {
                    // WHERE present but not supported format
                    throw new RuntimeException("Unsupported WHERE clause: $whereClause");
                }
            } else {
                // No WHERE
                $results = iterator_to_array($table->selectAll());
            }
        } else {
            throw new RuntimeException("Invalid SELECT Syntax: $sql");
        }

        // 4. Handle ORDER BY
        if ($orderByCol) {
            usort($results, function ($a, $b) use ($orderByCol, $orderDir) {
                $valA = $a[$orderByCol] ?? null;
                $valB = $b[$orderByCol] ?? null;

                if ($valA == $valB) return 0;
                
                // Numeric comparison if possible
                if (is_numeric($valA) && is_numeric($valB)) {
                    $cmp = ($valA < $valB) ? -1 : 1;
                } else {
                    $cmp = strcmp((string)$valA, (string)$valB);
                }

                return ($orderDir === 'DESC') ? -$cmp : $cmp;
            });
        }

        return $results;
    }

    private function getTable(string $name): Table
    {
        if (!isset($this->tables[$name])) {
            // Lazy load if it exists on disk, or throw if not created yet?
            // "CREATE TABLE" implies we explicitly create it.
            // But if we restart the script, we might want to load existing tables.
            // Let's try to instantiate it. If file doesn't exist, Table ctor creates it.
            // But strict SQL usually implies CREATE TABLE must be run first or checked.
            // For this persistent DB, we just instantiate.
            $this->tables[$name] = new Table($name);
        }
        return $this->tables[$name];
    }

    private function handleCreate(string $tableName): string
    {
        $this->tables[$tableName] = new Table($tableName);
        return "Table '$tableName' created.";
    }

    private function handleInsert(string $tableName, string $columnsStr, string $valuesStr): array
    {
        $table = $this->getTable($tableName);

        $columns = array_map('trim', explode(',', $columnsStr));
        
        // Use single quote as enclosure for SQL compliance
        $values = [];
        $parts = str_getcsv($valuesStr, ',', "'"); 
        foreach ($parts as $part) {
            $val = trim($part);
            // Handle numeric casting
            if (is_numeric($val)) {
                $val = $val + 0; // cast to int or float
            }
            $values[] = $val;
        }

        if (count($columns) !== count($values)) {
            throw new RuntimeException("Column count doesn't match value count.");
        }

        $data = array_combine($columns, $values);
        return $table->insert($data);
    }

    private function handleDelete(string $tableName, string $idVal): string
    {
        $table = $this->getTable($tableName);
        $idVal = trim($idVal);
        
        if (is_numeric($idVal)) {
            $idVal = $idVal + 0;
        }

        if ($table->delete($idVal)) {
            return "Row deleted.";
        }
        return "Row not found.";
    }

    private function handleVacuum(string $tableName): string
    {
        $table = $this->getTable($tableName);
        $table->compact();
        return "Table '$tableName' compacted.";
    }
}
