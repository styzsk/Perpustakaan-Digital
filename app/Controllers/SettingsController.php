<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Validator;
use App\Models\BookModel;
use App\Models\FineModel;
use App\Models\LoanModel;
use App\Models\MemberModel;
use App\Models\SettingsModel;
use App\Models\UserModel;

final class SettingsController extends BaseController
{
    protected string $pageKey = 'settings';

    public function index(): void
    {
        $this->render('settings/index', [
            'pageTitle' => 'Pengaturan',
            'settings' => SettingsModel::get(),
            'bookStats' => BookModel::stats(),
            'memberStats' => MemberModel::stats(),
            'loanStats' => LoanModel::stats(),
            'fineStats' => FineModel::stats(),
            'system' => $this->systemInfo(),
        ]);
    }

    public function update(): void
    {
        $this->guardCsrf();

        $data = [
            'nama_aplikasi' => (string) input('nama_aplikasi', ''),
            'nama_instansi' => (string) input('nama_instansi', ''),
            'tarif_denda' => (string) input('tarif_denda', ''),
            'durasi_hari' => (string) input('durasi_hari', ''),
            'maks_pinjam_default' => (string) input('maks_pinjam_default', ''),
            'email_kontak' => (string) input('email_kontak', ''),
            'alamat' => (string) input('alamat', ''),
        ];

        $validator = Validator::make($data)
            ->required('nama_aplikasi', 'Nama aplikasi')
            ->maxLength('nama_aplikasi', 'Nama aplikasi', 60)
            ->required('nama_instansi', 'Nama instansi')
            ->maxLength('nama_instansi', 'Nama instansi', 120)
            ->integer('tarif_denda', 'Tarif denda', 0, 100000)
            ->integer('durasi_hari', 'Durasi peminjaman', 1, 60)
            ->integer('maks_pinjam_default', 'Batas pinjam default', 1, 10)
            ->maxLength('email_kontak', 'Email kontak', 120)
            ->maxLength('alamat', 'Alamat', 255);

        if ($data['email_kontak'] !== '') {
            $validator->email('email_kontak', 'Email kontak');
        }

        if ($validator->fails()) {
            $this->failValidation($validator, $data);
        }

        SettingsModel::update($data);
        audit_trail('update', 'pengaturan', 1, 'Memperbarui pengaturan perpustakaan');
        flash('success', 'Pengaturan perpustakaan berhasil disimpan.');
        redirect(url('settings'));
    }

    private function systemInfo(): array
    {
        return [
            'php' => PHP_VERSION,
            'mysql' => (string) db_value('SELECT VERSION()'),
            'database' => (string) db_value('SELECT DATABASE()'),
            'timezone' => date_default_timezone_get(),
            'users' => UserModel::count(),
        ];
    }
}
