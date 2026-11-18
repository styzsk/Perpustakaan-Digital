<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Core\Database;

/**
 * Logika pembayaran denda.
 */
final class FineService
{
    /**
     * Tandai denda sebagai lunas. Pembayaran ganda ditolak.
     *
     * @return array{total:int, nama:string, book:string}
     * @throws AppException
     */
    public static function pay(int $fineId): array
    {
        return Database::transaction(static function ($pdo) use ($fineId): array {
            $stmt = $pdo->prepare(
                'SELECT d.*, a.nama AS anggota_nama, b.judul
                 FROM denda d
                 INNER JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
                 INNER JOIN anggota a ON a.id_anggota = p.id_anggota
                 INNER JOIN buku b ON b.id_buku = p.id_buku
                 WHERE d.id_denda = :id
                 FOR UPDATE'
            );
            $stmt->execute(['id' => $fineId]);
            $fine = $stmt->fetch();

            if (!$fine) {
                throw new AppException('Data denda tidak ditemukan.');
            }

            if ($fine['status_bayar'] === 'lunas') {
                throw new AppException('Denda ini sudah dibayar. Pembayaran ganda dicegah.');
            }

            $update = $pdo->prepare(
                "UPDATE denda SET status_bayar = 'lunas', tgl_bayar = CURDATE(),
                        paid_by = :paid_by
                 WHERE id_denda = :id AND status_bayar = 'belum_lunas'"
            );
            $update->execute([
                'paid_by' => $_SESSION['user_id'] ?? null,
                'id' => $fineId,
            ]);

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
