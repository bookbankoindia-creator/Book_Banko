<?php
/**
 * Database Migration & Seeder for Competitive Exams (JEE, NEET, GATE, etc.)
 * Book Banko Backend
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();

    // 1. Create table: competitive_exams
    $db->exec("
        CREATE TABLE IF NOT EXISTS `competitive_exams` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(150) NOT NULL,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `exam_code` VARCHAR(50) NOT NULL,
            `category` VARCHAR(100) DEFAULT 'Engineering / Medical',
            `description` TEXT NULL,
            `icon` VARCHAR(100) DEFAULT 'emoji_events',
            `color_hex` VARCHAR(10) DEFAULT '#0061A4',
            `banner_image` VARCHAR(255) NULL,
            `display_order` INT NOT NULL DEFAULT 1,
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Create table: competitive_exam_materials
    $db->exec("
        CREATE TABLE IF NOT EXISTS `competitive_exam_materials` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `exam_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `subject_name` VARCHAR(100) NULL,
            `material_type` VARCHAR(100) DEFAULT 'Question Paper',
            `year` VARCHAR(20) NULL,
            `description` TEXT NULL,
            `pdf_file_path` VARCHAR(255) NULL,
            `pdf_external_url` VARCHAR(500) NULL,
            `thumbnail` VARCHAR(255) NULL,
            `page_count` INT NOT NULL DEFAULT 1,
            `file_size_mb` DECIMAL(6,2) DEFAULT 0.00,
            `is_free` TINYINT(1) NOT NULL DEFAULT 1,
            `views_count` INT NOT NULL DEFAULT 0,
            `display_order` INT NOT NULL DEFAULT 1,
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`exam_id`) REFERENCES `competitive_exams`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Seed default competitive exams: JEE, NEET, GATE, GUJCET
    $exams = [
        [
            'id' => 1,
            'title' => 'JEE (Main & Advanced)',
            'slug' => 'jee',
            'exam_code' => 'JEE',
            'category' => 'Engineering Entrance',
            'description' => 'Joint Entrance Examination for IITs, NITs & Engineering Colleges. Solved PYQs, Formula Sheets & Mock Papers.',
            'icon' => 'engineering_rounded',
            'color_hex' => '#0061A4',
            'display_order' => 1,
            'status' => 'active'
        ],
        [
            'id' => 2,
            'title' => 'NEET (UG)',
            'slug' => 'neet',
            'exam_code' => 'NEET',
            'category' => 'Medical Entrance',
            'description' => 'National Eligibility cum Entrance Test for MBBS & BDS. Complete Biology, Physics & Chemistry Solved Papers.',
            'icon' => 'biotech_rounded',
            'color_hex' => '#059669',
            'display_order' => 2,
            'status' => 'active'
        ],
        [
            'id' => 3,
            'title' => 'GATE',
            'slug' => 'gate',
            'exam_code' => 'GATE',
            'category' => 'Postgraduate & PSU',
            'description' => 'Graduate Aptitude Test in Engineering for M.Tech & PSU Recruitment. Engineering Math, Aptitude & Technical Papers.',
            'icon' => 'account_balance_rounded',
            'color_hex' => '#7C3AED',
            'display_order' => 3,
            'status' => 'active'
        ],
        [
            'id' => 4,
            'title' => 'GUJCET',
            'slug' => 'gujcet',
            'exam_code' => 'GUJCET',
            'category' => 'Gujarat State Entrance',
            'description' => 'Gujarat Common Entrance Test for Degree Engineering and Pharmacy Admissions in Gujarat.',
            'icon' => 'school_rounded',
            'color_hex' => '#D97706',
            'display_order' => 4,
            'status' => 'active'
        ]
    ];

    $examStmt = $db->prepare("
        INSERT INTO `competitive_exams` (`id`, `title`, `slug`, `exam_code`, `category`, `description`, `icon`, `color_hex`, `display_order`, `status`)
        VALUES (:id, :title, :slug, :exam_code, :category, :description, :icon, :color_hex, :display_order, :status)
        ON DUPLICATE KEY UPDATE 
            `title` = VALUES(`title`),
            `exam_code` = VALUES(`exam_code`),
            `category` = VALUES(`category`),
            `description` = VALUES(`description`),
            `icon` = VALUES(`icon`),
            `color_hex` = VALUES(`color_hex`),
            `display_order` = VALUES(`display_order`),
            `status` = VALUES(`status`)
    ");

    foreach ($exams as $exam) {
        $examStmt->execute($exam);
    }

    // 4. Find any available uploaded sample PDF to seed
    $existingPdfs = glob(PDF_UPLOADS_PATH . '*.pdf');
    $samplePdf = !empty($existingPdfs) ? basename($existingPdfs[0]) : 'ch1_number_systems.pdf';

    // 5. Seed default study materials & PDFs for JEE, NEET, GATE
    $materials = [
        // JEE Materials
        [
            'id' => 1,
            'exam_id' => 1,
            'title' => 'JEE Main 2024 Physics Question Paper with Complete Solutions',
            'subject_name' => 'Physics',
            'material_type' => 'Question Paper',
            'year' => '2024',
            'description' => 'Official JEE Main Shift 1 & 2 solved physics paper with step-by-step detailed explanations.',
            'pdf_file_path' => $samplePdf,
            'pdf_external_url' => '',
            'page_count' => 38,
            'file_size_mb' => 2.45,
            'display_order' => 1,
            'status' => 'active'
        ],
        [
            'id' => 2,
            'exam_id' => 1,
            'title' => 'JEE Mathematics Master Formula Book & Quick Tricks',
            'subject_name' => 'Mathematics',
            'material_type' => 'Formula Book',
            'year' => '2025',
            'description' => 'Complete compilation of Calculus, Algebra, Coordinate Geometry and Vectors formulas with short tricks.',
            'pdf_file_path' => $samplePdf,
            'pdf_external_url' => '',
            'page_count' => 52,
            'file_size_mb' => 3.10,
            'display_order' => 2,
            'status' => 'active'
        ],
        [
            'id' => 3,
            'exam_id' => 1,
            'title' => 'JEE Chemistry Organic Reactions & Mechanism Revision Notes',
            'subject_name' => 'Chemistry',
            'material_type' => 'Notes',
            'year' => '2025',
            'description' => 'High-yield named reactions, reagents, conversions and mechanisms for JEE Main & Advanced.',
            'pdf_file_path' => $samplePdf,
            'pdf_external_url' => '',
            'page_count' => 45,
            'file_size_mb' => 2.80,
            'display_order' => 3,
            'status' => 'active'
        ],

        // NEET Materials
        [
            'id' => 4,
            'exam_id' => 2,
            'title' => 'NEET UG 2024 Biology 10-Year Solved Chapterwise PYQs',
            'subject_name' => 'Biology',
            'material_type' => 'Question Paper',
            'year' => '2024',
            'description' => 'NCERT line-by-line past 10 years NEET questions with verified answer keys.',
            'pdf_file_path' => $samplePdf,
            'pdf_external_url' => '',
            'page_count' => 64,
            'file_size_mb' => 4.20,
            'display_order' => 1,
            'status' => 'active'
        ],
        [
            'id' => 5,
            'exam_id' => 2,
            'title' => 'NEET Physics Most Scoring Topics Formula Handbook',
            'subject_name' => 'Physics',
            'material_type' => 'Formula Book',
            'year' => '2025',
            'description' => 'Formula handbook covering Mechanics, Electrodynamics, Optics and Modern Physics.',
            'pdf_file_path' => $samplePdf,
            'pdf_external_url' => '',
            'page_count' => 36,
            'file_size_mb' => 2.15,
            'display_order' => 2,
            'status' => 'active'
        ],

        // GATE Materials
        [
            'id' => 6,
            'exam_id' => 3,
            'title' => 'GATE General Aptitude & Engineering Mathematics Solved Papers',
            'subject_name' => 'General Aptitude',
            'material_type' => 'Question Paper',
            'year' => '2024',
            'description' => 'Comprehensive solved papers for common 30 marks section covering Quantitative Aptitude and Engineering Math.',
            'pdf_file_path' => $samplePdf,
            'pdf_external_url' => '',
            'page_count' => 48,
            'file_size_mb' => 3.50,
            'display_order' => 1,
            'status' => 'active'
        ],
        [
            'id' => 7,
            'exam_id' => 3,
            'title' => 'GATE Core Subject Quick Revision Notes & Concepts',
            'subject_name' => 'Technical',
            'material_type' => 'Notes',
            'year' => '2025',
            'description' => 'Handwritten summary notes and high priority formulas for GATE technical sections.',
            'pdf_file_path' => $samplePdf,
            'pdf_external_url' => '',
            'page_count' => 55,
            'file_size_mb' => 3.90,
            'display_order' => 2,
            'status' => 'active'
        ]
    ];

    $matStmt = $db->prepare("
        INSERT INTO `competitive_exam_materials` (
            `id`, `exam_id`, `title`, `subject_name`, `material_type`, `year`, `description`,
            `pdf_file_path`, `pdf_external_url`, `page_count`, `file_size_mb`, `display_order`, `status`
        ) VALUES (
            :id, :exam_id, :title, :subject_name, :material_type, :year, :description,
            :pdf_file_path, :pdf_external_url, :page_count, :file_size_mb, :display_order, :status
        )
        ON DUPLICATE KEY UPDATE
            `exam_id` = VALUES(`exam_id`),
            `title` = VALUES(`title`),
            `subject_name` = VALUES(`subject_name`),
            `material_type` = VALUES(`material_type`),
            `year` = VALUES(`year`),
            `description` = VALUES(`description`),
            `pdf_file_path` = VALUES(`pdf_file_path`),
            `pdf_external_url` = VALUES(`pdf_external_url`),
            `page_count` = VALUES(`page_count`),
            `file_size_mb` = VALUES(`file_size_mb`),
            `display_order` = VALUES(`display_order`),
            `status` = VALUES(`status`)
    ");

    foreach ($materials as $mat) {
        $matStmt->execute($mat);
    }

    echo "SUCCESS: Competitive Exams tables created and seeded successfully!\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
