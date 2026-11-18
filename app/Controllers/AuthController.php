<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Validator;

final class AuthController extends BaseController
{
    protected string $pageKey = 'login';

    protected function actions(): array
    {
        return [
            'index' => 'showLogin',
            'login' => 'login',
            'logout' => 'logout',
        ];
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(url('dashboard'));
        }

        $this->render('auth/login', [
            'pageTitle' => 'Masuk',
        ], 'auth');
    }

    public function login(): void
    {
        $this->requirePost();

        if (!verify_csrf()) {
            flash('error', 'Sesi login kedaluwarsa. Silakan coba lagi.');
            redirect(url('login'));
        }

        // Pembatasan percobaan login: maksimal 5 kali per 5 menit.
        $now = time();
        $attempts = $_SESSION['login_attempts'] ?? [];
        $attempts = array_values(array_filter($attempts, static fn ($time) => ($now - (int) $time) < 300));

        if (count($attempts) >= 5) {
            flash('error', 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.');
            redirect(url('login'));
        }

        $username = (string) input('username', '');
        $password = (string) ($_POST['password'] ?? '');

        $validator = Validator::make(['username' => $username, 'password' => $password])
            ->required('username', 'Username')
            ->required('password', 'Password')
            ->maxLength('username', 'Username', 60);

        if ($validator->fails()) {
            remember_form_errors($validator->errors());
            remember_old_input(['username' => $username]);
            flash('error', $validator->firstError());
            redirect(url('login'));
        }

        if (!Auth::attempt($username, $password)) {
            $attempts[] = $now;
            $_SESSION['login_attempts'] = $attempts;

            remember_old_input(['username' => $username]);
            flash('error', 'Username atau password salah.');
            redirect(url('login'));
        }

        unset($_SESSION['login_attempts']);
        audit_trail('login', 'users', (int) ($_SESSION['user_id'] ?? 0), 'Login berhasil sebagai ' . ($_SESSION['user_username'] ?? $username));
        flash('success', 'Selamat datang kembali, ' . ($_SESSION['user_nama'] ?? 'Pustakawan') . '.');
        redirect(url('dashboard'));
    }

    public function logout(): void
    {
        $this->requirePost();

        if (!verify_csrf()) {
            flash('error', 'Permintaan keluar tidak valid.');
            redirect(url('dashboard'));
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        audit_trail('logout', 'users', $userId > 0 ? $userId : null, 'Pengguna keluar dari aplikasi', $userId > 0 ? $userId : null);

        Auth::logout();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        flash('success', 'Anda telah keluar dari BukuHub.');
        redirect(url('login'));
    }
}
