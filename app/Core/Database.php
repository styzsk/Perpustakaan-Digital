<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Koneksi PDO tunggal (singleton) untuk seluruh aplikasi.
 */
final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = require BASE_PATH . '/config/database.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $exception) {
            // Detail teknis hanya masuk log, tidak ditampilkan ke pengguna.
            app_log('Koneksi database gagal: ' . $exception->getMessage());
            throw new RuntimeException(
                'Aplikasi tidak dapat terhubung ke database. Periksa konfigurasi di config/database.php.',
                0,
                $exception
            );
        }

        return self::$pdo;
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
