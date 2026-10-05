<?php
/**
 * Streams Management (Science, Commerce, Arts)
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Streams Management';
$pageSubtitle = 'Academic streams linked with Board, Medium and Higher Secondary Standards';
$db = Database::getConnection();

$stmt = $db->query("
    SELECT st.*, 
           b.code as board_code, b.name as board_name,
           m.name as medium_name, m.code as medium_code,
           std.name as standard_name,
           (SELECT COUNT(*) FROM subjects s WHERE s.stream_id = st.id) as subject_count
    FROM streams st
    LEFT JOIN boards b ON st.board_id = b.id
    LEFT JOIN mediums m ON st.medium_id = m.id
    LEFT JOIN standards std ON st.standard_id = std.id
    ORDER BY st.display_order ASC
");
$streams = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0 text-dark">Higher Secondary Streams (<?= count($streams) ?>)</h5>
    <a href="<?= url('modules/streams/add.php') ?>" class="btn btn-bb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Stream
    </a>
</div>

<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="50">Order</th>
                    <th>Stream Name</th>
                    <th>Slug Code</th>
                    <th>Board</th>
                    <th>Medium</th>
                    <th>Standard</th>
                    <th>Linked Subjects</th>
                    <th>Status</th>
                    <th width="120" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($streams as $str): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= $str['display_order'] ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($str['name']) ?></div>
                            <?php if (!empty($str['description'])): ?>
                                <small class="text-secondary"><?= htmlspecialchars(substr($str['description'], 0, 50)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($str['slug']) ?></code>
                        </td>
                        <td>
                            <?php if (!empty($str['board_code'])): ?>
                                <span class="badge bg-primary text-white"><?= htmlspecialchars($str['board_code']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">All Boards</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($str['medium_name'])): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($str['medium_name']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">All Mediums</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($str['standard_name'])): ?>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><?= htmlspecialchars($str['standard_name']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">11th & 12th</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <?= $str['subject_count'] ?> Subjects
                            </span>
                        </td>
                        <td>
                            <?php if ($str['status'] === 'active'): ?>
                                <span class="badge-bb badge-bb-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-bb badge-bb-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/streams/edit.php?id=' . $str['id']) ?>" class="btn btn-sm btn-light border p-1 px-2" title="Edit">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </a>
                            <a href="<?= url('modules/streams/delete.php?id=' . $str['id'] . '&csrf=' . getCSRFToken()) ?>" class="btn btn-sm btn-light border p-1 px-2 text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete stream <?= htmlspecialchars($str['name']) ?>?');">
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
