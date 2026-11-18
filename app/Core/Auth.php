<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autentikasi pustakawan berbasis session PHP.
 */
final class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $user = \App\Models\UserModel::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            \App\Models\UserModel::updatePassword((int) $user['id_user'], $password);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id_user'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        \App\Models\UserModel::touchLogin((int) $user['id_user']);

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

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();
    }

    /**
     * Logout otomatis bila melewati batas waktu tidak aktif.
     */
    public static function enforceTimeout(): void
    {
        if (!self::check()) {
            return;
        }

        $lifetime = (int) config('session.lifetime', 1800);
        $last = (int) ($_SESSION['last_activity'] ?? 0);

        if ($last > 0 && (time() - $last) > $lifetime) {
            self::logout();
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            flash('warning', 'Session berakhir karena tidak ada aktivitas. Silakan masuk kembali.');
            return;
        }

        $_SESSION['last_activity'] = time();
    }
}
