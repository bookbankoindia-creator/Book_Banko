<?php
/**
 * API: Get Study Material Products & Stationery (Affiliate Links)
 * Method: GET
 * Endpoint: /backend/api/get_study_products.php
 * Parameters (Optional):
 *   - search (e.g., 'pen', 'pencil', 'rubber', 'sharpener')
 *   - tag
 *   - is_fallback_ad (1 or 0)
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();

    $search = trim($_GET['search'] ?? '');
    $tag = trim($_GET['tag'] ?? '');
    $isFallback = isset($_GET['is_fallback_ad']) && $_GET['is_fallback_ad'] !== '' ? (int)$_GET['is_fallback_ad'] : null;

    $whereClauses = ["status = 'active'"];
    $params = [];

    if (!empty($search)) {
        $whereClauses[] = "(title LIKE :search1 OR description LIKE :search2 OR search_tags LIKE :search3)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }

    if (!empty($tag)) {
        $whereClauses[] = "search_tags LIKE :tag";
        $params[':tag'] = '%' . $tag . '%';
    }

    if ($isFallback !== null) {
        $whereClauses[] = "is_fallback_ad = :fallback";
        $params[':fallback'] = $isFallback;
    }

    $whereSql = implode(" AND ", $whereClauses);

    $sql = "
        SELECT *
        FROM study_products
        WHERE {$whereSql}
        ORDER BY display_order ASC, id DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    foreach ($products as &$item) {
        if (!empty($item['image_path'])) {
            $cleanPath = ltrim(str_replace('products/', '', $item['image_path']), '/');
            $item['full_image_url'] = resolveMediaUrl($cleanPath, 'products');
        } elseif (!empty($item['image_external_url'])) {
            $item['full_image_url'] = $item['image_external_url'];
        } else {
            $item['full_image_url'] = '';
        }
    }

    jsonResponse('success', 'Study products fetched successfully', $products);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch study products: ' . $e->getMessage(), null, 500);
}
