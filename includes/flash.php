<?php
/**
 * Helper Notifikasi Flash Message
 * Menyimpan pesan sementara di session dan menampilkannya sekali baca.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'text' => $message
    ];
}

function render_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $type = htmlspecialchars($flash['type']);
        $text = htmlspecialchars($flash['text']);
        unset($_SESSION['flash_message']);

        echo "<div class='alert alert-{$type}' id='flashAlert'>
                <div class='alert-content'>
                    <span class='alert-icon'></span>
                    <span class='alert-text'>{$text}</span>
                </div>
                <button type='button' class='alert-close' onclick='this.parentElement.remove()'>&times;</button>
              </div>";
    }
}
