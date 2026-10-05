<?php
/**
 * Edit Dashboard Module
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$db = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM dashboard_modules WHERE id = :id");
$stmt->execute([':id' => $id]);
$module = $stmt->fetch();

if (!$module) {
    flash('error', 'Module not found.');
    header('Location: ' . url('modules/modules/index.php'));
    exit;
}

$pageTitle = 'Edit Module: ' . $module['title'];
$pageSubtitle = 'Update dashboard module';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $routeKey = trim($_POST['route_key'] ?? '');
        $icon = trim($_POST['icon'] ?? 'menu_book_rounded');
        $badgeText = trim($_POST['badge_text'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($title) || empty($slug) || empty($routeKey)) {
            flash('error', 'Title, slug, and route key are required.');
        } else {
            try {
                $update = $db->prepare("
                    UPDATE dashboard_modules 
                    SET title = :title, slug = :slug, route_key = :route, icon = :icon, badge_text = :badge, display_order = :order, status = :status
                    WHERE id = :id
                ");
                $update->execute([
                    ':title' => $title,
                    ':slug' => $slug,
                    ':route' => $routeKey,
                    ':icon' => $icon,
                    ':badge' => $badgeText,
                    ':order' => $displayOrder,
                    ':status' => $status,
                    ':id' => $id
                ]);
                flash('success', "Module '{$title}' updated successfully.");
                header('Location: ' . url('modules/modules/index.php'));
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
    <div class="col-lg-7">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Module: <?= htmlspecialchars($module['title']) ?></h5>
                <a href="<?= url('modules/modules/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="title">Module Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($module['title']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($module['slug']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="route_key">App Route Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="route_key" name="route_key" value="<?= htmlspecialchars($module['route_key']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="badge_text">Badge / Tag</label>
                        <input type="text" class="form-control" id="badge_text" name="badge_text" value="<?= htmlspecialchars($module['badge_text'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Icon Name</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($module['icon'] ?? 'menu_book_rounded') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)$module['display_order'] ?>" min="1">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $module['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $module['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/modules/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
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
