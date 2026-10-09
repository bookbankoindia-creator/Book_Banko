<?php
/**
 * Edit Blueprint PDF
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$db = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM chapters_content WHERE id = :id");
$stmt->execute([':id' => $id]);
$blueprint = $stmt->fetch();

if (!$blueprint) {
    flash('error', 'Blueprint material not found.');
    header('Location: ' . url('modules/blueprints/index.php'));
    exit;
}

$pageTitle = 'Edit Blueprint: ' . $blueprint['title'];
$pageSubtitle = 'Update blueprint details, paper pattern, and PDF file';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $chapterNumber = (int)($_POST['chapter_number'] ?? 1);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $pageCount = (int)($_POST['page_count'] ?? 1);
        $externalUrl = trim($_POST['pdf_external_url'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? $chapterNumber);
        $status = $_POST['status'] ?? 'active';

        $pdfFileName = $blueprint['pdf_file_path'];
        $fileSizeMB = $blueprint['file_size_mb'];
        $uploadError = false;

        if (!empty($_POST['direct_uploaded_file'])) {
            $pdfFileName = trim($_POST['direct_uploaded_file']);
            $fileSizeMB = (float)($_POST['direct_file_size_mb'] ?? 0.0);
        } elseif (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadFile($_FILES['pdf_file'], PDF_UPLOADS_PATH, ['pdf'], 500);
            if ($uploadResult['status']) {
                if (!empty($blueprint['pdf_file_path']) && file_exists(PDF_UPLOADS_PATH . $blueprint['pdf_file_path'])) {
                    @unlink(PDF_UPLOADS_PATH . $blueprint['pdf_file_path']);
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
                flash('error', 'Subject and blueprint title are required.');
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE chapters_content SET
                            subject_id = :sub,
                            chapter_number = :chap_num,
                            title = :title,
                            description = :desc,
                            pdf_file_path = :file,
                            pdf_external_url = :url,
                            page_count = :pages,
                            file_size_mb = :size,
                            display_order = :order,
                            status = :status
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':sub' => $subjectId,
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

                    flash('success', 'Blueprint updated successfully.');
                    header('Location: ' . url('modules/blueprints/index.php'));
                    exit;
                } catch (PDOException $e) {
                    flash('error', 'Database Error: ' . $e->getMessage());
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
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Blueprint
                    </h5>
                    <small class="text-muted"><?= htmlspecialchars($blueprint['title']) ?></small>
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
                                <option value="<?= $s['id'] ?>" <?= $blueprint['subject_id'] == $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['board_code']) ?> &bull; <?= htmlspecialchars($s['standard_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Module</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary"><i class="bi bi-compass-fill"></i></span>
                            <input type="text" class="form-control bg-light fw-bold text-primary" value="Blueprint" readonly>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="chapter_number">Paper / Index # <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="chapter_number" name="chapter_number" min="1" value="<?= htmlspecialchars($blueprint['chapter_number']) ?>" required>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label" for="title">Blueprint Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($blueprint['title']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Blueprint Description / Notes</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($blueprint['description'] ?? '') ?></textarea>
                    </div>

                    <!-- PDF Upload Section -->
                    <div class="col-12">
                        <label class="form-label">Update PDF Document</label>
                        <?php if (!empty($blueprint['pdf_file_path'])): ?>
                            <div class="p-3 bg-light rounded border mb-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-4 me-2 align-middle"></i>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($blueprint['pdf_file_path']) ?></span>
                                    <span class="badge bg-secondary-subtle text-secondary ms-2"><?= $blueprint['file_size_mb'] ?> MB</span>
                                </div>
                                <a href="<?= getPdfUrl($blueprint['pdf_file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View Current PDF
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="upload-drop-zone" onclick="document.getElementById('pdf_file').click();">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <h6 class="fw-bold text-dark mb-1">Upload replacement PDF file</h6>
                            <p class="text-muted small mb-0" id="file_status_label">Leave empty to keep existing file</p>
                        </div>
                        <input type="file" id="pdf_file" name="pdf_file" class="d-none" accept=".pdf" onchange="handleFileSelected(this)">
                        <input type="hidden" id="direct_uploaded_file" name="direct_uploaded_file" value="">
                        <input type="hidden" id="direct_file_size_mb" name="direct_file_size_mb" value="">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="pdf_external_url">Or External / Cloud PDF URL</label>
                        <input type="url" class="form-control" id="pdf_external_url" name="pdf_external_url" value="<?= htmlspecialchars($blueprint['pdf_external_url'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="page_count">Page Count</label>
                        <input type="number" class="form-control" id="page_count" name="page_count" min="1" value="<?= htmlspecialchars($blueprint['page_count']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="1" value="<?= htmlspecialchars($blueprint['display_order']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Publication Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $blueprint['status'] === 'active' ? 'selected' : '' ?>>Active (Visible in App)</option>
                            <option value="inactive" <?= $blueprint['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/blueprints/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
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
