<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Akses data tabel `buku`.
 */
final class BookModel
{
    private const SORTABLE = [
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
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
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

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $sortKey = (string) ($filters['sort'] ?? 'judul');
        $sortColumn = self::SORTABLE[$sortKey] ?? self::SORTABLE['judul'];
        $direction = strtolower((string) ($filters['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderSql = ' ORDER BY ' . $sortColumn . ' ' . $direction;

        $pdo = Database::pdo();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM buku b' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT b.*, (b.jumlah_eksemplar - b.stok_tersedia) AS dipinjam FROM buku b'
            . $whereSql . $orderSql . ' LIMIT :limit OFFSET :offset';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT b.*, (b.jumlah_eksemplar - b.stok_tersedia) AS dipinjam FROM buku b WHERE b.id_buku = :id'
        );
        $stmt->execute(['id' => $id]);
        $book = $stmt->fetch();

        return $book ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO buku (judul, pengarang, kategori, isbn, penerbit, tahun_terbit, sinopsis,
                               jumlah_eksemplar, stok_tersedia)
             VALUES (:judul, :pengarang, :kategori, :isbn, :penerbit, :tahun, :sinopsis, :total, :tersedia)'
        );

        $stmt->execute([
            'judul' => $data['judul'],
            'pengarang' => $data['pengarang'],
            'kategori' => $data['kategori'],
            'isbn' => $data['isbn'] !== '' ? $data['isbn'] : null,
            'penerbit' => $data['penerbit'] !== '' ? $data['penerbit'] : null,
            'tahun' => $data['tahun_terbit'] !== '' ? (int) $data['tahun_terbit'] : null,
            'sinopsis' => $data['sinopsis'] !== '' ? $data['sinopsis'] : null,
            'total' => (int) $data['jumlah_eksemplar'],
            'tersedia' => (int) $data['jumlah_eksemplar'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Update metadata buku. Stok tersedia disesuaikan bila total eksemplar berubah,
     * tanpa mengubah jumlah eksemplar yang sedang dipinjam.
     */
    public static function update(int $id, array $data): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            'SELECT jumlah_eksemplar, stok_tersedia FROM buku WHERE id_buku = :id FOR UPDATE'
        );

        $pdo->beginTransaction();
        try {
            $stmt->execute(['id' => $id]);
            $current = $stmt->fetch();
            if (!$current) {
                throw new \RuntimeException('Buku tidak ditemukan.');
            }

            $onLoan = (int) $current['jumlah_eksemplar'] - (int) $current['stok_tersedia'];
            $newTotal = (int) $data['jumlah_eksemplar'];

            if ($newTotal < $onLoan) {
                throw new \App\Core\AppException(
                    'Jumlah eksemplar tidak boleh kurang dari ' . $onLoan . ' eksemplar yang sedang dipinjam.'
                );
            }

            $update = $pdo->prepare(
                'UPDATE buku SET judul = :judul, pengarang = :pengarang, kategori = :kategori,
                        isbn = :isbn, penerbit = :penerbit, tahun_terbit = :tahun, sinopsis = :sinopsis,
                        jumlah_eksemplar = :total, stok_tersedia = :tersedia
                 WHERE id_buku = :id'
            );

            $update->execute([
                'judul' => $data['judul'],
                'pengarang' => $data['pengarang'],
                'kategori' => $data['kategori'],
                'isbn' => $data['isbn'] !== '' ? $data['isbn'] : null,
                'penerbit' => $data['penerbit'] !== '' ? $data['penerbit'] : null,
                'tahun' => $data['tahun_terbit'] !== '' ? (int) $data['tahun_terbit'] : null,
                'sinopsis' => $data['sinopsis'] !== '' ? $data['sinopsis'] : null,
                'total' => $newTotal,
                'tersedia' => $newTotal - $onLoan,
                'id' => $id,
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM buku WHERE id_buku = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function hasLoans(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM peminjaman WHERE id_buku = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function categories(): array
    {
        $stmt = Database::pdo()->query('SELECT DISTINCT kategori FROM buku ORDER BY kategori ASC');
        return array_column($stmt->fetchAll(), 'kategori');
    }

    public static function stats(): array
    {
        $row = Database::pdo()->query(
            'SELECT COUNT(*) AS total_judul,
                    COALESCE(SUM(jumlah_eksemplar), 0) AS total_eksemplar,
                    COALESCE(SUM(stok_tersedia), 0) AS stok_tersedia,
                    COALESCE(SUM(jumlah_eksemplar - stok_tersedia), 0) AS dipinjam
             FROM buku'
        )->fetch();

        return [
            'total_judul' => (int) ($row['total_judul'] ?? 0),
            'total_eksemplar' => (int) ($row['total_eksemplar'] ?? 0),
            'stok_tersedia' => (int) ($row['stok_tersedia'] ?? 0),
            'dipinjam' => (int) ($row['dipinjam'] ?? 0),
        ];
    }

    /** Buku dengan stok tersedia > 0 untuk form peminjaman. */
    public static function availableForSelect(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT id_buku, judul, pengarang, kategori, stok_tersedia
             FROM buku WHERE stok_tersedia > 0 ORDER BY judul ASC'
        );
        return $stmt->fetchAll();
    }

    public static function allForExport(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT b.*, (b.jumlah_eksemplar - b.stok_tersedia) AS dipinjam,
                    (SELECT COUNT(*) FROM peminjaman p WHERE p.id_buku = b.id_buku) AS total_pinjam
             FROM buku b ORDER BY b.judul ASC'
        );
        return $stmt->fetchAll();
    }

    /** Buku populer berdasarkan jumlah transaksi. */
    public static function popular(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::pdo()->query(
            'SELECT b.id_buku, b.judul, b.pengarang, b.kategori, COUNT(p.id_peminjaman) AS total
             FROM buku b
             LEFT JOIN peminjaman p ON p.id_buku = b.id_buku
             GROUP BY b.id_buku, b.judul, b.pengarang, b.kategori
             ORDER BY total DESC, b.judul ASC
             LIMIT ' . $limit
        );
        return $stmt->fetchAll();
    }

    public static function totalCopies(): int
    {
        return (int) Database::pdo()->query('SELECT COALESCE(SUM(jumlah_eksemplar),0) FROM buku')->fetchColumn();
    }
}
