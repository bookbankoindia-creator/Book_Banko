<?php
/**
 * Top Navbar Include
 * Book Banko Admin Panel
 */
?>
<header class="top-navbar">
    <div class="d-flex align-items-center gap-3">
        <!-- Mobile Sidebar Toggle -->
        <button class="btn btn-light d-lg-none p-2 rounded-3" id="sidebarToggle" type="button">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="page-title">
            <h4><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h4>
            <p><?= htmlspecialchars($pageSubtitle ?? 'Manage educational resources, PDFs, and application configuration') ?></p>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">

        <!-- Admin Profile Pill Dropdown -->
        <div class="dropdown">
            <div class="admin-profile-pill" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="admin-avatar">
                    <?= strtoupper(substr($currentAdmin['full_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="d-none d-md-block text-start pe-2">
                    <div class="fw-bold text-dark" style="font-size: 13px; line-height: 1.2;">
                        <?= htmlspecialchars($currentAdmin['full_name'] ?? 'Administrator') ?>
                    </div>
                    <div class="text-secondary" style="font-size: 11px;">
                        <?= ucfirst($currentAdmin['role'] ?? 'Admin') ?>
                    </div>
                </div>
                <i class="bi bi-chevron-down text-muted" style="font-size: 12px;"></i>
            </div>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 rounded-3">
                <li><a class="dropdown-item py-2" href="<?= url('auth/profile.php') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item py-2" href="<?= url('modules/settings/index.php') ?>"><i class="bi bi-gear me-2"></i>System Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2 text-danger" href="<?= url('auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
            </ul>
        </div>
    </div>
</header>
