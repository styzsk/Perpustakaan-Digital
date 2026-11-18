-- ============================================================================
-- BukuHub — Data contoh (seed)
-- Impor setelah database/schema.sql. File ini mengosongkan data lama,
-- lalu mengisi akun demo, anggota, buku, peminjaman, denda, dan pengaturan.
--
-- Akun demo: admin / admin123
-- Tanggal transaksi dibuat relatif terhadap tanggal impor (CURDATE()),
-- sehingga daftar pinjaman aktif dan terlambat selalu relevan.
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';
USE `bukuhub`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `audit_log`;
TRUNCATE TABLE `denda`;
TRUNCATE TABLE `peminjaman`;
TRUNCATE TABLE `buku`;
TRUNCATE TABLE `anggota`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `pengaturan`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Akun pengelola (password: admin123, hash bcrypt)
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id_user`, `nama`, `username`, `email`, `password_hash`, `role`) VALUES
(1, 'Admin BukuHub', 'admin', 'admin@bukuhub.id', '$2y$10$95ewpC7uw8auPVCdsy0TGO2W37OREAeJQGUkecv7016XSTSBRqydm', 'admin');

-- ----------------------------------------------------------------------------
-- Anggota perpustakaan
-- ----------------------------------------------------------------------------
INSERT INTO `anggota` (`id_anggota`, `nama`, `nim_nik`, `email`, `telepon`, `alamat`, `maks_pinjam`, `status`, `created_at`) VALUES
(1, 'Andi Pratama', '20240001', 'andi.pratama@kampus.ac.id', '0812-3400-1001', 'Jl. Merdeka No. 12, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 413 DAY)),
(2, 'Siti Rahmawati', '20240002', 'siti.rahmawati@kampus.ac.id', '0812-3400-1002', 'Jl. Cihampelas No. 8, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 406 DAY)),
(3, 'Budi Santoso', '20240003', 'budi.santoso@kampus.ac.id', '0812-3400-1003', 'Jl. Dipatiukur No. 21, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 399 DAY)),
(4, 'Dewi Lestari', '20240004', 'dewi.lestari@kampus.ac.id', '0812-3400-1004', 'Jl. Sukajadi No. 45, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 392 DAY)),
(5, 'Rizky Maulana', '20240005', 'rizky.maulana@kampus.ac.id', '0812-3400-1005', 'Jl. Setiabudi No. 77, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 385 DAY)),
(6, 'Nur Aisyah', '20240006', 'nur.aisyah@kampus.ac.id', '0812-3400-1006', 'Jl. Pasteur No. 5, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 378 DAY)),
(7, 'Fajar Nugroho', '20240007', 'fajar.nugroho@kampus.ac.id', '0812-3400-1007', 'Jl. Buah Batu No. 18, Bandung', 2, 'aktif', DATE_SUB(NOW(), INTERVAL 371 DAY)),
(8, 'Putri Amelia', '20240008', 'putri.amelia@kampus.ac.id', '0812-3400-1008', 'Jl. Kiaracondong No. 33, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 364 DAY)),
(9, 'Agus Setiawan', '20240009', 'agus.setiawan@kampus.ac.id', '0812-3400-1009', 'Jl. Soekarno Hatta No. 91, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 357 DAY)),
(10, 'Rina Wulandari', '20240010', 'rina.wulandari@kampus.ac.id', '0812-3400-1010', 'Jl. Cibiru No. 14, Bandung', 4, 'aktif', DATE_SUB(NOW(), INTERVAL 350 DAY)),
(11, 'Dimas Saputra', '20240011', 'dimas.saputra@kampus.ac.id', '0812-3400-1011', 'Jl. Antapani No. 27, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 343 DAY)),
(12, 'Maya Anggraini', '20240012', 'maya.anggraini@kampus.ac.id', '0812-3400-1012', 'Jl. Ujung Berung No. 9, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 336 DAY)),
(13, 'Hendra Wijaya', '20240013', 'hendra.wijaya@kampus.ac.id', '0812-3400-1013', 'Jl. Gedebage No. 41, Bandung', 2, 'aktif', DATE_SUB(NOW(), INTERVAL 329 DAY)),
(14, 'Laila Fitriani', '20240014', 'laila.fitriani@kampus.ac.id', '0812-3400-1014', 'Jl. Riau No. 16, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 322 DAY)),
(15, 'Yoga Pratama', '20240015', 'yoga.pratama@kampus.ac.id', '0812-3400-1015', 'Jl. Dago No. 62, Bandung', 3, 'aktif', DATE_SUB(NOW(), INTERVAL 315 DAY)),
(16, 'Intan Permatasari', '20240016', 'intan.permatasari@kampus.ac.id', '0812-3400-1016', 'Jl. Kemuning No. 3, Bandung', 3, 'nonaktif', DATE_SUB(NOW(), INTERVAL 308 DAY)),
(17, 'Bayu Kurniawan', '20240017', 'bayu.kurniawan@kampus.ac.id', '0812-3400-1017', 'Jl. Melati No. 11, Bandung', 2, 'nonaktif', DATE_SUB(NOW(), INTERVAL 301 DAY)),
(18, 'Sari Melati', '20240018', 'sari.melati@kampus.ac.id', '0812-3400-1018', 'Jl. Anggrek No. 7, Bandung', 3, 'nonaktif', DATE_SUB(NOW(), INTERVAL 294 DAY));

-- ----------------------------------------------------------------------------
-- Katalog buku (jumlah_eksemplar = total kopi)
-- stok_tersedia dihitung ulang di akhir dari transaksi aktif
-- ----------------------------------------------------------------------------
INSERT INTO `buku` (`id_buku`, `judul`, `pengarang`, `kategori`, `isbn`, `penerbit`, `tahun_terbit`, `sinopsis`, `jumlah_eksemplar`, `stok_tersedia`, `created_at`) VALUES
(1, 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Sastra', '978-979-97312-3-9', 'Lentera Dipantara', 1980, 'Kisah Minke pada masa kolonial dan pergulatan identitasnya.', 5, 5, DATE_SUB(NOW(), INTERVAL 375 DAY)),
(2, 'Laskar Pelangi', 'Andrea Hirata', 'Fiksi', '978-979-3062-79-2', 'Bentang Pustaka', 2005, 'Sepuluh anak Belitung dan sekolah Muhammadiyah yang penuh harapan.', 6, 6, DATE_SUB(NOW(), INTERVAL 370 DAY)),
(3, 'Perahu Kertas', 'Dee Lestari', 'Fiksi', '978-602-03-2288-8', 'Bentang Pustaka', 2009, 'Persahabatan, mimpi, dan pilihan antara Kugy dan Keenan.', 4, 4, DATE_SUB(NOW(), INTERVAL 365 DAY)),
(4, 'Filosofi Teras', 'Henry Manampiring', 'Filsafat', '978-602-412-518-9', 'Kompas', 2018, 'Filsafat Stoa untuk menghadapi emosi negatif sehari-hari.', 5, 5, DATE_SUB(NOW(), INTERVAL 360 DAY)),
(5, 'Sapiens', 'Yuval Noah Harari', 'Sejarah', '978-602-424-419-4', 'Kepustakaan Populer Gramedia', 2011, 'Sejarah singkat umat manusia dari revolusi kognitif hingga kini.', 4, 4, DATE_SUB(NOW(), INTERVAL 355 DAY)),
(6, 'Homo Deus', 'Yuval Noah Harari', 'Sejarah', '978-602-424-663-1', 'Kepustakaan Populer Gramedia', 2016, 'Masa depan manusia, teknologi, dan pencarian kebahagiaan.', 3, 3, DATE_SUB(NOW(), INTERVAL 350 DAY)),
(7, 'Atomic Habits', 'James Clear', 'Pengembangan Diri', '978-602-06-3112-5', 'Gramedia Pustaka Utama', 2018, 'Cara membangun kebiasaan kecil yang memberi hasil besar.', 5, 5, DATE_SUB(NOW(), INTERVAL 345 DAY)),
(8, 'Clean Code', 'Robert C. Martin', 'Teknologi', '978-013-235-088-4', 'Prentice Hall', 2008, 'Prinsip menulis kode yang mudah dibaca dan dirawat.', 3, 3, DATE_SUB(NOW(), INTERVAL 340 DAY)),
(9, 'The Pragmatic Programmer', 'Andrew Hunt', 'Teknologi', '978-020-161-622-4', 'Addison-Wesley', 1999, 'Kebiasaan dan pola pikir praktis seorang programmer.', 3, 3, DATE_SUB(NOW(), INTERVAL 335 DAY)),
(10, 'Algoritma dan Struktur Data', 'Rinaldi Munir', 'Teknologi', '978-602-1514-34-9', 'Informatika', 2016, 'Dasar algoritma, kompleksitas, dan struktur data.', 5, 5, DATE_SUB(NOW(), INTERVAL 330 DAY)),
(11, 'Basis Data', 'Fathansyah', 'Teknologi', '978-602-8759-01-0', 'Informatika', 2018, 'Konsep basis data relasional dan perancangan skema.', 5, 5, DATE_SUB(NOW(), INTERVAL 325 DAY)),
(12, 'Pemrograman PHP', 'Abdul Kadir', 'Teknologi', '978-979-29-6170-3', 'Andi Publisher', 2019, 'Dasar pemrograman PHP untuk aplikasi web.', 4, 4, DATE_SUB(NOW(), INTERVAL 320 DAY)),
(13, 'Matematika Diskrit', 'Kenneth H. Rosen', 'Sains', '978-007-338-309-5', 'McGraw-Hill', 2012, 'Logika, kombinatorika, graf, dan struktur diskrit.', 3, 3, DATE_SUB(NOW(), INTERVAL 315 DAY)),
(14, 'Fisika Dasar', 'David Halliday', 'Sains', '978-047-046-228-8', 'Wiley', 2010, 'Mekanika, gelombang, termodinamika, dan elektromagnetisme.', 4, 4, DATE_SUB(NOW(), INTERVAL 310 DAY)),
(15, 'Kimia Organik', 'Jonathan Clayden', 'Sains', '978-019-927-029-3', 'Oxford University Press', 2012, 'Struktur, reaksi, dan sintesis senyawa organik.', 2, 2, DATE_SUB(NOW(), INTERVAL 305 DAY)),
(16, 'Biologi Molekuler Sel', 'Bruce Alberts', 'Sains', '978-081-534-100-6', 'Garland Science', 2015, 'Mekanisme seluler dan molekuler kehidupan.', 2, 2, DATE_SUB(NOW(), INTERVAL 300 DAY)),
(17, 'Sejarah Indonesia Modern', 'M.C. Ricklefs', 'Sejarah', '978-979-461-568-9', 'Serambi', 2008, 'Dinamika politik dan sosial Indonesia sejak abad ke-13.', 4, 4, DATE_SUB(NOW(), INTERVAL 295 DAY)),
(18, 'Api Sejarah', 'Ahmad Mansur Suryanegara', 'Sejarah', '978-979-166-11-7-4', 'Salamadani', 2009, 'Peran umat Islam dalam perjalanan sejarah Indonesia.', 3, 3, DATE_SUB(NOW(), INTERVAL 290 DAY)),
(19, 'Nusantara', 'Bernard H. M. Vlekke', 'Sejarah', '978-979-910-056-7', 'Kepustakaan Populer Gramedia', 2008, 'Sejarah kepulauan Nusantara dari masa awal.', 2, 2, DATE_SUB(NOW(), INTERVAL 285 DAY)),
(20, 'Pengantar Ilmu Ekonomi', 'Sadono Sukirno', 'Ekonomi', '978-979-769-273-3', 'Rajawali Pers', 2013, 'Konsep dasar mikro dan makroekonomi.', 4, 4, DATE_SUB(NOW(), INTERVAL 280 DAY)),
(21, 'Manajemen Keuangan', 'Eugene F. Brigham', 'Ekonomi', '978-981-441-055-6', 'Cengage Learning', 2014, 'Pengelolaan keuangan perusahaan dan keputusan investasi.', 3, 3, DATE_SUB(NOW(), INTERVAL 275 DAY)),
(22, 'Ekonomi Pembangunan', 'Michael P. Todaro', 'Ekonomi', '978-129-200-127-6', 'Pearson', 2011, 'Teori dan kebijakan pembangunan ekonomi negara berkembang.', 2, 2, DATE_SUB(NOW(), INTERVAL 270 DAY)),
(23, 'Sosiologi Umum', 'Soerjono Soekanto', 'Sosial', '978-979-421-023-9', 'Rajawali Pers', 2012, 'Konsep dasar sosiologi dan masyarakat.', 4, 4, DATE_SUB(NOW(), INTERVAL 265 DAY)),
(24, 'Psikologi Pendidikan', 'John W. Santrock', 'Pendidikan', '978-602-8555-14-2', 'Salemba Humanika', 2011, 'Perkembangan peserta didik dan proses belajar.', 3, 3, DATE_SUB(NOW(), INTERVAL 260 DAY)),
(25, 'Dasar-Dasar Kependidikan', 'Hasbullah', 'Pendidikan', '978-979-769-741-7', 'Rajawali Pers', 2015, 'Landasan filosofis dan sosial pendidikan.', 3, 3, DATE_SUB(NOW(), INTERVAL 255 DAY)),
(26, 'Metodologi Penelitian', 'Sugiyono', 'Pendidikan', '978-602-289-373-8', 'Alfabeta', 2017, 'Metode penelitian kuantitatif, kualitatif, dan R&D.', 5, 5, DATE_SUB(NOW(), INTERVAL 250 DAY)),
(27, 'Seni Rupa Indonesia', 'Soedarso Sp.', 'Seni', '978-979-126-123-4', 'BP ISI Yogyakarta', 2006, 'Perkembangan seni rupa Indonesia dalam tinjauan sejarah.', 2, 2, DATE_SUB(NOW(), INTERVAL 245 DAY)),
(28, 'Desain Komunikasi Visual', 'Adi Kusrianto', 'Seni', '978-979-29-6187-1', 'Andi Publisher', 2007, 'Prinsip desain grafis dan komunikasi visual.', 3, 3, DATE_SUB(NOW(), INTERVAL 240 DAY)),
(29, 'Filsafat Ilmu', 'Jujun S. Suriasumantri', 'Filsafat', '978-979-428-553-8', 'Pustaka Sinar Harapan', 2003, 'Hakikat pengetahuan dan metodologi keilmuan.', 3, 3, DATE_SUB(NOW(), INTERVAL 235 DAY)),
(30, 'Etika', 'K. Bertens', 'Filsafat', '978-979-605-466-0', 'Gramedia Pustaka Utama', 2007, 'Dasar-dasar etika dan penerapannya dalam kehidupan.', 2, 2, DATE_SUB(NOW(), INTERVAL 230 DAY)),
(31, 'Laut Bercerita', 'Leila S. Chudori', 'Sastra', '978-602-424-722-7', 'Kepustakaan Populer Gramedia', 2017, 'Kisah aktivis mahasiswa yang hilang pada 1998.', 4, 4, DATE_SUB(NOW(), INTERVAL 225 DAY)),
(32, 'Cantik Itu Luka', 'Eka Kurniawan', 'Sastra', '978-602-030-218-5', 'Gramedia Pustaka Utama', 2002, 'Saga keluarga dan sejarah kelam sebuah kota.', 3, 3, DATE_SUB(NOW(), INTERVAL 220 DAY)),
(33, 'Pulang', 'Leila S. Chudori', 'Sastra', '978-602-424-010-5', 'Kepustakaan Populer Gramedia', 2012, 'Para eksil politik dan kerinduan untuk pulang.', 3, 3, DATE_SUB(NOW(), INTERVAL 215 DAY)),
(34, 'Negeri 5 Menara', 'Ahmad Fuadi', 'Fiksi', '978-979-22-4864-9', 'Gramedia Pustaka Utama', 2009, 'Persahabatan santri dan mantra man jadda wajada.', 4, 4, DATE_SUB(NOW(), INTERVAL 210 DAY));

-- ----------------------------------------------------------------------------
-- Peminjaman: 14 aktif (7 terlambat) + 46 riwayat 12 bulan terakhir
-- ----------------------------------------------------------------------------
INSERT INTO `peminjaman` (`id_peminjaman`, `id_anggota`, `id_buku`, `tgl_pinjam`, `tgl_kembali_rencana`, `tgl_kembali_real`, `status`, `denda_per_hari`, `created_by`, `created_at`) VALUES
(1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 25 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(2, 1, 8, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 1 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 2, 4, DATE_SUB(CURDATE(), INTERVAL 32 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 32 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 32 DAY)),
(4, 2, 26, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 3, 2, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 20 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(6, 4, 31, DATE_SUB(CURDATE(), INTERVAL 18 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 18 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
(7, 4, 33, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 3 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(8, 5, 5, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 15 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(9, 5, 9, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 4 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(10, 6, 10, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 5 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(11, 7, 12, DATE_SUB(CURDATE(), INTERVAL 21 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 21 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 21 DAY)),
(12, 8, 29, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 2 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(13, 9, 17, DATE_SUB(CURDATE(), INTERVAL 16 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 16 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 16 DAY)),
(14, 10, 23, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 6 DAY), INTERVAL 7 DAY), NULL, 'dipinjam', 1000, 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(15, 1, 1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 14 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
(16, 8, 12, DATE_SUB(CURDATE(), INTERVAL 23 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 23 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 13 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 23 DAY)),
(17, 15, 23, DATE_SUB(CURDATE(), INTERVAL 32 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 32 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 27 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 32 DAY)),
(18, 7, 34, DATE_SUB(CURDATE(), INTERVAL 41 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 41 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 29 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 41 DAY)),
(19, 14, 11, DATE_SUB(CURDATE(), INTERVAL 42 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 42 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 35 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 42 DAY)),
(20, 6, 22, DATE_SUB(CURDATE(), INTERVAL 51 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 51 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 45 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 51 DAY)),
(21, 13, 33, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 60 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 51 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
(22, 5, 10, DATE_SUB(CURDATE(), INTERVAL 69 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 69 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 65 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 69 DAY)),
(23, 12, 21, DATE_SUB(CURDATE(), INTERVAL 70 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 70 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 59 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 70 DAY)),
(24, 4, 32, DATE_SUB(CURDATE(), INTERVAL 79 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 79 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 73 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 79 DAY)),
(25, 11, 9, DATE_SUB(CURDATE(), INTERVAL 88 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 88 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 83 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 88 DAY)),
(26, 3, 20, DATE_SUB(CURDATE(), INTERVAL 97 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 97 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 83 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 97 DAY)),
(27, 10, 31, DATE_SUB(CURDATE(), INTERVAL 98 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 98 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 91 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 98 DAY)),
(28, 2, 8, DATE_SUB(CURDATE(), INTERVAL 107 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 107 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 97 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 107 DAY)),
(29, 9, 19, DATE_SUB(CURDATE(), INTERVAL 116 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 116 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 111 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 116 DAY)),
(30, 1, 30, DATE_SUB(CURDATE(), INTERVAL 125 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 125 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 121 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 125 DAY)),
(31, 8, 7, DATE_SUB(CURDATE(), INTERVAL 126 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 126 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 113 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 126 DAY)),
(32, 15, 18, DATE_SUB(CURDATE(), INTERVAL 135 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 135 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 129 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 135 DAY)),
(33, 7, 29, DATE_SUB(CURDATE(), INTERVAL 144 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 144 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 135 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 144 DAY)),
(34, 14, 6, DATE_SUB(CURDATE(), INTERVAL 153 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 153 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 149 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 153 DAY)),
(35, 6, 17, DATE_SUB(CURDATE(), INTERVAL 154 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 154 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 147 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 154 DAY)),
(36, 13, 28, DATE_SUB(CURDATE(), INTERVAL 163 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 163 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 151 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 163 DAY)),
(37, 5, 5, DATE_SUB(CURDATE(), INTERVAL 172 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 172 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 167 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 172 DAY)),
(38, 12, 16, DATE_SUB(CURDATE(), INTERVAL 181 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 181 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 167 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 181 DAY)),
(39, 4, 27, DATE_SUB(CURDATE(), INTERVAL 182 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 182 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 175 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 182 DAY)),
(40, 11, 4, DATE_SUB(CURDATE(), INTERVAL 191 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 191 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 185 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 191 DAY)),
(41, 3, 15, DATE_SUB(CURDATE(), INTERVAL 200 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 200 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 189 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 200 DAY)),
(42, 10, 26, DATE_SUB(CURDATE(), INTERVAL 209 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 209 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 205 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 209 DAY)),
(43, 2, 3, DATE_SUB(CURDATE(), INTERVAL 210 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 210 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 197 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 210 DAY)),
(44, 9, 14, DATE_SUB(CURDATE(), INTERVAL 219 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 219 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 213 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 219 DAY)),
(45, 1, 25, DATE_SUB(CURDATE(), INTERVAL 228 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 228 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 223 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 228 DAY)),
(46, 8, 2, DATE_SUB(CURDATE(), INTERVAL 237 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 237 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 227 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 237 DAY)),
(47, 15, 13, DATE_SUB(CURDATE(), INTERVAL 238 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 238 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 231 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 238 DAY)),
(48, 7, 24, DATE_SUB(CURDATE(), INTERVAL 247 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 247 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 235 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 247 DAY)),
(49, 14, 1, DATE_SUB(CURDATE(), INTERVAL 256 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 256 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 251 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 256 DAY)),
(50, 6, 12, DATE_SUB(CURDATE(), INTERVAL 265 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 265 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 261 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 265 DAY)),
(51, 13, 23, DATE_SUB(CURDATE(), INTERVAL 266 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 266 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 257 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 266 DAY)),
(52, 5, 34, DATE_SUB(CURDATE(), INTERVAL 275 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 275 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 269 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 275 DAY)),
(53, 12, 11, DATE_SUB(CURDATE(), INTERVAL 284 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 284 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 273 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 284 DAY)),
(54, 4, 22, DATE_SUB(CURDATE(), INTERVAL 293 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 293 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 289 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 293 DAY)),
(55, 11, 33, DATE_SUB(CURDATE(), INTERVAL 294 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 294 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 287 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 294 DAY)),
(56, 3, 10, DATE_SUB(CURDATE(), INTERVAL 303 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 303 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 289 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 303 DAY)),
(57, 10, 21, DATE_SUB(CURDATE(), INTERVAL 312 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 312 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 307 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 312 DAY)),
(58, 2, 32, DATE_SUB(CURDATE(), INTERVAL 321 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 321 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 311 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 321 DAY)),
(59, 9, 9, DATE_SUB(CURDATE(), INTERVAL 322 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 322 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 315 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 322 DAY)),
(60, 1, 20, DATE_SUB(CURDATE(), INTERVAL 331 DAY), DATE_ADD(DATE_SUB(CURDATE(), INTERVAL 331 DAY), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 325 DAY), 'dikembalikan', 1000, 1, DATE_SUB(NOW(), INTERVAL 331 DAY));

-- Stok tersedia = total eksemplar - peminjaman yang masih berjalan.
UPDATE `buku` b SET b.`stok_tersedia` = b.`jumlah_eksemplar` - (
  SELECT COUNT(*) FROM `peminjaman` p
  WHERE p.`id_buku` = b.`id_buku` AND p.`status` = 'dipinjam'
);

-- ----------------------------------------------------------------------------
-- Denda otomatis dari pengembalian yang melewati jatuh tempo.
-- Sekitar sepertiga denda ditandai lunas agar laporan punya dua kondisi.
-- ----------------------------------------------------------------------------
INSERT INTO `denda` (`id_peminjaman`, `jumlah_hari`, `tarif_per_hari`, `total_denda`, `status_bayar`, `tgl_bayar`, `paid_by`, `created_at`)
SELECT p.`id_peminjaman`,
       DATEDIFF(p.`tgl_kembali_real`, p.`tgl_kembali_rencana`),
       p.`denda_per_hari`,
       DATEDIFF(p.`tgl_kembali_real`, p.`tgl_kembali_rencana`) * p.`denda_per_hari`,
       CASE WHEN p.`id_peminjaman` % 3 = 0 THEN 'lunas' ELSE 'belum_lunas' END,
       CASE WHEN p.`id_peminjaman` % 3 = 0 THEN DATE_ADD(p.`tgl_kembali_real`, INTERVAL 2 DAY) ELSE NULL END,
       CASE WHEN p.`id_peminjaman` % 3 = 0 THEN 1 ELSE NULL END,
       TIMESTAMP(p.`tgl_kembali_real`, '11:00:00')
FROM `peminjaman` p
WHERE p.`status` = 'dikembalikan'
  AND p.`tgl_kembali_real` > p.`tgl_kembali_rencana`
ORDER BY p.`id_peminjaman`;

-- ----------------------------------------------------------------------------
-- Pengaturan aplikasi (satu baris)
-- ----------------------------------------------------------------------------
INSERT INTO `pengaturan` (`id_pengaturan`, `nama_aplikasi`, `nama_instansi`, `tarif_denda`, `durasi_hari`, `maks_pinjam_default`, `email_kontak`, `alamat`) VALUES
(1, 'BukuHub', 'Perpustakaan Digital BukuHub', 1000, 7, 3, 'halo@bukuhub.id', 'Jl. Pendidikan No. 1, Bandung');

-- ----------------------------------------------------------------------------
-- Catatan audit awal agar tabel tidak kosong.
-- ----------------------------------------------------------------------------
INSERT INTO `audit_log` (`id_user`, `aksi`, `entitas`, `entitas_id`, `detail`, `ip_address`, `created_at`) VALUES
(1, 'seed', 'database', NULL, 'Data contoh BukuHub berhasil dimuat', '127.0.0.1', NOW());
