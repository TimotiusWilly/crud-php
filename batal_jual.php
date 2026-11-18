<?php
/**
 * Batalkan Transaksi Penjualan & Kembalikan Sisa Stok
 * PHP Native
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/flash.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Ambil data transaksi penjualan
    $stmt = mysqli_prepare($conn, "SELECT p.*, s.nama_akun FROM penjualan p JOIN stok_akun s ON p.stok_id = s.id WHERE p.id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $trx = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($trx) {
        mysqli_begin_transaction($conn);
        try {
            // 1. Kembalikan sisa_stok ke stok_akun
            $updStok = mysqli_prepare($conn, "UPDATE stok_akun SET sisa_stok = sisa_stok + ? WHERE id = ?");
            mysqli_stmt_bind_param($updStok, "ii", $trx['jumlah_terjual'], $trx['stok_id']);
            mysqli_stmt_execute($updStok);
            mysqli_stmt_close($updStok);

            // 2. Hapus catatan penjualan
            $del = mysqli_prepare($conn, "DELETE FROM penjualan WHERE id = ?");
            mysqli_stmt_bind_param($del, "i", $id);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);

            mysqli_commit($conn);
            set_flash('success', "Transaksi penjualan '{$trx['nama_akun']}' berhasil dibatalkan. {$trx['jumlah_terjual']} akun telah dikembalikan ke stok.");
        } catch (Exception $e) {
            mysqli_rollback($conn);
            set_flash('error', "Gagal membatalkan transaksi: " . $e->getMessage());
        }
    }
}

header("Location: penjualan.php");
exit;
