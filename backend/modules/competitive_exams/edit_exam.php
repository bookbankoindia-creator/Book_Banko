<?php
/**
 * Edit Competitive Exam
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Invalid exam ID.');
    header('Location: ' . url('modules/competitive_exams/index.php'));
    exit;
}

$pageTitle = 'Edit Competitive Exam';
$pageSubtitle = 'Update exam details, name, short code or description';
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM competitive_exams WHERE id = :id");
$stmt->execute([':id' => $id]);
$exam = $stmt->fetch();

if (!$exam) {
    flash('error', 'Exam not found.');
    header('Location: ' . url('modules/competitive_exams/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $examCode = strtoupper(trim($_POST['exam_code'] ?? ''));
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $category = trim($_POST['category'] ?? 'Engineering / Medical');
        $colorHex = trim($_POST['color_hex'] ?? '#0061A4');
        $description = trim($_POST['description'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($slug)) {
            $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($examCode ?: $title));
        }

        if (empty($title) || empty($examCode)) {
            flash('error', 'Exam Title and Exam Code are required.');
        } else {
            try {
                $stmt = $db->prepare("
                    UPDATE competitive_exams SET
                        title = :title,
                        slug = :slug,
                        exam_code = :exam_code,
                        category = :category,
                        color_hex = :color,
                        description = :desc,
                        display_order = :ord,
                        status = :status
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':slug' => $slug,
                    ':exam_code' => $examCode,
                    ':category' => $category,
                    ':color' => $colorHex,
                    ':desc' => $description,
                    ':ord' => $displayOrder,
                    ':status' => $status,
                    ':id' => $id
                ]);
                flash('success', "Competitive Exam '{$title}' updated successfully.");
                header('Location: ' . url('modules/competitive_exams/index.php'));
                exit;
            } catch (PDOException $e) {
                flash('error', 'Database error: ' . $e->getMessage());
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Competitive Exam</h5>
                <a href="<?= url('modules/competitive_exams/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="title">Exam Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($exam['title']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="exam_code">Short Code / Badge <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="exam_code" name="exam_code" value="<?= htmlspecialchars($exam['exam_code']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="slug">URL / API Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($exam['slug']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="category">Exam Category / Stream</label>
                        <input type="text" class="form-control" id="category" name="category" value="<?= htmlspecialchars($exam['category'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="color_hex">Theme / Badge Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="color_hex_picker" value="<?= htmlspecialchars($exam['color_hex'] ?? '#0061A4') ?>" onchange="document.getElementById('color_hex').value = this.value">
                            <input type="text" class="form-control" id="color_hex" name="color_hex" value="<?= htmlspecialchars($exam['color_hex'] ?? '#0061A4') ?>" onchange="document.getElementById('color_hex_picker').value = this.value">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="1" value="<?= htmlspecialchars($exam['display_order']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Exam Description / Details</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($exam['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $exam['status'] === 'active' ? 'selected' : '' ?>>Active (Visible in App)</option>
                            <option value="inactive" <?= $exam['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= url('modules/competitive_exams/index.php') ?>" class="btn btn-bb-light">Cancel</a>
                    <button type="submit" class="btn btn-bb-primary">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
