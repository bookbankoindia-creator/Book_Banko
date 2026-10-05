<?php
/**
 * Delete Competitive Exam Category
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if ($id <= 0 || !verifyCSRFToken($csrf)) {
    flash('error', 'Invalid request or expired security token.');
    header('Location: ' . url('modules/competitive_exams/index.php'));
    exit;
}

$db = Database::getConnection();

try {
    // Delete attached material PDF files from disk
    $matStmt = $db->prepare("SELECT pdf_file_path FROM competitive_exam_materials WHERE exam_id = :id");
    $matStmt->execute([':id' => $id]);
    $materials = $matStmt->fetchAll();

    foreach ($materials as $mat) {
        if (!empty($mat['pdf_file_path']) && file_exists(PDF_UPLOADS_PATH . $mat['pdf_file_path'])) {
            @unlink(PDF_UPLOADS_PATH . $mat['pdf_file_path']);
        }
    }

    $stmt = $db->prepare("DELETE FROM competitive_exams WHERE id = :id");
    $stmt->execute([':id' => $id]);

    flash('success', 'Competitive Exam and all its materials were deleted.');
} catch (PDOException $e) {
    flash('error', 'Failed to delete exam: ' . $e->getMessage());
}

header('Location: ' . url('modules/competitive_exams/index.php'));
exit;
