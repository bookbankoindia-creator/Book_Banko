<?php
/**
 * Add Dashboard Module
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Add Dashboard Module';
$pageSubtitle = 'Create a new standard dashboard section (e.g. Textbooks, PYQs, Blueprints)';
$db = Database::getConnection();

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
            flash('error', 'Module title, slug, and route key are required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO dashboard_modules (title, slug, route_key, icon, badge_text, display_order, status)
                    VALUES (:title, :slug, :route, :icon, :badge, :order, :status)
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':slug' => $slug,
                    ':route' => $routeKey,
                    ':icon' => $icon,
                    ':badge' => $badgeText,
                    ':order' => $displayOrder,
                    ':status' => $status
                ]);
                flash('success', "Module '{$title}' created successfully.");
                header('Location: ' . url('modules/modules/index.php'));
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
    <div class="col-lg-7">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-collection-fill text-primary me-2"></i>New Module Details</h5>
                <a href="<?= url('modules/modules/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="title">Module Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Textbooks, Old PYQs" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" placeholder="e.g. textbooks, pyqs" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="route_key">App Route Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="route_key" name="route_key" placeholder="e.g. /textbooks, /pyqs" value="<?= htmlspecialchars($_POST['route_key'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="badge_text">Badge / Tag (Optional)</label>
                        <input type="text" class="form-control" id="badge_text" name="badge_text" placeholder="e.g. Most Important, Past Papers" value="<?= htmlspecialchars($_POST['badge_text'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Icon Name</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($_POST['icon'] ?? 'menu_book_rounded') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)($_POST['display_order'] ?? 1) ?>" min="1">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/modules/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-check-lg me-1"></i>Create Module
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
