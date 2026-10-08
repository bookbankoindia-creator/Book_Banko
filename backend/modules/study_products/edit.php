<?php
/**
 * Edit Study Material Product
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Edit Study Material Product';
$pageSubtitle = 'Update product details, affiliate link, image, or tags';
$db = Database::getConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flash('error', 'Invalid product ID.');
    header('Location: ' . url('modules/study_products/index.php'));
    exit;
}

$stmt = $db->prepare("SELECT * FROM study_products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    flash('error', 'Product not found.');
    header('Location: ' . url('modules/study_products/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Invalid security token. Please refresh the page and try again.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $affiliateLink = trim($_POST['affiliate_link'] ?? '');
        $searchTags = trim($_POST['search_tags'] ?? '');
        $imageExternalUrl = trim($_POST['image_external_url'] ?? '');
        $displayOrder = isset($_POST['display_order']) && $_POST['display_order'] !== '' ? (int)$_POST['display_order'] : 99;
        $status = $_POST['status'] ?? 'active';
        $isFallbackAd = isset($_POST['is_fallback_ad']) ? 1 : 0;

        $imageFileName = $product['image_path'];

        // Handle Image Replacement (direct client-side upload or standard)
        if (!empty($_POST['direct_uploaded_file'])) {
            $imageFileName = trim($_POST['direct_uploaded_file']);
        } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadFile($_FILES['product_image'], PRODUCTS_UPLOADS_PATH, ['jpg', 'jpeg', 'png', 'webp'], 20);
            if ($uploadResult['status']) {
                if (!empty($product['image_path']) && file_exists(PRODUCTS_UPLOADS_PATH . $product['image_path'])) {
                    @unlink(PRODUCTS_UPLOADS_PATH . $product['image_path']);
                }
                $imageFileName = $uploadResult['filename'];
            } else {
                flash('error', $uploadResult['message']);
            }
        }

        if (empty($title)) {
            flash('error', 'Product title is required.');
        } elseif (empty($affiliateLink)) {
            flash('error', 'Affiliate Link is required.');
        } else {
            try {
                $updateStmt = $db->prepare("
                    UPDATE study_products SET
                        title = :title,
                        description = :description,
                        affiliate_link = :affiliate_link,
                        search_tags = :search_tags,
                        image_path = :image_path,
                        image_external_url = :image_external_url,
                        display_order = :display_order,
                        status = :status,
                        is_fallback_ad = :is_fallback_ad
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':affiliate_link' => $affiliateLink,
                    ':search_tags' => $searchTags,
                    ':image_path' => $imageFileName,
                    ':image_external_url' => $imageExternalUrl,
                    ':display_order' => $displayOrder,
                    ':status' => $status,
                    ':is_fallback_ad' => $isFallbackAd,
                    ':id' => $id
                ]);
                flash('success', "Product '{$title}' updated successfully.");
                header('Location: ' . url('modules/study_products/index.php'));
                exit;
            } catch (PDOException $e) {
                flash('error', 'Database error: ' . $e->getMessage());
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-pencil-square text-primary me-2"></i>Edit Study Material Product</h5>
                <a href="<?= url('modules/study_products/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="title">Product Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $product['title']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="display_order">Display Order (lower = first)</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="1" value="<?= htmlspecialchars($_POST['display_order'] ?? $product['display_order']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="affiliate_link">Affiliate Link <span class="text-danger">*</span> (Amazon / Flipkart / etc.)</label>
                        <input type="url" class="form-control" id="affiliate_link" name="affiliate_link" value="<?= htmlspecialchars($_POST['affiliate_link'] ?? $product['affiliate_link']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="search_tags">Search Tags (comma-separated)</label>
                        <input type="text" class="form-control" id="search_tags" name="search_tags" value="<?= htmlspecialchars($_POST['search_tags'] ?? $product['search_tags']) ?>">
                        <small class="text-muted">Keywords students can type (e.g. pen, pencil, rubber, sharpener).</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Product Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($_POST['description'] ?? $product['description']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-image text-primary me-2"></i>Product Image</h6>
                            
                            <?php 
                            $currentImg = !empty($product['image_path']) ? resolveMediaUrl($product['image_path'], 'products') : (!empty($product['image_external_url']) ? $product['image_external_url'] : '');
                            if (!empty($currentImg)): ?>
                                <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-white rounded border">
                                    <img src="<?= htmlspecialchars($currentImg) ?>" alt="Current Product Image" class="rounded object-fit-cover" style="width: 60px; height: 60px;">
                                    <div>
                                        <div class="small fw-bold text-dark">Current Product Image</div>
                                        <small class="text-muted">Upload a new image below if you wish to replace it.</small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label" for="product_image">Upload New Image</label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                            </div>

                            <div class="text-center text-muted my-2 small fw-bold">— OR EXTERNAL IMAGE URL —</div>

                            <div>
                                <label class="form-label" for="image_external_url">External Image URL</label>
                                <input type="url" class="form-control" id="image_external_url" name="image_external_url" value="<?= htmlspecialchars($_POST['image_external_url'] ?? $product['image_external_url']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Publication Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= ($_POST['status'] ?? $product['status']) === 'active' ? 'selected' : '' ?>>Active (visible to students)</option>
                            <option value="inactive" <?= ($_POST['status'] ?? $product['status']) === 'inactive' ? 'selected' : '' ?>>Inactive (hidden)</option>
                        </select>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check mt-3 pt-2">
                            <input class="form-check-input" type="checkbox" id="is_fallback_ad" name="is_fallback_ad" value="1" <?= ($_POST['is_fallback_ad'] ?? $product['is_fallback_ad']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-dark" for="is_fallback_ad">
                                Use as fallback ad (when no regular ads run)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= url('modules/study_products/index.php') ?>" class="btn btn-bb-light">Cancel</a>
                    <button type="submit" class="btn btn-bb-primary">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
