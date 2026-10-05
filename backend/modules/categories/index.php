<?php
/**
 * Learning Categories Management
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Learning Categories';
$pageSubtitle = 'Home Screen 2x2 Grid Categories (Study Material, Extra Material, Competitive Exams, Higher Education)';
$db = Database::getConnection();

$stmt = $db->query("SELECT * FROM learning_categories ORDER BY display_order ASC");
$categories = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0 text-dark">Home Categories (<?= count($categories) ?>)</h5>
    <a href="<?= url('modules/categories/add.php') ?>" class="btn btn-bb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Category
    </a>
</div>

<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="60">Order</th>
                    <th>Category Title</th>
                    <th>Slug Code</th>
                    <th>Color</th>
                    <th>Status</th>
                    <th width="140" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= $cat['display_order'] ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge p-2 rounded-3 text-white" style="background-color: <?= htmlspecialchars($cat['color_hex']) ?>;">
                                    <i class="bi bi-bookmark-fill"></i>
                                </span>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($cat['name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($cat['slug']) ?></code>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($cat['color_hex']) ?></span>
                        </td>
                        <td>
                            <?php if ($cat['status'] === 'active'): ?>
                                <span class="badge-bb badge-bb-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-bb badge-bb-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/categories/edit.php?id=' . $cat['id']) ?>" class="btn btn-sm btn-light border p-1 px-2" title="Edit">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </a>
                            <a href="<?= url('modules/categories/delete.php?id=' . $cat['id'] . '&csrf=' . getCSRFToken()) ?>" class="btn btn-sm btn-light border p-1 px-2 text-danger" title="Delete" onclick="return confirm('Delete this category?');">
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
