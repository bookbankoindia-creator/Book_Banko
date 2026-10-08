<?php
/**
 * Add Banner
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Add Promo Banner';
$pageSubtitle = 'Create a new promotional carousel banner';
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $subTitle = trim($_POST['sub_title'] ?? '');
        $actionType = $_POST['action_type'] ?? 'none';
        $actionValue = trim($_POST['action_value'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        $imagePath = 'banner_default.png';
        $uploadError = false;

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadFile($_FILES['banner_image'], BANNER_UPLOADS_PATH, ['jpg', 'jpeg', 'png', 'webp'], 10);
            if ($uploadResult['status']) {
                $imagePath = $uploadResult['filename'];
            } else {
                $uploadError = true;
                flash('error', $uploadResult['message']);
            }
        }

        if (!$uploadError) {
            if (empty($title)) {
                flash('error', 'Banner title is required.');
            } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO banners (title, sub_title, image_path, action_type, action_value, display_order, status)
                    VALUES (:title, :sub, :img, :action, :val, :order, :status)
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':sub' => $subTitle,
                    ':img' => $imagePath,
                    ':action' => $actionType,
                    ':val' => $actionValue,
                    ':order' => $displayOrder,
                    ':status' => $status
                ]);
                flash('success', "Banner '{$title}' added successfully.");
                header('Location: ' . url('modules/banners/index.php'));
                exit;
            } catch (PDOException $e) {
                flash('error', 'Database error: ' . $e->getMessage());
            }
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
                <h5><i class="bi bi-card-image text-primary me-2"></i>New Banner Details</h5>
                <a href="<?= url('modules/banners/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label" for="title">Banner Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. GSEB 2026 Question Blueprints Available!" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" for="sub_title">Subtitle / Secondary Text</label>
                        <input type="text" class="form-control" id="sub_title" name="sub_title" placeholder="e.g. Download official syllabus and format updates" value="<?= htmlspecialchars($_POST['sub_title'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Banner Image (JPG, PNG, WEBP)</label>
                        <input type="file" class="form-control" name="banner_image" accept="image/*">
                        <small class="text-muted">Recommended aspect ratio 16:9 or 2:1 (e.g., 1200x600px)</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="action_type">Click Action Type</label>
                        <select class="form-select" id="action_type" name="action_type">
                            <option value="none">No Action (Informational Only)</option>
                            <option value="open_url">Open Web URL</option>
                            <option value="open_subject">Open Specific Subject</option>
                            <option value="open_standard">Open Standard Dashboard</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="action_value">Action Target Value (URL or ID)</label>
                        <input type="text" class="form-control" id="action_value" name="action_value" placeholder="https://... or Subject ID" value="<?= htmlspecialchars($_POST['action_value'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)($_POST['display_order'] ?? 1) ?>" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/banners/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-check-lg me-1"></i>Publish Banner
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
