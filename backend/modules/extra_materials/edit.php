<?php
/**
 * Edit Extra Material PDF
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Edit Extra Material';
$pageSubtitle = 'Update extra material details, PDF file, or publication status';
$db = Database::getConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flash('error', 'Invalid material ID specified.');
    header('Location: ' . url('modules/extra_materials/index.php'));
    exit;
}

$stmt = $db->prepare("SELECT * FROM extra_materials WHERE id = :id");
$stmt->execute([':id' => $id]);
$material = $stmt->fetch();

if (!$material) {
    flash('error', 'Material not found.');
    header('Location: ' . url('modules/extra_materials/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
        $sizeMB = round((int)$_SERVER['CONTENT_LENGTH'] / (1024 * 1024), 1);
        flash('error', "The uploaded file ({$sizeMB} MB) exceeds PHP's post_max_size limit.");
    } else {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrf)) {
            flash('error', 'Invalid security token. Please refresh the page and try again.');
        } else {
            $title = trim($_POST['title'] ?? '');
            $categorySlug = trim($_POST['category_slug'] ?? 'extra_material');
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $boardId = !empty($_POST['board_id']) ? (int)$_POST['board_id'] : null;
            $mediumId = !empty($_POST['medium_id']) ? (int)$_POST['medium_id'] : null;
            $standardId = !empty($_POST['standard_id']) ? (int)$_POST['standard_id'] : null;
            $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
            $description = trim($_POST['description'] ?? '');
            $pageCount = (int)($_POST['page_count'] ?? 1);
            $externalUrl = trim($_POST['pdf_external_url'] ?? '');
            $displayOrder = (int)($_POST['display_order'] ?? 1);
            $status = $_POST['status'] ?? 'active';

            $pdfFileName = $material['pdf_file_path'];
            $fileSizeMB = $material['file_size_mb'];

            // Handle New PDF File Upload (direct client-side upload or standard)
            if (!empty($_POST['direct_uploaded_file'])) {
                $pdfFileName = trim($_POST['direct_uploaded_file']);
                $fileSizeMB = (float)($_POST['direct_file_size_mb'] ?? 0.0);
            } elseif (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['pdf_file'], PDF_UPLOADS_PATH, ['pdf'], 500);
                if ($uploadResult['status']) {
                    // Delete old file if exists
                    if (!empty($material['pdf_file_path']) && file_exists(PDF_UPLOADS_PATH . $material['pdf_file_path'])) {
                        @unlink(PDF_UPLOADS_PATH . $material['pdf_file_path']);
                    }
                    $pdfFileName = $uploadResult['filename'];
                    $fileSizeMB = $uploadResult['size_mb'];
                } else {
                    flash('error', $uploadResult['message']);
                }
            }

            if (empty($title)) {
                flash('error', 'Material title is required.');
            } else {
                try {
                    $updateStmt = $db->prepare("
                        UPDATE extra_materials SET
                            category_id = :cat_id,
                            category_slug = :cat_slug,
                            board_id = :board_id,
                            medium_id = :medium_id,
                            standard_id = :standard_id,
                            subject_id = :subject_id,
                            title = :title,
                            description = :desc,
                            pdf_file_path = :file,
                            pdf_external_url = :url,
                            page_count = :pages,
                            file_size_mb = :size,
                            display_order = :ord,
                            status = :status
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':cat_id' => $categoryId,
                        ':cat_slug' => $categorySlug,
                        ':board_id' => $boardId,
                        ':medium_id' => $mediumId,
                        ':standard_id' => $standardId,
                        ':subject_id' => $subjectId,
                        ':title' => $title,
                        ':desc' => $description,
                        ':file' => $pdfFileName,
                        ':url' => $externalUrl,
                        ':pages' => $pageCount,
                        ':size' => $fileSizeMB,
                        ':ord' => $displayOrder,
                        ':status' => $status,
                        ':id' => $id
                    ]);
                    flash('success', "Extra Material '{$title}' updated successfully.");
                    header('Location: ' . url('modules/extra_materials/index.php'));
                    exit;
                } catch (PDOException $e) {
                    flash('error', 'Database error: ' . $e->getMessage());
                }
            }
        }
    }
}

// Fetch dropdown data
$categories = $db->query("SELECT * FROM learning_categories ORDER BY display_order ASC")->fetchAll();
$boards = $db->query("SELECT id, code, name FROM boards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$mediums = $db->query("SELECT id, code, name FROM mediums WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$standards = $db->query("SELECT id, standard_number, name FROM standards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
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
    <div class="col-lg-9">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Extra Material</h5>
                <a href="<?= url('modules/extra_materials/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="title">Material Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $material['title']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="category_slug">Learning Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_slug" name="category_slug" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= ($_POST['category_slug'] ?? $material['category_slug']) === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="board_id">Target Board</label>
                        <select class="form-select" id="board_id" name="board_id">
                            <option value="">-- All Boards (Universal) --</option>
                            <?php foreach ($boards as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($_POST['board_id'] ?? $material['board_id']) == $b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['code']) ?> - <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="medium_id">Target Medium</label>
                        <select class="form-select" id="medium_id" name="medium_id">
                            <option value="">-- All Mediums --</option>
                            <?php foreach ($mediums as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($_POST['medium_id'] ?? $material['medium_id']) == $m['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="standard_id">Target Standard / Class</label>
                        <select class="form-select" id="standard_id" name="standard_id">
                            <option value="">-- All Standards --</option>
                            <?php foreach ($standards as $st): ?>
                                <option value="<?= $st['id'] ?>" <?= ($_POST['standard_id'] ?? $material['standard_id']) == $st['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="subject_id">Related Subject (Optional)</label>
                        <select class="form-select" id="subject_id" name="subject_id">
                            <option value="">-- General / No Specific Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($_POST['subject_id'] ?? $material['subject_id']) == $s['id'] ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($s['board_code']) ?> - <?= htmlspecialchars($s['standard_name']) ?>] <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="page_count">Total Pages</label>
                        <input type="number" class="form-control" id="page_count" name="page_count" min="1" value="<?= htmlspecialchars($_POST['page_count'] ?? $material['page_count']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="1" value="<?= htmlspecialchars($_POST['display_order'] ?? $material['display_order']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Short Description / Key Topics</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($_POST['description'] ?? $material['description']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF Document</h6>
                            
                            <?php if (!empty($material['pdf_file_path'])): ?>
                                <div class="alert alert-info py-2 px-3 d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <i class="bi bi-file-earmark-check me-2"></i>
                                        Current PDF: <strong><?= htmlspecialchars($material['pdf_file_path']) ?></strong> (<?= $material['file_size_mb'] ?> MB)
                                    </div>
                                    <?php $pdfUrl = resolveMediaUrl($material['pdf_file_path'], 'pdfs'); ?>
                                    <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" class="btn btn-xs btn-primary">
                                        <i class="bi bi-eye me-1"></i>Preview
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label" for="pdf_file">Upload New / Replacement PDF (Leave empty to keep current)</label>
                                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf,application/pdf">
                            </div>

                            <div class="text-center text-muted my-2 small fw-bold">— OR EXTERNAL URL —</div>

                            <div>
                                <label class="form-label" for="pdf_external_url">External PDF URL</label>
                                <input type="url" class="form-control" id="pdf_external_url" name="pdf_external_url" value="<?= htmlspecialchars($_POST['pdf_external_url'] ?? $material['pdf_external_url']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Publication Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= ($_POST['status'] ?? $material['status']) === 'active' ? 'selected' : '' ?>>Active (Visible to Students)</option>
                            <option value="inactive" <?= ($_POST['status'] ?? $material['status']) === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= url('modules/extra_materials/index.php') ?>" class="btn btn-bb-light">Cancel</a>
                    <button type="submit" class="btn btn-bb-primary">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
