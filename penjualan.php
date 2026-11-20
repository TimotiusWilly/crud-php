<?php
/**
 * Rekapitulasi Penjualan & Laba Bersih (Fintech UI)
 * PHP Native
 */
$pageTitle = 'Rekapan Laba & Penjualan';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/flash.php';

require_login();

$tglMulai = $_GET['tgl_mulai'] ?? '';
$tglSelesai = $_GET['tgl_selesai'] ?? '';

// Handle Ekspor ke CSV / Excel
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $whereExp = [];
    if (!empty($tglMulai)) $whereExp[] = "p.tanggal_jual >= '" . mysqli_real_escape_string($conn, $tglMulai) . "'";
    if (!empty($tglSelesai)) $whereExp[] = "p.tanggal_jual <= '" . mysqli_real_escape_string($conn, $tglSelesai) . "'";
    $whereExpSql = count($whereExp) > 0 ? "WHERE " . implode(" AND ", $whereExp) : "";

    $qExp = mysqli_query($conn, "SELECT p.*, s.nama_akun FROM penjualan p JOIN stok_akun s ON p.stok_id = s.id $whereExpSql ORDER BY p.tanggal_jual DESC");

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Laba_Game_' . date('Ymd_His') . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['No', 'Tanggal Jual', 'Nama Akun', 'Jumlah Terjual', 'Modal Terjual', 'Harga Jual', 'Laba Bersih', 'Catatan']);

    $no = 1;
    while ($r = mysqli_fetch_assoc($qExp)) {
        fputcsv($out, [
            $no++,
            $r['tanggal_jual'],
            $r['nama_akun'],
            $r['jumlah_terjual'],
            $r['modal_terjual'],
            $r['harga_jual'],
            $r['laba_bersih'],
            $r['catatan']
        ]);
    }
    fclose($out);
    exit;
}

require_once __DIR__ . '/includes/header.php';

