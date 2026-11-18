<?php
/**
 * Pusat bantuan.
 *
 * @var array $bookStats
 * @var array $memberStats
 * @var array $loanStats
 * @var array $fineStats
 * @var int   $duration
 * @var int   $tariff
 */
?>
<section class="view is-active" aria-labelledby="helpHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Panduan</p>
      <h2 class="module-title" id="helpHeading">Pusat bantuan</h2>
      <p class="module-sub">Alur kerja singkat BukuHub, aturan bisnis yang berjalan di server, dan tips penyelesaian masalah.</p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('dashboard')) ?>">
        <svg class="icon"><use href="#i-grid" /></svg> Ke dashboard
      </a>
    </div>
  </div>

  <div class="mini-stats">
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-clock" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Durasi pinjam</span>
        <p class="mini-stat-value"><?= $duration ?> hari</p>
        <span class="mini-stat-meta">Jatuh tempo otomatis</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--dark"><svg class="icon"><use href="#i-wallet" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Tarif denda</span>
        <p class="mini-stat-value"><?= money($tariff) ?></p>
        <span class="mini-stat-meta">Per hari keterlambatan</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--green"><svg class="icon"><use href="#i-book" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Koleksi aktif</span>
        <p class="mini-stat-value"><?= num($bookStats['total_judul'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($bookStats['stok_tersedia'] ?? 0) ?> eksemplar tersedia</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-users" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Anggota aktif</span>
        <p class="mini-stat-value"><?= num($memberStats['aktif'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($loanStats['aktif'] ?? 0) ?> pinjaman berjalan</span>
      </div>
    </div>
  </div>

  <div class="help-grid">
    <article class="card help-card">
      <span class="help-icon"><svg class="icon"><use href="#i-repeat" /></svg></span>
      <h3>Alur peminjaman</h3>
      <p>Buka menu <strong>Peminjaman</strong>, pilih anggota dan buku yang stoknya tersedia. Server memeriksa status anggota, batas pinjam, dan stok di dalam satu transaksi database.</p>
      <ul class="help-list">
        <li>Tanggal pinjam maksimal 30 hari ke belakang.</li>
        <li>Jatuh tempo = tanggal pinjam + <?= $duration ?> hari.</li>
        <li>Stok berkurang satu secara otomatis.</li>
      </ul>
    </article>

    <article class="card help-card">
      <span class="help-icon"><svg class="icon"><use href="#i-check" /></svg></span>
      <h3>Pengembalian &amp; denda</h3>
      <p>Tombol <strong>Kembalikan</strong> menutup transaksi, menambah stok, dan menghitung keterlambatan. Denda baru dibuat jika tanggal kembali melewati jatuh tempo.</p>
      <ul class="help-list">
        <li>Tarif aktif <?= money($tariff) ?> per hari.</li>
        <li>Denda hanya bisa dibayar sekali.</li>
        <li>Riwayat tetap tersimpan setelah lunas.</li>
      </ul>
    </article>

    <article class="card help-card">
      <span class="help-icon"><svg class="icon"><use href="#i-book" /></svg></span>
      <h3>Stok buku</h3>
      <p><strong>Jumlah eksemplar</strong> adalah total kopi yang dimiliki. <strong>Stok tersedia</strong> adalah kopi yang ada di rak. Keduanya dipisahkan agar laporan stok tetap akurat.</p>
      <ul class="help-list">
        <li>Stok hanya berubah lewat transaksi.</li>
        <li>Total eksemplar tidak boleh kurang dari yang sedang dipinjam.</li>
        <li>Buku dengan riwayat pinjam tidak bisa dihapus.</li>
      </ul>
    </article>

    <article class="card help-card">
      <span class="help-icon"><svg class="icon"><use href="#i-lock" /></svg></span>
      <h3>Keamanan</h3>
      <p>Semua form yang mengubah data memakai token CSRF. Query memakai prepared statement PDO, output di-escape, dan password disimpan dengan <code>password_hash</code>.</p>
      <ul class="help-list">
        <li>Sesi HttpOnly + SameSite Lax.</li>
        <li>Maksimal 5 percobaan login per 5 menit.</li>
        <li>Aktivitas penting dicatat di audit log.</li>
      </ul>
    </article>

    <article class="card help-card">
      <span class="help-icon"><svg class="icon"><use href="#i-search" /></svg></span>
      <h3>Pencarian &amp; filter</h3>
      <p>Pencarian, filter, pengurutan, dan pagination diproses di server dengan <code>LIMIT/OFFSET</code>. Gunakan kolom pencarian di dalam tabel untuk hasil yang lebih spesifik.</p>
      <ul class="help-list">
        <li>Tekan <kbd>/</kbd> untuk fokus ke pencarian katalog.</li>
        <li>Filter akan diterapkan otomatis saat diubah.</li>
        <li>Tombol <strong>Reset</strong> menghapus semua filter.</li>
      </ul>
    </article>

    <article class="card help-card">
      <span class="help-icon"><svg class="icon"><use href="#i-download" /></svg></span>
      <h3>Ekspor &amp; laporan</h3>
      <p>CSV dibuat dari database, bukan dari tampilan. Buka menu Laporan untuk memilih periode, lalu unduh ringkasan lengkap berisi tren, buku populer, kategori, anggota, dan denda.</p>
      <ul class="help-list">
        <li>File CSV memakai UTF-8 BOM agar rapi di Excel.</li>
        <li>Periode khusus memakai tanggal dari–sampai.</li>
        <li>Semua angka mengikuti data saat tombol diklik.</li>
      </ul>
    </article>
  </div>

  <div class="card report-card">
    <div class="report-head">
      <div>
        <h2 class="module-title module-title--sm">Pemecahan masalah cepat</h2>
        <p class="module-sub">Gejala umum saat menjalankan di XAMPP atau Laragon.</p>
      </div>
      <span class="pill-soft">FAQ</span>
    </div>
    <div class="settings-grid">
      <div>
        <h3 class="help-subtitle">Tidak bisa terhubung ke database</h3>
        <p class="module-sub">Pastikan MySQL berjalan, database <code>bukuhub</code> sudah diimpor, dan kredensial di <code>config/database.php</code> sesuai. Pengguna default XAMPP/Laragon adalah <code>root</code> tanpa password.</p>
        <h3 class="help-subtitle">Halaman menampilkan error 500</h3>
        <p class="module-sub">Buka <code>storage/logs/app.log</code> dan <code>storage/logs/php-error.log</code>. Detail teknis sengaja tidak ditampilkan di browser.</p>
      </div>
      <div>
        <h3 class="help-subtitle">Tombol simpan tidak merespons</h3>
        <p class="module-sub">Pastikan JavaScript tidak diblokir. Validasi utama tetap berjalan di server, tetapi modal memerlukan JavaScript Bootstrap untuk tampil.</p>
        <h3 class="help-subtitle">Data contoh ingin direset</h3>
        <p class="module-sub">Impor ulang <code>database/schema.sql</code> lalu <code>database/seed.sql</code>. Tindakan ini menghapus seluruh data lama.</p>
      </div>
    </div>
    <p class="fine-foot">Butuh bantuan lebih lanjut? Hubungi pengelola sistem dan sertakan waktu kejadian serta isi log terbaru. Jangan pernah membagikan password atau hash password.</p>
  </div>
</section>
