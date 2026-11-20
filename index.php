<?php
/**
 * Dashboard Overview - Exact Fintech UI Layout
 * PHP Native
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/flash.php';

require_login();

// Handle Tambah Stok Akun Baru (diproses sebelum HTML dirender)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_stok') {
    $tglBeli = $_POST['tanggal_beli'] ?? date('Y-m-d');
    $namaAkun = trim($_POST['nama_akun'] ?? '');
    $modal = (float)($_POST['modal'] ?? 0);
    $jumlah = (int)($_POST['jumlah_total'] ?? 1);
    $catatanLogin = trim($_POST['catatan_login'] ?? '');

    if (empty($namaAkun)) {
        set_flash('error', 'Nama/keterangan akun tidak boleh kosong.');
    } elseif ($modal < 0) {
        set_flash('error', 'Modal tidak boleh bernilai negatif.');
    } elseif ($jumlah <= 0) {
        set_flash('error', 'Jumlah akun minimal 1.');
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO stok_akun (tanggal_beli, nama_akun, modal, jumlah_total, sisa_stok, catatan_login) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssdiis", $tglBeli, $namaAkun, $modal, $jumlah, $jumlah, $catatanLogin);
        if (mysqli_stmt_execute($stmt)) {
            set_flash('success', "Stok '$namaAkun' ($jumlah akun) berhasil ditambahkan dengan modal " . format_rupiah($modal) . ".");
        } else {
            set_flash('error', 'Gagal menambahkan stok: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: index.php");
    exit;
}

$pageTitle = 'Overview';
require_once __DIR__ . '/includes/header.php';

// 1. Hitung Modal Tertahan di Stok (Uang Nyangkut) & Sisa Akun Ready
$qModalNyangkut = mysqli_query($conn, "SELECT 
    SUM((modal / jumlah_total) * sisa_stok) AS modal_nyangkut,
    SUM(sisa_stok) AS total_sisa_akun 
    FROM stok_akun 
    WHERE sisa_stok > 0");
$dataModal = mysqli_fetch_assoc($qModalNyangkut);
$modalNyangkut = (float)($dataModal['modal_nyangkut'] ?? 0);
$totalSisaAkun = (int)($dataModal['total_sisa_akun'] ?? 0);

// 2. Hitung Total Laba Bersih & Total Akun Terjual dari Tabel Penjualan
$qLaba = mysqli_query($conn, "SELECT 
    SUM(jumlah_terjual) AS total_terjual,
    SUM(harga_jual) AS total_omzet,
    SUM(laba_bersih) AS total_laba 
    FROM penjualan");
$dataLaba = mysqli_fetch_assoc($qLaba);
$totalAkunTerjual = (int)($dataLaba['total_terjual'] ?? 0);
$totalOmzet = (float)($dataLaba['total_omzet'] ?? 0);
$totalLabaBersih = (float)($dataLaba['total_laba'] ?? 0);

// Filter status stok
$filter = $_GET['filter'] ?? 'semua';
$search = trim($_GET['search'] ?? '');

$where = [];
if ($filter === 'ready') {
    $where[] = "sisa_stok > 0";
} elseif ($filter === 'habis') {
    $where[] = "sisa_stok = 0";
}

if (!empty($search)) {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where[] = "(nama_akun LIKE '%$safeSearch%' OR catatan_login LIKE '%$safeSearch%')";
}

$whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$qStok = mysqli_query($conn, "SELECT * FROM stok_akun $whereSql ORDER BY sisa_stok > 0 DESC, id DESC");
?>

<!-- SECTION 1: TOP GRID (PORTFOLIO CARD + YOUR ASSETS PASTEL CARDS) -->
<div class="top-dashboard-grid">
    <!-- KIRI: PORTFOLIO BALANCE CARD -->
    <div>
        <div class="section-label-row">
            <span class="section-label">Portfolio</span>
        </div>
        
        <div class="portfolio-card">
            <div class="portfolio-card-header">
                <div>
                    <div class="portfolio-balance-amount"><?= format_rupiah($modalNyangkut); ?></div>
                    <div class="portfolio-balance-label">Modal Tertahan di Stok (Active Inventory)</div>
                </div>
                <div class="balance-target-tag">
                    <?= $totalSisaAkun; ?> Akun Ready
                </div>
            </div>

            <!-- Wave Graph Spline SVG persis di gambar -->
            <svg class="portfolio-wave-svg" viewBox="0 0 300 60" preserveAspectRatio="none">
                <path d="M0,45 C40,43 70,35 110,38 C150,42 190,20 220,25 C240,28 260,10 300,18" 
                      fill="none" stroke="#60a5fa" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="220" cy="25" r="4" fill="#3b82f6"/>
            </svg>

            <!-- Time Filter Range Pills -->
            <div class="time-range-pills">
                <span class="time-pill">1H</span>
                <span class="time-pill">24H</span>
                <span class="time-pill active">1W</span>
                <span class="time-pill">1M</span>
                <span class="time-pill">1Y</span>
                <span class="time-pill">ALL</span>
            </div>
        </div>
    </div>

    <!-- KANAN: YOUR ASSETS CARDS (3 KARTU PASTEL BERDAMPINGAN) -->
    <div>
        <div class="section-label-row">
            <span class="section-label">Your Assets</span>
            <span style="font-size: 0.82rem; color: var(--text-secondary); font-weight: 600;">Ringkasan</span>
        </div>

        <div class="assets-card-grid">
            <!-- 1. Lavender Card: Sisa Stok Akun -->
            <div class="asset-card asset-card-purple">
                <div class="asset-top-row">
                    <div>
                        <div class="asset-main-val"><?= $totalSisaAkun; ?> Unit</div>
                        <div class="asset-sub-val">Sisa Stok Ready</div>
                    </div>
                </div>

                <div class="asset-bottom-row">
                    <div class="asset-icon-pill">ST</div>
                    <div class="asset-status-pill">Ready</div>
                </div>
            </div>

            <!-- 2. Mint Green Card: Akun Terjual -->
            <div class="asset-card asset-card-green">
                <div class="asset-top-row">
                    <div>
                        <div class="asset-main-val"><?= $totalAkunTerjual; ?> Laku</div>
                        <div class="asset-sub-val">Omzet: <?= format_rupiah($totalOmzet); ?></div>
                    </div>
                </div>

                <div class="asset-bottom-row">
                    <div class="asset-icon-pill">SL</div>
                    <div class="asset-status-pill">Terjual</div>
                </div>
            </div>

            <!-- 3. Soft Yellow Card: Total Laba Bersih -->
            <div class="asset-card asset-card-yellow">
                <div class="asset-top-row">
                    <div>
                        <div class="asset-main-val"><?= format_rupiah($totalLabaBersih); ?></div>
                        <div class="asset-sub-val">Total Laba Bersih</div>
                    </div>
                </div>

                <div class="asset-bottom-row">
                    <div class="asset-icon-pill">PF</div>
                    <div class="asset-status-pill">Profit</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 2: BOTTOM GRID (TABLE + DARK PROMO/ACTION CARD) -->
<div class="bottom-dashboard-grid">
    <!-- KIRI: TABEL DAFTAR STOK AKUN -->
    <div class="table-section-card">
        <div class="table-top-actions">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 800; letter-spacing: -0.01em;">Daftar Stok Akun</h3>
                <span style="font-size: 0.8rem; color: var(--text-secondary);">Inventaris aktif & riwayat modal</span>
            </div>

            <!-- Filter Pills Tabs persis di gambar -->
            <div class="table-filter-pills">
                <a href="index.php?filter=semua" class="filter-chip <?= ($filter === 'semua') ? 'active' : ''; ?>">Semua</a>
                <a href="index.php?filter=ready" class="filter-chip <?= ($filter === 'ready') ? 'active' : ''; ?>">Ready</a>
                <a href="index.php?filter=habis" class="filter-chip <?= ($filter === 'habis') ? 'active' : ''; ?>">Habis</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Akun</th>
                        <th>Total Modal</th>
                        <th>Sisa Unit</th>
                        <th>Modal/Unit</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($qStok) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($qStok)): 
                            $modalPerAkun = ($s['jumlah_total'] > 0) ? ($s['modal'] / $s['jumlah_total']) : $s['modal'];
                            $isReady = $s['sisa_stok'] > 0;
                            $inisial = strtoupper(substr($s['nama_akun'], 0, 2));
                        ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="account-avatar-sq"><?= $inisial; ?></div>
                                        <div>
                                            <strong style="display: block; font-size: 0.92rem; color: var(--text-primary);"><?= clean($s['nama_akun']); ?></strong>
                                            <span style="font-size: 0.78rem; color: var(--text-secondary);"><?= format_tanggal($s['tanggal_beli']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= format_rupiah($s['modal']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $isReady ? 'badge-tersedia' : 'badge-habis'; ?>">
                                        <?= (int)$s['sisa_stok']; ?> / <?= (int)$s['jumlah_total']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color: var(--text-secondary); font-size: 0.85rem;">
                                        <?= format_rupiah($modalPerAkun); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="btn-group">
                                        <?php if ($isReady): ?>
                                            <a href="jual.php?id=<?= $s['id']; ?>" class="btn btn-dark btn-sm" title="Jual Akun">
                                                Jual
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($s['catatan_login'])): ?>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="alert('Catatan Akun:\n\n<?= addslashes(clean($s['catatan_login'])); ?>')">
                                                Intip
                                            </button>
                                        <?php endif; ?>

                                        <a href="stok_form.php?id=<?= $s['id']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                            Edit
                                        </a>

                                        <a href="stok_hapus.php?id=<?= $s['id']; ?>" 
                                           class="btn btn-danger-outline btn-sm btn-confirm-delete" 
                                           data-confirm-msg="Yakin ingin menghapus stok '<?= clean($s['nama_akun']); ?>'?" 
                                           title="Hapus">
                                            Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                Belum ada data stok akun. Silakan tambah lewat form di sebelah kanan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- KANAN: MATTE DARK ACTION CARD (Persis Card "Earn Free Crypto" di Gambar) -->
    <div class="action-dark-card" id="formInputStok">
        <h3 class="action-dark-title">Tambah Stok Akun Baru</h3>
        <p class="action-dark-desc">
            Input modal pembelian dan jumlah akun yang Anda beli untuk mengupdate inventaris secara instan.
        </p>

        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="tambah_stok">

            <div class="form-group-dark">
                <label class="form-label-dark">Nama / Jenis Akun</label>
                <input type="text" name="nama_akun" class="form-control-dark" placeholder="Contoh: Akun MLBB, Valorant" required>
            </div>

            <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 10px; margin-bottom: 14px;">
                <div>
                    <label class="form-label-dark">Modal Beli (Rp)</label>
                    <input type="number" name="modal" class="form-control-dark" placeholder="0" min="0" step="1000" required>
                </div>
                <div>
                    <label class="form-label-dark">Jumlah</label>
                    <input type="number" name="jumlah_total" class="form-control-dark" value="1" min="1" required>
                </div>
            </div>

            <div class="form-group-dark">
                <label class="form-label-dark">Tanggal Pembelian</label>
                <input type="date" name="tanggal_beli" class="form-control-dark" value="<?= date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group-dark" style="margin-bottom: 22px;">
                <label class="form-label-dark">Catatan / Kredensial Login (Opsional)</label>
                <input type="text" name="catatan_login" class="form-control-dark" placeholder="Email / ID / Pass">
            </div>

            <button type="submit" class="btn-white-pill" style="width: 100%;">
                Simpan Stok
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
