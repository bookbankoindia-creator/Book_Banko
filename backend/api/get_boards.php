<?php
/**
 * API: Get All Active Boards
 * Method: GET
 * Endpoint: /backend/api/get_boards.php
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("
        SELECT id, code, name, description, icon, display_order 
        FROM boards 
        WHERE status = 'active' 
        ORDER BY display_order ASC
    ");
    $boards = $stmt->fetchAll();

    jsonResponse('success', 'Boards fetched successfully', $boards);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch boards: ' . $e->getMessage(), null, 500);
}
