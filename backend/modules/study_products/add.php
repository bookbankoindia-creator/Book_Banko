<?php
/**
 * Add New Study Material Product / Affiliate Item
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

requireLogin();

$pageTitle = 'Add Study Material Product';
$pageSubtitle = 'Add a pen, pencil, rubber, sharpener, notebook or stationery product with Amazon/Flipkart affiliate link';
$db = Database::getConnection();

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

        $imageFileName = '';

        // Handle Image File Upload (direct client-side upload or standard)
        if (!empty($_POST['direct_uploaded_file'])) {
            $imageFileName = trim($_POST['direct_uploaded_file']);
        } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadFile($_FILES['product_image'], PRODUCTS_UPLOADS_PATH, ['jpg', 'jpeg', 'png', 'webp'], 20);
            if ($uploadResult['status']) {
                $imageFileName = $uploadResult['filename'];
            } else {
                flash('error', $uploadResult['message']);
            }
        }

        if (empty($title)) {
            flash('error', 'Product title is required.');
        } elseif (empty($affiliateLink)) {
            flash('error', 'Affiliate Link (Amazon / Flipkart / etc.) is required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO study_products (
                        title, description, affiliate_link, search_tags, 
                        image_path, image_external_url, display_order, 
                        status, is_fallback_ad
                    ) VALUES (
                        :title, :description, :affiliate_link, :search_tags, 
                        :image_path, :image_external_url, :display_order, 
                        :status, :is_fallback_ad
                    )
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':affiliate_link' => $affiliateLink,
                    ':search_tags' => $searchTags,
                    ':image_path' => $imageFileName,
                    ':image_external_url' => $imageExternalUrl,
                    ':display_order' => $displayOrder,
                    ':status' => $status,
                    ':is_fallback_ad' => $isFallbackAd
                ]);
                flash('success', "Product '{$title}' added successfully.");
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
                <h5><i class="bi bi-cart-plus-fill text-primary me-2"></i>Add Study Material Product</h5>
                <a href="<?= url('modules/study_products/index.php') ?>" class="btn btn-sm btn-bb-light">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="title">Product Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Cello Butterflow Ball Pen (Pack of 10), Doms Eraser" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="display_order">Display Order (lower = first)</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="1" value="<?= htmlspecialchars($_POST['display_order'] ?? '99') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="affiliate_link">Affiliate Link <span class="text-danger">*</span> (Amazon / Flipkart / etc.)</label>
                        <input type="url" class="form-control" id="affiliate_link" name="affiliate_link" placeholder="https://www.amazon.in/dp/... or https://www.flipkart.com/..." value="<?= htmlspecialchars($_POST['affiliate_link'] ?? '') ?>" required>
                        <small class="text-muted">When students tap this product in the app, it will open this link in their browser.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="search_tags">Search Tags (comma-separated)</label>
                        <input type="text" class="form-control" id="search_tags" name="search_tags" placeholder="e.g. pen, pencil, rubber, sharpener, eraser, notebook, geometry box" value="<?= htmlspecialchars($_POST['search_tags'] ?? '') ?>">
                        <small class="text-muted">Keywords students can type in the search bar (e.g. pen, pencil, rubber, sharpner).</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Product Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief features, brand, quantity, smooth writing, extra dark..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-image text-primary me-2"></i>Product Image</h6>
                            
                            <div class="mb-3">
                                <label class="form-label" for="product_image">Upload Product Image (PNG, JPG, WEBP)</label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                            </div>

                            <div class="text-center text-muted my-2 small fw-bold">— OR EXTERNAL IMAGE URL —</div>

                            <div>
                                <label class="form-label" for="image_external_url">External Image URL (Amazon/Flipkart CDN image link)</label>
                                <input type="url" class="form-control" id="image_external_url" name="image_external_url" placeholder="https://m.media-amazon.com/images/I/..." value="<?= htmlspecialchars($_POST['image_external_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="status">Publication Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (visible to students)</option>
                            <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (hidden)</option>
                        </select>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check mt-3 pt-2">
                            <input class="form-check-input" type="checkbox" id="is_fallback_ad" name="is_fallback_ad" value="1" <?= isset($_POST['is_fallback_ad']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-dark" for="is_fallback_ad">
                                Use as fallback ad (when no regular ads run)
                            </label>
                            <div class="form-text text-muted small">Highlights this product as an ad card across the app.</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= url('modules/study_products/index.php') ?>" class="btn btn-bb-light">Cancel</a>
                    <button type="submit" class="btn btn-bb-primary">
                        <i class="bi bi-check-lg me-1"></i>Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
