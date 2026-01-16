<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Table.php';
require_once __DIR__ . '/src/Database.php';

use SavannahDB\Database;

$db = new Database();

echo "Welcome to SavannahDB Console v1.0\n";
echo "Type 'exit' or 'quit' to leave.\n\n";

while (true) {
    $input = readline("\033[1;32mSavannahDB\033[0m ");

    // Handle EOF (Ctrl+D) or empty input
    if ($input === false) {
        break;
    }
    
    $input = trim($input);
    if ($input === '') {
        continue;
    }

    // Add to history
    readline_add_history($input);

    if (in_array(strtolower($input), ['exit', 'quit'])) {
        echo "Bye!\n";
        break;
    }

    try {
        $result = $db->execute($input);

        if (is_array($result)) {
            $count = count($result);
            if ($count === 0) {
                echo "Empty set.\n";
            } else {
                echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                echo "({$count} rows)\n";
            }
        } else {
            // String message (success)
            echo "✅ " . $result . "\n";
        }
    } catch (Throwable $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
