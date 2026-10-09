<?php
/**
 * Delete Blueprint Material
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!verifyCSRFToken($csrf)) {
    flash('error', 'Invalid security token.');
    header('Location: ' . url('modules/blueprints/index.php'));
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM chapters_content WHERE id = :id");
$stmt->execute([':id' => $id]);
$blueprint = $stmt->fetch();

if (!$blueprint) {
    flash('error', 'Blueprint material not found.');
} else {
    // Delete local file if present
    if (!empty($blueprint['pdf_file_path']) && file_exists(PDF_UPLOADS_PATH . $blueprint['pdf_file_path'])) {
        @unlink(PDF_UPLOADS_PATH . $blueprint['pdf_file_path']);
    }

    $deleteStmt = $db->prepare("DELETE FROM chapters_content WHERE id = :id");
    $deleteStmt->execute([':id' => $id]);
    flash('success', 'Blueprint material deleted successfully.');
}

header('Location: ' . url('modules/blueprints/index.php'));
exit;
