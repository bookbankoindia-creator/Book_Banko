<?php
/**
 * Common Header Include
 * Book Banko Admin Panel
 */
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Check if user is logged in
requireLogin();

$pageTitle = $pageTitle ?? 'Dashboard';
$currentAdmin = currentAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?> Admin Panel</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/custom.css">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Include -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <!-- Top Navbar Include -->
        <?php include __DIR__ . '/navbar.php'; ?>

        <!-- Main Body Container -->
        <main class="page-body">
            <!-- Flash Message Banner -->
            <?= renderFlashMessages() ?>
