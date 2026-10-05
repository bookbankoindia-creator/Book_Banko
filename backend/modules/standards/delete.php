<?php
/**
 * Delete Standard Controller
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
        $stmt = $db->prepare("DELETE FROM standards WHERE id = :id");
        $stmt->execute([':id' => $id]);
        flash('success', 'Standard deleted successfully.');
    } catch (PDOException $e) {
        flash('error', 'Could not delete standard: ' . $e->getMessage());
    }
}

header('Location: ' . url('modules/standards/index.php'));
exit;
