<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Core\Database;
use App\Models\BookModel;
use App\Models\LoanModel;
use App\Models\MemberModel;
use App\Models\SettingsModel;

/**
 * Logika bisnis peminjaman dan pengembalian.
 * Semua aturan dijalankan di backend di dalam transaksi database.
 */
final class LoanService
{
    /**
     * Buat transaksi peminjaman baru.
     *
     * @throws AppException
     */
    public static function create(int $memberId, int $bookId, string $loanDate): int
    {
        return Database::transaction(static function ($pdo) use ($memberId, $bookId, $loanDate): int {
            $memberStmt = $pdo->prepare('SELECT * FROM anggota WHERE id_anggota = :id FOR UPDATE');
            $memberStmt->execute(['id' => $memberId]);
            $member = $memberStmt->fetch();

            if (!$member) {
                throw new AppException('Anggota tidak ditemukan.');
            }

            if ($member['status'] !== 'aktif') {
                throw new AppException('Anggota ' . $member['nama'] . ' berstatus nonaktif dan tidak dapat meminjam.');
            }

            $activeStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id AND status = 'dipinjam'"
            );
            $activeStmt->execute(['id' => $memberId]);
            $activeLoans = (int) $activeStmt->fetchColumn();

            if ($activeLoans >= (int) $member['maks_pinjam']) {
                throw new AppException(
                    'Anggota ' . $member['nama'] . ' sudah mencapai batas maksimal '
                    . (int) $member['maks_pinjam'] . ' peminjaman aktif.'
                );
            }

            $bookStmt = $pdo->prepare('SELECT * FROM buku WHERE id_buku = :id FOR UPDATE');
            $bookStmt->execute(['id' => $bookId]);
            $book = $bookStmt->fetch();

            if (!$book) {
                throw new AppException('Buku tidak ditemukan.');
            }

            if ((int) $book['stok_tersedia'] <= 0) {
                throw new AppException('Stok buku "' . $book['judul'] . '" sedang habis.');
            }

            $duration = SettingsModel::loanDuration();
            $dueDate = date('Y-m-d', strtotime($loanDate . ' +' . $duration . ' days'));

            $insert = $pdo->prepare(
                "INSERT INTO peminjaman (id_anggota, id_buku, tgl_pinjam, tgl_kembali_rencana,
                                         status, denda_per_hari, created_by)
                 VALUES (:id_anggota, :id_buku, :tgl_pinjam, :tgl_rencana, 'dipinjam', :tarif, :created_by)"
            );

            $insert->execute([
                'id_anggota' => $memberId,
                'id_buku' => $bookId,
                'tgl_pinjam' => $loanDate,
                'tgl_rencana' => $dueDate,
                'tarif' => SettingsModel::tariff(),
                'created_by' => $_SESSION['user_id'] ?? null,
            ]);

            $loanId = (int) $pdo->lastInsertId();

            $stock = $pdo->prepare(
                'UPDATE buku SET stok_tersedia = stok_tersedia - 1
                 WHERE id_buku = :id AND stok_tersedia > 0'
            );
            $stock->execute(['id' => $bookId]);

            if ($stock->rowCount() !== 1) {
                throw new AppException('Stok buku berubah saat proses berjalan. Silakan coba lagi.');
            }

            return $loanId;
        });
    }

    /**
     * Proses pengembalian buku beserta perhitungan denda.
     *
     * @return array{late_days:int, fine:int, loan_id:int}
     * @throws AppException
     */
    public static function returnBook(int $loanId): array
    {
        return Database::transaction(static function ($pdo) use ($loanId): array {
            $loan = LoanModel::lockForUpdate($pdo, $loanId);

            if (!$loan) {
                throw new AppException('Transaksi peminjaman tidak ditemukan.');
            }

            if ($loan['status'] !== 'dipinjam') {
                throw new AppException('Transaksi ini sudah dikembalikan sebelumnya.');
            }

            $bookStmt = $pdo->prepare('SELECT * FROM buku WHERE id_buku = :id FOR UPDATE');
            $bookStmt->execute(['id' => $loan['id_buku']]);
            $book = $bookStmt->fetch();

            if (!$book) {
                throw new AppException('Data buku untuk transaksi ini tidak ditemukan.');
            }

            $returnDate = today();
            $lateDays = max(0, diff_days((string) $loan['tgl_kembali_rencana'], $returnDate));
            $tariff = (int) ($loan['denda_per_hari'] ?: SettingsModel::tariff());
            $fine = $lateDays * $tariff;

            $update = $pdo->prepare(
                "UPDATE peminjaman SET status = 'dikembalikan', tgl_kembali_real = :tgl
                 WHERE id_peminjaman = :id"
            );
            $update->execute(['tgl' => $returnDate, 'id' => $loanId]);

            $stock = $pdo->prepare(
                'UPDATE buku SET stok_tersedia = LEAST(jumlah_eksemplar, stok_tersedia + 1) WHERE id_buku = :id'
            );
            $stock->execute(['id' => $loan['id_buku']]);

            if ($lateDays > 0) {
                $insert = $pdo->prepare(
                    "INSERT INTO denda (id_peminjaman, jumlah_hari, tarif_per_hari, total_denda, status_bayar)
                     VALUES (:id_peminjaman, :hari, :tarif, :total, 'belum_lunas')"
                );
                $insert->execute([
                    'id_peminjaman' => $loanId,
                    'hari' => $lateDays,
                    'tarif' => $tariff,
                    'total' => $fine,
                ]);
            }

            return ['late_days' => $lateDays, 'fine' => $fine, 'loan_id' => $loanId];
        });
    }

    /** Proyeksi denda untuk peminjaman aktif (dipakai konfirmasi & tabel). */
    public static function projectFine(array $loan): int
    {
        return projected_fine($loan);
    }

    /** Cek batas pinjaman seorang anggota (untuk tampilan form). */
    public static function remainingQuota(int $memberId): int
    {
        $member = MemberModel::find($memberId);
        if (!$member) {
            return 0;
        }

        return max(0, (int) $member['maks_pinjam'] - (int) $member['active_loans']);
    }

    /** Cek stok buku (untuk tampilan detail). */
    public static function bookAvailability(int $bookId): int
    {
        $book = BookModel::find($bookId);
        return $book ? (int) $book['stok_tersedia'] : 0;
    }
}
