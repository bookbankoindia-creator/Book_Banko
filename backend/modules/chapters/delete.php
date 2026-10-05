<?php
/**
 * Delete Chapter Controller
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!verifyCSRFToken($csrf)) {
    flash('error', 'Security token mismatch.');
} else {
    try {
        $db = Database::getConnection();
        
        // Find PDF file path to delete local storage file
        $stmt = $db->prepare("SELECT pdf_file_path, subject_id FROM chapters_content WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            if (!empty($row['pdf_file_path']) && file_exists(PDF_UPLOADS_PATH . $row['pdf_file_path'])) {
                @unlink(PDF_UPLOADS_PATH . $row['pdf_file_path']);
            }

            $deleteStmt = $db->prepare("DELETE FROM chapters_content WHERE id = :id");
            $deleteStmt->execute([':id' => $id]);
            flash('success', 'Chapter and associated PDF deleted successfully.');
        } else {
            flash('error', 'Chapter not found.');
        }
    } catch (PDOException $e) {
        flash('error', 'Could not delete chapter: ' . $e->getMessage());
    }
}

$subjectId = isset($row['subject_id']) ? '?subject_id=' . $row['subject_id'] : '';
header('Location: ' . url('modules/chapters/index.php' . $subjectId));
exit;
