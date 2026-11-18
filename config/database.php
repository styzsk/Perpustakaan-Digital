<?php

declare(strict_types=1);

/**
 * Konfigurasi koneksi database (PDO).
 *
 * Nilai dapat diubah lewat environment variable tanpa mengedit file ini:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 *
 * Default mengikuti instalasi XAMPP/Laragon: user "root" tanpa password.
 */
$env = static function (string $key, string $default): string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
};

return [
    'host' => $env('DB_HOST', '127.0.0.1'),
    'port' => $env('DB_PORT', '3306'),
    'name' => $env('DB_NAME', 'bukuhub'),
    'user' => $env('DB_USER', 'root'),
    'pass' => $env('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
