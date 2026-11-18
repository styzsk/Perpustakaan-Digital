<?php

declare(strict_types=1);

/* ==========================================================================
   Bootstrap BukuHub
   Titik awal konfigurasi: path, autoload, session, error handling, helper.
   ========================================================================== */

define('BASE_PATH', dirname(__DIR__));

// Helper harus dimuat sebelum config() dipakai di bawah.
require BASE_PATH . '/app/Helpers/functions.php';

$GLOBALS['app_config'] = require BASE_PATH . '/config/app.php';

date_default_timezone_set((string) config('timezone', 'Asia/Jakarta'));
mb_internal_encoding('UTF-8');

// Tampilkan error hanya saat development; detail teknis masuk ke log.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

if (!is_dir(BASE_PATH . '/storage/logs')) {
    @mkdir(BASE_PATH . '/storage/logs', 0775, true);
}

/**
 * Autoload sederhana: App\Core\Database => app/Core/Database.php
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

/* -------------------------------- Session -------------------------------- */

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_name((string) config('session.name', 'bukuhub_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Header keamanan dasar untuk semua response.
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/* ----------------------------- Error handling ---------------------------- */

set_exception_handler(static function (Throwable $exception): void {
    app_log(
        get_class($exception) . ': ' . $exception->getMessage()
        . ' in ' . $exception->getFile() . ':' . $exception->getLine()
    );

    if (!headers_sent()) {
        http_response_code(500);
    }

    $isDatabaseError = str_contains($exception->getMessage(), 'database');
    $message = $isDatabaseError
        ? 'Koneksi database gagal. Periksa MySQL dan konfigurasi config/database.php.'
        : 'Terjadi kesalahan internal. Detail teknis sudah dicatat di log aplikasi.';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    include BASE_PATH . '/views/errors/500.php';
});

/**
 * Halaman 404 sederhana.
 */
function abort_404(): void
{
    http_response_code(404);
    include BASE_PATH . '/views/errors/404.php';
    exit;
}

\App\Core\Auth::enforceTimeout();
