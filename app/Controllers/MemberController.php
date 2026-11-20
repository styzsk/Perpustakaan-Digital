<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Validator;
use App\Models\FineModel;
use App\Models\MemberModel;
use App\Models\SettingsModel;

final class MemberController extends BaseController
{
    protected string $pageKey = 'members';

    public function index(): void
    {
        $filters = [
            'q' => (string) input('q', ''),
            'filter' => (string) input('filter', ''),
            'status_anggota' => (string) input('status_anggota', ''),
            'sort' => (string) input('sort', 'nama'),
            'dir' => (string) input('dir', 'asc'),
        ];

        $this->render('members/index', [
            'pageTitle' => 'Anggota',
            'filters' => $filters,
            'members' => MemberModel::paginate($filters, $this->pageNumber(), $this->perPage()),
            'stats' => MemberModel::stats(),
            'defaultLimit' => SettingsModel::defaultLimit(),
            'showMemberModal' => true,
        ]);
    }

    public function show(): void
    {
        $id = id_input();
        $member = $id > 0 ? MemberModel::find($id) : null;

        if (!$member) {
            flash('error', 'Anggota tidak ditemukan.');
            redirect(url('members'));
        }

        $this->render('members/show', [
            'pageTitle' => 'Detail Anggota',
            'member' => $member,
            'loans' => MemberModel::loansFor($id),
            'unpaidFines' => FineModel::unpaidTotalForMember($id),
            'defaultLimit' => SettingsModel::defaultLimit(),
            'showMemberModal' => true,
        ]);
    }

    public function save(): void
    {
        $this->guardCsrf();

        $id = id_input();
        $existing = $id > 0 ? MemberModel::find($id) : null;

        if ($id > 0 && !$existing) {
            flash('error', 'Anggota yang akan diubah tidak ditemukan.');
            redirect(url('members'));
        }

        $data = [
            'nama' => (string) input('nama', ''),
            'nim_nik' => (string) input('nim_nik', ''),
            'email' => (string) input('email', ''),
            'telepon' => (string) input('telepon', ''),
            'alamat' => (string) input('alamat', ''),
            'maks_pinjam' => (string) input('maks_pinjam', (string) SettingsModel::defaultLimit()),
            'status' => (string) input('status', 'aktif'),
        ];

        $validator = $this->validate($data, $id);
        if ($validator->fails()) {
            $this->failValidation($validator, $data + ['id' => $id], 'member');
        }

        try {
            if ($existing) {
                MemberModel::update($id, $data);
                audit_trail('update', 'anggota', $id, 'Mengubah anggota: ' . $data['nama']);
                flash('success', 'Data anggota "' . $data['nama'] . '" tersimpan.');
            } else {
                $id = MemberModel::create($data);
                audit_trail('create', 'anggota', $id, 'Menambah anggota: ' . $data['nama']);
                flash('success', 'Anggota "' . $data['nama'] . '" berhasil ditambahkan.');
            }
        } catch (\Throwable $exception) {
            app_log('Gagal menyimpan anggota: ' . $exception->getMessage());
            flash('error', 'Data anggota gagal disimpan. Pastikan NIM/NIK dan email belum dipakai.');
            redirect(url('members'));
        }

        redirect(url('members', ['action' => 'show', 'id' => $id]));
    }

    public function delete(): void
    {
        $this->guardCsrf();

        $id = id_input();
        $member = $id > 0 ? MemberModel::find($id) : null;
        if (!$member) {
            flash('error', 'Anggota tidak ditemukan.');
            redirect(url('members'));
        }

        if (MemberModel::hasLoans($id)) {
            flash('error', 'Anggota "' . $member['nama'] . '" tidak dapat dihapus karena memiliki riwayat peminjaman.');
            redirect(url('members'));
        }

        try {
            MemberModel::delete($id);
        } catch (\Throwable $exception) {
            app_log('Gagal menghapus anggota #' . $id . ': ' . $exception->getMessage());
            flash('error', 'Anggota "' . $member['nama'] . '" tidak dapat dihapus. Periksa relasi datanya.');
            redirect(url('members'));
        }

        audit_trail('delete', 'anggota', $id, 'Menghapus anggota: ' . $member['nama']);
        flash('success', 'Anggota "' . $member['nama'] . '" dihapus.');
        redirect(url('members'));
    }

    public function export(): void
    {
        $rows = [];
        foreach (MemberModel::allForExport() as $member) {
            $rows[] = [
                $member['id_anggota'],
                $member['nama'],
                $member['nim_nik'],
                $member['email'],
                $member['telepon'],
                $member['status'],
                $member['maks_pinjam'],
                (int) $member['active_loans'],
                (int) $member['total_loans'],
                (int) $member['unpaid_fines'],
            ];
        }

        send_csv('bukuhub-anggota-' . today() . '.csv', [
            'ID', 'Nama', 'NIM/NIK', 'Email', 'Telepon', 'Status', 'Batas Pinjam',
            'Pinjaman Aktif', 'Total Pinjaman', 'Denda Belum Lunas',
        ], $rows);
    }

    private function validate(array $data, int $id): Validator
    {
        $validator = Validator::make($data)
            ->required('nama', 'Nama')
            ->maxLength('nama', 'Nama', 100)
            ->required('nim_nik', 'NIM/NIK')
            ->maxLength('nim_nik', 'NIM/NIK', 30)
            ->required('email', 'Email')
            ->maxLength('email', 'Email', 120)
            ->email('email', 'Email')
            ->maxLength('telepon', 'Telepon', 20)
            ->maxLength('alamat', 'Alamat', 255)
            ->integer('maks_pinjam', 'Batas pinjam', 1, 10)
            ->inList('status', 'Status', ['aktif', 'nonaktif']);

        if ($data['nim_nik'] !== '' && MemberModel::existsNim($data['nim_nik'], $id)) {
            $validator->addError('nim_nik', 'NIM/NIK sudah digunakan anggota lain.');
        }

        if ($data['email'] !== '' && MemberModel::existsEmail($data['email'], $id)) {
            $validator->addError('email', 'Email sudah digunakan anggota lain.');
        }

        return $validator;
    }
}
