<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Query tabel `users`.
 */
final class UserModel
{
    public static function findByUsername(string $username): ?array
    {
        return db_one(
            'SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1',
            ['username' => $username, 'email' => $username]
        );
    }

    public static function touchLogin(int $id): void
    {
        db_run('UPDATE users SET last_login_at = NOW() WHERE id_user = :id', ['id' => $id]);
    }

    public static function count(): int
    {
        return (int) db_value('SELECT COUNT(*) FROM users');
    }
}
