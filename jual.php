<?php
/**
 * Form Jual Stok Akun & Kalkulasi Laba (Simple & Cepat)
 * PHP Native
 */
$pageTitle = 'Catat Penjualan Akun';
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

if ($stok['sisa_stok'] <= 0) {
    set_flash('warning', "Stok '{$stok['nama_akun']}' sudah habis terjual.");
    header("Location: index.php");
    exit;
}

$modalPerAkun = ($stok['jumlah_total'] > 0) ? ($stok['modal'] / $stok['jumlah_total']) : $stok['modal'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tglJual = $_POST['tanggal_jual'] ?? date('Y-m-d');
    $jmlJual = (int)($_POST['jumlah_terjual'] ?? 1);
    $hargaJual = (float)($_POST['harga_jual'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    if ($jmlJual <= 0) $errors[] = 'Jumlah akun yang dijual minimal 1.';
    if ($jmlJual > $stok['sisa_stok']) $errors[] = "Jumlah yang dijual ({$jmlJual}) melebihi sisa stok ({$stok['sisa_stok']}).";
    if ($hargaJual <= 0) $errors[] = 'Harga jual harus lebih dari 0.';

    if (empty($errors)) {
        $modalTerjual = $jmlJual * $modalPerAkun;
        $labaBersih = $hargaJual - $modalTerjual;

        mysqli_begin_transaction($conn);
        try {
            // 1. Simpan ke tabel penjualan
            $ins = mysqli_prepare($conn, "INSERT INTO penjualan (stok_id, tanggal_jual, jumlah_terjual, modal_terjual, harga_jual, laba_bersih, catatan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, "isiddds", $id, $tglJual, $jmlJual, $modalTerjual, $hargaJual, $labaBersih, $catatan);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            // 2. Kurangi sisa stok akun
            $sisaBaru = $stok['sisa_stok'] - $jmlJual;
            $updStok = mysqli_prepare($conn, "UPDATE stok_akun SET sisa_stok = ? WHERE id = ?");
            mysqli_stmt_bind_param($updStok, "ii", $sisaBaru, $id);
            mysqli_stmt_execute($updStok);
            mysqli_stmt_close($updStok);

            mysqli_commit($conn);

            set_flash('success', "Penjualan berhasil dicatat! Laba bersih: " . format_rupiah($labaBersih) . " (Sisa stok: {$sisaBaru} akun).");
            header("Location: penjualan.php");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = 'Gagal menyimpan transaksi: ' . $e->getMessage();
        }
    }
}
?>

<div style="max-width: 650px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>💰 Catat Penjualan Akun</span>
            </h3>
            <a href="index.php" class="btn btn-outline btn-sm">&larr; Kembali</a>
        </div>
        <div class="card-body">
            <!-- INFO RINGKAS STOK -->
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Akun yang Dijual:</span>
                    <strong style="font-size: 1.1rem; color: var(--text-main);"><?= clean($stok['nama_akun']); ?></strong>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Modal / Akun:</span>
                    <strong style="font-size: 1.1rem; color: var(--primary);" id="valModalPerAkun" data-modal="<?= $modalPerAkun; ?>">
                        <?= format_rupiah($modalPerAkun); ?>
                    </strong>
                    <small style="color: var(--success-text); display: block; font-weight: 600;">Sisa Stok: <?= $stok['sisa_stok']; ?> akun</small>
                </div>
            </div>

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

            <form action="jual.php?id=<?= $id; ?>" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="jumlah_terjual" class="form-label">
                            Jumlah Akun Terjual <span class="required">*</span>
                        </label>
                        <input type="number" name="jumlah_terjual" id="jumlah_terjual" class="form-control" 
                               value="1" min="1" max="<?= $stok['sisa_stok']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_jual" class="form-label">Tanggal Penjualan</label>
                        <input type="date" name="tanggal_jual" id="tanggal_jual" class="form-control" 
                               value="<?= date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="harga_jual" class="form-label">
                        Total Uang Diterima / Harga Jual (Rp) <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-addon">Rp</span>
                        <input type="number" name="harga_jual" id="harga_jual" class="form-control" 
                               placeholder="0" min="0" step="1000" required autofocus>
                    </div>
                    <div class="form-help">Nominal bersih yang Anda terima dari pembeli.</div>
                </div>

                <!-- LIVE KALKULATOR LABA BERSIH -->
                <div class="profit-preview-card" style="margin-top: 16px; margin-bottom: 20px;">
                    <div class="profit-preview-title">
                        <span>📊 Live Hitung Laba Bersih</span>
                    </div>

                    <div class="profit-preview-grid">
                        <div class="profit-item">
                            <span class="profit-item-label">Modal Terpakai</span>
                            <span class="profit-item-value" id="calcModalTerpakai" style="color: #cbd5e1;"><?= format_rupiah($modalPerAkun); ?></span>
                        </div>

                        <div class="profit-item">
                            <span class="profit-item-label">Laba Bersih Riil</span>
                            <span class="profit-item-value profit-result-highlight text-profit" id="calcLabaBersih">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="catatan" class="form-label">Catatan Pembeli / Transaksi (Opsional)</label>
                    <input type="text" name="catatan" id="catatan" class="form-control" 
                           placeholder="Contoh: Laku ke Dimas via WA / Transfer BCA">
                </div>

                <div style="margin-top: 24px; display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="index.php" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-success">
                        <span>💰 Simpan Penjualan & Laba</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inpJml = document.getElementById('jumlah_terjual');
    const inpHarga = document.getElementById('harga_jual');
    const valModal = parseFloat(document.getElementById('valModalPerAkun').getAttribute('data-modal')) || 0;
    const dispModal = document.getElementById('calcModalTerpakai');
    const dispLaba = document.getElementById('calcLabaBersih');

    function hitung() {
        const jml = parseInt(inpJml.value) || 0;
        const harga = parseFloat(inpHarga.value) || 0;
        const modalTerpakai = jml * valModal;
        const laba = harga - modalTerpakai;

        if (dispModal) dispModal.textContent = formatRupiahJS(modalTerpakai);
        if (dispLaba) {
            dispLaba.textContent = formatRupiahJS(laba);
            if (laba >= 0) {
                dispLaba.className = 'profit-item-value profit-result-highlight text-profit';
            } else {
                dispLaba.className = 'profit-item-value profit-result-highlight text-loss';
            }
        }
    }

    if (inpJml && inpHarga) {
        inpJml.addEventListener('input', hitung);
        inpHarga.addEventListener('input', hitung);
        hitung();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
