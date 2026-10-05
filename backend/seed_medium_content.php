<?php
/**
 * Seed Dynamic Gujarati & English Medium Subjects and Chapters
 * Book Banko
 */
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to database.\n";

    // Resolve Medium IDs
    $gujMediumId = (int)$db->query("SELECT id FROM mediums WHERE code IN ('gujrati', 'gujarati') ORDER BY id ASC LIMIT 1")->fetchColumn();
    $engMediumId = (int)$db->query("SELECT id FROM mediums WHERE code = 'english' ORDER BY id ASC LIMIT 1")->fetchColumn();
    $hinMediumId = (int)$db->query("SELECT id FROM mediums WHERE code = 'hindi' ORDER BY id ASC LIMIT 1")->fetchColumn();

    // Resolve Standard IDs
    $std6Id = (int)$db->query("SELECT id FROM standards WHERE standard_number = 6 LIMIT 1")->fetchColumn();
    $std7Id = (int)$db->query("SELECT id FROM standards WHERE standard_number = 7 LIMIT 1")->fetchColumn();
    $std9Id = (int)$db->query("SELECT id FROM standards WHERE standard_number = 9 LIMIT 1")->fetchColumn();

    echo "Found IDs: Gujarati Medium = $gujMediumId, English Medium = $engMediumId, Std 6 = $std6Id, Std 7 = $std7Id, Std 9 = $std9Id\n";

    // Update existing subjects to have the correct medium
    $db->exec("UPDATE subjects SET medium_id = $gujMediumId WHERE code LIKE '%guj%' OR name LIKE '%ગણિત%' OR name LIKE '%વિજ્ઞાન%' OR name = 'maths' OR id = 2");

    // Insert or update Standard 6 Gujarati Medium Maths (Subject code: math_6_guj)
    $db->exec("
        INSERT INTO subjects (id, board_id, medium_id, standard_id, stream_id, name, code, icon, color_hex, display_order, status)
        VALUES (101, 1, $gujMediumId, $std6Id, NULL, 'ગણિત (Maths)', 'math_6_guj', 'calculate_rounded', '#0284C7', 1, 'active')
        ON DUPLICATE KEY UPDATE name = 'ગણિત (Maths)', medium_id = $gujMediumId, standard_id = $std6Id, code = 'math_6_guj';
    ");

    // Also update subject 2 (which is maths std 6) to be 'ગણિત (Maths)' in Gujarati Medium
    if ($std6Id) {
        $db->exec("UPDATE subjects SET name = 'ગણિત (Maths)', medium_id = $gujMediumId, standard_id = $std6Id WHERE id = 2");
    }

    // Insert Standard 6 English Medium Maths
    if ($engMediumId && $std6Id) {
        $db->exec("
            INSERT INTO subjects (id, board_id, medium_id, standard_id, stream_id, name, code, icon, color_hex, display_order, status)
            VALUES (111, 1, $engMediumId, $std6Id, NULL, 'Mathematics', 'math_6_eng', 'calculate_rounded', '#0284C7', 1, 'active')
            ON DUPLICATE KEY UPDATE name = 'Mathematics', medium_id = $engMediumId, standard_id = $std6Id, code = 'math_6_eng';
        ");
    }

    // Seed Gujarati Medium Standard 6 Maths Chapters (for both Subject 101 and Subject 2)
    $subjectIdsToSeed = [101, 2];
    foreach ($subjectIdsToSeed as $sId) {
        $exists = $db->query("SELECT id FROM subjects WHERE id = $sId")->fetchColumn();
        if ($exists) {
            $db->exec("
                DELETE FROM chapters_content WHERE subject_id = $sId;
                INSERT INTO chapters_content (subject_id, module_id, chapter_number, title, description, pdf_file_path, pdf_external_url, page_count, file_size_mb, display_order, status) VALUES
                ($sId, 1, 1, 'ગણિતમાં પૅટર્ન', 'પ્રકરણ ૧: ગણિતમાં પૅટર્ન - પ્રારંભિક સંકલ્પનાઓ', 'gseb_std6_math_ch1.pdf', '', 24, 2.50, 1, 'active'),
                ($sId, 1, 2, 'રેખાઓ અને ખૂણાઓ', 'પ્રકરણ ૨: રેખાઓ અને ખૂણાઓ - ભૂમિતિની સમજ', 'gseb_std6_math_ch2.pdf', '', 28, 3.10, 2, 'active'),
                ($sId, 1, 3, 'સંખ્યાની રમત', 'પ્રકરણ ૩: સંખ્યાની રમત - ગુણકો અને અવયવો', 'gseb_std6_math_ch3.pdf', '', 32, 3.40, 3, 'active'),
                ($sId, 1, 4, 'માહિતીનું નિયમન અને રજૂઆત', 'પ્રકરણ ૪: માહિતીનું નિયમન અને આલેખ', 'gseb_std6_math_ch4.pdf', '', 26, 2.80, 4, 'active'),
                ($sId, 1, 5, 'અવિભાજ્ય સંખ્યાઓ', 'પ્રકરણ ૫: અવિભાજ્ય અને વિભાજ્ય સંખ્યાઓ', 'gseb_std6_math_ch5.pdf', '', 22, 2.20, 5, 'active'),
                ($sId, 1, 6, 'પરિમિતિ અને ક્ષેત્રફળ', 'પ્રકરણ ૬: પરિમિતિ અને ક્ષેત્રફળના સૂત્રો', 'gseb_std6_math_ch6.pdf', '', 30, 3.50, 6, 'active'),
                ($sId, 1, 7, 'અપૂર્ણાંક', 'પ્રકરણ ૭: અપૂર્ણાંક સંખ્યાઓ', 'gseb_std6_math_ch7.pdf', '', 34, 3.80, 7, 'active'),
                ($sId, 1, 8, 'રચનાઓ સાથે રમત', 'પ્રકરણ ૮: ભૌમિતિક રચનાઓ', 'gseb_std6_math_ch8.pdf', '', 20, 2.10, 8, 'active'),
                ($sId, 1, 9, 'સંમિતિ', 'પ્રકરણ ૯: સંમિતિ અને પરાવર્તન', 'gseb_std6_math_ch9.pdf', '', 18, 1.90, 9, 'active'),
                ($sId, 1, 10, 'શૂન્યની બીજી બાજુ', 'પ્રકરણ ૧૦: શૂન્યની બીજી બાજુ - ઋણ સંખ્યાઓ', 'gseb_std6_math_ch10.pdf', '', 25, 2.70, 10, 'active');
            ");
            echo "Seeded Gujarati chapters for Subject ID $sId.\n";
        }
    }

    // Seed English Medium Standard 6 Maths Chapters for Subject 111
    $existsEng = $db->query("SELECT id FROM subjects WHERE id = 111")->fetchColumn();
    if ($existsEng) {
        $db->exec("
            DELETE FROM chapters_content WHERE subject_id = 111;
            INSERT INTO chapters_content (subject_id, module_id, chapter_number, title, description, pdf_file_path, pdf_external_url, page_count, file_size_mb, display_order, status) VALUES
            (111, 1, 1, 'Patterns in Mathematics', 'Chapter 1: Patterns in Mathematics - Concepts and Activities', 'cbse_std6_math_ch1.pdf', '', 24, 2.50, 1, 'active'),
            (111, 1, 2, 'Lines and Angles', 'Chapter 2: Lines and Angles - Fundamentals of Geometry', 'cbse_std6_math_ch2.pdf', '', 28, 3.10, 2, 'active'),
            (111, 1, 3, 'Playing with Numbers', 'Chapter 3: Playing with Numbers - Factors and Multiples', 'cbse_std6_math_ch3.pdf', '', 32, 3.40, 3, 'active'),
            (111, 1, 4, 'Data Handling and Presentation', 'Chapter 4: Data Handling, Bar Graphs and Presentation', 'cbse_std6_math_ch4.pdf', '', 26, 2.80, 4, 'active'),
            (111, 1, 5, 'Prime Numbers', 'Chapter 5: Prime and Composite Numbers', 'cbse_std6_math_ch5.pdf', '', 22, 2.20, 5, 'active'),
            (111, 1, 6, 'Perimeter and Area', 'Chapter 6: Perimeter and Area of Plane Figures', 'cbse_std6_math_ch6.pdf', '', 30, 3.50, 6, 'active'),
            (111, 1, 7, 'Fractions', 'Chapter 7: Proper, Improper and Mixed Fractions', 'cbse_std6_math_ch7.pdf', '', 34, 3.80, 7, 'active'),
            (111, 1, 8, 'Playing with Shapes', 'Chapter 8: Geometrical Constructions and Shapes', 'cbse_std6_math_ch8.pdf', '', 20, 2.10, 8, 'active'),
            (111, 1, 9, 'Symmetry', 'Chapter 9: Lines of Symmetry and Reflections', 'cbse_std6_math_ch9.pdf', '', 18, 1.90, 9, 'active'),
            (111, 1, 10, 'The Other Side of Zero', 'Chapter 10: Negative Integers and Number Line', 'cbse_std6_math_ch10.pdf', '', 25, 2.70, 10, 'active');
        ");
        echo "Seeded English chapters for Subject ID 111.\n";
    }

    echo "DYNAMIC_SEEDS_COMPLETED_SUCCESSFULLY\n";
} catch (PDOException $e) {
    echo "Seed Error: " . $e->getMessage() . "\n";
}
