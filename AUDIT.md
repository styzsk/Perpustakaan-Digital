# FASE 1 — Audit BukuHub (sebelumnya Librio)

Dokumen ini mencatat temuan audit kode aktual dan rencana implementasi yang
dipakai pada pengerjaan berikutnya. Sumber kebenaran adalah isi file di repo,
bukan README lama.

## 1. Kondisi proyek saat audit

| Area | Kondisi aktual |
| --- | --- |
| Halaman | `index.html`, `catalog.html`, `members.html`, `borrowing.html`, `reports.html` — tidak ada `settings.html` dan `help.html` walau ditautkan di sidebar dan README |
| Backend | Tidak ada. Tidak ada PHP, tidak ada koneksi database |
| Database | Tidak ada `schema.sql` / `seed.sql` |
| Data | Tidak ada `assets/js/data.js`. `store.js` hanya stub dengan array kosong |
| Interaksi | Semua aksi ubah data inert (`demoOnly()`), `logout` hanya toast, tombol topbar memakai `data-bs-target="#bookModal"` padahal modal sudah dihapus |
| Render | `modules.js` hanya menulis placeholder "Backend pending" ke setiap `<tbody>` |
| Aset | `assets/css/native-modals.css` direferensikan semua halaman tetapi tidak ada |

## 2. Kerusakan file yang ditemukan

1. **Find–replace merusak HTML.** Kata `title`/`action`/`name` berubah sebagian
   menjadi `Judul`/`Aksi`/`Nama` dan `active` menjadi `Aktif`.
   - `catalog.html`: `<Judul>`, `page-Judul`, `topbar-Aksis`, `data-Aksi`, `th-Aksis`,
     `catalogKategori` (seharusnya `catalogCategory`), `Ketersediaan` (seharusnya
     `Availability`), `Habis`.
   - `members.html`: `<meta Nama=...>`, `.brand-Nama`.
   - `borrowing.html`: `is-Aktif`, `id="DendasBody"` (seharusnya `FinesBody`).
   - Akibatnya class CSS tidak match dan halaman tampil rusak.
2. **Modal dihapus.** Semua komentar `BOOK MODAL`, `MEMBER MODAL`, `NEW LOAN MODAL`,
   `CONFIRM MODAL` kosong. Tidak ada form buku/anggota/peminjaman sama sekali.
3. **Tag script rusak.** `<script src="...bootstrap.bundle.min.js">` tidak ditutup
   dan menelan `<script src="assets/js/app.js">`.
4. **Tag penutup berlebih.** `</div>` ekstra sebelum `.toast-stack`.
5. **CSS tidak ada brand BukuHub**, seluruh komentar dan judul masih "Librio".
6. **Inkonsistensi bahasa**: campuran Inggris/Indonesia pada label, istilah stok
   (`jumlah_eksemplar` dipakai sebagai stok tersedia) bertentangan dengan kebutuhan
   total vs tersedia.
7. **README tidak sesuai kenyataan**: mengklaim 7 halaman lengkap, `data.js`,
   `settings.html`, `help.html`, 34 buku/167 pinjaman, semuanya tidak ada.

## 3. Yang dipertahankan

- Seluruh design system di `assets/css/*.css` (token warna, sidebar, topbar, KPI,
  chart, tabel, modal, toast, pagination, laporan, settings, help).
- Font lokal `assets/vendor/fonts/plus-jakarta-sans-latin.woff2`.
- Bootstrap 5 hanya untuk perilaku modal + dropdown (JS), bukan layout.
- Ikon SVG inline (Lucide-style) yang sudah ada.
- Karakter visual: aksen `#FF6B2C`, sidebar `#1A1C22`, kanvas `#F4F5F7`, radius 22px.

## 4. Rencana implementasi

**FASE 2 — UI**: template PHP + partial (sidebar, topbar, ikon, modal), brand
BukuHub, Plus Jakarta Sans konsisten, perbaikan konflik Bootstrap, halaman baru
Settings, Fines, Help, Login, detail buku/anggota. File HTML lama dipindahkan ke
`legacy/` (tidak dihapus).

**FASE 3 — Database**: `database/schema.sql` + `seed.sql`, 6 tabel utama
(`users`, `anggota`, `buku`, `peminjaman`, `denda`, `pengaturan`), InnoDB,
foreign key, unique, index, stok total (`jumlah_eksemplar`) vs tersedia
(`stok_tersedia`).

**FASE 4 — Backend**: PDO terpusat, model, service, controller, router
whitelist, autentikasi session, CSRF, escaping, validasi server, error logging.

**FASE 5 — Business logic**: CRUD buku/anggota, search backend, pagination
`LIMIT/OFFSET`, transaksi peminjaman dengan `SELECT ... FOR UPDATE`, pengembalian
+ denda otomatis, pembayaran denda anti-double.

**FASE 6 — Integrasi**: seluruh halaman server-rendered dari database, chart dan
dashboard dari query nyata, export CSV dari database, toast dari flash session.

**FASE 7 — Testing & dokumentasi**: README baru berisi instalasi XAMPP/Laragon,
akun demo, relasi, keamanan, troubleshooting, dan catatan pengujian yang benar-benar
dilakukan (termasuk keterbatasan lingkungan tanpa PHP/MySQL).

## 5. Keputusan teknis dan asumsi

1. **Routing**: front controller tunggal `index.php` dengan `?page=...&action=...`.
   Alasan: kompatibel dengan subfolder XAMPP/Laragon tanpa konfigurasi vhost.
2. **Stok**: `buku.jumlah_eksemplar` = total eksemplar, `buku.stok_tersedia` =
   eksemplar siap pinjam. Stok tersedia diubah hanya di dalam transaksi DB.
3. **Status terlambat** tidak disimpan sebagai kolom status agar tidak basi.
   `peminjaman.status` hanya `dipinjam`/`dikembalikan`; keterlambatan dihitung dari
   `tgl_kembali_rencana < CURDATE()`.
4. **Satu denda per peminjaman** dijaga oleh `UNIQUE(id_peminjaman)` pada tabel
   `denda`; denda hanya dibuat saat pengembalian terlambat.
5. **Tarif denda di-snapshot** pada baris peminjaman (`denda_per_hari`) agar
   perubahan tarif di Settings tidak mengubah denda transaksi lama.
6. **Bahasa UI**: Indonesia konsisten, mengikuti instruksi prompt.
7. **Nama class internal** `librio-*` diganti menjadi `app-*` / `modal-app` agar
   tidak ada sisa identitas lama.