$whereClauses = [];
if (!empty($tglMulai)) $whereClauses[] = "p.tanggal_jual >= '" . mysqli_real_escape_string($conn, $tglMulai) . "'";
if (!empty($tglSelesai)) $whereClauses[] = "p.tanggal_jual <= '" . mysqli_real_escape_string($conn, $tglSelesai) . "'";
$whereSql = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Hitung total agregat keuangan
$qAgregat = mysqli_query($conn, "SELECT 
    COUNT(*) AS total_transaksi,
    SUM(jumlah_terjual) AS total_unit_terjual,
    SUM(modal_terjual) AS total_modal,
    SUM(harga_jual) AS total_omzet,
    SUM(laba_bersih) AS total_laba 
    FROM penjualan p $whereSql");
$agregat = mysqli_fetch_assoc($qAgregat);

$totTransaksi = (int)($agregat['total_transaksi'] ?? 0);
$totUnit = (int)($agregat['total_unit_terjual'] ?? 0);
$totModal = (float)($agregat['total_modal'] ?? 0);
$totOmzet = (float)($agregat['total_omzet'] ?? 0);
$totLaba = (float)($agregat['total_laba'] ?? 0);
$marginRoi = ($totModal > 0) ? round(($totLaba / $totModal) * 100, 1) : 0;

// Query daftar penjualan
$qPenjualan = mysqli_query($conn, "SELECT p.*, s.nama_akun 
    FROM penjualan p 
    JOIN stok_akun s ON p.stok_id = s.id 
    $whereSql 
    ORDER BY p.tanggal_jual DESC, p.id DESC");
?>

<!-- 3 KARTU PASTEL (PERFORMA PENJUALAN) -->
<div class="assets-card-grid" style="margin-bottom: 24px;">
    <!-- 1. Omzet Diterima (Mint Green) -->
    <div class="asset-card asset-card-green" style="min-height: 160px;">
        <div class="asset-top-row">
            <div>
                <div class="asset-main-val"><?= format_rupiah($totOmzet); ?></div>
                <div class="asset-sub-val">Total Omzet (Dari <?= $totUnit; ?> Akun Terjual)</div>
            </div>
        </div>
        <div class="asset-bottom-row" style="padding-top: 10px;">
            <div class="asset-status-pill">Omzet Kas Masuk</div>
        </div>
    </div>

    <!-- 2. Modal Terjual (Soft Yellow) -->
    <div class="asset-card asset-card-yellow" style="min-height: 160px;">
        <div class="asset-top-row">
            <div>
                <div class="asset-main-val"><?= format_rupiah($totModal); ?></div>
                <div class="asset-sub-val">Modal Terpakai (Sudah Balik Kas)</div>
            </div>
        </div>
        <div class="asset-bottom-row" style="padding-top: 10px;">
            <div class="asset-status-pill">Balik Modal</div>
        </div>
    </div>

    <!-- 3. Total Laba Bersih (Lavender) -->
    <div class="asset-card asset-card-purple" style="min-height: 160px;">
        <div class="asset-top-row">
            <div>
                <div class="asset-main-val <?= $totLaba >= 0 ? '' : 'text-loss'; ?>"><?= format_rupiah($totLaba); ?></div>
                <div class="asset-sub-val">Total Laba Bersih (Margin <?= ($marginRoi > 0 ? '+' : '') . $marginRoi; ?>%)</div>
            </div>
        </div>
        <div class="asset-bottom-row" style="padding-top: 10px;">
            <div class="asset-status-pill">Keuntungan Riil</div>
        </div>
    </div>
</div>

<!-- FILTER & TABEL REKAPAN -->
<div class="table-section-card">
    <div class="table-top-actions no-print">
        <div>
            <h3 style="font-size: 1.15rem; font-weight: 800;">Riwayat Penjualan & Laba</h3>
            <span style="font-size: 0.8rem; color: var(--text-secondary);">Filter periode transaksi</span>
        </div>

        <form action="penjualan.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <input type="date" name="tgl_mulai" class="form-control" style="width: auto; padding: 6px 12px; font-size: 0.82rem;" value="<?= clean($tglMulai); ?>">
            <span style="font-size: 0.82rem; color: var(--text-secondary);">s/d</span>
            <input type="date" name="tgl_selesai" class="form-control" style="width: auto; padding: 6px 12px; font-size: 0.82rem;" value="<?= clean($tglSelesai); ?>">

            <button type="submit" class="btn btn-dark btn-sm">Filter</button>
            <?php if (!empty($tglMulai) || !empty($tglSelesai)): ?>
                <a href="penjualan.php" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>

            <button type="button" onclick="window.print()" class="btn btn-outline btn-sm">Cetak / PDF</button>
            <a href="penjualan.php?export=csv<?= !empty($tglMulai) ? '&tgl_mulai='.$tglMulai : ''; ?><?= !empty($tglSelesai) ? '&tgl_selesai='.$tglSelesai : ''; ?>" class="btn btn-outline btn-sm">Unduh CSV</a>
        </form>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Akun</th>
                    <th>Jml Laku</th>
                    <th>Modal</th>
                    <th>Harga Jual</th>
                    <th>Laba Bersih</th>
                    <th>Catatan</th>
                    <th class="no-print" style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($totTransaksi > 0): ?>
                    <?php $no = 1; while ($trx = mysqli_fetch_assoc($qPenjualan)): 
                        $laba = (float)$trx['laba_bersih'];
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= format_tanggal($trx['tanggal_jual']); ?></td>
                            <td><strong><?= clean($trx['nama_akun']); ?></strong></td>
                            <td><?= (int)$trx['jumlah_terjual']; ?> unit</td>
                            <td><?= format_rupiah($trx['modal_terjual']); ?></td>
                            <td><?= format_rupiah($trx['harga_jual']); ?></td>
                            <td>
                                <strong style="color: <?= $laba >= 0 ? 'var(--pastel-green-text)' : '#dc2626'; ?>;">
                                    <?= format_rupiah($laba); ?>
                                </strong>
                            </td>
                            <td>
                                <span style="color: var(--text-secondary); font-size: 0.82rem;">
                                    <?= !empty($trx['catatan']) ? clean($trx['catatan']) : '-'; ?>
                                </span>
                            </td>
                            <td class="no-print" style="text-align: right;">
                                <a href="batal_jual.php?id=<?= $trx['id']; ?>" 
                                   class="btn btn-danger-outline btn-sm btn-confirm-delete" 
                                   data-confirm-msg="Batalkan transaksi ini? <?= $trx['jumlah_terjual']; ?> akun akan dikembalikan ke sisa stok.">
                                    Batalkan
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            Belum ada riwayat penjualan pada periode ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
