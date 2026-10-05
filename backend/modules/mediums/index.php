<?php
/**
 * Mediums of Study Management
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Mediums of Study';
$pageSubtitle = 'Manage instruction mediums like Gujarati Medium, English Medium, Hindi Medium';
$db = Database::getConnection();

// Fetch all mediums
$stmt = $db->query("
    SELECT m.*, 
    (SELECT COUNT(*) FROM standards st WHERE st.medium_id = m.id) as standard_count,
    (SELECT COUNT(*) FROM subjects s WHERE s.medium_id = m.id) as subject_count
    FROM mediums m
    ORDER BY m.display_order ASC, m.id ASC
");
$mediums = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0 text-dark">All Mediums of Study (<?= count($mediums) ?>)</h5>
    <a href="<?= url('modules/mediums/add.php') ?>" class="btn btn-bb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add New Medium
    </a>
</div>

<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="60">Order</th>
                    <th>Medium Code</th>
                    <th>Medium Name</th>
                    <th>Standards Linked</th>
                    <th>Subjects Linked</th>
                    <th>Status</th>
                    <th width="140" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mediums as $med): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= $med['display_order'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info border border-info-subtle fw-semibold fs-6 px-3 py-1">
                                <?= htmlspecialchars($med['code']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($med['name']) ?></div>
                            <?php if (!empty($med['description'])): ?>
                                <small class="text-muted"><?= htmlspecialchars($med['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <?= $med['standard_count'] ?> Standards
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <?= $med['subject_count'] ?> Subjects
                            </span>
                        </td>
                        <td>
                            <?php if ($med['status'] === 'active'): ?>
                                <span class="badge-bb badge-bb-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-bb badge-bb-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/mediums/edit.php?id=' . $med['id']) ?>" class="btn btn-sm btn-light border p-1 px-2" title="Edit">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </a>
                            <a href="<?= url('modules/mediums/delete.php?id=' . $med['id'] . '&csrf=' . getCSRFToken()) ?>" class="btn btn-sm btn-light border p-1 px-2 text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete medium <?= htmlspecialchars($med['name']) ?>?');">
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
