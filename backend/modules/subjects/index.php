<?php
/**
 * Subjects Management
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Subjects Library';
$pageSubtitle = 'Manage academic subjects linked to specific Boards, Mediums, Standards, and Streams';
$db = Database::getConnection();

// Filters
$boardFilter = isset($_GET['board_id']) && $_GET['board_id'] !== '' ? (int)$_GET['board_id'] : null;
$mediumFilter = isset($_GET['medium_id']) && $_GET['medium_id'] !== '' ? (int)$_GET['medium_id'] : null;
$standardFilter = isset($_GET['standard_id']) && $_GET['standard_id'] !== '' ? (int)$_GET['standard_id'] : null;
$streamFilter = isset($_GET['stream_id']) && $_GET['stream_id'] !== '' ? (int)$_GET['stream_id'] : null;

// Build query
$whereClauses = [];
$params = [];

if ($boardFilter) {
    $whereClauses[] = "s.board_id = :board_id";
    $params[':board_id'] = $boardFilter;
}
if ($mediumFilter) {
    $whereClauses[] = "s.medium_id = :medium_id";
    $params[':medium_id'] = $mediumFilter;
}
if ($standardFilter) {
    $whereClauses[] = "s.standard_id = :standard_id";
    $params[':standard_id'] = $standardFilter;
}
if ($streamFilter) {
    $whereClauses[] = "s.stream_id = :stream_id";
    $params[':stream_id'] = $streamFilter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$query = "
    SELECT s.*, 
           b.code as board_code, 
           m.name as medium_name,
           st.name as standard_name, st.standard_number,
           str.name as stream_name,
           (SELECT COUNT(*) FROM chapters_content c WHERE c.subject_id = s.id) as chapter_count
    FROM subjects s
    JOIN boards b ON s.board_id = b.id
    LEFT JOIN mediums m ON s.medium_id = m.id
    JOIN standards st ON s.standard_id = st.id
    LEFT JOIN streams str ON s.stream_id = str.id
    {$whereSql}
    ORDER BY b.display_order ASC, st.display_order ASC, s.display_order ASC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$subjects = $stmt->fetchAll();

// Fetch filter options
$boards = $db->query("SELECT id, code, name FROM boards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$mediums = $db->query("SELECT id, code, name FROM mediums WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$standards = $db->query("SELECT id, standard_number, name FROM standards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$streams = $db->query("SELECT id, name FROM streams WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Filter Bar -->
<div class="bb-card mb-4 p-3">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Filter by Board</label>
            <select class="form-select form-select-sm" name="board_id" onchange="this.form.submit()">
                <option value="">All Boards</option>
                <?php foreach ($boards as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $boardFilter == $b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['code']) ?> - <?= htmlspecialchars($b['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Filter by Medium</label>
            <select class="form-select form-select-sm" name="medium_id" onchange="this.form.submit()">
                <option value="">All Mediums</option>
                <?php foreach ($mediums as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $mediumFilter == $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Filter by Standard</label>
            <select class="form-select form-select-sm" name="standard_id" onchange="this.form.submit()">
                <option value="">All Standards</option>
                <?php foreach ($standards as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $standardFilter == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Filter by Stream</label>
            <select class="form-select form-select-sm" name="stream_id" onchange="this.form.submit()">
                <option value="">All Streams</option>
                <?php foreach ($streams as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $streamFilter == $st['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2 pt-3">
            <button type="submit" class="btn btn-sm btn-bb-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="<?= url('modules/subjects/index.php') ?>" class="btn btn-sm btn-light border">Reset</a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0 text-dark">Subject Records (<?= count($subjects) ?>)</h5>
    <a href="<?= url('modules/subjects/add.php') ?>" class="btn btn-bb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add New Subject
    </a>
</div>

<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="50">Order</th>
                    <th>Subject Name</th>
                    <th>Code</th>
                    <th>Board & Standard</th>
                    <th>Medium</th>
                    <th>Stream</th>
                    <th>Chapters & PDFs</th>
                    <th>Status</th>
                    <th width="140" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $sub): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= $sub['display_order'] ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge p-2 rounded-3 text-white" style="background-color: <?= htmlspecialchars($sub['color_hex'] ?? '#0061A4') ?>;">
                                    <i class="bi bi-journal-text"></i>
                                </span>
                                <div>
                                    <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($sub['name']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($sub['code']) ?></code>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                <?= htmlspecialchars($sub['board_code']) ?> &bull; <?= htmlspecialchars($sub['standard_name']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($sub['medium_name'])): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($sub['medium_name']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">All Mediums</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($sub['stream_name'])): ?>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                    <?= htmlspecialchars($sub['stream_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">General / All</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= url('modules/chapters/index.php?subject_id=' . $sub['id']) ?>" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none">
                                <i class="bi bi-file-earmark-pdf me-1"></i><?= $sub['chapter_count'] ?> Chapters
                            </a>
                        </td>
                        <td>
                            <?php if ($sub['status'] === 'active'): ?>
                                <span class="badge-bb badge-bb-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-bb badge-bb-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/chapters/add.php?subject_id=' . $sub['id']) ?>" class="btn btn-sm btn-light border p-1 px-2 text-success" title="Add Chapter to Subject">
                                <i class="bi bi-plus-circle-fill"></i>
                            </a>
                            <a href="<?= url('modules/subjects/edit.php?id=' . $sub['id']) ?>" class="btn btn-sm btn-light border p-1 px-2" title="Edit Subject">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </a>
                            <a href="<?= url('modules/subjects/delete.php?id=' . $sub['id'] . '&csrf=' . getCSRFToken()) ?>" class="btn btn-sm btn-light border p-1 px-2 text-danger" title="Delete" onclick="return confirm('Delete this subject and all its chapters?');">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
