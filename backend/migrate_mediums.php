<?php
/**
 * Database Migration Script: Add Mediums Table and Medium ID to Subjects
 * Book Banko
 */
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to database.\n";

    // 1. Create mediums table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `mediums` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `code` VARCHAR(50) NOT NULL UNIQUE,
          `name` VARCHAR(100) NOT NULL,
          `description` VARCHAR(255) NULL,
          `icon` VARCHAR(100) DEFAULT 'translate',
          `display_order` INT NOT NULL DEFAULT 1,
          `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table 'mediums' verified/created.\n";

    // 2. Seed default mediums
    $db->exec("
        INSERT INTO `mediums` (`id`, `code`, `name`, `description`, `icon`, `display_order`, `status`) VALUES
        (1, 'gujarati', 'Gujarati Medium', 'Gujarati Medium Curriculum & Textbooks', 'translate', 1, 'active'),
        (2, 'english', 'English Medium', 'English Medium Curriculum & Textbooks', 'language', 2, 'active'),
        (3, 'hindi', 'Hindi Medium', 'Hindi Medium Curriculum & Textbooks', 'menu_book', 3, 'active')
        ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);
    ");
    echo "Default mediums seeded.\n";

    // 3. Add medium_id column to subjects table if not exists
    $columns = $db->query("SHOW COLUMNS FROM `subjects` LIKE 'medium_id'")->fetchAll();
    if (empty($columns)) {
        $db->exec("
            ALTER TABLE `subjects` 
            ADD COLUMN `medium_id` INT NULL DEFAULT 1 AFTER `board_id`,
            ADD CONSTRAINT `fk_subjects_medium` FOREIGN KEY (`medium_id`) REFERENCES `mediums`(`id`) ON DELETE SET NULL;
        ");
        echo "Added 'medium_id' column to 'subjects' table with foreign key.\n";
    } else {
        echo "'medium_id' column already exists in 'subjects' table.\n";
    }

    // Set existing subjects to Gujarati medium by default if NULL
    $db->exec("UPDATE `subjects` SET `medium_id` = 1 WHERE `medium_id` IS NULL");
    echo "Updated existing subjects to Gujarati medium.\n";

    echo "MIGRATION_COMPLETED_SUCCESSFULLY\n";
} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
