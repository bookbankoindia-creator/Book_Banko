<?php
/**
 * Study Material & Stationery Products Management (Affiliate Store)
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Study Material & Stationery Products';
$pageSubtitle = 'Manage stationery items, books, pen/pencil products and Amazon/Flipkart affiliate links for students';
$db = Database::getConnection();

// Filters
$searchFilter = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$fallbackFilter = isset($_GET['is_fallback_ad']) && $_GET['is_fallback_ad'] !== '' ? (int)$_GET['is_fallback_ad'] : null;

$whereClauses = [];
$params = [];

if (!empty($searchFilter)) {
    $whereClauses[] = "(title LIKE :search1 OR description LIKE :search2 OR search_tags LIKE :search3)";
    $params[':search1'] = '%' . $searchFilter . '%';
    $params[':search2'] = '%' . $searchFilter . '%';
    $params[':search3'] = '%' . $searchFilter . '%';
}
if (!empty($statusFilter)) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $statusFilter;
}
if ($fallbackFilter !== null) {
    $whereClauses[] = "is_fallback_ad = :fallback";
    $params[':fallback'] = $fallbackFilter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$query = "
    SELECT *
    FROM study_products
    {$whereSql}
    ORDER BY display_order ASC, id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="fw-bold mb-0 text-dark">Study Material & Stationery Store (<?= count($products) ?>)</h5>
        <small class="text-muted">Students can search for pens, pencils, rubbers, sharpeners, notebooks and buy via your Amazon/Flipkart affiliate links</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('modules/study_products/add.php') ?>" class="btn btn-bb-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Product
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="bb-card p-3 mb-3">
    <form method="GET" action="" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small fw-bold text-secondary mb-1">Search Products or Tags (pen, pencil, rubber...)</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="e.g. pen, pencil, rubber, sharpener, geometry box..." value="<?= htmlspecialchars($searchFilter) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Status</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- All Statuses --</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Fallback Ad</label>
            <select name="is_fallback_ad" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- All --</option>
                <option value="1" <?= $fallbackFilter === 1 ? 'selected' : '' ?>>Fallback Ads Only</option>
                <option value="0" <?= $fallbackFilter === 0 ? 'selected' : '' ?>>Regular Products</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-bb-primary flex-grow-1">
                <i class="bi bi-search me-1"></i>Search
            </button>
            <?php if (!empty($searchFilter) || !empty($statusFilter) || $fallbackFilter !== null): ?>
                <a href="<?= url('modules/study_products/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset">
                    <i class="bi bi-x-circle"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Products Table -->
<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="60">Order</th>
                    <th width="70">Image</th>
                    <th>Product Details</th>
                    <th>Search Tags</th>
                    <th>Affiliate Link</th>
                    <th>Fallback Ad</th>
                    <th>Status</th>
                    <th width="120" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                            No study material products found.
                            <div class="mt-2">
                                <a href="<?= url('modules/study_products/add.php') ?>" class="btn btn-sm btn-bb-primary">
                                    <i class="bi bi-plus-lg me-1"></i>Add First Product
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $item): 
                        $imgUrl = !empty($item['image_path']) ? resolveMediaUrl($item['image_path'], 'products') : (!empty($item['image_external_url']) ? $item['image_external_url'] : '');
                    ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-secondary border"><?= $item['display_order'] ?></span>
                            </td>
                            <td>
                                <?php if (!empty($imgUrl)): ?>
                                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Product" class="rounded-3 border object-fit-cover" style="width: 50px; height: 50px;" onerror="this.src='https://placehold.co/100x100?text=Product'">
                                <?php else: ?>
                                    <div class="rounded-3 bg-light text-secondary border d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="bi bi-pencil-fill"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['title']) ?></div>
                                <?php if (!empty($item['description'])): ?>
                                    <small class="text-muted d-block text-truncate" style="max-width: 280px;"><?= htmlspecialchars($item['description']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($item['search_tags'])):
                                    $tags = explode(',', $item['search_tags']);
                                    foreach (array_slice($tags, 0, 3) as $t): 
                                        $t = trim($t);
                                        if (empty($t)) continue;
                                ?>
                                        <span class="badge bg-light text-primary border me-1 mb-1"><?= htmlspecialchars($t) ?></span>
                                    <?php endforeach;
                                    if (count($tags) > 3): ?>
                                        <span class="badge bg-light text-secondary border">+<?= count($tags) - 3 ?></span>
                                    <?php endif;
                                endif; ?>
                            </td>
                            <td>
                                <a href="<?= htmlspecialchars($item['affiliate_link']) ?>" target="_blank" class="btn btn-xs btn-outline-primary text-truncate" style="max-width: 160px;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Open Link
                                </a>
                            </td>
                            <td>
                                <?php if ($item['is_fallback_ad']): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="bi bi-check-circle me-1"></i>Fallback Ad
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('modules/study_products/edit.php?id=' . $item['id']) ?>" class="btn btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="<?= url('modules/study_products/delete.php?id=' . $item['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this product?');" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
