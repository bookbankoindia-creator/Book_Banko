<?php
/**
 * Edit Banner
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$db = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM banners WHERE id = :id");
$stmt->execute([':id' => $id]);
$banner = $stmt->fetch();

if (!$banner) {
    flash('error', 'Banner not found.');
    header('Location: ' . url('modules/banners/index.php'));
    exit;
}

$pageTitle = 'Edit Banner: ' . $banner['title'];
$pageSubtitle = 'Update banner graphics and call-to-action';

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

        $imagePath = $banner['image_path'];

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadFile($_FILES['banner_image'], BANNER_UPLOADS_PATH, ['jpg', 'jpeg', 'png', 'webp'], 10);
            if ($uploadResult['status']) {
                if (!empty($banner['image_path']) && file_exists(BANNER_UPLOADS_PATH . $banner['image_path'])) {
                    @unlink(BANNER_UPLOADS_PATH . $banner['image_path']);
                }
                $imagePath = $uploadResult['filename'];
            } else {
                flash('error', $uploadResult['message']);
            }
        }

        if (empty($title)) {
            flash('error', 'Banner title is required.');
        } else {
            try {
                $update = $db->prepare("
                    UPDATE banners 
                    SET title = :title, sub_title = :sub, image_path = :img, action_type = :action, 
                        action_value = :val, display_order = :order, status = :status
                    WHERE id = :id
                ");
                $update->execute([
                    ':title' => $title,
                    ':sub' => $subTitle,
                    ':img' => $imagePath,
                    ':action' => $actionType,
                    ':val' => $actionValue,
                    ':order' => $displayOrder,
                    ':status' => $status,
                    ':id' => $id
                ]);
                flash('success', "Banner '{$title}' updated successfully.");
                header('Location: ' . url('modules/banners/index.php'));
                exit;
            } catch (PDOException $e) {
                flash('error', 'Update error: ' . $e->getMessage());
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
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Banner</h5>
                <a href="<?= url('modules/banners/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label" for="title">Banner Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($banner['title']) ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" for="sub_title">Subtitle / Secondary Text</label>
                        <input type="text" class="form-control" id="sub_title" name="sub_title" value="<?= htmlspecialchars($banner['sub_title'] ?? '') ?>">
                    </div>

                    <?php if (!empty($banner['image_path']) && file_exists(BANNER_UPLOADS_PATH . $banner['image_path'])): ?>
                        <div class="col-12">
                            <span class="text-muted small d-block mb-1">Current Banner Image:</span>
                            <?php $bannerPreviewUrl = resolveMediaUrl($banner['image_path'], 'banners'); ?>
                            <img src="<?= htmlspecialchars($bannerPreviewUrl) ?>" alt="Current" class="rounded-3 border" style="max-height: 120px;">
                        </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <label class="form-label">Replace Banner Image</label>
                        <input type="file" class="form-control" name="banner_image" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="action_type">Click Action Type</label>
                        <select class="form-select" id="action_type" name="action_type">
                            <option value="none" <?= $banner['action_type'] === 'none' ? 'selected' : '' ?>>No Action (Informational Only)</option>
                            <option value="open_url" <?= $banner['action_type'] === 'open_url' ? 'selected' : '' ?>>Open Web URL</option>
                            <option value="open_subject" <?= $banner['action_type'] === 'open_subject' ? 'selected' : '' ?>>Open Specific Subject</option>
                            <option value="open_standard" <?= $banner['action_type'] === 'open_standard' ? 'selected' : '' ?>>Open Standard Dashboard</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="action_value">Action Target Value</label>
                        <input type="text" class="form-control" id="action_value" name="action_value" value="<?= htmlspecialchars($banner['action_value'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)$banner['display_order'] ?>" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $banner['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $banner['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/banners/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-save me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
