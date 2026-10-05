<?php
/**
 * Extra Materials Management
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Extra Materials & PDFs';
$pageSubtitle = 'Upload and manage extra materials, notes, formula sheets, and reference PDFs for students';
$db = Database::getConnection();

// Filters
$categoryFilter = trim($_GET['category_slug'] ?? '');
$boardFilter = isset($_GET['board_id']) && $_GET['board_id'] !== '' ? (int)$_GET['board_id'] : null;
$mediumFilter = isset($_GET['medium_id']) && $_GET['medium_id'] !== '' ? (int)$_GET['medium_id'] : null;
$standardFilter = isset($_GET['standard_id']) && $_GET['standard_id'] !== '' ? (int)$_GET['standard_id'] : null;
$subjectFilter = isset($_GET['subject_id']) && $_GET['subject_id'] !== '' ? (int)$_GET['subject_id'] : null;
$searchFilter = trim($_GET['search'] ?? '');

$whereClauses = [];
$params = [];

if (!empty($categoryFilter)) {
    $whereClauses[] = "(em.category_slug = :cat_slug OR lc.slug = :cat_slug)";
    $params[':cat_slug'] = $categoryFilter;
}
if ($boardFilter) {
    $whereClauses[] = "em.board_id = :board_id";
    $params[':board_id'] = $boardFilter;
}
if ($mediumFilter) {
    $whereClauses[] = "em.medium_id = :medium_id";
    $params[':medium_id'] = $mediumFilter;
}
if ($standardFilter) {
    $whereClauses[] = "em.standard_id = :standard_id";
    $params[':standard_id'] = $standardFilter;
}
if ($subjectFilter) {
    $whereClauses[] = "em.subject_id = :subject_id";
    $params[':subject_id'] = $subjectFilter;
}
if (!empty($searchFilter)) {
    $whereClauses[] = "(em.title LIKE :search OR em.description LIKE :search)";
    $params[':search'] = '%' . $searchFilter . '%';
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$query = "
    SELECT em.*,
           lc.name as category_name,
           b.code as board_code,
           b.name as board_name,
           m.name as medium_name,
           st.name as standard_name,
           s.name as subject_name
    FROM extra_materials em
    LEFT JOIN learning_categories lc ON em.category_id = lc.id OR em.category_slug = lc.slug
    LEFT JOIN boards b ON em.board_id = b.id
    LEFT JOIN mediums m ON em.medium_id = m.id
    LEFT JOIN standards st ON em.standard_id = st.id
    LEFT JOIN subjects s ON em.subject_id = s.id
    {$whereSql}
    ORDER BY em.display_order ASC, em.id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

// Dropdown filter sources
$categories = $db->query("SELECT * FROM learning_categories ORDER BY display_order ASC")->fetchAll();
$boards = $db->query("SELECT id, code, name FROM boards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$mediums = $db->query("SELECT id, code, name FROM mediums WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$standards = $db->query("SELECT id, standard_number, name FROM standards WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();
$subjects = $db->query("SELECT id, name FROM subjects WHERE status = 'active' ORDER BY name ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="fw-bold mb-0 text-dark">Extra Material Library (<?= count($materials) ?>)</h5>
        <small class="text-muted">Uploaded PDFs will show directly to students when they tap Extra Material in the app</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('modules/extra_materials/add.php') ?>" class="btn btn-bb-primary btn-sm">
            <i class="bi bi-cloud-arrow-up-fill me-1"></i>Upload Extra Material
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="bb-card p-3 mb-3">
    <form method="GET" action="" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Category</label>
            <select name="category_slug" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- All Categories --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $categoryFilter === $cat['slug'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Board</label>
            <select name="board_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- All Boards --</option>
                <?php foreach ($boards as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $boardFilter === (int)$b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['code']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Medium</label>
            <select name="medium_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- All Mediums --</option>
                <?php foreach ($mediums as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $mediumFilter === (int)$m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Standard</label>
            <select name="standard_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- All Standards --</option>
                <?php foreach ($standards as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= $standardFilter === (int)$st['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <div class="flex-grow-1">
                <label class="form-label small fw-bold text-secondary mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search material..." value="<?= htmlspecialchars($searchFilter) ?>">
            </div>
            <div>
                <label class="form-label d-block small mb-1">&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-bb-primary">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <?php if (!empty($categoryFilter) || $boardFilter || $mediumFilter || $standardFilter || !empty($searchFilter)): ?>
                <div>
                    <label class="form-label d-block small mb-1">&nbsp;</label>
                    <a href="<?= url('modules/extra_materials/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Reset">
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
                    <th>Material Info</th>
                    <th>Category</th>
                    <th>Audience / Subject</th>
                    <th>PDF File</th>
                    <th>Pages / Size</th>
                    <th>Status</th>
                    <th width="120" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materials)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-secondary"></i>
                            No extra materials found matching your filters.
                            <div class="mt-2">
                                <a href="<?= url('modules/extra_materials/add.php') ?>" class="btn btn-sm btn-bb-primary">
                                    <i class="bi bi-plus-lg me-1"></i>Upload Material
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($materials as $item): 
                        $hasPdf = !empty($item['pdf_file_path']) || !empty($item['pdf_external_url']);
                        $pdfUrl = !empty($item['pdf_file_path']) ? resolveMediaUrl($item['pdf_file_path'], 'pdfs') : ($item['pdf_external_url'] ?? '');
                    ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-secondary border"><?= $item['display_order'] ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-start gap-2">
                                    <div class="rounded-3 p-2 bg-light text-primary border">
                                        <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['title']) ?></div>
                                        <?php if (!empty($item['description'])): ?>
                                            <small class="text-muted d-block text-truncate" style="max-width: 260px;"><?= htmlspecialchars($item['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <?= htmlspecialchars($item['category_name'] ?? 'Extra Material') ?>
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="badge bg-light text-dark border me-1">
                                        <?= htmlspecialchars($item['board_code'] ?? 'All Boards') ?>
                                    </span>
                                    <?php if (!empty($item['standard_name'])): ?>
                                        <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($item['standard_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['medium_name'])): ?>
                                        <span class="badge bg-light text-secondary border me-1"><?= htmlspecialchars($item['medium_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['subject_name'])): ?>
                                        <div class="text-primary fw-semibold mt-1">
                                            <i class="bi bi-journal-bookmark me-1"></i><?= htmlspecialchars($item['subject_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($hasPdf): ?>
                                    <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" class="btn btn-xs btn-outline-primary" title="Open PDF">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>View PDF
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">No File</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="bi bi-file-text me-1 text-muted"></i><?= $item['page_count'] ?> Pages</div>
                                    <div class="text-muted"><i class="bi bi-hdd me-1"></i><?= $item['file_size_mb'] ?> MB</div>
                                </div>
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
                                    <a href="<?= url('modules/extra_materials/edit.php?id=' . $item['id']) ?>" class="btn btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="<?= url('modules/extra_materials/delete.php?id=' . $item['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this material?');" title="Delete">
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
