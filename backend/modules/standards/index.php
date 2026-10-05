<?php
/**
 * Standards / Classes Management
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Standards & Classes';
$pageSubtitle = 'Manage academic grades linked to specific Educational Boards and Mediums';
$db = Database::getConnection();

// Filter values
$boardFilter = isset($_GET['board_id']) && $_GET['board_id'] !== '' ? (int)$_GET['board_id'] : null;
$mediumFilter = isset($_GET['medium_id']) && $_GET['medium_id'] !== '' ? (int)$_GET['medium_id'] : null;

$whereClauses = [];
$params = [];

if ($boardFilter) {
    $whereClauses[] = "st.board_id = :board_id";
    $params[':board_id'] = $boardFilter;
}
if ($mediumFilter) {
    $whereClauses[] = "st.medium_id = :medium_id";
    $params[':medium_id'] = $mediumFilter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Fetch all standards
$query = "
    SELECT st.*, 
           b.code as board_code, b.name as board_name,
           m.name as medium_name, m.code as medium_code,
           (SELECT COUNT(*) FROM subjects s WHERE s.standard_id = st.id) as subject_count,
           (SELECT COUNT(*) FROM streams str WHERE str.standard_id = st.id OR (str.standard_id IS NULL AND st.standard_number IN (11, 12))) as stream_count
    FROM standards st
    LEFT JOIN boards b ON st.board_id = b.id
    LEFT JOIN mediums m ON st.medium_id = m.id
    {$whereSql}
    ORDER BY b.display_order ASC, m.display_order ASC, st.display_order ASC, st.standard_number ASC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$standards = $stmt->fetchAll();

$boards = $db->query("SELECT id, code, name FROM boards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$mediums = $db->query("SELECT id, code, name FROM mediums WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Filter Bar -->
<div class="bb-card mb-4 p-3">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Filter by Board</label>
            <select class="form-select form-select-sm" name="board_id" onchange="this.form.submit()">
                <option value="">All Educational Boards</option>
                <?php foreach ($boards as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $boardFilter == $b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['code']) ?> - <?= htmlspecialchars($b['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
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

        <div class="col-md-4 d-flex align-items-end gap-2 pt-3">
            <button type="submit" class="btn btn-sm btn-bb-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="<?= url('modules/standards/index.php') ?>" class="btn btn-sm btn-light border">Reset</a>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0 text-dark">Academic Standards (<?= count($standards) ?>)</h5>
    <a href="<?= url('modules/standards/add.php') ?>" class="btn btn-bb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Standard
    </a>
</div>

<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="50">Order</th>
                    <th>Standard #</th>
                    <th>Standard Name</th>
                    <th>Board</th>
                    <th>Medium</th>
                    <th>Stream Required?</th>
                    <th>Subjects Linked</th>
                    <th>Status</th>
                    <th width="140" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($standards as $std): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= $std['display_order'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-primary text-white fs-6 px-3 py-2 rounded-3">
                                <?= $std['standard_number'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($std['name']) ?></div>
                        </td>
                        <td>
                            <?php if (!empty($std['board_code'])): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                    <?= htmlspecialchars($std['board_code']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">All Boards</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($std['medium_name'])): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle fw-semibold">
                                    <?= htmlspecialchars($std['medium_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">All Mediums</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($std['requires_stream'] || $std['standard_number'] >= 11 || $std['stream_count'] > 0): ?>
                                <a href="<?= url('modules/streams/index.php?board_id=' . ($std['board_id'] ?? '') . '&medium_id=' . ($std['medium_id'] ?? '')) ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none px-2 py-1">
                                    <i class="bi bi-diagram-3-fill me-1"></i> <?= $std['stream_count'] > 0 ? $std['stream_count'] . ' Stream' . ($std['stream_count'] > 1 ? 's' : '') : 'Requires Stream' ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">No Stream</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <?= $std['subject_count'] ?> Subjects
                            </span>
                        </td>
                        <td>
                            <?php if ($std['status'] === 'active'): ?>
                                <span class="badge-bb badge-bb-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-bb badge-bb-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/standards/edit.php?id=' . $std['id']) ?>" class="btn btn-sm btn-light border p-1 px-2" title="Edit">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </a>
                            <a href="<?= url('modules/standards/delete.php?id=' . $std['id'] . '&csrf=' . getCSRFToken()) ?>" class="btn btn-sm btn-light border p-1 px-2 text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this standard?');">
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
