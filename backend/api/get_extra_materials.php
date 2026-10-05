<?php
/**
 * API: Get Extra Material / Category Material PDFs
 * Method: GET
 * Endpoint: /backend/api/get_extra_materials.php
 * Parameters (Optional):
 *   - category_slug (default: 'extra_material')
 *   - board_code
 *   - medium_code
 *   - standard_number
 *   - subject_id
 *   - search
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();

    $categorySlug = trim($_GET['category_slug'] ?? 'extra_material');
    $boardCode = trim($_GET['board_code'] ?? '');
    $mediumCode = trim($_GET['medium_code'] ?? '');
    $standardNumber = isset($_GET['standard_number']) && $_GET['standard_number'] !== '' ? (int)$_GET['standard_number'] : null;
    $subjectId = isset($_GET['subject_id']) && $_GET['subject_id'] !== '' ? (int)$_GET['subject_id'] : null;
    $search = trim($_GET['search'] ?? '');

    $whereClauses = ["em.status = 'active'"];
    $params = [];

    if (!empty($categorySlug)) {
        $whereClauses[] = "(em.category_slug = :cat_slug1 OR lc.slug = :cat_slug2)";
        $params[':cat_slug1'] = $categorySlug;
        $params[':cat_slug2'] = $categorySlug;
    }

    if (!empty($boardCode)) {
        $whereClauses[] = "(b.code = :board_code OR em.board_id IS NULL)";
        $params[':board_code'] = $boardCode;
    }

    if (!empty($mediumCode)) {
        $whereClauses[] = "(m.code = :medium_code OR em.medium_id IS NULL)";
        $params[':medium_code'] = $mediumCode;
    }

    if ($standardNumber !== null && $standardNumber > 0) {
        $whereClauses[] = "(st.standard_number = :std_num OR em.standard_id IS NULL)";
        $params[':std_num'] = $standardNumber;
    }

    if ($subjectId !== null && $subjectId > 0) {
        $whereClauses[] = "(em.subject_id = :sub_id OR em.subject_id IS NULL)";
        $params[':sub_id'] = $subjectId;
    }

    if (!empty($search)) {
        $whereClauses[] = "(em.title LIKE :search1 OR em.description LIKE :search2 OR s.name LIKE :search3)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }

    $whereSql = implode(" AND ", $whereClauses);

    $sql = "
        SELECT em.*,
               lc.name as category_name,
               b.name as board_name,
               b.code as board_code,
               m.name as medium_name,
               m.code as medium_code,
               st.name as standard_name,
               st.standard_number,
               s.name as subject_name,
               s.code as subject_code,
               s.icon as subject_icon,
               s.color_hex as subject_color
        FROM extra_materials em
        LEFT JOIN learning_categories lc ON em.category_id = lc.id OR em.category_slug = lc.slug
        LEFT JOIN boards b ON em.board_id = b.id
        LEFT JOIN mediums m ON em.medium_id = m.id
        LEFT JOIN standards st ON em.standard_id = st.id
        LEFT JOIN subjects s ON em.subject_id = s.id
        WHERE {$whereSql}
        ORDER BY em.display_order ASC, em.id DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $materials = $stmt->fetchAll();

    // Attach full PDF URL
    foreach ($materials as &$item) {
        if (!empty($item['pdf_file_path'])) {
            $item['full_pdf_url'] = resolveMediaUrl($item['pdf_file_path'], 'pdfs');
        } elseif (!empty($item['pdf_external_url'])) {
            $item['full_pdf_url'] = $item['pdf_external_url'];
        } else {
            $item['full_pdf_url'] = '';
        }
    }

    jsonResponse('success', 'Extra materials fetched successfully', $materials);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch extra materials: ' . $e->getMessage(), null, 500);
}
