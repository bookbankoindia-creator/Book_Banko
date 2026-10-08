<?php
/**
 * Edit Chapter PDF
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$db = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM chapters_content WHERE id = :id");
$stmt->execute([':id' => $id]);
$chapter = $stmt->fetch();

if (!$chapter) {
    flash('error', 'Chapter not found.');
    header('Location: ' . url('modules/chapters/index.php'));
    exit;
}

$pageTitle = 'Edit Chapter: ' . $chapter['title'];
$pageSubtitle = 'Update chapter material, page count, and PDF file';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $moduleId = (int)($_POST['module_id'] ?? 1);
        $chapterNumber = (int)($_POST['chapter_number'] ?? 1);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $pageCount = (int)($_POST['page_count'] ?? 1);
        $externalUrl = trim($_POST['pdf_external_url'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? $chapterNumber);
        $status = $_POST['status'] ?? 'active';

        $pdfFileName = $chapter['pdf_file_path'];
        $fileSizeMB = $chapter['file_size_mb'];
        $uploadError = false;

        // Check if new PDF file was uploaded (direct or standard)
        if (!empty($_POST['direct_uploaded_file'])) {
            $pdfFileName = trim($_POST['direct_uploaded_file']);
            $fileSizeMB = (float)($_POST['direct_file_size_mb'] ?? 0.0);
        } elseif (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadFile($_FILES['pdf_file'], PDF_UPLOADS_PATH, ['pdf'], 500);
            if ($uploadResult['status']) {
                // Delete old file if exists
                if (!empty($chapter['pdf_file_path']) && file_exists(PDF_UPLOADS_PATH . $chapter['pdf_file_path'])) {
                    @unlink(PDF_UPLOADS_PATH . $chapter['pdf_file_path']);
                }
                $pdfFileName = $uploadResult['filename'];
                $fileSizeMB = $uploadResult['size_mb'];
            } else {
                $uploadError = true;
                flash('error', $uploadResult['message']);
            }
        }

        if (!$uploadError) {
            if (empty($subjectId) || empty($title)) {
                flash('error', 'Subject selection and chapter title are required.');
            } else {
            try {
                $update = $db->prepare("
                    UPDATE chapters_content 
                    SET subject_id = :sub, module_id = :mod, chapter_number = :chap_num, 
                        title = :title, description = :desc, pdf_file_path = :file, 
                        pdf_external_url = :url, page_count = :pages, file_size_mb = :size, 
                        display_order = :order, status = :status
                    WHERE id = :id
                ");
                $update->execute([
                    ':sub' => $subjectId,
                    ':mod' => $moduleId,
                    ':chap_num' => $chapterNumber,
                    ':title' => $title,
                    ':desc' => $description,
                    ':file' => $pdfFileName,
                    ':url' => $externalUrl,
                    ':pages' => $pageCount,
                    ':size' => $fileSizeMB,
                    ':order' => $displayOrder,
                    ':status' => $status,
                    ':id' => $id
                ]);
                flash('success', "Chapter '{$title}' updated successfully.");
                header('Location: ' . url('modules/chapters/index.php?subject_id=' . $subjectId));
                exit;
            } catch (PDOException $e) {
                flash('error', 'Update error: ' . $e->getMessage());
            }
        }
        }
    }
}

$subjects = $db->query("
    SELECT s.id, s.name, b.code as board_code, st.name as standard_name 
    FROM subjects s
    JOIN boards b ON s.board_id = b.id
    JOIN standards st ON s.standard_id = st.id
    WHERE s.status = 'active'
    ORDER BY b.display_order ASC, st.display_order ASC, s.name ASC
")->fetchAll();

$modules = $db->query("SELECT id, title FROM dashboard_modules WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Chapter: <?= htmlspecialchars($chapter['title']) ?></h5>
                <a href="<?= url('modules/chapters/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="subject_id">Select Subject <span class="text-danger">*</span></label>
                        <select class="form-select" id="subject_id" name="subject_id" required>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $chapter['subject_id'] == $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['board_code']) ?> &bull; <?= htmlspecialchars($s['standard_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label" for="module_id">Dashboard Module <span class="text-danger">*</span></label>
                        <select class="form-select" id="module_id" name="module_id" required>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $chapter['module_id'] == $m['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="chapter_number">Chapter Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="chapter_number" name="chapter_number" value="<?= (int)$chapter['chapter_number'] ?>" required>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label" for="title">Chapter Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($chapter['title']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Chapter Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($chapter['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Current File Status -->
                    <?php if (!empty($chapter['pdf_file_path'])): ?>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small d-block">Current PDF File:</span>
                                    <strong class="text-dark"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i><?= htmlspecialchars($chapter['pdf_file_path']) ?> (<?= $chapter['file_size_mb'] ?> MB)</strong>
                                </div>
                                <?php $previewUrl = resolveMediaUrl($chapter['pdf_file_path'], 'pdfs'); ?>
                                <a href="<?= htmlspecialchars($previewUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Preview
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Replace PDF Upload -->
                    <div class="col-12">
                        <label class="form-label">Replace PDF Document (Leave blank to keep existing)</label>
                        <div class="upload-drop-zone" onclick="document.getElementById('pdf_file').click();">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <h6 class="fw-bold text-dark mb-1">Click to browse new PDF to replace current file</h6>
                            <p class="text-muted small mb-0" id="file_status_label">Supported format: .pdf</p>
                            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" style="display: none;" onchange="handleFileSelected(this)">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="pdf_external_url">External PDF URL</label>
                        <input type="url" class="form-control" id="pdf_external_url" name="pdf_external_url" value="<?= htmlspecialchars($chapter['pdf_external_url'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="page_count">Total Pages</label>
                        <input type="number" class="form-control" id="page_count" name="page_count" value="<?= (int)$chapter['page_count'] ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)$chapter['display_order'] ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $chapter['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $chapter['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/chapters/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-save me-1"></i>Save Changes
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
            `<strong class="text-primary"><i class="bi bi-check2-circle me-1"></i>New File: ${file.name} (${sizeMB} MB)</strong>`;
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
