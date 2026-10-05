<?php
/**
 * Delete Study Material Product
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

        $stmt = $db->prepare("SELECT image_path, title FROM study_products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();

        if ($product) {
            if (!empty($product['image_path']) && file_exists(PRODUCTS_UPLOADS_PATH . $product['image_path'])) {
                @unlink(PRODUCTS_UPLOADS_PATH . $product['image_path']);
            }

            $delStmt = $db->prepare("DELETE FROM study_products WHERE id = :id");
            $delStmt->execute([':id' => $id]);

            flash('success', "Product '{$product['title']}' deleted successfully.");
        } else {
            flash('error', 'Product not found.');
        }
    } catch (PDOException $e) {
        flash('error', 'Failed to delete product: ' . $e->getMessage());
    }
}

header('Location: ' . url('modules/study_products/index.php'));
exit;
