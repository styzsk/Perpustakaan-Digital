<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\AppException;

/**
 * Query tabel `peminjaman` dan proses pinjam/kembali.
 */
final class LoanModel
{
    private const SELECT = "SELECT p.*, b.judul, b.pengarang, b.kategori,
                b.jumlah_eksemplar, b.stok_tersedia,
                a.nama AS anggota_nama, a.nim_nik, a.email AS anggota_email, a.maks_pinjam,
                d.id_denda, d.jumlah_hari, d.total_denda,
                d.status_bayar AS status_denda, d.tgl_bayar
         FROM peminjaman p
         INNER JOIN buku b ON b.id_buku = p.id_buku
         INNER JOIN anggota a ON a.id_anggota = p.id_anggota
         LEFT JOIN denda d ON d.id_peminjaman = p.id_peminjaman";

    public static function paginate(array $filters, int $page, int $perPage): array
    {
        $where = [];
        $params = [];

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $where[] = '(b.judul LIKE :q1 OR b.pengarang LIKE :q2 OR a.nama LIKE :q3 OR a.nim_nik LIKE :q4)';
            $like = '%' . $keyword . '%';
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }

        $tab = (string) ($filters['tab'] ?? 'all');
        $order = ' ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC';

        if ($tab === 'active') {
            $where[] = "p.status = 'dipinjam' AND p.tgl_kembali_rencana >= CURDATE()";
            $order = ' ORDER BY p.tgl_kembali_rencana ASC, p.id_peminjaman DESC';
        } elseif ($tab === 'overdue') {
            $where[] = "p.status = 'dipinjam' AND p.tgl_kembali_rencana < CURDATE()";
            $order = ' ORDER BY p.tgl_kembali_rencana ASC, p.id_peminjaman DESC';
        } elseif ($tab === 'returned') {
            $where[] = "p.status = 'dikembalikan'";
            $order = ' ORDER BY p.tgl_kembali_real DESC, p.id_peminjaman DESC';
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        return db_paginate(
            'SELECT COUNT(*) FROM peminjaman p
             INNER JOIN buku b ON b.id_buku = p.id_buku
             INNER JOIN anggota a ON a.id_anggota = p.id_anggota' . $whereSql,
            self::SELECT . $whereSql . $order,
            $params,
            $page,
            $perPage
        );
    }

    public static function find(int $id): ?array
    {
        return db_one(self::SELECT . ' WHERE p.id_peminjaman = :id', ['id' => $id]);
    }

