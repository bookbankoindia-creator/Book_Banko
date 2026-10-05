<?php
/**
 * Add Standard
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Add Standard';
$pageSubtitle = 'Create a new class standard bound to Board and Medium';
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $boardId = !empty($_POST['board_id']) ? (int)$_POST['board_id'] : null;
        $mediumId = !empty($_POST['medium_id']) ? (int)$_POST['medium_id'] : null;
        $stdNum = (int)($_POST['standard_number'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $requiresStream = isset($_POST['requires_stream']) ? 1 : 0;
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if ($stdNum <= 0 || empty($name)) {
            flash('error', 'Valid standard number and name are required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO standards (board_id, medium_id, standard_number, name, requires_stream, display_order, status)
                    VALUES (:board_id, :medium_id, :num, :name, :stream, :order, :status)
                ");
                $stmt->execute([
                    ':board_id' => $boardId,
                    ':medium_id' => $mediumId,
                    ':num' => $stdNum,
                    ':name' => $name,
                    ':stream' => $requiresStream,
                    ':order' => $displayOrder,
                    ':status' => $status
                ]);
                flash('success', "Standard '{$name}' created successfully.");
                header('Location: ' . url('modules/standards/index.php'));
                exit;
            } catch (PDOException $e) {
                flash('error', 'Database error: ' . $e->getMessage());
            }
        }
    }
}

$boards = $db->query("SELECT * FROM boards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$mediums = $db->query("SELECT * FROM mediums WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-mortarboard-fill text-primary me-2"></i>New Standard Details</h5>
                <a href="<?= url('modules/standards/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="board_id">Educational Board</label>
                        <select class="form-select" id="board_id" name="board_id">
                            <option value="">-- All / General Boards --</option>
                            <?php foreach ($boards as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= (isset($_POST['board_id']) && $_POST['board_id'] == $b['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['code']) ?> - <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">e.g. CBSE, GSEB, NCERT, ICSE</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="medium_id">Medium of Study</label>
                        <select class="form-select" id="medium_id" name="medium_id">
                            <option value="">-- All / Any Medium --</option>
                            <?php foreach ($mediums as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= (isset($_POST['medium_id']) && $_POST['medium_id'] == $m['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">e.g. English Medium, Gujarati Medium, Hindi Medium</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="standard_number">Standard Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="standard_number" name="standard_number" placeholder="e.g. 6" min="1" max="20" value="<?= (int)($_POST['standard_number'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label" for="name">Display Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Standard 6" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch p-3 bg-light rounded-3 border">
                            <input class="form-check-input" type="checkbox" role="switch" id="requires_stream" name="requires_stream" value="1" <?= !empty($_POST['requires_stream']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold ms-2 text-dark" for="requires_stream">
                                Requires Stream Selection (For Higher Secondary Std 11 & 12)
                            </label>
                            <div class="text-muted small ms-2">When checked, students in the app will be prompted to select Science, Commerce, or Arts.</div>
                        </div>
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
                        <a href="<?= url('modules/standards/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-check-lg me-1"></i>Create Standard
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stdNumInput = document.getElementById('standard_number');
    const streamSwitch = document.getElementById('requires_stream');
    const nameInput = document.getElementById('name');

    if (stdNumInput && streamSwitch) {
        stdNumInput.addEventListener('input', function() {
            const num = parseInt(this.value, 10);
            if (num >= 11) {
                streamSwitch.checked = true;
            }
            if (nameInput && (!nameInput.value || nameInput.value.startsWith('Standard ') || nameInput.value.startsWith('standard-'))) {
                nameInput.value = num ? 'standard-' + num : '';
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
