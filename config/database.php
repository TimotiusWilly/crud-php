<?php
/**
 * Konfigurasi Database MySQL
 * Menggunakan mysqli PHP Native
 */

// Konfigurasi koneksi XAMPP default
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_stok_game');
define('DB_PORT', 3306);

// Buat koneksi mysqli
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Periksa koneksi
if (!$conn) {
    die("Gagal terhubung ke database MySQL: " . mysqli_connect_error());
}

// Set charset utf8mb4
mysqli_set_charset($conn, "utf8mb4");

/**
 * Format angka ke format Rupiah (Contoh: Rp 450.000)
 */
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia (Contoh: 18 Nov 2026)
 */
function format_tanggal($tanggal) {
    if (!$tanggal || $tanggal == '0000-00-00') return '-';
    $bulanIndo = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $bulan = $bulanIndo[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    return "$hari $bulan $tahun";
}

/**
 * Sanitasi string untuk output HTML
 */
function clean($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}
