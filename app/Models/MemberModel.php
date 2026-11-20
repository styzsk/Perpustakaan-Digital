<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Query tabel `anggota`.
 */
final class MemberModel
{
    private const SORT = [
        'nama' => 'a.nama',
        'nim' => 'a.nim_nik',
        'terbaru' => 'a.created_at',
        'pinjaman' => 'active_loans',
    ];

    /** Data anggota + ringkasan pinjaman dan denda. */
    private static function select(): string
    {
        return "SELECT a.*,
                (SELECT COUNT(*) FROM peminjaman p
                  WHERE p.id_anggota = a.id_anggota AND p.status = 'dipinjam') AS active_loans,
                (SELECT COUNT(*) FROM peminjaman p
                  WHERE p.id_anggota = a.id_anggota) AS total_loans,
                (SELECT COALESCE(SUM(d.total_denda), 0) FROM denda d
                  INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
                  WHERE p.id_anggota = a.id_anggota AND d.status_bayar = 'belum_lunas') AS unpaid_fines
             FROM anggota a";
    }

    public static function paginate(array $filters, int $page, int $perPage): array
    {
        $where = [];
        $having = [];
        $params = [];

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $where[] = '(a.nama LIKE :q1 OR a.nim_nik LIKE :q2 OR a.email LIKE :q3)';
            $like = '%' . $keyword . '%';
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        $status = (string) ($filters['status_anggota'] ?? '');
        if (in_array($status, ['aktif', 'nonaktif'], true)) {
            $where[] = 'a.status = :status';
            $params['status'] = $status;
        }

        $filter = (string) ($filters['filter'] ?? '');
        if ($filter === 'active') {
            $having[] = 'active_loans > 0';
        } elseif ($filter === 'idle') {
            $having[] = 'active_loans = 0';
        } elseif ($filter === 'limit') {
            $having[] = 'active_loans >= a.maks_pinjam';
        } elseif ($filter === 'fines') {
            $having[] = 'unpaid_fines > 0';
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $havingSql = $having ? ' HAVING ' . implode(' AND ', $having) : '';
        $base = self::select() . $whereSql . $havingSql;

        $sort = self::SORT[(string) ($filters['sort'] ?? 'nama')] ?? 'a.nama';
        $dir = strtolower((string) ($filters['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        return db_paginate(
            'SELECT COUNT(*) FROM (' . $base . ') AS filtered',
            $base . ' ORDER BY ' . $sort . ' ' . $dir,
            $params,
            $page,
            $perPage
        );
    }

    public static function find(int $id): ?array
    {
        return db_one(self::select() . ' WHERE a.id_anggota = :id', ['id' => $id]);
    }

    public static function create(array $data): int
    {
        db_run(
            'INSERT INTO anggota (nama, nim_nik, email, telepon, alamat, maks_pinjam, status)
             VALUES (:nama, :nim_nik, :email, :telepon, :alamat, :maks_pinjam, :status)',
            [
                'nama' => $data['nama'],
                'nim_nik' => $data['nim_nik'],
                'email' => $data['email'],
                'telepon' => $data['telepon'] !== '' ? $data['telepon'] : null,
                'alamat' => $data['alamat'] !== '' ? $data['alamat'] : null,
                'maks_pinjam' => (int) $data['maks_pinjam'],
                'status' => $data['status'],
            ]
        );

        return db_id();
    }

    public static function update(int $id, array $data): void
    {
        db_run(
            'UPDATE anggota SET nama = :nama, nim_nik = :nim_nik, email = :email,
                    telepon = :telepon, alamat = :alamat, maks_pinjam = :maks_pinjam, status = :status
             WHERE id_anggota = :id',
            [
                'nama' => $data['nama'],
                'nim_nik' => $data['nim_nik'],
                'email' => $data['email'],
                'telepon' => $data['telepon'] !== '' ? $data['telepon'] : null,
                'alamat' => $data['alamat'] !== '' ? $data['alamat'] : null,
                'maks_pinjam' => (int) $data['maks_pinjam'],
                'status' => $data['status'],
                'id' => $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        db_run('DELETE FROM anggota WHERE id_anggota = :id', ['id' => $id]);
    }

    public static function hasLoans(int $id): bool
    {
        return (int) db_value('SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id', ['id' => $id]) > 0;
    }

    public static function existsNim(string $nim, int $exceptId = 0): bool
    {
        return (int) db_value(
            'SELECT COUNT(*) FROM anggota WHERE nim_nik = :nim AND id_anggota <> :except',
            ['nim' => $nim, 'except' => $exceptId]
        ) > 0;
    }

    public static function existsEmail(string $email, int $exceptId = 0): bool
    {
        return (int) db_value(
            'SELECT COUNT(*) FROM anggota WHERE email = :email AND id_anggota <> :except',
            ['email' => $email, 'except' => $exceptId]
        ) > 0;
    }

    public static function stats(): array
    {
        $row = db_one(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'aktif'), 0) AS aktif,
                    COALESCE(SUM(status = 'nonaktif'), 0) AS nonaktif
             FROM anggota"
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'aktif' => (int) ($row['aktif'] ?? 0),
            'nonaktif' => (int) ($row['nonaktif'] ?? 0),
            'sedang_meminjam' => (int) db_value(
                "SELECT COUNT(DISTINCT id_anggota) FROM peminjaman WHERE status = 'dipinjam'"
            ),
        ];
    }

    public static function activeForSelect(): array
    {
        return db_all(
            "SELECT a.id_anggota, a.nama, a.nim_nik, a.maks_pinjam,
                    (SELECT COUNT(*) FROM peminjaman p
                      WHERE p.id_anggota = a.id_anggota AND p.status = 'dipinjam') AS active_loans
             FROM anggota a WHERE a.status = 'aktif' ORDER BY a.nama ASC"
        );
    }

    public static function loansFor(int $id): array
    {
        return db_all(
            'SELECT p.*, b.judul, b.pengarang, b.kategori,
                    d.total_denda, d.status_bayar AS status_denda
             FROM peminjaman p
             INNER JOIN buku b ON b.id_buku = p.id_buku
             LEFT JOIN denda d ON d.id_peminjaman = p.id_peminjaman
             WHERE p.id_anggota = :id
             ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC',
            ['id' => $id]
        );
    }

    public static function allForExport(): array
    {
        return db_all(self::select() . ' ORDER BY a.nama ASC');
    }
}
