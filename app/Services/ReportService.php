<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\FineModel;
use App\Models\LoanModel;

/**
 * Perhitungan laporan dan analitik berdasarkan periode.
 */
final class ReportService
{
    /**
     * Tentukan periode laporan dari query string.
     * Default: 30 hari terakhir. Preset: 30, 90, ytd, all.
     *
     * @return array{from:string,to:string,preset:string,label:string}
     */
    public static function period(): array
    {
        $preset = (string) ($_GET['preset'] ?? '30');
        $today = today();

        if ($preset === 'all') {
            $first = Database::pdo()->query('SELECT MIN(tgl_pinjam) FROM peminjaman')->fetchColumn();
            $from = $first ?: date('Y-01-01');
            return [
                'from' => (string) $from,
                'to' => $today,
                'preset' => 'all',
                'label' => 'Semua data',
            ];
        }

        if ($preset === 'ytd') {
            $from = date('Y-01-01');
            return [
                'from' => $from,
                'to' => $today,
                'preset' => 'ytd',
                'label' => 'Tahun ' . date('Y'),
            ];
        }

        if ($preset === 'custom') {
            $from = (string) ($_GET['from'] ?? '');
            $to = (string) ($_GET['to'] ?? '');

            $validFrom = self::isValidDate($from);
            $validTo = self::isValidDate($to);

            if (!$validFrom || !$validTo) {
                $preset = '30';
            } else {
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
        }

        $days = $preset === '90' ? 90 : 30;

        return [
            'from' => date('Y-m-d', strtotime('-' . ($days - 1) . ' days')),
            'to' => $today,
            'preset' => (string) $preset,
            'label' => $days . ' hari terakhir',
        ];
    }

    private static function isValidDate(string $value): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }

    public static function summary(string $from, string $to): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(tgl_pinjam BETWEEN :from_a AND :to_a), 0) AS dipinjam,
                COALESCE(SUM(tgl_kembali_real BETWEEN :from_b AND :to_b), 0) AS dikembalikan,
                COALESCE(SUM(status = 'dipinjam'), 0) AS aktif,
                COALESCE(SUM(status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()), 0) AS terlambat
             FROM peminjaman"
        );
        $stmt->execute([
            'from_a' => $from,
            'to_a' => $to,
            'from_b' => $from,
            'to_b' => $to,
        ]);
        $row = $stmt->fetch();

        $finesStmt = $pdo->prepare(
            "SELECT
                COALESCE(SUM(d.total_denda), 0) AS total_denda,
                COALESCE(SUM(CASE WHEN d.status_bayar = 'lunas' THEN d.total_denda ELSE 0 END), 0) AS dibayar,
                COALESCE(SUM(CASE WHEN d.status_bayar = 'belum_lunas' THEN d.total_denda ELSE 0 END), 0) AS belum
             FROM denda d
             INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
             WHERE p.tgl_kembali_real BETWEEN :from AND :to"
        );
        $finesStmt->execute(['from' => $from, 'to' => $to]);
        $fines = $finesStmt->fetch();

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

    /**
     * Deret 12 bulan (atau N bulan) untuk grafik dashboard/report.
     *
     * @return array<int, array{key:string,label:string,year:int,current:int,previous:int,is_current:bool}>
     */
    public static function monthlySeries(int $months = 12): array
    {
        $months = max(1, min(24, $months));

        $currentStart = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
        $currentEnd = new \DateTimeImmutable('last day of this month');
        $previousStart = $currentStart->modify('-' . $months . ' months');
        $previousEnd = $currentStart->modify('-1 day');

        $currentCounts = LoanModel::countsByMonth(
            $currentStart->format('Y-m-d'),
            $currentEnd->format('Y-m-d')
        );
        $previousCounts = LoanModel::countsByMonth(
            $previousStart->format('Y-m-d'),
            $previousEnd->format('Y-m-d')
        );

        $series = [];
        $cursor = $currentStart;

        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $prevKey = (new \DateTimeImmutable($cursor->format('Y-m-01')))->modify('-1 year')->format('Y-m');

            $series[] = [
                'key' => $key,
                'label' => month_label((int) $cursor->format('n')),
                'year' => (int) $cursor->format('Y'),
                'current' => $currentCounts[$key] ?? 0,
                'previous' => $previousCounts[$prevKey] ?? 0,
                'is_current' => $key === date('Y-m'),
            ];

            $cursor = $cursor->modify('+1 month');
        }

        return $series;
    }

    /** @return array<int, array{kategori:string,total:int,pct:float,count:int}> */
    public static function categoryStats(string $from, string $to): array
    {
        $rows = LoanModel::totalsByCategory($from, $to);
        $total = array_sum(array_map(static fn ($row) => (int) $row['total'], $rows));

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
        $stmt = Database::pdo()->prepare(
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
             LIMIT " . $limit
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        return $stmt->fetchAll();
    }
}
