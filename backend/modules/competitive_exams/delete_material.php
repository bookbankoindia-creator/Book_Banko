<?php
/**
 * Delete Competitive Exam Material PDF
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $db = Database::getConnection();

        // Get file path to delete local PDF
        $stmt = $db->prepare("SELECT pdf_file_path, title FROM competitive_exam_materials WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $material = $stmt->fetch();

        if ($material) {
            if (!empty($material['pdf_file_path']) && file_exists(PDF_UPLOADS_PATH . $material['pdf_file_path'])) {
                @unlink(PDF_UPLOADS_PATH . $material['pdf_file_path']);
            }

            $delStmt = $db->prepare("DELETE FROM competitive_exam_materials WHERE id = :id");
            $delStmt->execute([':id' => $id]);

            flash('success', "Competitive material '{$material['title']}' deleted successfully.");
        } else {
            flash('error', 'Exam material not found.');
        }
    } catch (PDOException $e) {
        flash('error', 'Failed to delete material: ' . $e->getMessage());
    }
}

header('Location: ' . url('modules/competitive_exams/index.php'));
exit;
