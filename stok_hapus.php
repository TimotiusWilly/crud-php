<?php
/**
 * Hapus Stok Akun
 * PHP Native
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/flash.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT nama_akun FROM stok_akun WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $stok = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($stok) {
        $del = mysqli_prepare($conn, "DELETE FROM stok_akun WHERE id = ?");
        mysqli_stmt_bind_param($del, "i", $id);
        if (mysqli_stmt_execute($del)) {
            set_flash('success', "Stok '{$stok['nama_akun']}' berhasil dihapus.");
        } else {
            set_flash('error', "Gagal menghapus stok: " . mysqli_error($conn));
        }
        mysqli_stmt_close($del);
    }
}

header("Location: index.php");
exit;
