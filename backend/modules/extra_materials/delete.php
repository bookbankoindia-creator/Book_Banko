<?php
/**
 * Delete Extra Material PDF
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
        $stmt = $db->prepare("SELECT pdf_file_path, title FROM extra_materials WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $material = $stmt->fetch();

        if ($material) {
            if (!empty($material['pdf_file_path']) && file_exists(PDF_UPLOADS_PATH . $material['pdf_file_path'])) {
                @unlink(PDF_UPLOADS_PATH . $material['pdf_file_path']);
            }

            $delStmt = $db->prepare("DELETE FROM extra_materials WHERE id = :id");
            $delStmt->execute([':id' => $id]);

            flash('success', "Extra Material '{$material['title']}' deleted successfully.");
        } else {
            flash('error', 'Material not found.');
        }
    } catch (PDOException $e) {
        flash('error', 'Failed to delete material: ' . $e->getMessage());
    }
}

header('Location: ' . url('modules/extra_materials/index.php'));
exit;
