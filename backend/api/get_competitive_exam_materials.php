<?php
/**
 * API: Get Competitive Exam Materials / PDFs
 * Method: GET
 * Endpoint: /backend/api/get_competitive_exam_materials.php
 * Parameters (Optional):
 *   - exam_id
 *   - exam_slug
 *   - exam_name
 *   - material_type
 *   - search
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();

    $examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : null;
    $examSlug = trim($_GET['exam_slug'] ?? '');
    $examName = trim($_GET['exam_name'] ?? '');
    $materialType = trim($_GET['material_type'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $whereClauses = ["cem.status = 'active'"];
    $params = [];

    if ($examId) {
        $whereClauses[] = "cem.exam_id = :exam_id";
        $params[':exam_id'] = $examId;
    } elseif (!empty($examSlug)) {
        $whereClauses[] = "ce.slug = :exam_slug";
        $params[':exam_slug'] = $examSlug;
    } elseif (!empty($examName)) {
        $whereClauses[] = "(ce.title LIKE :exam_name OR cem.subject_name LIKE :exam_name_sub OR cem.exam_name LIKE :exam_name_dir)";
        $params[':exam_name'] = '%' . $examName . '%';
        $params[':exam_name_sub'] = '%' . $examName . '%';
        $params[':exam_name_dir'] = '%' . $examName . '%';
    }

    if (!empty($materialType)) {
        $whereClauses[] = "cem.material_type = :mat_type";
        $params[':mat_type'] = $materialType;
    }

    if (!empty($search)) {
        $whereClauses[] = "(cem.title LIKE :search1 OR cem.description LIKE :search2 OR cem.subject_name LIKE :search3 OR cem.exam_name LIKE :search4)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
        $params[':search4'] = '%' . $search . '%';
    }

    $whereSql = implode(" AND ", $whereClauses);

    $sql = "
        SELECT cem.*,
               ce.title as exam_title,
               ce.slug as exam_slug,
               ce.exam_code
        FROM competitive_exam_materials cem
        LEFT JOIN competitive_exams ce ON cem.exam_id = ce.id
        WHERE {$whereSql}
        ORDER BY cem.display_order ASC, cem.id DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $materials = $stmt->fetchAll();

    // Attach full PDF URL and parse types
    foreach ($materials as &$item) {
        $item['page_count'] = (int)$item['page_count'];
        $item['file_size_mb'] = (float)$item['file_size_mb'];
        $item['is_free'] = (bool)$item['is_free'];

        if (!empty($item['pdf_file_path'])) {
            $item['full_pdf_url'] = resolveMediaUrl($item['pdf_file_path'], 'pdfs');
        } elseif (!empty($item['pdf_external_url'])) {
            $item['full_pdf_url'] = $item['pdf_external_url'];
        } else {
            $item['full_pdf_url'] = '';
        }
    }

    jsonResponse('success', 'Competitive exam materials fetched successfully', $materials);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch competitive exam materials: ' . $e->getMessage(), null, 500);
}
