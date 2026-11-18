<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Akses data tabel `anggota`.
 */
final class MemberModel
{
    private const SORTABLE = [
        'nama' => 'a.nama',
        'nim' => 'a.nim_nik',
        'terbaru' => 'a.created_at',
        'pinjaman' => 'active_loans',
    ];

    /**
     * Query dasar dengan agregat peminjaman aktif, total pinjaman, dan denda belum lunas.
     */
    private static function baseSelect(): string
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
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
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

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $havingSql = $having === [] ? '' : ' HAVING ' . implode(' AND ', $having);

        $sortKey = (string) ($filters['sort'] ?? 'nama');
        $sortColumn = self::SORTABLE[$sortKey] ?? self::SORTABLE['nama'];
        $direction = strtolower((string) ($filters['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $orderSql = ' ORDER BY ' . $sortColumn . ' ' . $direction;

        $pdo = Database::pdo();
        $base = self::baseSelect() . $whereSql . $havingSql;

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM (' . $base . ') AS filtered');
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare($base . $orderSql . ' LIMIT :limit OFFSET :offset');
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
        $stmt = Database::pdo()->prepare(self::baseSelect() . ' WHERE a.id_anggota = :id');
        $stmt->execute(['id' => $id]);
        $member = $stmt->fetch();

        return $member ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO anggota (nama, nim_nik, email, telepon, alamat, maks_pinjam, status)
             VALUES (:nama, :nim_nik, :email, :telepon, :alamat, :maks_pinjam, :status)'
        );

        $stmt->execute([
            'nama' => $data['nama'],
            'nim_nik' => $data['nim_nik'],
            'email' => $data['email'],
            'telepon' => $data['telepon'] !== '' ? $data['telepon'] : null,
            'alamat' => $data['alamat'] !== '' ? $data['alamat'] : null,
            'maks_pinjam' => (int) $data['maks_pinjam'],
            'status' => $data['status'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE anggota SET nama = :nama, nim_nik = :nim_nik, email = :email,
                    telepon = :telepon, alamat = :alamat, maks_pinjam = :maks_pinjam, status = :status
             WHERE id_anggota = :id'
        );

        $stmt->execute([
            'nama' => $data['nama'],
            'nim_nik' => $data['nim_nik'],
            'email' => $data['email'],
            'telepon' => $data['telepon'] !== '' ? $data['telepon'] : null,
            'alamat' => $data['alamat'] !== '' ? $data['alamat'] : null,
            'maks_pinjam' => (int) $data['maks_pinjam'],
            'status' => $data['status'],
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM anggota WHERE id_anggota = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function hasLoans(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function existsNim(string $nim, int $exceptId = 0): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM anggota WHERE nim_nik = :nim AND id_anggota <> :except'
        );
        $stmt->execute(['nim' => $nim, 'except' => $exceptId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function existsEmail(string $email, int $exceptId = 0): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM anggota WHERE email = :email AND id_anggota <> :except'
        );
        $stmt->execute(['email' => $email, 'except' => $exceptId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function stats(): array
    {
        $row = Database::pdo()->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'aktif'), 0) AS aktif,
                    COALESCE(SUM(status = 'nonaktif'), 0) AS nonaktif
             FROM anggota"
        )->fetch();

        $borrowing = (int) Database::pdo()->query(
            "SELECT COUNT(DISTINCT id_anggota) FROM peminjaman WHERE status = 'dipinjam'"
        )->fetchColumn();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'aktif' => (int) ($row['aktif'] ?? 0),
            'nonaktif' => (int) ($row['nonaktif'] ?? 0),
            'sedang_meminjam' => $borrowing,
        ];
    }

    /** Anggota aktif untuk form peminjaman, lengkap dengan jumlah pinjaman aktif. */
    public static function activeForSelect(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT a.id_anggota, a.nama, a.nim_nik, a.maks_pinjam,
                    (SELECT COUNT(*) FROM peminjaman p
                      WHERE p.id_anggota = a.id_anggota AND p.status = 'dipinjam') AS active_loans
             FROM anggota a WHERE a.status = 'aktif' ORDER BY a.nama ASC"
        );
        return $stmt->fetchAll();
    }

    public static function loansFor(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT p.*, b.judul, b.pengarang, b.kategori,
                    d.total_denda, d.status_bayar AS status_denda
             FROM peminjaman p
             INNER JOIN buku b ON b.id_buku = p.id_buku
             LEFT JOIN denda d ON d.id_peminjaman = p.id_peminjaman
             WHERE p.id_anggota = :id
             ORDER BY p.tgl_pinjam DESC, p.id_peminjaman DESC'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public static function allForExport(): array
    {
        $stmt = Database::pdo()->query(self::baseSelect() . ' ORDER BY a.nama ASC');
        return $stmt->fetchAll();
    }
}
