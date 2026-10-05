<?php
/**
 * Migration: Fix indexes and add board_id, medium_id to standards table
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "Fixing standards table indexes...\n";

    // Drop unique index on standard_number if exists
    $indexes = $db->query("SHOW INDEX FROM standards")->fetchAll(PDO::FETCH_ASSOC);
    $dropped = false;
    foreach ($indexes as $idx) {
        if ($idx['Key_name'] === 'standard_number' || ($idx['Column_name'] === 'standard_number' && $idx['Non_unique'] == 0 && $idx['Key_name'] !== 'PRIMARY')) {
            try {
                $db->exec("ALTER TABLE standards DROP INDEX `{$idx['Key_name']}`");
                echo "Dropped unique index: {$idx['Key_name']}\n";
                $dropped = true;
            } catch (Exception $e) {
                echo "Notice dropping index: " . $e->getMessage() . "\n";
            }
        }
    }

    // Add composite unique key if not exists
    try {
        $db->exec("ALTER TABLE standards ADD UNIQUE KEY `idx_board_medium_std` (board_id, medium_id, standard_number)");
        echo "Created composite unique index on (board_id, medium_id, standard_number).\n";
    } catch (Exception $e) {
        echo "Index notice: " . $e->getMessage() . "\n";
    }

    $gsebBoardId = $db->query("SELECT id FROM boards WHERE code = 'GSEB' LIMIT 1")->fetchColumn();
    $gujMedId = $db->query("SELECT id FROM mediums WHERE code IN ('gujrati', 'gujarati') LIMIT 1")->fetchColumn();
    $engMedId = $db->query("SELECT id FROM mediums WHERE code = 'english' LIMIT 1")->fetchColumn();
    $cbseBoardId = $db->query("SELECT id FROM boards WHERE code = 'CBSE' LIMIT 1")->fetchColumn();

    if ($cbseBoardId && $engMedId) {
        $exists = $db->query("SELECT COUNT(*) FROM standards WHERE board_id = $cbseBoardId AND medium_id = $engMedId AND standard_number = 6")->fetchColumn();
        if ($exists == 0) {
            $db->exec("INSERT INTO standards (board_id, medium_id, standard_number, name, requires_stream, display_order, status)
                       VALUES ($cbseBoardId, $engMedId, 6, 'Standard 6', 0, 1, 'active')");
            echo "Added CBSE English Medium Standard 6 successfully.\n";
        }
    }

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
