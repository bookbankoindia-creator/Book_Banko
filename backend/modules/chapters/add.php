<?php
/**
 * Upload & Add New Chapter PDF
 * Book Banko
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Upload Chapter PDF';
$pageSubtitle = 'Add a textbook chapter, study material, question paper or blueprint PDF';
$db = Database::getConnection();

$preselectedSubjectId = (int)($_GET['subject_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if post size exceeded limit (PHP clears $_POST when post_max_size is exceeded)
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
        $sizeMB = round((int)$_SERVER['CONTENT_LENGTH'] / (1024 * 1024), 1);
        flash('error', "The uploaded file ({$sizeMB} MB) exceeds PHP's post_max_size limit. Please increase 'upload_max_filesize' and 'post_max_size' in XAMPP's php.ini file.");
    } else {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrf)) {
            flash('error', 'Invalid security token. Please refresh the page and try again.');
        } else {
            $subjectId = (int)($_POST['subject_id'] ?? 0);
            $moduleId = (int)($_POST['module_id'] ?? 1);
            $chapterNumber = (int)($_POST['chapter_number'] ?? 1);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $pageCount = (int)($_POST['page_count'] ?? 1);
            $externalUrl = trim($_POST['pdf_external_url'] ?? '');
            $pageLinks = trim($_POST['page_links'] ?? '');
            $displayOrder = (int)($_POST['display_order'] ?? $chapterNumber);
            $status = $_POST['status'] ?? 'active';

            $pdfFileName = '';
            $fileSizeMB = 0.00;
            $uploadError = false;

            // Check file upload (direct client-side upload or standard upload)
            if (!empty($_POST['direct_uploaded_file'])) {
                $pdfFileName = trim($_POST['direct_uploaded_file']);
                $fileSizeMB = (float)($_POST['direct_file_size_mb'] ?? 0.0);
            } elseif (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['pdf_file'], PDF_UPLOADS_PATH, ['pdf'], 500);
                if ($uploadResult['status']) {
                    $pdfFileName = $uploadResult['filename'];
                    $fileSizeMB = $uploadResult['size_mb'];
                } else {
                    $uploadError = true;
                    flash('error', $uploadResult['message']);
                }
            }

            if (!$uploadError) {
                if (empty($subjectId) || empty($title)) {
                    flash('error', 'Subject selection and chapter title are required.');
                } else {
                    try {
                        $stmt = $db->prepare("
                            INSERT INTO chapters_content (
                                subject_id, module_id, chapter_number, title, description, 
                                pdf_file_path, pdf_external_url, page_count, file_size_mb, 
                                page_links, display_order, status
                            ) VALUES (
                                :sub, :mod, :chap_num, :title, :desc, 
                                :file, :url, :pages, :size, 
                                :links, :order, :status
                            )
                        ");
                        $stmt->execute([
                            ':sub' => $subjectId,
                            ':mod' => $moduleId,
                            ':chap_num' => $chapterNumber,
                            ':title' => $title,
                            ':desc' => $description,
                            ':file' => $pdfFileName,
                            ':url' => $externalUrl,
                            ':pages' => $pageCount,
                            ':size' => $fileSizeMB,
                            ':links' => $pageLinks,
                            ':order' => $displayOrder,
                            ':status' => $status
                        ]);
                        flash('success', "Chapter '{$title}' added successfully.");
                        header('Location: ' . url('modules/chapters/index.php?subject_id=' . $subjectId));
                        exit;
                    } catch (PDOException $e) {
                        flash('error', 'Database error: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}

$subjects = $db->query("
    SELECT s.id, s.name, b.code as board_code, st.name as standard_name 
    FROM subjects s
    JOIN boards b ON s.board_id = b.id
    JOIN standards st ON s.standard_id = st.id
    WHERE s.status = 'active'
    ORDER BY b.display_order ASC, st.display_order ASC, s.name ASC
")->fetchAll();

$modules = $db->query("SELECT id, title FROM dashboard_modules WHERE status = 'active' ORDER BY display_order ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i>Upload Chapter Material</h5>
                <a href="<?= url('modules/chapters/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="subject_id">Select Subject <span class="text-danger">*</span></label>
                        <select class="form-select" id="subject_id" name="subject_id" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($preselectedSubjectId == $s['id'] || (isset($_POST['subject_id']) && $_POST['subject_id'] == $s['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['board_code']) ?> &bull; <?= htmlspecialchars($s['standard_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label" for="module_id">Dashboard Section / Module <span class="text-danger">*</span></label>
                        <select class="form-select" id="module_id" name="module_id" required>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="chapter_number">Chapter Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="chapter_number" name="chapter_number" min="1" value="<?= (int)($_POST['chapter_number'] ?? 1) ?>" required>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label" for="title">Chapter Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Number Systems, Linear Equations" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Chapter Summary / Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief outline of concepts covered in this chapter"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- PDF Upload Section -->
                    <div class="col-12">
                        <label class="form-label">Upload PDF Document</label>
                        <div class="upload-drop-zone" onclick="document.getElementById('pdf_file').click();">
                            <i class="bi bi-file-earmark-pdf"></i>
                            <h6 class="fw-bold text-dark mb-1">Click to browse or drag & drop PDF file here</h6>
                            <p class="text-muted small mb-0" id="file_status_label">Supported formats: .pdf (Max size: 500MB)</p>
                            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" style="display: none;" onchange="handleFileSelected(this)">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="pdf_external_url">Or External PDF URL (Optional Fallback)</label>
                        <input type="url" class="form-control" id="pdf_external_url" name="pdf_external_url" placeholder="https://example.com/books/ch1.pdf" value="<?= htmlspecialchars($_POST['pdf_external_url'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="page_count">Total Pages</label>
                        <input type="number" class="form-control" id="page_count" name="page_count" min="1" value="<?= (int)($_POST['page_count'] ?? 30) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="display_order">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="1" value="<?= (int)($_POST['display_order'] ?? 1) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="card border rounded-3 p-3 bg-light">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <h6 class="fw-bold mb-0 text-primary">
                                        <i class="bi bi-link-45deg me-1"></i>Page-Wise JEE & NEET Question Links
                                    </h6>
                                    <small class="text-muted">Set specific question doc/quiz URLs for Page 4 and all pages after (4, 5, 6, 7...).</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPageRow()">
                                        <i class="bi bi-plus-circle me-1"></i>Add Page Link
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quickFillPage4Onwards()">
                                        <i class="bi bi-magic me-1"></i>Quick Fill Page 4+
                                    </button>
                                </div>
                            </div>

                            <!-- Global/Default Link (Optional fallback) -->
                            <div class="row g-2 mb-3 align-items-center bg-white p-2 rounded border">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold mb-0 text-dark">Default Link for Page 4 Onwards:</label>
                                    <small class="text-muted d-block" style="font-size: 11px;">Fallback if a page does not have a custom link</small>
                                </div>
                                <div class="col-md-8">
                                    <input type="url" class="form-control form-control-sm" id="default_page_link" placeholder="https://docs.google.com/document/d/..." oninput="syncPageLinksJson()">
                                </div>
                            </div>

                            <!-- Table of Custom Page Links -->
                            <div class="table-responsive bg-white rounded border">
                                <table class="table table-sm table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 140px;">Page Number</th>
                                            <th>JEE & NEET Question URL / Google Doc</th>
                                            <th style="width: 60px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="page_links_rows">
                                        <!-- Dynamic Rows -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="no_custom_links_msg" class="text-center py-2 text-muted small">
                                No specific page links added yet. Click <strong>+ Add Page Link</strong> or <strong>Quick Fill Page 4+</strong>.
                            </div>

                            <!-- Hidden field that stores the JSON string for backend processing -->
                            <input type="hidden" id="page_links" name="page_links" value="<?= htmlspecialchars($_POST['page_links'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <a href="<?= url('modules/chapters/index.php') ?>" class="btn btn-light border me-2">Cancel</a>
                        <button type="submit" class="btn btn-bb-primary">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i>Save & Upload Chapter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleFileSelected(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        document.getElementById('file_status_label').innerHTML = 
            `<strong class="text-primary"><i class="bi bi-check2-circle me-1"></i>Selected: ${file.name} (${sizeMB} MB)</strong>`;
    }
}

// Page Links Manager Script
function addPageRow(pageNum = '', url = '') {
    const tbody = document.getElementById('page_links_rows');
    const msg = document.getElementById('no_custom_links_msg');
    msg.style.display = 'none';

    const tr = document.createElement('tr');
    tr.className = 'page-link-row';
    tr.innerHTML = `
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted">Pg</span>
                <input type="number" min="1" class="form-control form-control-sm row-page-num fw-bold text-primary" value="${pageNum}" placeholder="e.g. 4" oninput="syncPageLinksJson()">
            </div>
        </td>
        <td>
            <input type="url" class="form-control form-control-sm row-page-url font-monospace small" value="${url}" placeholder="https://docs.google.com/document/d/..." oninput="syncPageLinksJson()">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="removePageRow(this)" title="Delete Row">
                <i class="bi bi-trash3"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    syncPageLinksJson();
}

function removePageRow(btn) {
    btn.closest('tr').remove();
    const rows = document.querySelectorAll('.page-link-row');
    if (rows.length === 0) {
        document.getElementById('no_custom_links_msg').style.display = 'block';
    }
    syncPageLinksJson();
}

function quickFillPage4Onwards() {
    const totalPagesInput = document.getElementById('page_count');
    const totalPages = parseInt(totalPagesInput?.value || '10', 10);
    const defaultUrl = document.getElementById('default_page_link').value.trim();

    // Add rows from 4 to totalPages or 4..10
    const endPage = Math.min(Math.max(totalPages, 4), 30);
    for (let p = 4; p <= endPage; p++) {
        // check if row for page p already exists
        const existing = Array.from(document.querySelectorAll('.row-page-num')).some(input => input.value == p);
        if (!existing) {
            addPageRow(p, defaultUrl);
        }
    }
}

function syncPageLinksJson() {
    const defaultUrl = document.getElementById('default_page_link').value.trim();
    const rows = document.querySelectorAll('.page-link-row');
    const mapping = {};

    if (defaultUrl) {
        mapping['default'] = defaultUrl;
    }

    rows.forEach(tr => {
        const pageNum = tr.querySelector('.row-page-num').value.trim();
        const url = tr.querySelector('.row-page-url').value.trim();
        if (pageNum && url) {
            mapping[pageNum] = url;
        }
    });

    const hiddenInput = document.getElementById('page_links');
    if (Object.keys(mapping).length > 0) {
        hiddenInput.value = JSON.stringify(mapping);
    } else {
        hiddenInput.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const initialVal = document.getElementById('page_links').value.trim();
    if (initialVal) {
        try {
            if (initialVal.startsWith('{') && initialVal.endsWith('}')) {
                const parsed = JSON.parse(initialVal);
                for (const [key, val] of Object.entries(parsed)) {
                    if (key === 'default') {
                        document.getElementById('default_page_link').value = val;
                    } else if (key && val) {
                        addPageRow(key, val);
                    }
                }
            } else if (initialVal.startsWith('http://') || initialVal.startsWith('https://')) {
                document.getElementById('default_page_link').value = initialVal;
            }
        } catch (e) {
            console.error('Error parsing initial page_links:', e);
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
