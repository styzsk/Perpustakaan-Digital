<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\AppException;

/**
 * Query tabel `denda`.
 */
final class FineModel
{
    private const SELECT = 'SELECT d.*, p.id_anggota, p.id_buku, p.tgl_pinjam, p.tgl_kembali_rencana,
                p.tgl_kembali_real, p.denda_per_hari,
                a.nama AS anggota_nama, a.nim_nik, a.email AS anggota_email,
                b.judul, b.pengarang, b.kategori
         FROM denda d
         INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
         INNER JOIN anggota a ON a.id_anggota = p.id_anggota
         INNER JOIN buku b ON b.id_buku = p.id_buku';

    public static function paginate(array $filters, int $page, int $perPage): array
    {
        $where = [];
        $params = [];

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $where[] = '(a.nama LIKE :q1 OR a.nim_nik LIKE :q2 OR b.judul LIKE :q3)';
            $like = '%' . $keyword . '%';
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        $status = (string) ($filters['status'] ?? 'all');
        if ($status === 'unpaid') {
            $where[] = "d.status_bayar = 'belum_lunas'";
        } elseif ($status === 'paid') {
            $where[] = "d.status_bayar = 'lunas'";
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        return db_paginate(
            'SELECT COUNT(*) FROM denda d
             INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
             INNER JOIN anggota a ON a.id_anggota = p.id_anggota
             INNER JOIN buku b ON b.id_buku = p.id_buku' . $whereSql,
            self::SELECT . $whereSql
                . " ORDER BY (d.status_bayar = 'belum_lunas') DESC, d.created_at DESC, d.id_denda DESC",
            $params,
            $page,
            $perPage
        );
    }

    public static function find(int $id): ?array
    {
        return db_one(self::SELECT . ' WHERE d.id_denda = :id', ['id' => $id]);
    }

    public static function stats(): array
    {
        $row = db_one(
            "SELECT COUNT(*) AS total,
                COALESCE(SUM(total_denda), 0) AS nilai_total,
                COALESCE(SUM(status_bayar = 'belum_lunas'), 0) AS jumlah_belum,
                COALESCE(SUM(CASE WHEN status_bayar = 'belum_lunas' THEN total_denda ELSE 0 END), 0) AS nilai_belum,
                COALESCE(SUM(status_bayar = 'lunas'), 0) AS jumlah_lunas,
                COALESCE(SUM(CASE WHEN status_bayar = 'lunas' THEN total_denda ELSE 0 END), 0) AS nilai_lunas
             FROM denda"
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'nilai_total' => (int) ($row['nilai_total'] ?? 0),
            'jumlah_belum' => (int) ($row['jumlah_belum'] ?? 0),
            'nilai_belum' => (int) ($row['nilai_belum'] ?? 0),
            'jumlah_lunas' => (int) ($row['jumlah_lunas'] ?? 0),
            'nilai_lunas' => (int) ($row['nilai_lunas'] ?? 0),
        ];
    }

    public static function recentPayments(int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));

        return db_all(
            self::SELECT . " WHERE d.status_bayar = 'lunas' AND d.tgl_bayar IS NOT NULL
             ORDER BY d.tgl_bayar DESC, d.id_denda DESC LIMIT " . $limit
        );
    }

    public static function allForExport(string $status = 'all'): array
    {
        $where = '';
        if ($status === 'unpaid') {
            $where = " WHERE d.status_bayar = 'belum_lunas'";
        } elseif ($status === 'paid') {
            $where = " WHERE d.status_bayar = 'lunas'";
        }

        return db_all(self::SELECT . $where . ' ORDER BY d.created_at DESC, d.id_denda DESC');
    }

    public static function unpaidTotalForMember(int $memberId): int
    {
        return (int) db_value(
            "SELECT COALESCE(SUM(d.total_denda), 0)
             FROM denda d
             INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
             WHERE p.id_anggota = :id AND d.status_bayar = 'belum_lunas'",
            ['id' => $memberId]
        );
    }

    /** Tandai denda lunas. */
    public static function pay(int $fineId): array
    {
        return db_transaction(static function () use ($fineId): array {
            $fine = db_one(
                'SELECT d.*, a.nama AS anggota_nama, b.judul
                 FROM denda d
                 INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
                 INNER JOIN anggota a ON a.id_anggota = p.id_anggota
                 INNER JOIN buku b ON b.id_buku = p.id_buku
                 WHERE d.id_denda = :id
                 FOR UPDATE',
                ['id' => $fineId]
            );

            if (!$fine) {
                throw new AppException('Data denda tidak ditemukan.');
            }
            if ($fine['status_bayar'] === 'lunas') {
                throw new AppException('Denda ini sudah dibayar. Pembayaran ganda dicegah.');
            }

            $update = db_run(
                "UPDATE denda SET status_bayar = 'lunas', tgl_bayar = CURDATE(), paid_by = :paid_by
                 WHERE id_denda = :id AND status_bayar = 'belum_lunas'",
                [
                    'paid_by' => $_SESSION['user_id'] ?? null,
                    'id' => $fineId,
                ]
            );

            if ($update->rowCount() !== 1) {
                throw new AppException('Denda gagal diperbarui. Silakan muat ulang halaman.');
            }

            return [
                'total' => (int) $fine['total_denda'],
                'nama' => (string) $fine['anggota_nama'],
                'book' => (string) $fine['judul'],
            ];
        });
    }
}
