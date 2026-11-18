<?php
/**
 * Edit Data Stok Akun & Modal
 * PHP Native
 */
$pageTitle = 'Edit Stok & Modal Akun';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'ID stok tidak valid.');
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM stok_akun WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$stok = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$stok) {
    set_flash('error', 'Data stok tidak ditemukan.');
    header("Location: index.php");
    exit;
}

// Cek berapa akun yang sudah pernah terjual dari stok ini
$qTerjual = mysqli_query($conn, "SELECT SUM(jumlah_terjual) AS terjual FROM penjualan WHERE stok_id = $id");
$dTerjual = mysqli_fetch_assoc($qTerjual);
$sudahTerjual = (int)($dTerjual['terjual'] ?? 0);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tglBeli = $_POST['tanggal_beli'] ?? date('Y-m-d');
    $namaAkun = trim($_POST['nama_akun'] ?? '');
    $modal = (float)($_POST['modal'] ?? 0);
    $jumlahTotal = (int)($_POST['jumlah_total'] ?? 1);
    $catatanLogin = trim($_POST['catatan_login'] ?? '');

    if (empty($namaAkun)) $errors[] = 'Nama/keterangan akun tidak boleh kosong.';
    if ($modal < 0) $errors[] = 'Modal tidak boleh negatif.';
    if ($jumlahTotal < $sudahTerjual) {
        $errors[] = "Jumlah total akun tidak boleh kurang dari jumlah yang sudah terjual ($sudahTerjual akun).";
    }

    if (empty($errors)) {
        // Hitung sisa stok baru
        $sisaStokBaru = $jumlahTotal - $sudahTerjual;

        $upd = mysqli_prepare($conn, "UPDATE stok_akun SET tanggal_beli = ?, nama_akun = ?, modal = ?, jumlah_total = ?, sisa_stok = ?, catatan_login = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, "ssdiisi", $tglBeli, $namaAkun, $modal, $jumlahTotal, $sisaStokBaru, $catatanLogin, $id);
        
        if (mysqli_stmt_execute($upd)) {
            // Update juga modal_terjual dan laba_bersih di riwayat penjualan jika modal per akun berubah
            $modalPerAkunBaru = ($jumlahTotal > 0) ? ($modal / $jumlahTotal) : $modal;
            $allPenjualan = mysqli_query($conn, "SELECT id, jumlah_terjual, harga_jual FROM penjualan WHERE stok_id = $id");
            while ($p = mysqli_fetch_assoc($allPenjualan)) {
                $modalTerjualBaru = $p['jumlah_terjual'] * $modalPerAkunBaru;
                $labaBaru = $p['harga_jual'] - $modalTerjualBaru;
                mysqli_query($conn, "UPDATE penjualan SET modal_terjual = $modalTerjualBaru, laba_bersih = $labaBaru WHERE id = " . $p['id']);
            }

            set_flash('success', "Stok '$namaAkun' berhasil diperbarui!");
            header("Location: index.php");
            exit;
        } else {
            $errors[] = 'Gagal menyimpan perubahan: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($upd);
    }
}
?>

<div style="max-width: 700px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>✏️ Edit Modal & Stok Akun</span>
            </h3>
            <a href="index.php" class="btn btn-outline btn-sm">&larr; Kembali</a>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div class="alert-content">
                        <span>⚠️</span>
                        <div>
                            <ul style="margin-left: 18px;">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= clean($e); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form action="stok_form.php?id=<?= $id; ?>" method="POST">
                <div class="form-group">
                    <label for="nama_akun" class="form-label">
                        Nama / Keterangan Akun <span class="required">*</span>
                    </label>
                    <input type="text" name="nama_akun" id="nama_akun" class="form-control" 
                           value="<?= clean($_POST['nama_akun'] ?? $stok['nama_akun']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="modal" class="form-label">
                            Total Modal Beli (Rp) <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-addon">Rp</span>
                            <input type="number" name="modal" id="modal" class="form-control" 
                                   value="<?= clean($_POST['modal'] ?? (int)$stok['modal']); ?>" min="0" step="1000" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="jumlah_total" class="form-label">
                            Jumlah Akun Total <span class="required">*</span>
                        </label>
                        <input type="number" name="jumlah_total" id="jumlah_total" class="form-control" 
                               value="<?= clean($_POST['jumlah_total'] ?? $stok['jumlah_total']); ?>" min="1" required>
                        <div class="form-help">Sudah laku: <?= $sudahTerjual; ?> akun. Sisa stok: <?= $stok['sisa_stok']; ?> akun.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="tanggal_beli" class="form-label">Tanggal Pembelian</label>
                    <input type="date" name="tanggal_beli" id="tanggal_beli" class="form-control" 
                           value="<?= clean($_POST['tanggal_beli'] ?? $stok['tanggal_beli']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="catatan_login" class="form-label">Catatan Login / Detail Kredensial</label>
                    <textarea name="catatan_login" id="catatan_login" class="form-control" rows="3"><?= clean($_POST['catatan_login'] ?? $stok['catatan_login']); ?></textarea>
                </div>

                <div style="margin-top: 24px; display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="index.php" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <span>💾 Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
