<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Akses data tabel `denda`.
 */
final class FineModel
{
    private const BASE_SELECT = 'SELECT d.*, p.id_anggota, p.id_buku, p.tgl_pinjam, p.tgl_kembali_rencana,
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
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        $status = (string) ($filters['status'] ?? 'all');
        if ($status === 'unpaid') {
            $where[] = "d.status_bayar = 'belum_lunas'";
        } elseif ($status === 'paid') {
            $where[] = "d.status_bayar = 'lunas'";
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $orderSql = " ORDER BY (d.status_bayar = 'belum_lunas') DESC, d.created_at DESC, d.id_denda DESC";

        $pdo = Database::pdo();

        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM denda d
             INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
             INNER JOIN anggota a ON a.id_anggota = p.id_anggota
             INNER JOIN buku b ON b.id_buku = p.id_buku' . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(self::BASE_SELECT . $whereSql . $orderSql . ' LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
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
        $stmt = Database::pdo()->prepare(self::BASE_SELECT . ' WHERE d.id_denda = :id');
        $stmt->execute(['id' => $id]);
        $fine = $stmt->fetch();

        return $fine ?: null;
    }

    public static function stats(): array
    {
        $row = Database::pdo()->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(total_denda), 0) AS nilai_total,
                COALESCE(SUM(status_bayar = 'belum_lunas'), 0) AS jumlah_belum,
                COALESCE(SUM(CASE WHEN status_bayar = 'belum_lunas' THEN total_denda ELSE 0 END), 0) AS nilai_belum,
                COALESCE(SUM(status_bayar = 'lunas'), 0) AS jumlah_lunas,
                COALESCE(SUM(CASE WHEN status_bayar = 'lunas' THEN total_denda ELSE 0 END), 0) AS nilai_lunas
             FROM denda"
        )->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'nilai_total' => (int) ($row['nilai_total'] ?? 0),
            'jumlah_belum' => (int) ($row['jumlah_belum'] ?? 0),
            'nilai_belum' => (int) ($row['nilai_belum'] ?? 0),
            'jumlah_lunas' => (int) ($row['jumlah_lunas'] ?? 0),
            'nilai_lunas' => (int) ($row['nilai_lunas'] ?? 0),
        ];
    }

    /** Riwayat pembayaran terakhir. */
    public static function recentPayments(int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::pdo()->query(
            self::BASE_SELECT . " WHERE d.status_bayar = 'lunas' AND d.tgl_bayar IS NOT NULL
             ORDER BY d.tgl_bayar DESC, d.id_denda DESC LIMIT " . $limit
        );
        return $stmt->fetchAll();
    }

    public static function allForExport(string $status = 'all'): array
    {
        $where = '';
        if ($status === 'unpaid') {
            $where = " WHERE d.status_bayar = 'belum_lunas'";
        } elseif ($status === 'paid') {
            $where = " WHERE d.status_bayar = 'lunas'";
        }

        $stmt = Database::pdo()->query(
            self::BASE_SELECT . $where . ' ORDER BY d.created_at DESC, d.id_denda DESC'
        );
        return $stmt->fetchAll();
    }

    public static function unpaidTotalForMember(int $memberId): int
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COALESCE(SUM(d.total_denda), 0)
             FROM denda d
             INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
             WHERE p.id_anggota = :id AND d.status_bayar = 'belum_lunas'"
        );
        $stmt->execute(['id' => $memberId]);

        return (int) $stmt->fetchColumn();
    }
}
