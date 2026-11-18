<?php
/**
 * Dashboard & Manajemen Stok Akun Game (Simple & Praktis)
 * PHP Native
 */
$pageTitle = 'Stok & Modal Akun Game';
require_once __DIR__ . '/includes/header.php';

// Handle Tambah Stok Akun Baru
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
            set_flash('success', "Stok '$namaAkun' ($jumlah akun) berhasil ditambahkan dengan modal " . format_rupiah($modal) . "!");
        } else {
            set_flash('error', 'Gagal menambahkan stok: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: index.php");
    exit;
}

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

<!-- 4 KARTU RINGKASAN FINANSIAL (KPI) -->
<div class="stats-grid">
    <div class="stat-card card-warning">
        <div class="stat-header">
            <span class="stat-title">Modal Nyangkut di Stok</span>
            <span class="stat-icon icon-warning">💼</span>
        </div>
        <div class="stat-value"><?= format_rupiah($modalNyangkut); ?></div>
        <div class="stat-caption">Uang modal pada <strong><?= $totalSisaAkun; ?> akun</strong> yang belum laku.</div>
    </div>

    <div class="stat-card card-primary">
        <div class="stat-header">
            <span class="stat-title">Sisa Stok Akun</span>
            <span class="stat-icon icon-primary">📦</span>
        </div>
        <div class="stat-value"><?= $totalSisaAkun; ?> <span style="font-size: 1rem; color: var(--text-muted);">Akun Ready</span></div>
        <div class="stat-caption">Total unit akun game yang siap dijual.</div>
    </div>

    <div class="stat-card card-info">
        <div class="stat-header">
            <span class="stat-title">Akun Berhasil Terjual</span>
            <span class="stat-icon icon-info">🤝</span>
        </div>
        <div class="stat-value"><?= $totalAkunTerjual; ?> <span style="font-size: 1rem; color: var(--text-muted);">Akun Laku</span></div>
        <div class="stat-caption">Total omzet penjualan: <strong><?= format_rupiah($totalOmzet); ?></strong></div>
    </div>

    <div class="stat-card card-success">
        <div class="stat-header">
            <span class="stat-title">Total Laba Bersih</span>
            <span class="stat-icon icon-success">📈</span>
        </div>
        <div class="stat-value <?= $totalLabaBersih >= 0 ? 'text-profit' : 'text-loss'; ?>">
            <?= format_rupiah($totalLabaBersih); ?>
        </div>
        <div class="stat-caption">Keuntungan bersih riil setelah dipotong modal.</div>
    </div>
</div>

<!-- FORM CEPAT INPUT MODAL & STOK AKUN -->
<div class="card" id="formInputStok" style="border-left: 4px solid var(--primary);">
    <div class="card-header">
        <h3 class="card-title">
            <span>➕ Input Modal & Stok Akun Baru</span>
        </h3>
    </div>
    <div class="card-body">
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="tambah_stok">

            <div class="form-row">
                <div class="form-group" style="flex: 1.5;">
                    <label for="nama_akun" class="form-label">
                        Nama / Keterangan Akun <span class="required">*</span>
                    </label>
                    <input type="text" name="nama_akun" id="nama_akun" class="form-control" 
                           placeholder="Contoh: Akun MLBB Mythic, Akun Valorant, atau Stok FF Borongan" required autofocus>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="modal" class="form-label">
                        Total Modal Beli (Rp) <span class="required">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-addon">Rp</span>
                        <input type="number" name="modal" id="modal" class="form-control" 
                               placeholder="0" min="0" step="1000" required>
                    </div>
                </div>

                <div class="form-group" style="flex: 0.8;">
                    <label for="jumlah_total" class="form-label">
                        Jumlah Akun <span class="required">*</span>
                    </label>
                    <input type="number" name="jumlah_total" id="jumlah_total" class="form-control" 
                           value="1" min="1" required>
                    <div class="form-help">Berapa akun yang dibeli</div>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="tanggal_beli" class="form-label">Tanggal Beli</label>
                    <input type="date" name="tanggal_beli" id="tanggal_beli" class="form-control" 
                           value="<?= date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="catatan_login" class="form-label">
                    Catatan Login / ID & Password (Opsional)
                </label>
                <input type="text" name="catatan_login" id="catatan_login" class="form-control" 
                       placeholder="Contoh: Email: ... | Pass: ... (disimpan rahasia untuk admin)">
            </div>

            <div style="text-align: right;">
                <button type="submit" class="btn btn-primary">
                    <span>💾 Simpan Modal & Stok</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FILTER & PENCARIAN STOK -->
<div class="filter-bar">
    <form action="index.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; width: 100%;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama akun atau catatan..." value="<?= clean($search); ?>">
        </div>

        <div style="min-width: 170px;">
            <select name="filter" class="form-select" onchange="this.form.submit()">
                <option value="semua" <?= ($filter === 'semua') ? 'selected' : ''; ?>>Semua Stok</option>
                <option value="ready" <?= ($filter === 'ready') ? 'selected' : ''; ?>>Masih Ada Stok (Ready)</option>
                <option value="habis" <?= ($filter === 'habis') ? 'selected' : ''; ?>>Sudah Habis Terjual</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm">
            <span>🔍 Cari</span>
        </button>

        <?php if (!empty($search) || $filter !== 'semua'): ?>
            <a href="index.php" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- TABEL DAFTAR STOK AKUN -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span>📋 Daftar Stok Akun & Modal</span>
        </h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Tgl Beli</th>
                        <th>Nama / Keterangan Akun</th>
                        <th>Total Modal</th>
                        <th>Jml Awal</th>
                        <th>Sisa Stok</th>
                        <th>Modal/Akun</th>
                        <th>Catatan / Kredensial</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($qStok) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($qStok)): 
                            $modalPerAkun = ($s['jumlah_total'] > 0) ? ($s['modal'] / $s['jumlah_total']) : $s['modal'];
                            $isReady = $s['sisa_stok'] > 0;
                        ?>
                            <tr>
                                <td><?= format_tanggal($s['tanggal_beli']); ?></td>
                                <td>
                                    <strong style="font-size: 0.95rem; color: var(--text-main);"><?= clean($s['nama_akun']); ?></strong>
                                </td>
                                <td><?= format_rupiah($s['modal']); ?></td>
                                <td><?= (int)$s['jumlah_total']; ?> akun</td>
                                <td>
                                    <strong style="font-size: 1rem; color: <?= $isReady ? 'var(--success-text)' : 'var(--text-muted)'; ?>;">
                                        <?= (int)$s['sisa_stok']; ?> akun
                                    </strong>
                                </td>
                                <td>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">
                                        <?= format_rupiah($modalPerAkun); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($s['catatan_login'])): ?>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="alert('Catatan Akun:\n\n<?= addslashes(clean($s['catatan_login'])); ?>')">
                                            👁️ Intip
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isReady): ?>
                                        <span class="badge badge-tersedia">READY (<?= $s['sisa_stok']; ?>)</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #f1f5f9; color: #64748b;">HABIS TERJUAL</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="btn-group">
                                        <?php if ($isReady): ?>
                                            <a href="jual.php?id=<?= $s['id']; ?>" class="btn btn-success btn-sm" title="Catat Penjualan">
                                                💰 Jual
                                            </a>
                                        <?php endif; ?>

                                        <a href="stok_form.php?id=<?= $s['id']; ?>" class="btn btn-outline btn-sm" title="Edit Stok">
                                            ✏️
                                        </a>

                                        <a href="stok_hapus.php?id=<?= $s['id']; ?>" 
                                           class="btn btn-outline btn-sm btn-confirm-delete" 
                                           data-confirm-msg="Yakin ingin menghapus stok '<?= clean($s['nama_akun']); ?>'? Semua data riwayat penjualan akun ini juga akan terhapus." 
                                           style="color: var(--danger); border-color: rgba(239, 68, 68, 0.3);" 
                                           title="Hapus Stok">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <div class="empty-icon">📦</div>
                                <div class="empty-title">Belum ada data stok akun</div>
                                <div class="empty-text">Gunakan form di atas untuk menginput modal dan jumlah akun baru Anda.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
