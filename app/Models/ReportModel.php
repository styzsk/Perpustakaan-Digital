<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Perhitungan laporan dan analitik.
 */
final class ReportModel
{
    /** Periode laporan dari query string (30 hari, 90 hari, tahun berjalan, semua, atau custom). */
    public static function period(): array
    {
        $preset = (string) ($_GET['preset'] ?? '30');
        $today = today();

        if ($preset === 'all') {
            $first = db_value('SELECT MIN(tgl_pinjam) FROM peminjaman');
            return [
                'from' => (string) ($first ?: date('Y-01-01')),
                'to' => $today,
                'preset' => 'all',
                'label' => 'Semua data',
            ];
        }

        if ($preset === 'ytd') {
            return [
                'from' => date('Y-01-01'),
                'to' => $today,
                'preset' => 'ytd',
                'label' => 'Tahun ' . date('Y'),
            ];
        }

        if ($preset === 'custom') {
            $from = (string) ($_GET['from'] ?? '');
            $to = (string) ($_GET['to'] ?? '');

            if (self::isDate($from) && self::isDate($to)) {
                if ($from > $to) {
                    [$from, $to] = [$to, $from];
                }

                return [
                    'from' => $from,
                    'to' => $to,
                    'preset' => 'custom',
                    'label' => format_date($from) . ' – ' . format_date($to),
                ];
            }

            $preset = '30';
        }

        $days = $preset === '90' ? 90 : 30;

        return [
            'from' => date('Y-m-d', strtotime('-' . ($days - 1) . ' days')),
            'to' => $today,
            'preset' => $preset,
            'label' => $days . ' hari terakhir',
        ];
    }

    private static function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    public static function summary(string $from, string $to): array
    {
        $row = db_one(
            "SELECT COUNT(*) AS total,
                COALESCE(SUM(tgl_pinjam BETWEEN :from_a AND :to_a), 0) AS dipinjam,
                COALESCE(SUM(tgl_kembali_real BETWEEN :from_b AND :to_b), 0) AS dikembalikan,
                COALESCE(SUM(status = 'dipinjam'), 0) AS aktif,
                COALESCE(SUM(status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()), 0) AS terlambat
             FROM peminjaman",
            [
                'from_a' => $from,
                'to_a' => $to,
                'from_b' => $from,
                'to_b' => $to,
            ]
        );

        $fines = db_one(
            "SELECT COALESCE(SUM(d.total_denda), 0) AS total_denda,
                COALESCE(SUM(CASE WHEN d.status_bayar = 'lunas' THEN d.total_denda ELSE 0 END), 0) AS dibayar,
                COALESCE(SUM(CASE WHEN d.status_bayar = 'belum_lunas' THEN d.total_denda ELSE 0 END), 0) AS belum
             FROM denda d
             INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
             WHERE p.tgl_kembali_real BETWEEN :from AND :to",
            ['from' => $from, 'to' => $to]
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'dipinjam' => (int) ($row['dipinjam'] ?? 0),
            'dikembalikan' => (int) ($row['dikembalikan'] ?? 0),
            'aktif' => (int) ($row['aktif'] ?? 0),
            'terlambat' => (int) ($row['terlambat'] ?? 0),
            'denda_total' => (int) ($fines['total_denda'] ?? 0),
            'denda_dibayar' => (int) ($fines['dibayar'] ?? 0),
            'denda_belum' => (int) ($fines['belum'] ?? 0),
        ];
    }

    /** Deret data grafik untuk N bulan terakhir. */
    public static function monthlySeries(int $months = 12): array
    {
        $months = max(1, min(24, $months));

        $start = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
        $end = new \DateTimeImmutable('last day of this month');
        $previousStart = $start->modify('-' . $months . ' months');
        $previousEnd = $start->modify('-1 day');

        $current = LoanModel::countsByMonth($start->format('Y-m-d'), $end->format('Y-m-d'));
        $previous = LoanModel::countsByMonth($previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d'));

        $series = [];
        $cursor = $start;

        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $lastYearKey = $cursor->modify('-1 year')->format('Y-m');

            $series[] = [
                'key' => $key,
                'label' => month_label((int) $cursor->format('n')),
                'year' => (int) $cursor->format('Y'),
                'current' => $current[$key] ?? 0,
                'previous' => $previous[$lastYearKey] ?? 0,
                'is_current' => $key === date('Y-m'),
            ];

            $cursor = $cursor->modify('+1 month');
        }

        return $series;
    }

    public static function categoryStats(string $from, string $to): array
    {
        $rows = LoanModel::totalsByCategory($from, $to);
        $total = array_sum(array_column($rows, 'total'));

        $result = [];
        foreach ($rows as $index => $row) {
            $result[] = [
                'kategori' => (string) $row['kategori'],
                'total' => (int) $row['total'],
                'count' => (int) $row['total'],
                'pct' => $total > 0 ? round(((int) $row['total'] / $total) * 100, 1) : 0.0,
                'color' => category_color($index),
            ];
        }

        return $result;
    }

    public static function finesSummary(): array
    {
        return FineModel::stats();
    }

    public static function memberActivity(string $from, string $to, int $limit = 12): array
    {
        $limit = max(1, min(100, $limit));

        return db_all(
            "SELECT a.id_anggota, a.nama, a.nim_nik, a.email,
                    (SELECT COUNT(*) FROM peminjaman p
                      WHERE p.id_anggota = a.id_anggota AND p.tgl_pinjam BETWEEN :from AND :to) AS pinjaman,
                    (SELECT COUNT(*) FROM peminjaman p
                      WHERE p.id_anggota = a.id_anggota AND p.status = 'dipinjam') AS aktif,
                    (SELECT COALESCE(SUM(d.total_denda), 0) FROM denda d
                      INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
                      WHERE p.id_anggota = a.id_anggota AND d.status_bayar = 'belum_lunas') AS denda
             FROM anggota a
             ORDER BY pinjaman DESC, a.nama ASC
             LIMIT " . $limit,
            ['from' => $from, 'to' => $to]
        );
    }
}
