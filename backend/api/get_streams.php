<?php
/**
 * API: Get Academic Streams (Science, Commerce, Arts)
 * Method: GET
 * Endpoint: /backend/api/get_streams.php
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();

    $boardCode = isset($_GET['board_code']) ? trim($_GET['board_code']) : null;
    $boardId = isset($_GET['board_id']) ? (int)$_GET['board_id'] : null;
    $mediumCode = isset($_GET['medium_code']) ? trim($_GET['medium_code']) : null;
    $mediumId = isset($_GET['medium_id']) ? (int)$_GET['medium_id'] : null;
    $standardNumber = isset($_GET['standard_number']) ? (int)$_GET['standard_number'] : null;
    $standardId = isset($_GET['standard_id']) ? (int)$_GET['standard_id'] : null;

    $whereClauses = ["st.status = 'active'"];
    $params = [];

    if ($boardId && $boardId > 0) {
        $whereClauses[] = "(st.board_id = :board_id OR st.board_id IS NULL)";
        $params[':board_id'] = $boardId;
    } elseif ($boardCode) {
        $whereClauses[] = "(b.code = :board_code OR st.board_id IS NULL)";
        $params[':board_code'] = $boardCode;
    }

    if ($mediumId && $mediumId > 0) {
        $whereClauses[] = "(st.medium_id = :medium_id OR st.medium_id IS NULL)";
        $params[':medium_id'] = $mediumId;
    } elseif ($mediumCode) {
        $whereClauses[] = "(m.code = :medium_code OR st.medium_id IS NULL)";
        $params[':medium_code'] = $mediumCode;
    }

    if ($standardId && $standardId > 0) {
        $whereClauses[] = "(st.standard_id = :standard_id OR st.standard_id IS NULL)";
        $params[':standard_id'] = $standardId;
    } elseif ($standardNumber) {
        $whereClauses[] = "(std.standard_number = :standard_number OR st.standard_id IS NULL)";
        $params[':standard_number'] = $standardNumber;
    }

    $whereSql = implode(" AND ", $whereClauses);

    $stmt = $db->prepare("
        SELECT st.id, st.board_id, st.medium_id, st.standard_id, st.slug, st.name, st.description, st.icon, st.display_order,
               b.code as board_code, b.name as board_name,
               m.code as medium_code, m.name as medium_name,
               std.standard_number, std.name as standard_name
        FROM streams st
        LEFT JOIN boards b ON st.board_id = b.id
        LEFT JOIN mediums m ON st.medium_id = m.id
        LEFT JOIN standards std ON st.standard_id = std.id
        WHERE {$whereSql}
        ORDER BY st.display_order ASC
    ");
    $stmt->execute($params);
    $streams = $stmt->fetchAll();

    jsonResponse('success', 'Streams fetched successfully', $streams);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch streams: ' . $e->getMessage(), null, 500);
}
