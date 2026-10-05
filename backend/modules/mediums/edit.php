<?php
/**
 * Edit Medium of Study
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$db = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM mediums WHERE id = :id");
$stmt->execute([':id' => $id]);
$medium = $stmt->fetch();

if (!$medium) {
    flash('error', 'Medium not found.');
    header('Location: ' . url('modules/mediums/index.php'));
    exit;
}

$pageTitle = 'Edit Medium: ' . $medium['name'];
$pageSubtitle = 'Update medium configurations';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'language');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($code) || empty($name)) {
            flash('error', 'Medium code and name are required.');
        } else {
            try {
                $update = $db->prepare("
                    UPDATE mediums
                    SET code = :code, name = :name, description = :description, 
                        icon = :icon, display_order = :order, status = :status
                    WHERE id = :id
                ");
                $update->execute([
                    ':code' => $code,
                    ':name' => $name,
                    ':description' => $description,
                    ':icon' => $icon,
                    ':order' => $displayOrder,
                    ':status' => $status,
                    ':id' => $id
                ]);
                flash('success', "Medium '{$name}' updated successfully.");
                header('Location: ' . url('modules/mediums/index.php'));
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
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Medium: <?= htmlspecialchars($medium['name']) ?></h5>
                <a href="<?= url('modules/mediums/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="code">Medium Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" value="<?= htmlspecialchars($medium['code']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="name">Medium Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($medium['name']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($medium['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Icon Identifier</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($medium['icon'] ?? 'language') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)$medium['display_order'] ?>" min="1">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $medium['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $medium['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/mediums/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
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
