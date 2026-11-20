<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LoanModel;
use App\Models\ReportModel;

final class ReportController extends BaseController
{
    protected string $pageKey = 'reports';

    public function index(): void
    {
        $period = ReportModel::period();

        $this->render('reports/index', [
            'pageTitle' => 'Laporan & Analitik',
            'period' => $period,
            'summary' => ReportModel::summary($period['from'], $period['to']),
            'series' => ReportModel::monthlySeries(12),
            'topBooks' => LoanModel::topBooks($period['from'], $period['to'], 5),
            'categories' => ReportModel::categoryStats($period['from'], $period['to']),
            'memberActivity' => ReportModel::memberActivity($period['from'], $period['to'], 10),
            'fines' => ReportModel::finesSummary(),
        ]);
    }

    public function export(): void
    {
        $period = ReportModel::period();
        $summary = ReportModel::summary($period['from'], $period['to']);
        $series = ReportModel::monthlySeries(12);
        $topBooks = LoanModel::topBooks($period['from'], $period['to'], 10);
        $categories = ReportModel::categoryStats($period['from'], $period['to']);
        $members = ReportModel::memberActivity($period['from'], $period['to'], 20);
        $fines = ReportModel::finesSummary();

        $rows = [
            ['Laporan BukuHub'],
            ['Periode', $period['from'] . ' s/d ' . $period['to']],
            ['Dibuat', date('Y-m-d H:i')],
            [],
            ['Ringkasan', 'Nilai'],
            ['Total transaksi', $summary['total']],
            ['Peminjaman pada periode', $summary['dipinjam']],
            ['Pengembalian pada periode', $summary['dikembalikan']],
            ['Peminjaman aktif', $summary['aktif']],
            ['Terlambat saat ini', $summary['terlambat']],
            ['Denda tercatat (periode)', $summary['denda_total']],
            ['Denda dibayar', $summary['denda_dibayar']],
            ['Denda belum dibayar', $summary['denda_belum']],
            [],
            ['Bulan', 'Peminjaman', 'Tahun sebelumnya'],
        ];

        foreach ($series as $month) {
            $rows[] = [$month['label'] . ' ' . $month['year'], $month['current'], $month['previous']];
        }

        $rows[] = [];
        $rows[] = ['Buku Terpopuler', 'Pengarang', 'Peminjaman'];
        foreach ($topBooks as $book) {
            $rows[] = [$book['judul'], $book['pengarang'], $book['total']];
        }

        $rows[] = [];
        $rows[] = ['Kategori', 'Peminjaman', 'Persentase'];
        foreach ($categories as $category) {
            $rows[] = [$category['kategori'], $category['total'], $category['pct'] . '%'];
        }

        $rows[] = [];
        $rows[] = ['Anggota', 'NIM/NIK', 'Peminjaman (periode)', 'Aktif', 'Denda Belum Lunas'];
        foreach ($members as $member) {
            $rows[] = [
                $member['nama'],
                $member['nim_nik'],
                $member['pinjaman'],
                $member['aktif'],
                $member['denda'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Rekap Denda', 'Jumlah'];
        $rows[] = ['Total denda', $fines['nilai_total']];
        $rows[] = ['Sudah dibayar', $fines['nilai_lunas']];
        $rows[] = ['Belum dibayar', $fines['nilai_belum']];

        send_csv('bukuhub-laporan-' . $period['from'] . '_' . $period['to'] . '.csv', [], $rows);
    }
}
