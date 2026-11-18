<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FineModel;
use App\Models\LoanModel;

/**
 * Notifikasi nyata berdasarkan data database (keterlambatan & denda).
 */
final class NotificationService
{
    public static function navCounts(): array
    {
        $loans = LoanModel::stats();
        $fines = FineModel::stats();

        return [
            'active_loans' => $loans['aktif'],
            'overdue' => $loans['terlambat'],
            'unpaid_fines' => $fines['jumlah_belum'],
        ];
    }

    public static function feed(int $limit = 5): array
    {
        $items = [];

        $overdueRows = LoanModel::paginate(['tab' => 'overdue'], 1, 3)['items'];
        foreach ($overdueRows as $loan) {
            $state = loan_state($loan);
            $items[] = [
                'tone' => 'orange',
                'icon' => 'clock',
                'title' => 'Terlambat ' . $state['late'] . ' hari',
                'message' => $loan['judul'] . ' · ' . $loan['anggota_nama'],
                'time' => format_date($loan['tgl_kembali_rencana']),
                'url' => url('borrowing', ['tab' => 'overdue']),
            ];
        }

        $fines = FineModel::paginate(['status' => 'unpaid'], 1, 3)['items'];
        foreach ($fines as $fine) {
            $items[] = [
                'tone' => 'dark',
                'icon' => 'wallet',
                'title' => 'Denda belum lunas',
                'message' => money($fine['total_denda']) . ' · ' . $fine['anggota_nama'],
                'time' => format_date($fine['tgl_kembali_real']),
                'url' => url('fines', ['status' => 'unpaid']),
            ];
        }

        return array_slice($items, 0, $limit);
    }
}
