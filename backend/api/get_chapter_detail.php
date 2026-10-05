<?php
/**
 * API: Get Single Chapter Detail & Increment View Count
 * Method: GET
 * Endpoint: /backend/api/get_chapter_detail.php?id=1
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$id) {
        jsonResponse('error', 'Missing chapter ID', null, 400);
    }

    // Increment view count
    $db->prepare("UPDATE chapters_content SET views_count = views_count + 1 WHERE id = :id")->execute([':id' => $id]);

    $stmt = $db->prepare("
        SELECT c.*, 
               s.name as subject_name, s.code as subject_code,
               b.code as board_code, b.name as board_name,
               st.standard_number, st.name as standard_name,
               m.title as module_title
        FROM chapters_content c
        LEFT JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN boards b ON s.board_id = b.id
        LEFT JOIN standards st ON s.standard_id = st.id
        LEFT JOIN dashboard_modules m ON c.module_id = m.id
        WHERE c.id = :id AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $chapter = $stmt->fetch();

    if (!$chapter) {
        jsonResponse('error', 'Chapter not found', null, 404);
    }

    if (!empty($chapter['pdf_file_path'])) {
        $chapter['full_pdf_url'] = resolveMediaUrl($chapter['pdf_file_path'], 'pdfs');
    } elseif (!empty($chapter['pdf_external_url'])) {
        $chapter['full_pdf_url'] = $chapter['pdf_external_url'];
    } else {
        $chapter['full_pdf_url'] = null;
    }

    jsonResponse('success', 'Chapter details fetched', $chapter);
} catch (PDOException $e) {
    jsonResponse('error', 'Database error: ' . $e->getMessage(), null, 500);
}
