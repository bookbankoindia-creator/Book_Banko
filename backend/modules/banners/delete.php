<?php
/**
 * Delete Banner Controller
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
        $stmt = $db->prepare("SELECT image_path FROM banners WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row && !empty($row['image_path']) && file_exists(BANNER_UPLOADS_PATH . $row['image_path'])) {
            @unlink(BANNER_UPLOADS_PATH . $row['image_path']);
        }

        $deleteStmt = $db->prepare("DELETE FROM banners WHERE id = :id");
        $deleteStmt->execute([':id' => $id]);
        flash('success', 'Banner deleted successfully.');
    } catch (PDOException $e) {
        flash('error', 'Could not delete banner: ' . $e->getMessage());
    }
}

header('Location: ' . url('modules/banners/index.php'));
exit;
