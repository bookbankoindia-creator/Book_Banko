<?php
/**
 * Edit Category
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$db = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM learning_categories WHERE id = :id");
$stmt->execute([':id' => $id]);
$cat = $stmt->fetch();

if (!$cat) {
    flash('error', 'Category not found.');
    header('Location: ' . url('modules/categories/index.php'));
    exit;
}

$pageTitle = 'Edit Category: ' . $cat['name'];
$pageSubtitle = 'Update learning category';

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
                $update = $db->prepare("
                    UPDATE learning_categories 
                    SET slug = :slug, name = :name, icon = :icon, color_hex = :color, display_order = :order, status = :status
                    WHERE id = :id
                ");
                $update->execute([
                    ':slug' => $slug,
                    ':name' => $name,
                    ':icon' => $icon,
                    ':color' => $color,
                    ':order' => $displayOrder,
                    ':status' => $status,
                    ':id' => $id
                ]);
                flash('success', "Category '{$name}' updated successfully.");
                header('Location: ' . url('modules/categories/index.php'));
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
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Category: <?= htmlspecialchars($cat['name']) ?></h5>
                <a href="<?= url('modules/categories/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($cat['slug']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Icon Name</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($cat['icon'] ?? 'bookmark_rounded') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="color_hex">Color Hex</label>
                        <input type="color" class="form-control form-control-color w-100" id="color_hex" name="color_hex" value="<?= htmlspecialchars($cat['color_hex'] ?? '#0061A4') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)$cat['display_order'] ?>" min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $cat['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $cat['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/categories/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
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
