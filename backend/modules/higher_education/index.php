<?php
/**
 * Higher Education Materials Management
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Higher Education Materials';
$pageSubtitle = 'Upload and manage PDFs and notes for Higher Education (Engineering, Medical, Commerce, Science, Arts, BCA, BBA, Diplomas & more)';
$db = Database::getConnection();

// Ready

// Filters
$searchFilter = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$whereClauses = [];
$params = [];

if (!empty($statusFilter)) {
    $whereClauses[] = "status = :status";
    $params[':status'] = $statusFilter;
}
if (!empty($searchFilter)) {
    $whereClauses[] = "(title LIKE :search1 OR description LIKE :search2 OR course_name LIKE :search3)";
    $params[':search1'] = '%' . $searchFilter . '%';
    $params[':search2'] = '%' . $searchFilter . '%';
    $params[':search3'] = '%' . $searchFilter . '%';
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$query = "
    SELECT *
    FROM higher_education_materials
    {$whereSql}
    ORDER BY display_order ASC, id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Header Action Buttons -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-mortarboard-fill text-success me-2"></i>Higher Education Materials (<?= count($materials) ?>)
        </h5>
        <small class="text-muted">Upload degree, diploma, college notes, textbooks & PDF materials to display in the app</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('modules/higher_education/add_material.php') ?>" class="btn btn-bb-primary btn-sm">
            <i class="bi bi-cloud-arrow-up-fill me-1"></i>Upload Higher Education PDF
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="bb-card p-3 mb-3">
    <form method="GET" action="" class="row g-2 align-items-end">
        <div class="col-md-7">
            <label class="form-label small fw-bold text-secondary mb-1">Search Materials</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by title, course name, or description..." value="<?= htmlspecialchars($searchFilter) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Status</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- All Statuses --</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <div class="flex-grow-1">
                <label class="form-label d-block small mb-1">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-bb-primary w-100">
                    <i class="bi bi-search me-1"></i>Search
                </button>
            </div>
            <?php if (!empty($statusFilter) || !empty($searchFilter)): ?>
                <div>
                    <label class="form-label d-block small mb-1">&nbsp;</label>
                    <a href="<?= url('modules/higher_education/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="bb-card">
    <div class="table-responsive">
        <table class="table bb-table data-table align-middle">
            <thead>
                <tr>
                    <th width="50">Order</th>
                    <th>Material / PDF Title</th>
                    <th>Course / Degree</th>
                    <th>PDF File</th>
                    <th>Pages & Size</th>
                    <th>Status</th>
                    <th width="90" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materials)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-pdf fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <h6 class="fw-bold mb-1">No Higher Education Materials Found</h6>
                            <p class="small mb-3">Upload your first Higher Education PDF to display in the student app.</p>
                            <a href="<?= url('modules/higher_education/add_material.php') ?>" class="btn btn-sm btn-bb-primary">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i>Upload Higher Education PDF
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($materials as $item): 
                        $pdfUrl = !empty($item['pdf_file_path']) ? resolveMediaUrl($item['pdf_file_path'], 'pdfs') : $item['pdf_external_url'];
                    ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$item['display_order'] ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary-subtle text-primary p-2 rounded">
                                        <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                                    </div>
                                    <div>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($item['title']) ?></strong>
                                        <?php if (!empty($item['description'])): ?>
                                            <small class="text-muted text-truncate d-block" style="max-width: 280px;">
                                                <?= htmlspecialchars($item['description']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($item['course_name'])): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1">
                                        <?= htmlspecialchars($item['course_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">General</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($pdfUrl)): ?>
                                    <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>View PDF
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning">No File</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="d-block text-secondary">
                                    <i class="bi bi-file-text me-1"></i><?= (int)$item['page_count'] ?> Pages
                                </small>
                                <small class="text-muted">
                                    <?= number_format((float)$item['file_size_mb'], 2) ?> MB
                                </small>
                            </td>
                            <td>
                                <?= statusBadge($item['status']) ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('modules/higher_education/edit_material.php?id=' . $item['id']) ?>" class="btn btn-outline-secondary btn-xs" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="<?= url('modules/higher_education/delete_material.php?id=' . $item['id']) ?>" 
                                       class="btn btn-outline-danger btn-xs" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this higher education material?');">
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
