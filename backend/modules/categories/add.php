<?php
/**
 * Add Learning Category
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Add Category';
$pageSubtitle = 'Create new home screen learning category';
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $icon = trim($_POST['icon'] ?? 'bookmark_rounded');
        $color = trim($_POST['color_hex'] ?? '#0061A4');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($slug)) {
            flash('error', 'Category name and slug are required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO learning_categories (slug, name, icon, color_hex, display_order, status)
                    VALUES (:slug, :name, :icon, :color, :order, :status)
                ");
                $stmt->execute([
                    ':slug' => $slug,
                    ':name' => $name,
                    ':icon' => $icon,
                    ':color' => $color,
                    ':order' => $displayOrder,
                    ':status' => $status
                ]);
                flash('success', "Category '{$name}' created successfully.");
                header('Location: ' . url('modules/categories/index.php'));
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
                <h5><i class="bi bi-tag-fill text-primary me-2"></i>New Category Details</h5>
                <a href="<?= url('modules/categories/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Study Material, Extra Material" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" placeholder="e.g. study_material, extra_material" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Icon Name</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($_POST['icon'] ?? 'bookmark_rounded') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="color_hex">Color Hex</label>
                        <input type="color" class="form-control form-control-color w-100" id="color_hex" name="color_hex" value="<?= htmlspecialchars($_POST['color_hex'] ?? '#0061A4') ?>">
                    </div>

                    <div class="col-md-4">
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
                        <a href="<?= url('modules/categories/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-check-lg me-1"></i>Create Category
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
