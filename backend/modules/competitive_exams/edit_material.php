<?php
/**
 * Edit Competitive Exam Material PDF
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Invalid material ID.');
    header('Location: ' . url('modules/competitive_exams/index.php'));
    exit;
}

$pageTitle = 'Edit Competitive Exam Material';
$pageSubtitle = 'Update exam question paper, formula handbook or PDF notes';
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM competitive_exam_materials WHERE id = :id");
$stmt->execute([':id' => $id]);
$material = $stmt->fetch();

if (!$material) {
    flash('error', 'Exam material not found.');
    header('Location: ' . url('modules/competitive_exams/index.php'));
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
            $examName = trim($_POST['exam_name'] ?? '');
            $materialType = trim($_POST['material_type'] ?? 'PDF Material');
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
                    // Remove old local file if replaced
                    if (!empty($pdfFileName) && file_exists(PDF_UPLOADS_PATH . $pdfFileName)) {
                        @unlink(PDF_UPLOADS_PATH . $pdfFileName);
                    }
                    $pdfFileName = $uploadResult['filename'];
                    $fileSizeMB = $uploadResult['size_mb'];
                } else {
                    flash('error', $uploadResult['message']);
                }
            }

            if (empty($title)) {
                flash('error', 'Material / PDF title is required.');
            } elseif (empty($pdfFileName) && empty($externalUrl)) {
                flash('error', 'Please upload a PDF file or provide an external PDF URL.');
            } else {
                try {
                    // Match existing competitive exam ID if available
                    $examId = $material['exam_id'] ?? null;
                    if (!empty($examName)) {
                        $findExam = $db->prepare("SELECT id FROM competitive_exams WHERE LOWER(title) = LOWER(:name) OR LOWER(slug) = LOWER(:slug) OR LOWER(exam_code) = LOWER(:code) LIMIT 1");
                        $findExam->execute([':name' => $examName, ':slug' => $examName, ':code' => $examName]);
                        $matched = $findExam->fetch();
                        if ($matched) {
                            $examId = (int)$matched['id'];
                        }
                    }

                    $stmt = $db->prepare("
                        UPDATE competitive_exam_materials
                        SET title = :title,
                            exam_id = :exam_id,
                            exam_name = :exam_name,
                            subject_name = :sub_name,
                            material_type = :mat_type,
                            description = :description,
                            pdf_file_path = :pdf_file,
                            pdf_external_url = :ext_url,
                            page_count = :page_count,
                            file_size_mb = :file_size,
                            display_order = :display_order,
                            status = :status
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':title' => $title,
                        ':exam_id' => $examId,
                        ':exam_name' => $examName,
                        ':sub_name' => $examName ?: 'General',
                        ':mat_type' => $materialType,
                        ':description' => $description,
                        ':pdf_file' => $pdfFileName,
                        ':ext_url' => $externalUrl,
                        ':page_count' => $pageCount,
                        ':file_size' => $fileSizeMB,
                        ':display_order' => $displayOrder,
                        ':status' => $status,
                        ':id' => $id,
                    ]);

                    flash('success', 'Competitive Exam material updated successfully!');
                    header('Location: ' . url('modules/competitive_exams/index.php'));
                    exit;
                } catch (PDOException $e) {
                    flash('error', 'Database Error: ' . $e->getMessage());
                }
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Edit Competitive Exam Material
                </h5>
                <small class="text-muted">Update details and attached PDF file</small>
            </div>
            <a href="<?= url('modules/competitive_exams/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to List
            </a>
        </div>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

            <!-- Material Details Card -->
            <div class="bb-card mb-3">
                <div class="bb-card-header">
                    <h6 class="fw-bold mb-0 text-dark">1. Material Information</h6>
                </div>
                <div class="bb-card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-dark">
                                Material / PDF Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($_POST['title'] ?? $material['title']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Exam / Category Name</label>
                            <input type="text" name="exam_name" class="form-control" placeholder="e.g. UPSC, SSC, Banking, GPSC, General..." value="<?= htmlspecialchars($_POST['exam_name'] ?? $material['exam_name'] ?? '') ?>" list="examSuggestions">
                            <datalist id="examSuggestions">
                                <option value="UPSC">
                                <option value="SSC">
                                <option value="Banking / IBPS">
                                <option value="Railways (RRB)">
                                <option value="GPSC / State PSC">
                                <option value="Defence / NDA / CDS">
                                <option value="JEE">
                                <option value="NEET">
                                <option value="GATE">
                                <option value="General Aptitude">
                            </datalist>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-dark">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($_POST['description'] ?? $material['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PDF Upload Card -->
            <div class="bb-card mb-3">
                <div class="bb-card-header">
                    <h6 class="fw-bold mb-0 text-dark">2. PDF Attachment</h6>
                </div>
                <div class="bb-card-body p-4">
                    <div class="row g-3">
                        <?php if (!empty($material['pdf_file_path'])): ?>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded border d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-4 me-2"></i>
                                        <strong>Current File:</strong> <?= htmlspecialchars($material['pdf_file_path']) ?>
                                        <small class="text-muted ms-2">(<?= $material['file_size_mb'] ?> MB)</small>
                                    </div>
                                    <?php $pdfUrl = resolveMediaUrl($material['pdf_file_path'], 'pdfs'); ?>
                                    <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>View Current PDF
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-dark">Replace PDF File (Optional)</label>
                            <input type="file" name="pdf_file" class="form-control" accept=".pdf,application/pdf">
                            <div class="form-text small text-muted">Leave empty to keep current PDF file.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-dark">External PDF URL</label>
                            <input type="url" name="pdf_external_url" class="form-control" placeholder="https://example.com/file.pdf" value="<?= htmlspecialchars($_POST['pdf_external_url'] ?? $material['pdf_external_url'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Page Count</label>
                            <input type="number" name="page_count" class="form-control" min="1" value="<?= htmlspecialchars($_POST['page_count'] ?? $material['page_count']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Display Order</label>
                            <input type="number" name="display_order" class="form-control" min="1" value="<?= htmlspecialchars($_POST['display_order'] ?? $material['display_order']) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($_POST['status'] ?? $material['status']) === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                                <option value="inactive" <?= ($_POST['status'] ?? $material['status']) === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="<?= url('modules/competitive_exams/index.php') ?>" class="btn btn-outline-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-bb-primary px-4">
                    <i class="bi bi-check-circle me-1"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
