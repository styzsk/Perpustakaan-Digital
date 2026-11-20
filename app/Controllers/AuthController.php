<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Validator;

final class AuthController extends BaseController
{
    protected string $pageKey = 'login';

    public function index(): void
    {
        if (Auth::check()) {
            redirect(url('dashboard'));
        }

        $this->render('auth/login', ['pageTitle' => 'Masuk'], 'auth');
    }

    public function login(): void
    {
        $this->requirePost();

        if (!verify_csrf()) {
            flash('error', 'Sesi login kedaluwarsa. Silakan coba lagi.');
            redirect(url('login'));
        }

        // Maksimal 5 percobaan dalam 5 menit.
        $resetAt = (int) ($_SESSION['login_reset_at'] ?? 0);
        if ($resetAt < time()) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_reset_at'] = time() + 300;
        }

        if ((int) ($_SESSION['login_attempts'] ?? 0) >= 5) {
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
            $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
            remember_old_input(['username' => $username]);
            flash('error', 'Username atau password salah.');
            redirect(url('login'));
        }

        unset($_SESSION['login_attempts'], $_SESSION['login_reset_at']);
        audit_trail('login', 'users', (int) $_SESSION['user_id'], 'Login berhasil');
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

        audit_trail('logout', 'users', (int) ($_SESSION['user_id'] ?? 0), 'Pengguna keluar');
        Auth::logout();
        session_start();
        flash('success', 'Anda telah keluar dari BukuHub.');
        redirect(url('login'));
    }
}
