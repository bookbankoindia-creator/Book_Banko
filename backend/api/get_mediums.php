<?php
/**
 * API: Get Active Mediums of Study
 * Method: GET
 * Endpoint: /backend/api/get_mediums.php
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();

    $stmt = $db->query("
        SELECT id, code, name, description, icon, display_order, status
        FROM mediums
        WHERE status = 'active'
        ORDER BY display_order ASC, name ASC
    ");
    $mediums = $stmt->fetchAll();

    jsonResponse('success', 'Mediums fetched successfully', $mediums);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch mediums: ' . $e->getMessage(), null, 500);
}
