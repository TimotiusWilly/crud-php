<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/flash.php';

// Wajib Login
require_login();

$currentUser = get_logged_user();
$currentPage = basename($_SERVER['PHP_SELF']);

if (!isset($pageTitle)) {
    $pageTitle = 'Manajemen Stok & Laba Akun Game';
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
    <!-- Backdrop Mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar Sederhana & Praktis -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-header">
            <a href="index.php" class="brand-logo">
                <span class="brand-icon">🎮</span>
                <span>GameStock</span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section-title">Menu Utama</span>
            <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📦</span>
                <span>Stok & Modal Akun</span>
            </a>
            <a href="penjualan.php" class="nav-link <?= (in_array($currentPage, ['penjualan.php', 'jual.php'])) ? 'active' : ''; ?>">
                <span class="nav-icon">💰</span>
                <span>Rekapan Laba Penjualan</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-badge">
                <div class="user-info">
                    <span class="user-name"><?= clean($currentUser['nama_lengkap'] ?? 'Admin'); ?></span>
                    <span class="user-role">@<?= clean($currentUser['username'] ?? 'admin'); ?></span>
                </div>
                <a href="logout.php" class="btn-logout" title="Logout" onclick="return confirm('Apakah Anda yakin ingin logout?');">
                    🚪
                </a>
            </div>
        </div>
    </aside>

    <!-- Konten Utama -->
    <main class="app-main">
        <header class="app-header">
            <div class="header-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle Navigation">
                    ☰
                </button>
                <h1 class="page-title"><?= clean($pageTitle); ?></h1>
            </div>
            <div class="header-right">
                <a href="index.php#formInputStok" class="btn btn-primary btn-sm">
                    <span>➕</span>
                    <span>Input Stok Baru</span>
                </a>
            </div>
        </header>

        <div class="content-body">
            <?php render_flash(); ?>
