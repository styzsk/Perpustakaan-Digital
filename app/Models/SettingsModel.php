<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Akses data tabel `pengaturan` (satu baris konfigurasi aplikasi).
 */
final class SettingsModel
{
    private static ?array $cache = null;

    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $stmt = Database::pdo()->query('SELECT * FROM pengaturan WHERE id_pengaturan = 1');
        $row = $stmt->fetch();

        if (!$row) {
            $defaults = config('defaults', []);
            $insert = Database::pdo()->prepare(
                "INSERT INTO pengaturan (id_pengaturan, nama_aplikasi, nama_instansi, tarif_denda,
                                         durasi_hari, maks_pinjam_default, email_kontak, alamat)
                 VALUES (1, 'BukuHub', 'Perpustakaan Digital BukuHub', :tarif, :durasi, :maks, '', '')"
            );
            $insert->execute([
                'tarif' => (int) ($defaults['tarif_denda'] ?? 1000),
                'durasi' => (int) ($defaults['durasi_hari'] ?? 7),
                'maks' => (int) ($defaults['maks_pinjam_default'] ?? 3),
            ]);

            $stmt = Database::pdo()->query('SELECT * FROM pengaturan WHERE id_pengaturan = 1');
            $row = $stmt->fetch();
        }

        self::$cache = $row ?: [];
        return self::$cache;
    }

    public static function update(array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE pengaturan SET nama_aplikasi = :nama_aplikasi, nama_instansi = :nama_instansi,
                    tarif_denda = :tarif_denda, durasi_hari = :durasi_hari,
                    maks_pinjam_default = :maks_pinjam_default, email_kontak = :email_kontak,
                    alamat = :alamat
             WHERE id_pengaturan = 1'
        );

        $stmt->execute([
            'nama_aplikasi' => $data['nama_aplikasi'],
            'nama_instansi' => $data['nama_instansi'],
            'tarif_denda' => (int) $data['tarif_denda'],
            'durasi_hari' => (int) $data['durasi_hari'],
            'maks_pinjam_default' => (int) $data['maks_pinjam_default'],
            'email_kontak' => $data['email_kontak'],
            'alamat' => $data['alamat'],
        ]);

        self::$cache = null;
    }

    public static function tariff(): int
    {
        return (int) (self::get()['tarif_denda'] ?? config('defaults.tarif_denda', 1000));
    }

    public static function loanDuration(): int
    {
        return (int) (self::get()['durasi_hari'] ?? config('defaults.durasi_hari', 7));
    }

    public static function defaultLimit(): int
    {
        return (int) (self::get()['maks_pinjam_default'] ?? config('defaults.maks_pinjam_default', 3));
    }
}
