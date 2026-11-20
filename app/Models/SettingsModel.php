<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Query tabel `pengaturan` (satu baris konfigurasi).
 */
final class SettingsModel
{
    private static ?array $cache = null;

    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $row = db_one('SELECT * FROM pengaturan WHERE id_pengaturan = 1');

        // Buat baris default bila tabel masih kosong.
        if (!$row) {
            $defaults = config('defaults', []);
            db_run(
                'INSERT INTO pengaturan (id_pengaturan, nama_aplikasi, nama_instansi, tarif_denda,
                                         durasi_hari, maks_pinjam_default, email_kontak, alamat)
                 VALUES (1, :nama, :instansi, :tarif, :durasi, :maks, :email, :alamat)',
                [
                    'nama' => 'BukuHub',
                    'instansi' => 'Perpustakaan Digital BukuHub',
                    'tarif' => (int) ($defaults['tarif_denda'] ?? 1000),
                    'durasi' => (int) ($defaults['durasi_hari'] ?? 7),
                    'maks' => (int) ($defaults['maks_pinjam_default'] ?? 3),
                    'email' => '',
                    'alamat' => '',
                ]
            );

            $row = db_one('SELECT * FROM pengaturan WHERE id_pengaturan = 1');
        }

        return self::$cache = ($row ?: []);
    }

    public static function update(array $data): void
    {
        db_run(
            'UPDATE pengaturan SET nama_aplikasi = :nama_aplikasi, nama_instansi = :nama_instansi,
                    tarif_denda = :tarif_denda, durasi_hari = :durasi_hari,
                    maks_pinjam_default = :maks_pinjam_default, email_kontak = :email_kontak,
                    alamat = :alamat
             WHERE id_pengaturan = 1',
            [
                'nama_aplikasi' => $data['nama_aplikasi'],
                'nama_instansi' => $data['nama_instansi'],
                'tarif_denda' => (int) $data['tarif_denda'],
                'durasi_hari' => (int) $data['durasi_hari'],
                'maks_pinjam_default' => (int) $data['maks_pinjam_default'],
                'email_kontak' => $data['email_kontak'],
                'alamat' => $data['alamat'],
            ]
        );

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
