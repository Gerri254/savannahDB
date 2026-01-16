<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Table.php';
require_once __DIR__ . '/src/Database.php';

use SavannahDB\Database;

$db = new Database();
// Direct truncation for setup
$t = new \SavannahDB\Table('users');
$t->truncate();

// 1. Insert Data
echo "--- Inserting 100 users ---\\n";
for ($i = 1; $i <= 100; $i++) {
    $db->execute("INSERT INTO users (name, num) VALUES ('User{$i}', {$i})");
}

$file = __DIR__ . '/data/users.ndjson';
echo "File Size (Before Delete): " . filesize($file) . " bytes\\n";

// 2. Delete 50 users (Soft Delete)
echo "--- Deleting 50 users ---\\n";
for ($i = 1; $i <= 50; $i++) {
    $db->execute("DELETE FROM users WHERE id = {$i}");
}
echo "File Size (After Soft Delete): " . filesize($file) . " bytes (Should be same)\\n";

// 3. Vacuum
echo "--- Running VACUUM ---\\n";
$start = microtime(true);
$db->execute("VACUUM users");
$end = microtime(true);

echo "Vacuum Time: " . number_format(($end - $start) * 1000, 2) . "ms\\n";
echo "File Size (After VACUUM): " . filesize($file) . " bytes (Should be smaller)\\n";

// Verify Data Integrity
echo "--- Verifying remaining data ---\\n";
$rows = $db->execute("SELECT * FROM users");
$count = count($rows);
echo "Rows remaining: $count (Expected 50)\\n";
$first = $rows[0]['id'];
echo "First ID: $first (Expected 51)\\n";
