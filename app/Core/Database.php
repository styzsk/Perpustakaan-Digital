<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Koneksi PDO tunggal untuk seluruh aplikasi.
 * File config/database.php mengembalikan variabel $conn.
 */
final class Database
{
    private static ?PDO $conn = null;

    public static function pdo(): PDO
    {
        if (self::$conn === null) {
            self::$conn = require BASE_PATH . '/config/database.php';
        }

        return self::$conn;
    }

    /**
     * Jalankan beberapa query sebagai satu transaksi.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
