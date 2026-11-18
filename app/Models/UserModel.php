<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Akses data tabel `users` (akun pengelola aplikasi).
 */
final class UserModel
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1'
        );
        $stmt->execute(['username' => $username, 'email' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function touchLogin(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET last_login_at = NOW() WHERE id_user = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function updatePassword(int $id, string $plainPassword): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET password_hash = :hash WHERE id_user = :id');
        $stmt->execute([
            'hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }

    public static function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
