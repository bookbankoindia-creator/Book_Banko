<?php
/**
 * Upload & Add New Competitive Exam Material PDF
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Upload Competitive Exam PDF';
$pageSubtitle = 'Upload question papers, formula handbooks, notes or mock tests for any competitive exam';
$db = Database::getConnection();

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
            $pageLinks = trim($_POST['page_links'] ?? '');
            $displayOrder = (int)($_POST['display_order'] ?? 1);
            $status = $_POST['status'] ?? 'active';

            $pdfFileName = '';
            $fileSizeMB = 0.00;

            // Handle PDF File Upload (direct client-side upload or standard)
            if (!empty($_POST['direct_uploaded_file'])) {
                $pdfFileName = trim($_POST['direct_uploaded_file']);
                $fileSizeMB = (float)($_POST['direct_file_size_mb'] ?? 0.0);
            } elseif (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['pdf_file'], PDF_UPLOADS_PATH, ['pdf'], 500);
                if ($uploadResult['status']) {
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
                    $examId = null;
                    if (!empty($examName)) {
                        $findExam = $db->prepare("SELECT id FROM competitive_exams WHERE LOWER(title) = LOWER(:name) OR LOWER(slug) = LOWER(:slug) OR LOWER(exam_code) = LOWER(:code) LIMIT 1");
                        $findExam->execute([':name' => $examName, ':slug' => $examName, ':code' => $examName]);
                        $matched = $findExam->fetch();
                        if ($matched) {
                            $examId = (int)$matched['id'];
                        }
                    }

                    $stmt = $db->prepare("
                        INSERT INTO competitive_exam_materials
                        (exam_id, exam_name, subject_name, title, material_type, description, pdf_file_path, pdf_external_url, page_count, file_size_mb, page_links, is_free, display_order, status)
                        VALUES
                        (:exam_id, :exam_name, :sub_name, :title, :mat_type, :description, :pdf_file, :ext_url, :page_count, :file_size, :links, true, :display_order, :status)
                    ");
                    $stmt->execute([
                        ':exam_id' => $examId,
                        ':exam_name' => $examName,
                        ':sub_name' => $examName ?: 'General',
                        ':title' => $title,
                        ':mat_type' => $materialType,
                        ':description' => $description,
                        ':pdf_file' => $pdfFileName,
                        ':ext_url' => $externalUrl,
                        ':page_count' => $pageCount,
                        ':file_size' => $fileSizeMB,
                        ':links' => $pageLinks,
                        ':display_order' => $displayOrder,
                        ':status' => $status,
                    ]);

                    flash('success', 'Competitive Exam PDF "' . htmlspecialchars($title) . '" uploaded successfully!');
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
                    <i class="bi bi-cloud-arrow-up text-primary me-2"></i>Upload Competitive Exam PDF
                </h5>
                <small class="text-muted">Fill in the material title and select a PDF to make it available to students</small>
            </div>
            <a href="<?= url('modules/competitive_exams/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to List
            </a>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" class="needs-validation">
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
                            <input type="text" name="title" class="form-control" required placeholder="e.g. UPSC Prelims Solved Papers, SSC CGL Formula Book, Grammar..." value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                            <div class="form-text small">This is the title students will see in the app card.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Exam / Category Name</label>
                            <input type="text" name="exam_name" class="form-control" placeholder="e.g. UPSC, SSC, Banking, GPSC, General..." value="<?= htmlspecialchars($_POST['exam_name'] ?? '') ?>" list="examSuggestions">
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
                            <div class="form-text small">Tag or exam name (optional).</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-dark">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Brief summary of contents..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-dark">
                                Choose PDF File <span class="text-danger">*</span>
                            </label>
                            <div class="p-3 border border-2 border-dashed rounded text-center bg-light">
                                <i class="bi bi-file-earmark-pdf fs-2 text-primary d-block mb-1"></i>
                                <input type="file" name="pdf_file" class="form-control" accept=".pdf,application/pdf">
                                <div class="form-text mt-2 small text-muted">Supports PDF files up to 500 MB.</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="text-center text-muted small my-1">── OR PROVIDE DIRECT PDF URL ──</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-dark">External PDF URL (Optional)</label>
                            <input type="url" name="pdf_external_url" class="form-control" placeholder="https://example.com/file.pdf" value="<?= htmlspecialchars($_POST['pdf_external_url'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Estimated Page Count</label>
                            <input type="number" name="page_count" class="form-control" min="1" value="<?= htmlspecialchars($_POST['page_count'] ?? '1') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Display Order</label>
                            <input type="number" name="display_order" class="form-control" min="1" value="<?= htmlspecialchars($_POST['display_order'] ?? '1') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-dark">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                                <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-dark">
                                <i class="bi bi-link-45deg text-primary me-1"></i>Page-Specific JEE & NEET Question Links (Google Docs / Quizzes)
                            </label>
                            <textarea class="form-control font-monospace small" name="page_links" rows="3" placeholder='{"1": "https://docs.google.com/document/d/1KpHDVKmK_OJhktZPq0wOphWFlPFUJE2jIIJCiC_Fz30/edit?tab=t.0", "2": "https://...", "default": "https://..."}'><?= htmlspecialchars($_POST['page_links'] ?? '') ?></textarea>
                            <div class="form-text">Enter JSON mapping of page numbers to question links (e.g. <code>{"1": "https://docs.google.com/...", "2": "https://..."}</code>) or a default Google Doc link.</div>
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
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i>Upload Material
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
