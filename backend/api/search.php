<?php
/**
 * API: Search Subjects and Chapters
 * Method: GET
 * Endpoint: /backend/api/search.php?q=mathematics
 */
define('IS_API_REQUEST', true);
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = Database::getConnection();
    $query = trim($_GET['q'] ?? '');

    if (empty($query)) {
        jsonResponse('error', 'Search query (q) parameter is required', null, 400);
    }

    $searchTerm = "%{$query}%";

    // Search matching subjects
    $subStmt = $db->prepare("
        SELECT s.id, s.name, s.code, s.icon, b.code as board_code, st.name as standard_name,
               (SELECT COUNT(*) FROM chapters_content c WHERE c.subject_id = s.id AND c.status = 'active') as chapter_count
        FROM subjects s
        JOIN boards b ON s.board_id = b.id
        JOIN standards st ON s.standard_id = st.id
        WHERE (s.name LIKE :q OR s.code LIKE :q) AND s.status = 'active'
        LIMIT 10
    ");
    $subStmt->execute([':q' => $searchTerm]);
    $subjects = $subStmt->fetchAll();

    // Search matching chapters
    $chapStmt = $db->prepare("
        SELECT c.id, c.chapter_number, c.title, c.page_count, c.pdf_file_path, c.pdf_external_url,
               s.name as subject_name, b.code as board_code, st.name as standard_name,
               m.title as module_title
        FROM chapters_content c
        JOIN subjects s ON c.subject_id = s.id
        JOIN boards b ON s.board_id = b.id
        JOIN standards st ON s.standard_id = st.id
        LEFT JOIN dashboard_modules m ON c.module_id = m.id
        WHERE (c.title LIKE :q OR c.description LIKE :q) AND c.status = 'active'
        LIMIT 20
    ");
    $chapStmt->execute([':q' => $searchTerm]);
    $chapters = $chapStmt->fetchAll();

    foreach ($chapters as &$chap) {
        if (!empty($chap['pdf_file_path'])) {
            $chap['full_pdf_url'] = resolveMediaUrl($chap['pdf_file_path'], 'pdfs');
        } elseif (!empty($chap['pdf_external_url'])) {
            $chap['full_pdf_url'] = $chap['pdf_external_url'];
        } else {
            $chap['full_pdf_url'] = null;
        }
    }

    $result = [
        'query' => $query,
        'matched_subjects_count' => count($subjects),
        'matched_chapters_count' => count($chapters),
        'subjects' => $subjects,
        'chapters' => $chapters
    ];

    jsonResponse('success', 'Search results found', $result);
} catch (PDOException $e) {
    jsonResponse('error', 'Search failed: ' . $e->getMessage(), null, 500);
}
