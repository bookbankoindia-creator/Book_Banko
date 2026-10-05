<?php
/**
 * API: Get Home Screen Learning Categories (Study Material, Extra Material, etc.)
 * Method: GET
 * Endpoint: /backend/api/get_categories.php
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("
        SELECT id, slug, name, icon, color_hex, display_order 
        FROM learning_categories 
        WHERE status = 'active' 
        ORDER BY display_order ASC
    ");
    $categories = $stmt->fetchAll();

    jsonResponse('success', 'Categories fetched successfully', $categories);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch categories: ' . $e->getMessage(), null, 500);
}
