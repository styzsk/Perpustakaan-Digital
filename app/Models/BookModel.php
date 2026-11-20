<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\AppException;

/**
 * Query tabel `buku`.
 */
final class BookModel
{
    private const SORT = [
        'judul' => 'b.judul',
        'pengarang' => 'b.pengarang',
        'kategori' => 'b.kategori',
        'stok' => 'b.stok_tersedia',
        'terbaru' => 'b.created_at',
    ];

    public static function paginate(array $filters, int $page, int $perPage): array
    {
        $where = [];
        $params = [];

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $where[] = '(b.judul LIKE :q1 OR b.pengarang LIKE :q2 OR b.kategori LIKE :q3 OR b.isbn LIKE :q4)';
            $like = '%' . $keyword . '%';
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }

        $category = trim((string) ($filters['kategori'] ?? ''));
        if ($category !== '') {
            $where[] = 'b.kategori = :kategori';
            $params['kategori'] = $category;
        }

        $availability = (string) ($filters['ketersediaan'] ?? '');
        if ($availability === 'available') {
            $where[] = 'b.stok_tersedia > 0';
        } elseif ($availability === 'out') {
            $where[] = 'b.stok_tersedia = 0';
        } elseif ($availability === 'loaned') {
            $where[] = 'b.jumlah_eksemplar > b.stok_tersedia';
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $sort = self::SORT[(string) ($filters['sort'] ?? 'judul')] ?? 'b.judul';
        $dir = strtolower((string) ($filters['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $select = 'SELECT b.*, (b.jumlah_eksemplar - b.stok_tersedia) AS dipinjam FROM buku b';
        $countSql = 'SELECT COUNT(*) FROM buku b' . $whereSql;
        $dataSql = $select . $whereSql . ' ORDER BY ' . $sort . ' ' . $dir;

        return db_paginate($countSql, $dataSql, $params, $page, $perPage);
    }

    public static function find(int $id): ?array
    {
        return db_one(
            'SELECT b.*, (b.jumlah_eksemplar - b.stok_tersedia) AS dipinjam
             FROM buku b WHERE b.id_buku = :id',
            ['id' => $id]
        );
    }

    public static function create(array $data): int
    {
        db_run(
            'INSERT INTO buku (judul, pengarang, kategori, isbn, penerbit, tahun_terbit, sinopsis,
                               jumlah_eksemplar, stok_tersedia)
             VALUES (:judul, :pengarang, :kategori, :isbn, :penerbit, :tahun, :sinopsis, :total, :tersedia)',
            [
                'judul' => $data['judul'],
                'pengarang' => $data['pengarang'],
                'kategori' => $data['kategori'],
                'isbn' => $data['isbn'] !== '' ? $data['isbn'] : null,
                'penerbit' => $data['penerbit'] !== '' ? $data['penerbit'] : null,
                'tahun' => $data['tahun_terbit'] !== '' ? (int) $data['tahun_terbit'] : null,
                'sinopsis' => $data['sinopsis'] !== '' ? $data['sinopsis'] : null,
                'total' => (int) $data['jumlah_eksemplar'],
                'tersedia' => (int) $data['jumlah_eksemplar'],
            ]
        );

        return db_id();
    }

    public static function update(int $id, array $data): void
    {
        db_transaction(static function () use ($id, $data): void {
            $current = db_one(
                'SELECT jumlah_eksemplar, stok_tersedia FROM buku WHERE id_buku = :id FOR UPDATE',
                ['id' => $id]
            );

            if (!$current) {
                throw new \RuntimeException('Buku tidak ditemukan.');
            }

            $onLoan = (int) $current['jumlah_eksemplar'] - (int) $current['stok_tersedia'];
            $total = (int) $data['jumlah_eksemplar'];

            if ($total < $onLoan) {
                throw new AppException(
                    'Jumlah eksemplar tidak boleh kurang dari ' . $onLoan . ' eksemplar yang sedang dipinjam.'
                );
            }

            db_run(
                'UPDATE buku SET judul = :judul, pengarang = :pengarang, kategori = :kategori,
                        isbn = :isbn, penerbit = :penerbit, tahun_terbit = :tahun, sinopsis = :sinopsis,
                        jumlah_eksemplar = :total, stok_tersedia = :tersedia
                 WHERE id_buku = :id',
                [
                    'judul' => $data['judul'],
                    'pengarang' => $data['pengarang'],
                    'kategori' => $data['kategori'],
                    'isbn' => $data['isbn'] !== '' ? $data['isbn'] : null,
                    'penerbit' => $data['penerbit'] !== '' ? $data['penerbit'] : null,
                    'tahun' => $data['tahun_terbit'] !== '' ? (int) $data['tahun_terbit'] : null,
                    'sinopsis' => $data['sinopsis'] !== '' ? $data['sinopsis'] : null,
                    'total' => $total,
                    'tersedia' => $total - $onLoan,
                    'id' => $id,
                ]
            );
        });
    }

    public static function delete(int $id): void
    {
        db_run('DELETE FROM buku WHERE id_buku = :id', ['id' => $id]);
    }

    public static function hasLoans(int $id): bool
    {
        return (int) db_value('SELECT COUNT(*) FROM peminjaman WHERE id_buku = :id', ['id' => $id]) > 0;
    }

    public static function categories(): array
    {
        return array_column(
            db_all('SELECT DISTINCT kategori FROM buku ORDER BY kategori ASC'),
            'kategori'
        );
    }

    public static function stats(): array
    {
        $row = db_one(
            'SELECT COUNT(*) AS total_judul,
                    COALESCE(SUM(jumlah_eksemplar), 0) AS total_eksemplar,
                    COALESCE(SUM(stok_tersedia), 0) AS stok_tersedia,
                    COALESCE(SUM(jumlah_eksemplar - stok_tersedia), 0) AS dipinjam
             FROM buku'
        );

        return [
            'total_judul' => (int) ($row['total_judul'] ?? 0),
            'total_eksemplar' => (int) ($row['total_eksemplar'] ?? 0),
            'stok_tersedia' => (int) ($row['stok_tersedia'] ?? 0),
            'dipinjam' => (int) ($row['dipinjam'] ?? 0),
        ];
    }

    public static function availableForSelect(): array
    {
        return db_all(
            'SELECT id_buku, judul, pengarang, kategori, stok_tersedia
             FROM buku WHERE stok_tersedia > 0 ORDER BY judul ASC'
        );
    }

    public static function allForExport(): array
    {
        return db_all(
            'SELECT b.*, (b.jumlah_eksemplar - b.stok_tersedia) AS dipinjam,
                    (SELECT COUNT(*) FROM peminjaman p WHERE p.id_buku = b.id_buku) AS total_pinjam
             FROM buku b ORDER BY b.judul ASC'
        );
    }

    public static function popular(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));

        return db_all(
            'SELECT b.id_buku, b.judul, b.pengarang, b.kategori, COUNT(p.id_peminjaman) AS total
             FROM buku b
             LEFT JOIN peminjaman p ON p.id_buku = b.id_buku
             GROUP BY b.id_buku, b.judul, b.pengarang, b.kategori
             ORDER BY total DESC, b.judul ASC
             LIMIT ' . $limit
        );
    }
}
