<?php
/**
 * Dashboard Modules Management (Textbooks, PYQs, Blueprint, M.IMP)
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Dashboard Modules';
$pageSubtitle = 'Manage Standard Dashboard Options (Textbooks, Old PYQs, Paper Sets, Blueprint, M.IMP)';
$db = Database::getConnection();

$stmt = $db->query("
    SELECT m.*, 
    (SELECT COUNT(*) FROM chapters_content c WHERE c.module_id = m.id) as content_count
    FROM dashboard_modules m
    ORDER BY m.display_order ASC
");
$modules = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0 text-dark">Dashboard Modules (<?= count($modules) ?>)</h5>
    <a href="<?= url('modules/modules/add.php') ?>" class="btn btn-bb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Module
    </a>
</div>

<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="60">Order</th>
                    <th>Module Title</th>
                    <th>Slug Code</th>
                    <th>App Route</th>
                    <th>Badge / Tag</th>
                    <th>Content Count</th>
                    <th>Status</th>
                    <th width="140" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modules as $mod): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= $mod['display_order'] ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($mod['title']) ?></div>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($mod['slug']) ?></code>
                        </td>
                        <td>
                            <span class="text-secondary small"><code><?= htmlspecialchars($mod['route_key']) ?></code></span>
                        </td>
                        <td>
                            <?php if (!empty($mod['badge_text'])): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <?= htmlspecialchars($mod['badge_text']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <?= $mod['content_count'] ?> PDF Items
                            </span>
                        </td>
                        <td>
                            <?php if ($mod['status'] === 'active'): ?>
                                <span class="badge-bb badge-bb-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-bb badge-bb-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/modules/edit.php?id=' . $mod['id']) ?>" class="btn btn-sm btn-light border p-1 px-2" title="Edit">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </a>
                            <a href="<?= url('modules/modules/delete.php?id=' . $mod['id'] . '&csrf=' . getCSRFToken()) ?>" class="btn btn-sm btn-light border p-1 px-2 text-danger" title="Delete" onclick="return confirm('Delete this module?');">
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
