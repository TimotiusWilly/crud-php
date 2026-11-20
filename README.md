# GameStock Pro - Sistem Manajemen Modal, Stok & Rekapan Laba Akun Game

Aplikasi pencatatan stok dan pembukuan laba-rugi bisnis sampingan jual-beli akun game berbasis **100% PHP Native** dan **100% Vanilla CSS** (khusus admin, simpel, cepat, dan to-the-point).

---

## Fokus Utama Sistem
Dirancang khusus untuk kebutuhan internal admin (bukan untuk buyer/katalog toko), berfokus pada 3 hal penting:
1. **Input Modal & Jumlah Akun:** Mencatat uang modal yang keluar dan berapa akun yang didapatkan (bisa beli ecer 1 akun maupun borongan/batch).
2. **Hitung Sisa Stok Real-time:** Mengetahui berapa sisa akun yang belum terjual dan berapa uang modal yang sedang nyangkut di stok.
3. **Pencatatan Penjualan & Rekapan Laba Bersih:** Saat akun laku, cukup masukkan nominal harga jual. Laba bersih langsung terhitung otomatis:
   $$\text{Laba Bersih} = \text{Harga Jual} - \text{Modal Akun Terjual}$$

Hal yang tidak diperlukan telah dihilangkan:
- Tanpa kategori game terpisah
- Tanpa kode SKU
- Tanpa biaya operasional terpisah
- Tanpa asal seller & deskripsi spek panjang
- Tanpa upload foto

---

## Fitur & Halaman

- **Dashboard & Input Stok Cepat (`index.php`):**
  - **4 Kartu Finansial:**
    * Modal Nyangkut di Stok: Total uang kas yang sedang tertahan pada akun yang belum laku.
    * Sisa Stok Akun: Total unit akun yang ready.
    * Akun Berhasil Terjual: Total unit akun yang sudah laku.
    * Total Laba Bersih: Akumulasi keuntungan riil yang sudah didapatkan.
  - **Formulir Cepat Tambah Stok:** Langsung input Nama Akun, Modal, dan Jumlah Akun tanpa perlu pindah halaman.
  - **Tabel Stok Akun:** Menampilkan modal per akun, sisa stok, catatan id/password (dengan tombol intip), dan tombol aksi cepat.
- **Catat Penjualan (`jual.php`):**
  - Form penjualan praktis dengan kalkulator live: ketik harga jual, laba bersih langsung muncul seketika sebelum disimpan. Sisa stok otomatis berkurang.
- **Rekapan Laba & Penjualan (`penjualan.php`):**
  - Riwayat lengkap transaksi penjualan akun beserta laba bersihnya.
  - Filter rentang tanggal (harian, bulanan, custom).
  - Tombol **Cetak / PDF** dan **Ekspor CSV (Excel)**.
  - Tombol **Batalkan Transaksi** jika ada salah input (jumlah akun otomatis kembali ke sisa stok).
- **Login Admin (`login.php`):**
  - Proteksi dashboard dengan autentikasi session PHP native dan hashing password bcrypt.

---

## Struktur File

```text
crud-manejemenstok/
├── config/
│   ├── database.php    # Koneksi MySQLi PHP Native & format rupiah
│   └── auth.php        # Proteksi session admin
├── assets/
│   ├── css/style.css   # 100% Vanilla CSS
│   └── js/main.js      # Vanilla JS interaktif
├── includes/
│   ├── header.php      # Header & sidebar simpel
│   ├── footer.php      # Footer & script
│   └── flash.php       # Alert notifikasi
├── index.php           # Dashboard, KPI & input stok akun
├── stok_form.php       # Form edit stok akun & modal
├── stok_hapus.php      # Hapus stok akun
├── jual.php            # Catat penjualan & otomatis kurangi stok
├── penjualan.php       # Rekapitulasi laba penjualan, filter & export
├── batal_jual.php      # Batalkan penjualan & kembalikan stok
├── login.php           # Login admin
├── logout.php          # Logout
└── database.sql        # Skema database MySQL (3 tabel sederhana)
```

---

## Cara Menjalankan di XAMPP

1. Buka browser Anda dan akses:
   ```
   http://localhost/crud-manejemenstok/
   ```
2. Login menggunakan:
   - **Username:** `admin`
   - **Password:** `password123`
