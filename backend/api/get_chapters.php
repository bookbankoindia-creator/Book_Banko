<?php
/**
 * API: Get Chapters & Content for a Subject
 * Method: GET
 * Endpoint: /backend/api/get_chapters.php
 * Parameters:
 *   - subject_id (int, required)
 *   - module_id (int, optional)
 *   - module_slug (string, optional, e.g. "textbooks", "pyqs")
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();

    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
    $moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : null;
    $moduleSlug = isset($_GET['module_slug']) ? trim($_GET['module_slug']) : null;

    if (!$subjectId) {
        jsonResponse('error', 'Missing required parameter: subject_id', null, 400);
    }

    $whereClauses = ["c.subject_id = :subject_id", "c.status = 'active'"];
    $params = [':subject_id' => $subjectId];

    if ($moduleId) {
        $whereClauses[] = "c.module_id = :module_id";
        $params[':module_id'] = $moduleId;
    } elseif ($moduleSlug) {
        $whereClauses[] = "m.slug = :module_slug";
        $params[':module_slug'] = $moduleSlug;
    }

    $whereSql = implode(" AND ", $whereClauses);

    $query = "
        SELECT c.id, c.chapter_number, c.title, c.description,
               c.pdf_file_path, c.pdf_external_url, c.page_count, c.file_size_mb,
               c.is_free, c.views_count, c.display_order,
               m.id as module_id, m.title as module_title, m.slug as module_slug,
               s.name as subject_name, s.code as subject_code
        FROM chapters_content c
        JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN dashboard_modules m ON c.module_id = m.id
        WHERE {$whereSql}
        ORDER BY c.display_order ASC, c.chapter_number ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $chapters = $stmt->fetchAll();

    // Fallback: If no chapters found for this specific module slug, fetch all active chapters for the subject
    if (empty($chapters) && ($moduleId || $moduleSlug)) {
        $fbStmt = $db->prepare("
            SELECT c.id, c.chapter_number, c.title, c.description,
                   c.pdf_file_path, c.pdf_external_url, c.page_count, c.file_size_mb,
                   c.is_free, c.views_count, c.display_order,
                   m.id as module_id, m.title as module_title, m.slug as module_slug,
                   s.name as subject_name, s.code as subject_code
            FROM chapters_content c
            JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN dashboard_modules m ON c.module_id = m.id
            WHERE c.subject_id = :subject_id AND c.status = 'active'
            ORDER BY c.display_order ASC, c.chapter_number ASC
        ");
        $fbStmt->execute([':subject_id' => $subjectId]);
        $chapters = $fbStmt->fetchAll();
    }

    // Attach full PDF URL
    foreach ($chapters as &$chap) {
        $chap['chapter_number'] = (int)$chap['chapter_number'];
        $chap['page_count'] = (int)$chap['page_count'];
        $chap['file_size_mb'] = (float)$chap['file_size_mb'];
        $chap['is_free'] = (bool)$chap['is_free'];

        if (!empty($chap['pdf_file_path'])) {
            $chap['full_pdf_url'] = resolveMediaUrl($chap['pdf_file_path'], 'pdfs');
        } elseif (!empty($chap['pdf_external_url'])) {
            $chap['full_pdf_url'] = $chap['pdf_external_url'];
        } else {
            $chap['full_pdf_url'] = null;
        }
    }

    jsonResponse('success', 'Chapters fetched successfully', $chapters);
} catch (PDOException $e) {
    jsonResponse('error', 'Failed to fetch chapters: ' . $e->getMessage(), null, 500);
}
