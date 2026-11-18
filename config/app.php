<?php

declare(strict_types=1);

/**
 * Konfigurasi aplikasi BukuHub.
 * File ini hanya berisi data konfigurasi, tidak ada logika bisnis.
 */
return [
    'name' => 'BukuHub',
    'tagline' => 'Perpustakaan Digital',
    'version' => '1.0.0',
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id',

    // Durasi session (detik). 1800 = 30 menit tanpa aktivitas.
    'session' => [
        'name' => 'bukuhub_session',
        'lifetime' => 1800,
    ],

    // Batas jumlah baris per halaman untuk daftar tabel.
    'per_page' => 8,

    // Nilai awal aturan perpustakaan. Nilai final tersimpan di tabel `pengaturan`.
    'defaults' => [
        'tarif_denda' => 1000,
        'durasi_hari' => 7,
        'maks_pinjam_default' => 3,
    ],
];
