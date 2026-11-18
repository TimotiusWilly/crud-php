/**
 * MANAJEMEN STOK AKUN GAME - VANILLA JAVASCRIPT (SIMPLE & RINGAN)
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Sidebar Toggle untuk Tampilan Mobile
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (toggleBtn && sidebar && backdrop) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      backdrop.classList.toggle('show');
    });

    backdrop.addEventListener('click', function () {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }

  // 2. Auto-close flash alert setelah 5 detik
  const flashAlert = document.getElementById('flashAlert');
  if (flashAlert) {
    setTimeout(function () {
      flashAlert.style.transition = 'opacity 0.5s ease';
      flashAlert.style.opacity = '0';
      setTimeout(function () {
        flashAlert.remove();
      }, 500);
    }, 5000);
  }

  // 3. Konfirmasi Tombol Aksi (Hapus & Batal)
  const deleteButtons = document.querySelectorAll('.btn-confirm-delete');
  deleteButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      const msg = this.getAttribute('data-confirm-msg') || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });
});

// Format Rupiah di JS
function formatRupiahJS(angka) {
  const isNeg = angka < 0;
  const abs = Math.abs(Math.round(angka));
  const formatted = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return (isNeg ? '- Rp ' : 'Rp ') + formatted;
}