    public static function recent(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));

        return db_all(
            self::SELECT . ' ORDER BY p.created_at DESC, p.id_peminjaman DESC LIMIT ' . $limit
        );
    }

    public static function stats(): array
    {
        $row = db_one(
            "SELECT COUNT(*) AS total,
                COALESCE(SUM(status = 'dipinjam'), 0) AS aktif,
                COALESCE(SUM(status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()), 0) AS terlambat,
                COALESCE(SUM(status = 'dikembalikan'), 0) AS kembali,
                COALESCE(SUM(tgl_pinjam >= DATE_FORMAT(CURDATE(), '%Y-%m-01')), 0) AS bulan_ini
             FROM peminjaman"
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'aktif' => (int) ($row['aktif'] ?? 0),
            'terlambat' => (int) ($row['terlambat'] ?? 0),
            'kembali' => (int) ($row['kembali'] ?? 0),
            'bulan_ini' => (int) ($row['bulan_ini'] ?? 0),
        ];
    }

    /** Jumlah peminjaman per bulan (format Y-m). */
    public static function countsByMonth(string $from, string $to, bool $returnedOnly = false): array
    {
        $column = $returnedOnly ? 'tgl_kembali_real' : 'tgl_pinjam';
        $rows = db_all(
            "SELECT DATE_FORMAT($column, '%Y-%m') AS ym, COUNT(*) AS total
             FROM peminjaman
             WHERE $column IS NOT NULL AND $column BETWEEN :from AND :to
             GROUP BY ym ORDER BY ym ASC",
            ['from' => $from, 'to' => $to]
        );

        $result = [];
        foreach ($rows as $row) {
            $result[$row['ym']] = (int) $row['total'];
        }
        return $result;
    }

    public static function totalsByCategory(string $from, string $to): array
    {
        return db_all(
            'SELECT b.kategori, COUNT(*) AS total
             FROM peminjaman p
             INNER JOIN buku b ON b.id_buku = p.id_buku
             WHERE p.tgl_pinjam BETWEEN :from AND :to
             GROUP BY b.kategori
             ORDER BY total DESC',
            ['from' => $from, 'to' => $to]
        );
    }

    public static function topBooks(string $from, string $to, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));

        return db_all(
            'SELECT b.id_buku, b.judul, b.pengarang, b.kategori, COUNT(p.id_peminjaman) AS total
             FROM peminjaman p
             INNER JOIN buku b ON b.id_buku = p.id_buku
             WHERE p.tgl_pinjam BETWEEN :from AND :to
             GROUP BY b.id_buku, b.judul, b.pengarang, b.kategori
             ORDER BY total DESC, b.judul ASC
             LIMIT ' . $limit,
            ['from' => $from, 'to' => $to]
        );
    }

    public static function loansForBook(int $bookId): array
    {
        return db_all(
            self::SELECT . ' WHERE p.id_buku = :id ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC',
            ['id' => $bookId]
        );
    }

    public static function allForExport(): array
    {
        return db_all(self::SELECT . ' ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC');
    }

    public static function countsForTabs(): array
    {
        $row = db_one(
            "SELECT COUNT(*) AS semua,
                COALESCE(SUM(status = 'dipinjam' AND tgl_kembali_rencana >= CURDATE()), 0) AS aktif,
                COALESCE(SUM(status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()), 0) AS terlambat,
                COALESCE(SUM(status = 'dikembalikan'), 0) AS kembali
             FROM peminjaman"
        );

        return [
            'all' => (int) ($row['semua'] ?? 0),
            'active' => (int) ($row['aktif'] ?? 0),
            'overdue' => (int) ($row['terlambat'] ?? 0),
            'returned' => (int) ($row['kembali'] ?? 0),
        ];
    }

    /** Simpan peminjaman baru dan kurangi stok buku. */
    public static function createLoan(int $memberId, int $bookId, string $loanDate): int
    {
        return db_transaction(static function () use ($memberId, $bookId, $loanDate): int {
            $member = db_one(
                'SELECT * FROM anggota WHERE id_anggota = :id FOR UPDATE',
                ['id' => $memberId]
            );

            if (!$member) {
                throw new AppException('Anggota tidak ditemukan.');
            }
            if ($member['status'] !== 'aktif') {
                throw new AppException('Anggota ' . $member['nama'] . ' berstatus nonaktif dan tidak dapat meminjam.');
            }

            $activeLoans = (int) db_value(
                "SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id AND status = 'dipinjam'",
                ['id' => $memberId]
            );

            if ($activeLoans >= (int) $member['maks_pinjam']) {
                throw new AppException(
                    'Anggota ' . $member['nama'] . ' sudah mencapai batas maksimal '
                    . (int) $member['maks_pinjam'] . ' peminjaman aktif.'
                );
            }

            $book = db_one(
                'SELECT * FROM buku WHERE id_buku = :id FOR UPDATE',
                ['id' => $bookId]
            );

            if (!$book) {
                throw new AppException('Buku tidak ditemukan.');
            }
            if ((int) $book['stok_tersedia'] <= 0) {
                throw new AppException('Stok buku "' . $book['judul'] . '" sedang habis.');
            }

            $dueDate = date('Y-m-d', strtotime($loanDate . ' +' . SettingsModel::loanDuration() . ' days'));

            db_run(
                "INSERT INTO peminjaman (id_anggota, id_buku, tgl_pinjam, tgl_kembali_rencana,
                                         status, denda_per_hari, created_by)
                 VALUES (:id_anggota, :id_buku, :tgl_pinjam, :tgl_rencana, 'dipinjam', :tarif, :created_by)",
                [
                    'id_anggota' => $memberId,
                    'id_buku' => $bookId,
                    'tgl_pinjam' => $loanDate,
                    'tgl_rencana' => $dueDate,
                    'tarif' => SettingsModel::tariff(),
                    'created_by' => $_SESSION['user_id'] ?? null,
                ]
            );

            $loanId = db_id();

            $stock = db_run(
                'UPDATE buku SET stok_tersedia = stok_tersedia - 1
                 WHERE id_buku = :id AND stok_tersedia > 0',
                ['id' => $bookId]
            );

            if ($stock->rowCount() !== 1) {
                throw new AppException('Stok buku berubah saat proses berjalan. Silakan coba lagi.');
            }

            return $loanId;
        });
    }

    /** Proses pengembalian buku dan catat denda bila terlambat. */
    public static function returnLoan(int $loanId): array
    {
        return db_transaction(static function () use ($loanId): array {
            $loan = db_one(
                'SELECT * FROM peminjaman WHERE id_peminjaman = :id FOR UPDATE',
                ['id' => $loanId]
            );

            if (!$loan) {
                throw new AppException('Transaksi peminjaman tidak ditemukan.');
            }
            if ($loan['status'] !== 'dipinjam') {
                throw new AppException('Transaksi ini sudah dikembalikan sebelumnya.');
            }

            $book = db_one('SELECT * FROM buku WHERE id_buku = :id FOR UPDATE', ['id' => $loan['id_buku']]);
            if (!$book) {
                throw new AppException('Data buku untuk transaksi ini tidak ditemukan.');
            }

            $returnDate = today();
            $lateDays = max(0, diff_days((string) $loan['tgl_kembali_rencana'], $returnDate));
            $tariff = (int) ($loan['denda_per_hari'] ?: SettingsModel::tariff());
            $fine = $lateDays * $tariff;

            db_run(
                "UPDATE peminjaman SET status = 'dikembalikan', tgl_kembali_real = :tgl
                 WHERE id_peminjaman = :id",
                ['tgl' => $returnDate, 'id' => $loanId]
            );

            db_run(
                'UPDATE buku SET stok_tersedia = LEAST(jumlah_eksemplar, stok_tersedia + 1) WHERE id_buku = :id',
                ['id' => $loan['id_buku']]
            );

            if ($lateDays > 0) {
                db_run(
                    "INSERT INTO denda (id_peminjaman, jumlah_hari, tarif_per_hari, total_denda, status_bayar)
                     VALUES (:id_peminjaman, :hari, :tarif, :total, 'belum_lunas')",
                    [
                        'id_peminjaman' => $loanId,
                        'hari' => $lateDays,
                        'tarif' => $tariff,
                        'total' => $fine,
                    ]
                );
            }

            return ['late_days' => $lateDays, 'fine' => $fine, 'loan_id' => $loanId];
        });
    }
}
