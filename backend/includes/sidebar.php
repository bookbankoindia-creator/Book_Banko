<?php
/**
 * Sidebar Navigation Include
 * Book Banko Admin Panel
 */
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
function isActiveNav(string $keyword): string {
    global $currentUri;
    return (strpos($currentUri, $keyword) !== false) ? 'active' : '';
}
?>
<aside class="sidebar" id="appSidebar">
    <!-- Brand Header -->
    <div class="sidebar-header">
        <div class="sidebar-brand-icon">
            <i class="bi bi-book-half"></i>
        </div>
        <div class="sidebar-brand-text">
            <h5>Book Banko</h5>
            <span>Admin Portal</span>
        </div>
        <button type="button" class="btn btn-sm text-white d-lg-none p-1 ms-auto" id="sidebarCloseBtn" aria-label="Close sidebar">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <!-- Navigation Links -->
    <div class="sidebar-menu">
        <a href="<?= url('modules/dashboard/index.php') ?>" class="nav-link-bb <?= isActiveNav('dashboard') ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-heading">Education Hierarchy</div>
        
        <a href="<?= url('modules/boards/index.php') ?>" class="nav-link-bb <?= isActiveNav('/boards/') ?>">
            <i class="bi bi-buildings"></i>
            <span>Boards</span>
        </a>

        <a href="<?= url('modules/mediums/index.php') ?>" class="nav-link-bb <?= isActiveNav('/mediums/') ?>">
            <i class="bi bi-translate"></i>
            <span>Mediums of Study</span>
        </a>

        <a href="<?= url('modules/standards/index.php') ?>" class="nav-link-bb <?= isActiveNav('/standards/') ?>">
            <i class="bi bi-mortarboard-fill"></i>
            <span>Standards / Classes</span>
        </a>

        <a href="<?= url('modules/streams/index.php') ?>" class="nav-link-bb <?= isActiveNav('/streams/') ?>">
            <i class="bi bi-diagram-3-fill"></i>
            <span>Streams (11th & 12th)</span>
        </a>

        <div class="sidebar-heading">Content & Library</div>

        <a href="<?= url('modules/categories/index.php') ?>" class="nav-link-bb <?= isActiveNav('/categories/') ?>">
            <i class="bi bi-tags-fill"></i>
            <span>Learning Categories</span>
        </a>

        <a href="<?= url('modules/modules/index.php') ?>" class="nav-link-bb <?= isActiveNav('/modules/') && !isActiveNav('categories') && !isActiveNav('boards') && !isActiveNav('standards') && !isActiveNav('streams') && !isActiveNav('subjects') && !isActiveNav('chapters') && !isActiveNav('banners') && !isActiveNav('settings') && !isActiveNav('dashboard') ? 'active' : '' ?>">
            <i class="bi bi-collection-fill"></i>
            <span>Dashboard Modules</span>
        </a>

        <a href="<?= url('modules/subjects/index.php') ?>" class="nav-link-bb <?= isActiveNav('/subjects/') ?>">
            <i class="bi bi-journal-bookmark-fill"></i>
            <span>Subjects</span>
        </a>

        <a href="<?= url('modules/chapters/index.php') ?>" class="nav-link-bb <?= isActiveNav('/chapters/') ?>">
            <i class="bi bi-file-earmark-pdf-fill"></i>
            <span>Chapters & PDFs</span>
        </a>

        <a href="<?= url('modules/extra_materials/index.php') ?>" class="nav-link-bb <?= isActiveNav('/extra_materials/') ?>">
            <i class="bi bi-bookmark-star-fill text-warning"></i>
            <span>Extra Material PDFs</span>
        </a>

        <a href="<?= url('modules/competitive_exams/index.php') ?>" class="nav-link-bb <?= isActiveNav('/competitive_exams/') ?>">
            <i class="bi bi-award-fill text-warning"></i>
            <span>Competitive Exams</span>
        </a>

        <a href="<?= url('modules/higher_education/index.php') ?>" class="nav-link-bb <?= isActiveNav('/higher_education/') ?>">
            <i class="bi bi-mortarboard-fill text-success"></i>
            <span>Higher Education</span>
        </a>

        <a href="<?= url('modules/study_products/index.php') ?>" class="nav-link-bb <?= isActiveNav('/study_products/') ?>">
            <i class="bi bi-cart4 text-success"></i>
            <span>Study Products (Store)</span>
        </a>

        <div class="sidebar-heading">App Management</div>

        <a href="<?= url('modules/banners/index.php') ?>" class="nav-link-bb <?= isActiveNav('/banners/') ?>">
            <i class="bi bi-megaphone-fill"></i>
            <span>Banners & Notices</span>
        </a>

        <a href="<?= url('modules/settings/index.php') ?>" class="nav-link-bb <?= isActiveNav('/settings/') ?>">
            <i class="bi bi-sliders"></i>
            <span>App Settings</span>
        </a>

        <div class="sidebar-heading">Security & Account</div>

        <a href="<?= url('auth/profile.php') ?>" class="nav-link-bb <?= isActiveNav('profile') ?>">
            <i class="bi bi-person-badge-fill"></i>
            <span>Admin Profile</span>
        </a>

        <a href="<?= url('auth/logout.php') ?>" class="nav-link-bb text-danger" onclick="return confirm('Are you sure you want to log out?');">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
