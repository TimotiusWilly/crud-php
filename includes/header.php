<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/flash.php';

// Wajib Login
require_login();

$currentUser = get_logged_user();
$currentPage = basename($_SERVER['PHP_SELF']);

if (!isset($pageTitle)) {
    $pageTitle = 'Overview';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle); ?> - GameStock</title>
    <!-- Vanilla CSS Stylesheet (Murni Tanpa Framework) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Sempit Matte Black (Persis Referensi Gambar) -->
    <aside class="app-sidebar">
        <!-- Cream Logo Button -->
        <a href="index.php" class="brand-logo-btn" title="GameStock">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="5"></rect>
                <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>
            </svg>
        </a>

        <!-- Vertical Icon Navigation -->
        <nav class="sidebar-nav">
            <!-- 1. Dashboard / Overview (Grid Icon) -->
            <a href="index.php" class="nav-item <?= ($currentPage == 'index.php') ? 'active' : ''; ?>" title="Overview / Stok">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                </svg>
            </a>

            <!-- 2. Rekapan Penjualan (Chart Icon) -->
            <a href="penjualan.php" class="nav-item <?= ($currentPage == 'penjualan.php' || $currentPage == 'jual.php') ? 'active' : ''; ?>" title="Rekapan Laba & Penjualan">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </a>

            <!-- 3. Form Input Stok Cepat -->
            <a href="index.php#formInputStok" class="nav-item" title="Input Stok Baru">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
            </a>
        </nav>

        <!-- Footer / Logout -->
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-item" title="Keluar / Logout" onclick="return confirm('Apakah Anda yakin ingin logout?');">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="app-main">
        <!-- Topbar Header (Overview + Search + Avatar Pill) -->
        <header class="topbar-header">
            <h1 class="topbar-title"><?= clean($pageTitle); ?></h1>

            <div class="topbar-actions">
                <!-- Search Button -->
                <div class="icon-btn" title="Cari">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>

                <!-- Bell Notification -->
                <div class="icon-btn" title="Notifikasi">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </div>

                <!-- User Profile Pill -->
                <div class="user-profile-pill">
                    <div class="user-avatar-circle">
                        <?= strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)); ?>
                    </div>
                    <span><?= clean($currentUser['nama_lengkap'] ?? 'Admin'); ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php render_flash(); ?>
