<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Table.php';
require_once __DIR__ . '/src/Database.php';

use SavannahDB\Database;

// Clear previous data for clean test
$tmpTable = new \SavannahDB\Table('users');
$tmpTable->truncate();
unset($tmpTable);

$db = new Database();

echo "--- 1. Create Table ---
";
echo $db->execute("CREATE TABLE users") . "\n";

echo "\n--- 2. Insert Data ---
";
$u1 = $db->execute("INSERT INTO users (name, role, age) VALUES ('Gerald', 'Architect', 25)");
print_r($u1);
$u2 = $db->execute("INSERT INTO users (name, role, age) VALUES ('Alice', 'Dev', 30)");
print_r($u2);
$u3 = $db->execute("INSERT INTO users (name, role, age) VALUES ('Bob', 'Dev', 22)");
print_r($u3);

echo "\n--- 3. Select All ---
";
$all = $db->execute("SELECT * FROM users");
print_r($all);

echo "\n--- 4. Select By ID (Optimized) ---
";
$byId = $db->execute("SELECT * FROM users WHERE id = 1");
echo "Found ID 1: " . json_encode($byId) . "\n";

echo "\n--- 5. Select By Non-ID (Scan) ---
";
$devs = $db->execute("SELECT * FROM users WHERE role = 'Dev'");
echo "Found Devs:\n";
print_r($devs);

echo "\n--- 6. Delete ---
";
echo $db->execute("DELETE FROM users WHERE id = 2") . "\n"; // Delete Alice

echo "\n--- 7. Verify Delete ---
";
$remaining = $db->execute("SELECT * FROM users");
print_r($remaining);