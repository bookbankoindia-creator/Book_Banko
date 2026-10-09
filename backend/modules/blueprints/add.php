<?php
/**
 * Upload & Add New Blueprint PDF
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Upload Blueprint PDF';
$pageSubtitle = 'Add a board exam blueprint, paper format, or marks weightage PDF';
$db = Database::getConnection();

// Get the Blueprint module ID
$blueprintModule = $db->query("SELECT id, title, slug FROM dashboard_modules WHERE slug = 'blueprint' LIMIT 1")->fetch();
$blueprintModuleId = $blueprintModule ? (int)$blueprintModule['id'] : 4;

$preselectedSubjectId = (int)($_GET['subject_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
        $sizeMB = round((int)$_SERVER['CONTENT_LENGTH'] / (1024 * 1024), 1);
        flash('error', "The uploaded file ({$sizeMB} MB) exceeds PHP's post_max_size limit. Please use the direct uploader or adjust PHP settings.");
    } else {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrf)) {
            flash('error', 'Invalid security token. Please refresh the page and try again.');
        } else {
            $subjectId = (int)($_POST['subject_id'] ?? 0);
            $moduleId = $blueprintModuleId;
            $chapterNumber = (int)($_POST['chapter_number'] ?? 1);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $pageCount = (int)($_POST['page_count'] ?? 1);
            $externalUrl = trim($_POST['pdf_external_url'] ?? '');
            $displayOrder = (int)($_POST['display_order'] ?? $chapterNumber);
            $status = $_POST['status'] ?? 'active';

            $pdfFileName = '';
            $fileSizeMB = 0.00;
            $uploadError = false;

            if (!empty($_POST['direct_uploaded_file'])) {
                $pdfFileName = trim($_POST['direct_uploaded_file']);
                $fileSizeMB = (float)($_POST['direct_file_size_mb'] ?? 0.0);
            } elseif (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['pdf_file'], PDF_UPLOADS_PATH, ['pdf'], 500);
                if ($uploadResult['status']) {
                    $pdfFileName = $uploadResult['filename'];
                    $fileSizeMB = $uploadResult['size_mb'];
                } else {
                    $uploadError = true;
                    flash('error', $uploadResult['message']);
                }
            }

            if (!$uploadError) {
                if (empty($subjectId) || empty($title)) {
                    flash('error', 'Subject selection and blueprint title are required.');
                } else {
                    try {
                        $stmt = $db->prepare("
                            INSERT INTO chapters_content (
                                subject_id, module_id, chapter_number, title, description, 
                                pdf_file_path, pdf_external_url, page_count, file_size_mb, 
                                page_links, display_order, status
                            ) VALUES (
                                :sub, :mod, :chap_num, :title, :desc, 
                                :file, :url, :pages, :size, 
                                :links, :order, :status
                            )
                        ");
                        $stmt->execute([
                            ':sub' => $subjectId,
                            ':mod' => $moduleId,
                            ':chap_num' => $chapterNumber,
                            ':title' => $title,
                            ':desc' => $description,
                            ':file' => $pdfFileName,
                            ':url' => $externalUrl,
                            ':pages' => $pageCount,
                            ':size' => $fileSizeMB,
                            ':links' => null,
                            ':order' => $displayOrder,
                            ':status' => $status
                        ]);

                        flash('success', "Blueprint '{$title}' uploaded successfully!");
                        header('Location: ' . url('modules/blueprints/index.php'));
                        exit;
                    } catch (PDOException $e) {
                        flash('error', 'Database Error: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}

// Dropdown lists
$subjects = $db->query("
    SELECT s.id, s.name, b.code as board_code, st.name as standard_name 
    FROM subjects s
    JOIN boards b ON s.board_id = b.id
    JOIN standards st ON s.standard_id = st.id
    WHERE s.status = 'active'
    ORDER BY b.display_order ASC, st.display_order ASC, s.name ASC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="bb-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-compass-fill text-primary me-2"></i>Upload New Blueprint PDF
                    </h5>
                    <small class="text-muted">Fill in blueprint details and attach the PDF file</small>
                </div>
                <a href="<?= url('modules/blueprints/index.php') ?>" class="btn btn-sm btn-light border">
                    <i class="bi bi-arrow-left me-1"></i>Back to Blueprints
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="subject_id">Select Subject <span class="text-danger">*</span></label>
                        <select class="form-select" id="subject_id" name="subject_id" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($preselectedSubjectId == $s['id'] || (isset($_POST['subject_id']) && $_POST['subject_id'] == $s['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['board_code']) ?> &bull; <?= htmlspecialchars($s['standard_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="module_badge">Dashboard Module</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary"><i class="bi bi-compass-fill"></i></span>
                            <input type="text" class="form-control bg-light fw-bold text-primary" id="module_badge" value="Blueprint (Exam Format)" readonly>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="chapter_number">Paper / Index # <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="chapter_number" name="chapter_number" min="1" value="<?= (int)($_POST['chapter_number'] ?? 1) ?>" required>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label" for="title">Blueprint Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Annual Exam Blueprint 2024-25 & Chapter Marks Weightage" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Blueprint Description / Notes</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="e.g. Complete question paper pattern, Section A-D weightage, and marking scheme"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- PDF Upload Section -->
                    <div class="col-12">
                        <label class="form-label">Upload PDF Document</label>
                        <div class="upload-drop-zone" onclick="document.getElementById('pdf_file').click();">
                            <i class="bi bi-file-earmark-pdf"></i>
                            <h6 class="fw-bold text-dark mb-1">Click to browse or drag & drop Blueprint PDF file here</h6>
                            <p class="text-muted small mb-0" id="file_status_label">Supported formats: .pdf (Max size: 500MB)</p>
                        </div>
                        <input type="file" id="pdf_file" name="pdf_file" class="d-none" accept=".pdf" onchange="handleFileSelected(this)">
                        
                        <!-- Direct / Cloud Upload Fallback -->
                        <input type="hidden" id="direct_uploaded_file" name="direct_uploaded_file" value="">
                        <input type="hidden" id="direct_file_size_mb" name="direct_file_size_mb" value="">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="pdf_external_url">Or External / Cloud PDF URL (Optional)</label>
                        <input type="url" class="form-control" id="pdf_external_url" name="pdf_external_url" placeholder="https://.../blueprint.pdf" value="<?= htmlspecialchars($_POST['pdf_external_url'] ?? '') ?>">
                        <small class="text-muted">If hosted on Google Drive, AWS S3, or Supabase</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="page_count">Page Count</label>
                        <input type="number" class="form-control" id="page_count" name="page_count" min="1" value="<?= (int)($_POST['page_count'] ?? 1) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="1" value="<?= (int)($_POST['display_order'] ?? 1) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Publication Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : '' ?>>Active (Visible in App)</option>
                            <option value="inactive" <?= (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/blueprints/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i>Save & Upload Blueprint
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleFileSelected(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        document.getElementById('file_status_label').innerHTML = 
            `<strong class="text-primary"><i class="bi bi-check2-circle me-1"></i>Selected: ${file.name} (${sizeMB} MB)</strong>`;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
