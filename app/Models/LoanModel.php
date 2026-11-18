<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Akses data tabel `peminjaman` beserta relasinya.
 */
final class LoanModel
{
    private const BASE_SELECT = "SELECT p.*, b.judul, b.pengarang, b.kategori,
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
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        $tab = (string) ($filters['tab'] ?? 'all');
        if ($tab === 'active') {
            $where[] = "p.status = 'dipinjam' AND p.tgl_kembali_rencana >= CURDATE()";
        } elseif ($tab === 'overdue') {
            $where[] = "p.status = 'dipinjam' AND p.tgl_kembali_rencana < CURDATE()";
        } elseif ($tab === 'returned') {
            $where[] = "p.status = 'dikembalikan'";
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $orderSql = " ORDER BY (p.status = 'dikembalikan') ASC, p.tgl_kembali_rencana ASC, p.id_peminjaman DESC";
        if ($tab === 'returned') {
            $orderSql = ' ORDER BY p.tgl_kembali_real DESC, p.id_peminjaman DESC';
        } elseif ($tab === 'all') {
            $orderSql = ' ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC';
        }

        $pdo = Database::pdo();

        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM peminjaman p
             INNER JOIN buku b ON b.id_buku = p.id_buku
             INNER JOIN anggota a ON a.id_anggota = p.id_anggota' . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(self::BASE_SELECT . $whereSql . $orderSql . ' LIMIT :limit OFFSET :offset');
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
        $stmt = Database::pdo()->prepare(self::BASE_SELECT . ' WHERE p.id_peminjaman = :id');
        $stmt->execute(['id' => $id]);
        $loan = $stmt->fetch();

        return $loan ?: null;
    }

    public static function recent(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::pdo()->query(
            self::BASE_SELECT . ' ORDER BY p.created_at DESC, p.id_peminjaman DESC LIMIT ' . $limit
        );
        return $stmt->fetchAll();
    }

    public static function stats(): array
    {
        $row = Database::pdo()->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'dipinjam'), 0) AS aktif,
                COALESCE(SUM(status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()), 0) AS terlambat,
                COALESCE(SUM(status = 'dikembalikan'), 0) AS kembali,
                COALESCE(SUM(tgl_pinjam >= DATE_FORMAT(CURDATE(), '%Y-%m-01')), 0) AS bulan_ini
             FROM peminjaman"
        )->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'aktif' => (int) ($row['aktif'] ?? 0),
            'terlambat' => (int) ($row['terlambat'] ?? 0),
            'kembali' => (int) ($row['kembali'] ?? 0),
            'bulan_ini' => (int) ($row['bulan_ini'] ?? 0),
        ];
    }

    /**
     * Hitung jumlah peminjaman per bulan (format Y-m) antara dua tanggal.
     *
     * @return array<string, int>
     */
    public static function countsByMonth(string $from, string $to, bool $returnedOnly = false): array
    {
        $column = $returnedOnly ? 'tgl_kembali_real' : 'tgl_pinjam';
        $params = ['from' => $from, 'to' => $to];

        $sql = "SELECT DATE_FORMAT($column, '%Y-%m') AS ym, COUNT(*) AS total
                FROM peminjaman
                WHERE $column IS NOT NULL AND $column BETWEEN :from AND :to
                GROUP BY ym ORDER BY ym ASC";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['ym']] = (int) $row['total'];
        }
        return $result;
    }

    public static function totalsByCategory(string $from, string $to): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT b.kategori, COUNT(*) AS total
             FROM peminjaman p
             INNER JOIN buku b ON b.id_buku = p.id_buku
             WHERE p.tgl_pinjam BETWEEN :from AND :to
             GROUP BY b.kategori
             ORDER BY total DESC'
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        return $stmt->fetchAll();
    }

    public static function topBooks(string $from, string $to, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::pdo()->prepare(
            'SELECT b.id_buku, b.judul, b.pengarang, b.kategori, COUNT(p.id_peminjaman) AS total
             FROM peminjaman p
             INNER JOIN buku b ON b.id_buku = p.id_buku
             WHERE p.tgl_pinjam BETWEEN :from AND :to
             GROUP BY b.id_buku, b.judul, b.pengarang, b.kategori
             ORDER BY total DESC, b.judul ASC
             LIMIT ' . $limit
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        return $stmt->fetchAll();
    }

    public static function loansForBook(int $bookId): array
    {
        $stmt = Database::pdo()->prepare(
            self::BASE_SELECT . ' WHERE p.id_buku = :id ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC'
        );
        $stmt->execute(['id' => $bookId]);

        return $stmt->fetchAll();
    }

    public static function allForExport(): array
    {
        $stmt = Database::pdo()->query(self::BASE_SELECT . ' ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC');
        return $stmt->fetchAll();
    }

    public static function activeCountForMember(int $memberId): int
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id AND status = 'dipinjam'"
        );
        $stmt->execute(['id' => $memberId]);

        return (int) $stmt->fetchColumn();
    }

    public static function countsForTabs(): array
    {
        $row = Database::pdo()->query(
            "SELECT
                COUNT(*) AS semua,
                COALESCE(SUM(status = 'dipinjam' AND tgl_kembali_rencana >= CURDATE()), 0) AS aktif,
                COALESCE(SUM(status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()), 0) AS terlambat,
                COALESCE(SUM(status = 'dikembalikan'), 0) AS kembali
             FROM peminjaman"
        )->fetch();

        return [
            'all' => (int) ($row['semua'] ?? 0),
            'active' => (int) ($row['aktif'] ?? 0),
            'overdue' => (int) ($row['terlambat'] ?? 0),
            'returned' => (int) ($row['kembali'] ?? 0),
        ];
    }

    /** Ambil baris peminjaman untuk proses pengembalian di dalam transaksi. */
    public static function lockForUpdate(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM peminjaman WHERE id_peminjaman = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        $loan = $stmt->fetch();

        return $loan ?: null;
    }
}
