<?php
/**
 * API: Get Competitive Exams List (JEE, NEET, GATE, etc.)
 * Method: GET
 * Endpoint: /backend/api/get_competitive_exams.php
 * Parameters (Optional):
 *   - search
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();

    $search = trim($_GET['search'] ?? '');
    $whereClauses = ["ce.status = 'active'"];
    $params = [];

    if (!empty($search)) {
        $whereClauses[] = "(ce.title LIKE :search1 OR ce.exam_code LIKE :search2 OR ce.category LIKE :search3)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }

    $whereSql = implode(" AND ", $whereClauses);

    $sql = "
        SELECT ce.id, ce.title, ce.slug, ce.exam_code, ce.category, ce.description,
               ce.icon, ce.color_hex, ce.banner_image, ce.display_order, ce.status,
               ce.created_at, ce.updated_at,
               COUNT(cem.id) as material_count
        FROM competitive_exams ce
        LEFT JOIN competitive_exam_materials cem ON ce.id = cem.exam_id AND cem.status = 'active'
        WHERE {$whereSql}
        GROUP BY ce.id, ce.title, ce.slug, ce.exam_code, ce.category, ce.description,
                 ce.icon, ce.color_hex, ce.banner_image, ce.display_order, ce.status,
                 ce.created_at, ce.updated_at
        ORDER BY ce.display_order ASC, ce.id ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $exams = $stmt->fetchAll();

    foreach ($exams as &$item) {
        $item['material_count'] = (int)($item['material_count'] ?? 0);
        if (!empty($item['banner_image'])) {
            $item['full_banner_url'] = resolveMediaUrl($item['banner_image'], 'banners');
        }
    }

    jsonResponse('success', 'Competitive exams fetched successfully', $exams);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch competitive exams: ' . $e->getMessage(), null, 500);
}
