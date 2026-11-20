<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\UserModel;

/**
 * Autentikasi pustakawan berbasis session.
 */
final class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $user = UserModel::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id_user'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        UserModel::touchLogin((int) $user['id_user']);

        return true;
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id_user' => (int) $_SESSION['user_id'],
            'nama' => $_SESSION['user_nama'] ?? 'Pustakawan',
            'username' => $_SESSION['user_username'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'pustakawan',
        ];
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('warning', 'Silakan masuk untuk mengakses BukuHub.');
            redirect(url('login'));
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    /** Logout otomatis setelah batas waktu tidak aktif. */
    public static function enforceTimeout(): void
    {
        if (!self::check()) {
            return;
        }

        $last = (int) ($_SESSION['last_activity'] ?? 0);

        if ($last > 0 && (time() - $last) > (int) config('session.lifetime', 1800)) {
            self::logout();
            session_start();
            flash('warning', 'Session berakhir karena tidak ada aktivitas. Silakan masuk kembali.');
            return;
        }

        $_SESSION['last_activity'] = time();
    }
}
