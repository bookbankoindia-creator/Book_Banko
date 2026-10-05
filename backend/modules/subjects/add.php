<?php
/**
 * Add Subject
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Add Subject';
$pageSubtitle = 'Create a new subject and bind to Board, Medium, Standard, and Stream';
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $boardId = (int)($_POST['board_id'] ?? 0);
        $mediumId = !empty($_POST['medium_id']) ? (int)$_POST['medium_id'] : null;
        $standardId = (int)($_POST['standard_id'] ?? 0);
        $streamId = !empty($_POST['stream_id']) ? (int)$_POST['stream_id'] : null;
        $name = trim($_POST['name'] ?? '');
        $code = strtolower(trim($_POST['code'] ?? ''));
        $icon = trim($_POST['icon'] ?? 'menu_book_rounded');
        $colorHex = trim($_POST['color_hex'] ?? '#0061A4');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($boardId) || empty($standardId) || empty($name) || empty($code)) {
            flash('error', 'Board, standard, subject name, and code are required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO subjects (board_id, medium_id, standard_id, stream_id, name, code, icon, color_hex, display_order, status)
                    VALUES (:board, :medium, :std, :stream, :name, :code, :icon, :color, :order, :status)
                ");
                $stmt->execute([
                    ':board' => $boardId,
                    ':medium' => $mediumId,
                    ':std' => $standardId,
                    ':stream' => $streamId,
                    ':name' => $name,
                    ':code' => $code,
                    ':icon' => $icon,
                    ':color' => $colorHex,
                    ':order' => $displayOrder,
                    ':status' => $status
                ]);
                flash('success', "Subject '{$name}' created successfully.");
                header('Location: ' . url('modules/subjects/index.php'));
                exit;
            } catch (PDOException $e) {
                flash('error', 'Database error: ' . $e->getMessage());
            }
        }
    }
}

$boards = $db->query("SELECT * FROM boards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$mediums = $db->query("SELECT * FROM mediums WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$standards = $db->query("SELECT * FROM standards WHERE status = 'active' ORDER BY display_order ASC, standard_number ASC")->fetchAll();
$streams = $db->query("SELECT * FROM streams WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-journal-plus text-primary me-2"></i>New Subject Details</h5>
                <a href="<?= url('modules/subjects/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="board_id">Select Board <span class="text-danger">*</span></label>
                        <select class="form-select" id="board_id" name="board_id" required>
                            <option value="">-- Choose Board --</option>
                            <?php foreach ($boards as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= (isset($_POST['board_id']) && $_POST['board_id'] == $b['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['code']) ?> - <?= htmlspecialchars($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="medium_id">Select Medium</label>
                        <select class="form-select" id="medium_id" name="medium_id">
                            <option value="">-- All / General Medium --</option>
                            <?php foreach ($mediums as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= (isset($_POST['medium_id']) && $_POST['medium_id'] == $m['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="standard_id">Select Standard / Class <span class="text-danger">*</span></label>
                        <select class="form-select" id="standard_id" name="standard_id" required>
                            <option value="">-- Choose Standard --</option>
                            <?php foreach ($standards as $st): ?>
                                <option value="<?= $st['id'] ?>" data-stream="<?= $st['requires_stream'] ?>" <?= (isset($_POST['standard_id']) && $_POST['standard_id'] == $st['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['name']) ?> <?= $st['requires_stream'] ? '(Stream based)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="stream_id">Select Stream (For Std 11 & 12)</label>
                        <select class="form-select" id="stream_id" name="stream_id">
                            <option value="">-- No Stream (General / All) --</option>
                            <?php foreach ($streams as $str): ?>
                                <option value="<?= $str['id'] ?>" <?= (isset($_POST['stream_id']) && $_POST['stream_id'] == $str['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($str['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="name">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Mathematics, Science" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="code">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" placeholder="e.g. math, sci, guj" value="<?= htmlspecialchars($_POST['code'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Flutter Icon Name</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($_POST['icon'] ?? 'menu_book_rounded') ?>" placeholder="e.g. calculate_rounded, science_rounded">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="color_hex">Theme Accent Color</label>
                        <input type="color" class="form-control form-control-color w-100" id="color_hex" name="color_hex" value="<?= htmlspecialchars($_POST['color_hex'] ?? '#0061A4') ?>">
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
                        <a href="<?= url('modules/subjects/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-check-lg me-1"></i>Create Subject
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
