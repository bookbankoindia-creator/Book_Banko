<?php
/**
 * Promotional & Announcement Banners Management
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'App Banners & Notices';
$pageSubtitle = 'Manage home screen promotional carousels, update announcements, and study alerts';
$db = Database::getConnection();

$stmt = $db->query("SELECT * FROM banners ORDER BY display_order ASC");
$banners = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0 text-dark">Active Banners (<?= count($banners) ?>)</h5>
    <a href="<?= url('modules/banners/add.php') ?>" class="btn btn-bb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add New Banner
    </a>
</div>

<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="60">Order</th>
                    <th>Banner Graphic</th>
                    <th>Banner Title & Subtitle</th>
                    <th>Action Target</th>
                    <th>Status</th>
                    <th width="140" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($banners as $b): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-secondary border"><?= $b['display_order'] ?></span>
                        </td>
                        <td>
                            <?php if (!empty($b['image_path']) && file_exists(BANNER_UPLOADS_PATH . $b['image_path'])): ?>
                                <?php $bannerImgUrl = resolveMediaUrl($b['image_path'], 'banners'); ?>
                                <img src="<?= htmlspecialchars($bannerImgUrl) ?>" alt="Banner" class="rounded-3 border shadow-sm" style="width: 110px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center fw-bold small" style="width: 110px; height: 50px;">
                                    <?= htmlspecialchars(substr($b['title'], 0, 15)) ?>...
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($b['title']) ?></div>
                            <?php if (!empty($b['sub_title'])): ?>
                                <small class="text-muted"><?= htmlspecialchars($b['sub_title']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= htmlspecialchars($b['action_type']) ?>
                                <?php if (!empty($b['action_value'])): ?>: <code><?= htmlspecialchars($b['action_value']) ?></code><?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($b['status'] === 'active'): ?>
                                <span class="badge-bb badge-bb-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-bb badge-bb-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('modules/banners/edit.php?id=' . $b['id']) ?>" class="btn btn-sm btn-light border p-1 px-2" title="Edit">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </a>
                            <a href="<?= url('modules/banners/delete.php?id=' . $b['id'] . '&csrf=' . getCSRFToken()) ?>" class="btn btn-sm btn-light border p-1 px-2 text-danger" title="Delete" onclick="return confirm('Delete this banner?');">
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
