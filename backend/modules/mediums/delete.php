<?php
/**
 * Delete Medium of Study
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!verifyCSRFToken($csrf)) {
    flash('error', 'Invalid security token.');
    header('Location: ' . url('modules/mediums/index.php'));
    exit;
}

$db = Database::getConnection();

try {
    $stmt = $db->prepare("DELETE FROM mediums WHERE id = :id");
    $stmt->execute([':id' => $id]);
    flash('success', 'Medium deleted successfully.');
} catch (PDOException $e) {
    flash('error', 'Failed to delete medium: ' . $e->getMessage());
}

header('Location: ' . url('modules/mediums/index.php'));
exit;
