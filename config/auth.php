<?php
/**
 * Manajemen Session & Otentikasi Pengguna
 * PHP Native
 */

// Aktifkan Output Buffering agar header() redirect selalu aman tanpa error 'headers already sent'
if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah admin sudah login
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Paksa login: jika belum login, redirect ke halaman login
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Ambil data user yang sedang login
 */
function get_logged_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap']
    ];
}
