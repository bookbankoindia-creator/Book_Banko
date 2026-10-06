<?php
/**
 * API: Get Higher Education Materials & PDFs (Degrees, Engineering, Commerce, Science, Arts, Diplomas)
 * Method: GET
 * Endpoint: /backend/api/get_higher_education_materials.php
 * Parameters (Optional):
 *   - search
 *   - course_name
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();

    $search = trim($_GET['search'] ?? '');
    $courseName = trim($_GET['course_name'] ?? '');

    $whereClauses = ["status = 'active'"];
    $params = [];

    if (!empty($courseName)) {
        $whereClauses[] = "course_name = :course_name";
        $params[':course_name'] = $courseName;
    }

    if (!empty($search)) {
        $whereClauses[] = "(title LIKE :search1 OR description LIKE :search2 OR course_name LIKE :search3)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }

    $whereSql = implode(" AND ", $whereClauses);

    $sql = "
        SELECT *
        FROM higher_education_materials
        WHERE {$whereSql}
        ORDER BY display_order ASC, id DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $materials = $stmt->fetchAll();

    // Attach full PDF URL and parse types
    foreach ($materials as &$item) {
        $item['page_count'] = (int)($item['page_count'] ?? 0);
        $item['file_size_mb'] = (float)($item['file_size_mb'] ?? 0.0);
        $item['is_free'] = (bool)($item['is_free'] ?? true);

        if (!empty($item['pdf_file_path'])) {
            $item['full_pdf_url'] = resolveMediaUrl($item['pdf_file_path'], 'pdfs');
        } elseif (!empty($item['pdf_external_url'])) {
            $item['full_pdf_url'] = $item['pdf_external_url'];
        } else {
            $item['full_pdf_url'] = '';
        }
    }

    jsonResponse('success', 'Higher education materials fetched successfully', $materials);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch higher education materials: ' . $e->getMessage(), null, 500);
}
