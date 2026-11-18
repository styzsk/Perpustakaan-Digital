# Arsip prototipe lama (legacy)

Folder ini menyimpan halaman HTML prototipe awal BukuHub (sebelumnya Librio)
yang HTML-nya rusak/terpotong. File di sini **tidak dipakai** oleh aplikasi
PHP yang berjalan.

- `index.html` — dashboard
- `catalog.html` — katalog buku
- `members.html` — anggota
- `borrowing.html` — peminjaman
- `reports.html` — laporan

Catatan:

- Tautan aset relatif di halaman ini sengaja tidak diperbaiki, sehingga CSS/JS
  bisa gagal dimuat bila dibuka langsung. Halaman ini hanya arsip untuk
  membandingkan desain awal.
- Aplikasi aktif ada di root proyek melalui `index.php?page=...`.
- Jangan menautkan halaman di folder ini dari aplikasi aktif.
