<?php
/**
 * API: Get Standard Dashboard Modules (Textbooks, PYQs, Blueprint, M.IMP)
 * Method: GET
 * Endpoint: /backend/api/get_modules.php
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("
        SELECT id, title, slug, route_key, icon, badge_text, display_order 
        FROM dashboard_modules 
        WHERE status = 'active' 
        ORDER BY display_order ASC
    ");
    $modules = $stmt->fetchAll();

    jsonResponse('success', 'Dashboard modules fetched successfully', $modules);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch dashboard modules: ' . $e->getMessage(), null, 500);
}
