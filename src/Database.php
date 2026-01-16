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

        // C. SELECT * FROM users [WHERE ...]
        if (preg_match('/^SELECT\s+\*\s+FROM\s+(\w+)(?:\s+WHERE\s+(.+))?$/i', $sql, $matches)) {
            $tableName = $matches[1];
            $whereClause = $matches[2] ?? null;
            return $this->handleSelect($tableName, $whereClause);
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

    private function handleSelect(string $tableName, ?string $whereClause): array
    {
        $table = $this->getTable($tableName);

        if ($whereClause === null) {
            // Return all
            return iterator_to_array($table->selectAll());
        }

        // Parse WHERE clause
        // Supports: id = 1  OR  col = 'val'
        if (preg_match('/^(\w+)\s*=\s*(.+)$/', trim($whereClause), $whereMatches)) {
            $col = $whereMatches[1];
            $val = trim($whereMatches[2]);

            // Strip quotes if string
            if ((str_starts_with($val, "'") && str_ends_with($val, "'")) || (str_starts_with($val, '"') && str_ends_with($val, '"'))) {
                $val = substr($val, 1, -1);
            } elseif (is_numeric($val)) {
                $val = $val + 0;
            }

            // Optimization: Primary Key Lookup
            if ($col === 'id') {
                $row = $table->findById($val);
                return $row ? [$row] : [];
            }

            // Scan and Filter
            $results = [];
            foreach ($table->selectAll() as $row) {
                if (isset($row[$col]) && $row[$col] == $val) {
                    $results[] = $row;
                }
            }
            return $results;
        }

        throw new RuntimeException("Unsupported WHERE clause: $whereClause");
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
