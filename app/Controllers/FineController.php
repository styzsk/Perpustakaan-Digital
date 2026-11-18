<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Models\FineModel;
use App\Services\FineService;

final class FineController extends BaseController
{
    protected string $pageKey = 'fines';

    protected function actions(): array
    {
        return [
            'index' => 'index',
            'pay' => 'pay',
            'export' => 'export',
        ];
    }

    public function index(): void
    {
        $filters = [
            'q' => (string) input('q', ''),
            'status' => (string) input('status', 'all'),
        ];

        if (!in_array($filters['status'], ['all', 'unpaid', 'paid'], true)) {
            $filters['status'] = 'all';
        }

        $this->render('fines/index', [
            'pageTitle' => 'Denda',
            'filters' => $filters,
            'fines' => FineModel::paginate($filters, $this->pageNumber(), $this->perPage()),
            'stats' => FineModel::stats(),
            'payments' => FineModel::recentPayments(5),
            'tariff' => \App\Models\SettingsModel::tariff(),
        ]);
    }

    public function pay(): void
    {
        $this->guardCsrf();

        $id = id_input();
        if ($id <= 0) {
            flash('error', 'Data denda tidak valid.');
            redirect(url('fines'));
        }

        try {
            $result = FineService::pay($id);
            audit_trail(
                'pay',
                'denda',
                $id,
                'Pembayaran denda ' . $result['total'] . ' untuk ' . $result['nama']
            );
            flash(
                'success',
                'Denda ' . money($result['total']) . ' dari ' . $result['nama'] . ' ditandai lunas.'
            );
        } catch (AppException $exception) {
            flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            app_log('Gagal membayar denda #' . $id . ': ' . $exception->getMessage());
            flash('error', 'Pembayaran denda gagal diproses.');
        }

        redirect_back(url('fines'));
    }

    public function export(): void
    {
        $status = (string) input('status', 'all');
        $rows = [];

        foreach (FineModel::allForExport($status) as $fine) {
            $rows[] = [
                $fine['id_denda'],
                $fine['anggota_nama'],
                $fine['nim_nik'],
                $fine['judul'],
                $fine['tgl_kembali_real'],
                $fine['jumlah_hari'],
                $fine['tarif_per_hari'],
                $fine['total_denda'],
                $fine['status_bayar'] === 'lunas' ? 'Lunas' : 'Belum lunas',
                $fine['tgl_bayar'],
            ];
        }

        send_csv('bukuhub-denda-' . today() . '.csv', [
            'ID Denda', 'Anggota', 'NIM/NIK', 'Buku', 'Tanggal Kembali', 'Hari Terlambat',
            'Tarif/Hari', 'Total Denda', 'Status', 'Tanggal Bayar',
        ], $rows);
    }
}
