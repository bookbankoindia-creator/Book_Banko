<?php
/**
 * Add Stream
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Add Stream';
$pageSubtitle = 'Create a new higher secondary stream with Board, Medium & Standard binding';
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token.');
    } else {
        $boardId = !empty($_POST['board_id']) ? (int)$_POST['board_id'] : null;
        $mediumId = !empty($_POST['medium_id']) ? (int)$_POST['medium_id'] : null;
        $standardId = !empty($_POST['standard_id']) ? (int)$_POST['standard_id'] : null;
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'science');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($slug)) {
            flash('error', 'Stream name and slug are required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO streams (board_id, medium_id, standard_id, slug, name, description, icon, display_order, status)
                    VALUES (:board_id, :medium_id, :standard_id, :slug, :name, :desc, :icon, :order, :status)
                ");
                $stmt->execute([
                    ':board_id' => $boardId,
                    ':medium_id' => $mediumId,
                    ':standard_id' => $standardId,
                    ':slug' => $slug,
                    ':name' => $name,
                    ':desc' => $description,
                    ':icon' => $icon,
                    ':order' => $displayOrder,
                    ':status' => $status
                ]);
                flash('success', "Stream '{$name}' created successfully.");
                header('Location: ' . url('modules/streams/index.php'));
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

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-diagram-3-fill text-primary me-2"></i>New Stream Details</h5>
                <a href="<?= url('modules/streams/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="board_id">Educational Board</label>
                        <select class="form-select" id="board_id" name="board_id">
                            <option value="">-- All / General Boards --</option>
                            <?php foreach ($boards as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= (isset($_POST['board_id']) && $_POST['board_id'] == $b['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['code']) ?> (<?= htmlspecialchars($b['name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Select board this stream applies to.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="medium_id">Medium of Study</label>
                        <select class="form-select" id="medium_id" name="medium_id">
                            <option value="">-- All / Any Medium --</option>
                            <?php foreach ($mediums as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= (isset($_POST['medium_id']) && $_POST['medium_id'] == $m['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Select medium (e.g. Gujarati, English).</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="standard_id">Standard / Class</label>
                        <select class="form-select" id="standard_id" name="standard_id">
                            <option value="">-- All Higher Secondary (11th & 12th) --</option>
                            <?php foreach ($standards as $st): ?>
                                <option value="<?= $st['id'] ?>" <?= (isset($_POST['standard_id']) && $_POST['standard_id'] == $st['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Target standard for this stream.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="name">Stream Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Science, Commerce, Arts" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="slug" name="slug" placeholder="e.g. science, commerce, arts" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief info on subjects, curriculum and careers"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="icon">Icon Name</label>
                        <input type="text" class="form-control" id="icon" name="icon" value="<?= htmlspecialchars($_POST['icon'] ?? 'science') ?>" placeholder="e.g. science, calculate, school">
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
                        <a href="<?= url('modules/streams/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-check-lg me-1"></i>Create Stream
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
