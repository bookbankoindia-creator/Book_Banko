<?php
/**
 * Add New Board
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Add Education Board';
$pageSubtitle = 'Create a new education board (e.g., GSEB, CBSE, NCERT, ICSE)';
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token. Please try again.');
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
                $stmt = $db->prepare("
                    INSERT INTO boards (code, name, description, icon, display_order, status)
                    VALUES (:code, :name, :desc, :icon, :order, :status)
                ");
                $stmt->execute([
                    ':code' => $code,
                    ':name' => $name,
                    ':desc' => $description,
                    ':icon' => $icon,
                    ':order' => $displayOrder,
                    ':status' => $status
                ]);
                flash('success', "Board '{$code}' added successfully.");
                header('Location: ' . url('modules/boards/index.php'));
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
                <h5><i class="bi bi-buildings-fill text-primary me-2"></i>New Board Details</h5>
                <a href="<?= url('modules/boards/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back to Boards
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="code">Board Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" placeholder="e.g. GSEB, CBSE" value="<?= htmlspecialchars($_POST['code'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label" for="name">Board Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Gujarat Secondary and Higher Secondary Board" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Curriculum details or notes"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Icon / Identifier</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($_POST['icon'] ?? 'school') ?>" placeholder="e.g. school, menu_book">
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
                        <a href="<?= url('modules/boards/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-check-lg me-1"></i>Create Board
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
