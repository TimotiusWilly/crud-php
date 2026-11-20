<?php
/**
 * Halaman Login Admin
 * PHP Native
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/flash.php';

// Jika sudah login, langsung ke dashboard
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Silakan isi username dan password Anda.';
    } else {
        // Query user dengan prepared statement
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, nama_lengkap FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Verifikasi password hash
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

                set_flash('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');
                header("Location: index.php");
                exit;
            } else {
                $error = 'Password yang Anda masukkan salah.';
            }
        } else {
            $error = 'Username tidak ditemukan.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Manajemen Stok Akun Game</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo" style="background-color: var(--logo-cream); color: var(--logo-cream-text);">GS</div>
            <h1 class="login-title">GameStock Pro</h1>
            <p class="login-subtitle">Sistem Manajemen Stok & Laba Jual Beli Akun Game</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <div class="alert-content">
                    <span><?= clean($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php render_flash(); ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus value="<?= clean($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-dark" style="width: 100%; padding: 12px; border-radius: var(--radius-pill); font-size: 0.95rem;">
                    Masuk ke Sistem
                </button>
            </div>

            <div style="margin-top: 20px; text-align: center; font-size: 0.8rem; color: #64748b; background: #f8fafc; padding: 10px; border-radius: 6px;">
                <span>Default Login:</span><br>
                <strong>Username:</strong> <code>admin</code> &nbsp;|&nbsp; <strong>Password:</strong> <code>password123</code>
            </div>
        </form>
    </div>
</body>
</html>
