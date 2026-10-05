<?php
/**
 * Edit Education Board
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$db = Database::getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM boards WHERE id = :id");
$stmt->execute([':id' => $id]);
$board = $stmt->fetch();

if (!$board) {
    flash('error', 'Board not found.');
    header('Location: ' . url('modules/boards/index.php'));
    exit;
}

$pageTitle = 'Edit Board: ' . $board['code'];
$pageSubtitle = 'Update board details and settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'school');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($code) || empty($name)) {
            flash('error', 'Board code and name are required.');
        } else {
            try {
                $update = $db->prepare("
                    UPDATE boards 
                    SET code = :code, name = :name, description = :desc, icon = :icon, display_order = :order, status = :status
                    WHERE id = :id
                ");
                $update->execute([
                    ':code' => $code,
                    ':name' => $name,
                    ':desc' => $description,
                    ':icon' => $icon,
                    ':order' => $displayOrder,
                    ':status' => $status,
                    ':id' => $id
                ]);
                flash('success', "Board '{$code}' updated successfully.");
                header('Location: ' . url('modules/boards/index.php'));
                exit;
            } catch (PDOException $e) {
                flash('error', 'Update failed: ' . $e->getMessage());
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
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Board: <?= htmlspecialchars($board['code']) ?></h5>
                <a href="<?= url('modules/boards/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back to Boards
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="code">Board Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" value="<?= htmlspecialchars($board['code']) ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label" for="name">Board Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($board['name']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($board['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Icon Name</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($board['icon'] ?? 'school') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)$board['display_order'] ?>" min="1">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $board['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $board['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/boards/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
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
