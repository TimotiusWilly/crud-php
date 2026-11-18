<?php
/**
 * Rekapitulasi Penjualan & Laba Bersih (Simple & Praktis)
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

<div class="print-header">
    <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;">LAPORAN REKAPITULASI LABA PENJUALAN AKUN GAME</h2>
    <p style="font-size: 0.9rem; color: #475569;">
        Periode: <?= !empty($tglMulai) ? format_tanggal($tglMulai) : 'Awal'; ?> s/d <?= !empty($tglSelesai) ? format_tanggal($tglSelesai) : 'Sekarang'; ?>
        &bull; Dicetak: <?= date('d M Y H:i'); ?>
    </p>
</div>

<!-- FILTER RENTANG TANGGAL & EXPORT -->
<div class="filter-bar no-print">
    <form action="penjualan.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; width: 100%;">
        <div class="filter-group">
            <label for="tgl_mulai" style="font-size: 0.85rem; font-weight: 600;">Dari:</label>
            <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control" value="<?= clean($tglMulai); ?>">
        </div>

        <div class="filter-group">
            <label for="tgl_selesai" style="font-size: 0.85rem; font-weight: 600;">Sampai:</label>
            <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control" value="<?= clean($tglSelesai); ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-sm">
            <span>🔍 Terapkan Filter</span>
        </button>

        <?php if (!empty($tglMulai) || !empty($tglSelesai)): ?>
            <a href="penjualan.php" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>

        <div style="margin-left: auto; display: flex; gap: 8px;">
            <button type="button" onclick="window.print()" class="btn btn-outline btn-sm">
                <span>🖨️ Cetak / PDF</span>
            </button>
            <a href="penjualan.php?export=csv<?= !empty($tglMulai) ? '&tgl_mulai='.$tglMulai : ''; ?><?= !empty($tglSelesai) ? '&tgl_selesai='.$tglSelesai : ''; ?>" class="btn btn-success btn-sm">
                <span>📥 Unduh CSV</span>
            </a>
        </div>
    </form>
</div>

<!-- RINGKASAN FINANSIAL PENJUALAN -->
<div class="stats-grid">
    <div class="stat-card card-info">
        <div class="stat-header">
            <span class="stat-title">Omzet Diterima</span>
            <span class="stat-icon icon-info">💵</span>
        </div>
        <div class="stat-value"><?= format_rupiah($totOmzet); ?></div>
        <div class="stat-caption">Dari penjualan <strong><?= $totUnit; ?> unit akun</strong>.</div>
    </div>

    <div class="stat-card card-warning">
        <div class="stat-header">
            <span class="stat-title">Modal Akun Terjual</span>
            <span class="stat-icon icon-warning">💼</span>
        </div>
        <div class="stat-value"><?= format_rupiah($totModal); ?></div>
        <div class="stat-caption">Total modal yang sudah balik kas.</div>
    </div>

    <div class="stat-card card-success">
        <div class="stat-header">
            <span class="stat-title">Total Laba Bersih</span>
            <span class="stat-icon icon-success">📈</span>
        </div>
        <div class="stat-value <?= $totLaba >= 0 ? 'text-profit' : 'text-loss'; ?>">
            <?= format_rupiah($totLaba); ?>
        </div>
        <div class="stat-caption">Margin Keuntungan: <strong><?= ($marginRoi > 0 ? '+' : '') . $marginRoi; ?>%</strong></div>
    </div>
</div>

<!-- TABEL RIWAYAT PENJUALAN & LABA -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span>📋 Riwayat Transaksi Penjualan & Laba</span>
        </h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Jual</th>
                        <th>Nama Akun</th>
                        <th>Jml Terjual</th>
                        <th>Modal Pokok</th>
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
                                <td>
                                    <strong><?= clean($trx['nama_akun']); ?></strong>
                                </td>
                                <td><strong><?= (int)$trx['jumlah_terjual']; ?> akun</strong></td>
                                <td><?= format_rupiah($trx['modal_terjual']); ?></td>
                                <td><?= format_rupiah($trx['harga_jual']); ?></td>
                                <td>
                                    <strong class="<?= $laba >= 0 ? 'text-profit' : 'text-loss'; ?>" style="font-size: 1rem;">
                                        <?= format_rupiah($laba); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span style="color: #64748b; font-size: 0.85rem;">
                                        <?= !empty($trx['catatan']) ? clean($trx['catatan']) : '-'; ?>
                                    </span>
                                </td>
                                <td class="no-print" style="text-align: right;">
                                    <a href="batal_jual.php?id=<?= $trx['id']; ?>" 
                                       class="btn btn-outline btn-sm btn-confirm-delete" 
                                       data-confirm-msg="Batalkan transaksi ini? <?= $trx['jumlah_terjual']; ?> akun akan dikembalikan ke sisa stok."
                                       style="color: var(--danger); border-color: rgba(239, 68, 68, 0.3);" 
                                       title="Batalkan Transaksi">
                                        Batalkan
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <div class="empty-icon">📊</div>
                                <div class="empty-title">Belum ada catatan penjualan</div>
                                <div class="empty-text">Saat akun terjual di menu stok, data laba bersih akan langsung otomatis tercatat di sini.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
