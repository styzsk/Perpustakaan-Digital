<?php
/**
 * Layout halaman autentikasi.
 *
 * @var string $content
 * @var string $pageTitle
 */
$documentTitle = ($pageTitle ?? 'Masuk') . ' · BukuHub';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <meta name="theme-color" content="#1a1c22">
  <title><?= e($documentTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%23ff6b2c'/%3E%3Cpath d='M16 46V18h12l4 8 4-8h12v28H38V32l-6 11-6-11v14z' fill='white'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/widgets.css">
  <link rel="stylesheet" href="assets/css/modules.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-body">
  <?php require BASE_PATH . '/views/partials/icons.php'; ?>

  <main class="auth-shell">
    <section class="auth-art" aria-label="Tentang BukuHub">
      <div class="auth-art-glow auth-art-glow--one"></div>
      <div class="auth-art-glow auth-art-glow--two"></div>

      <a class="brand auth-brand" href="<?= e(url('login')) ?>">
        <span class="brand-mark">
          <svg class="icon"><use href="#i-book-open" /></svg>
        </span>
        <span class="brand-text">
          <span class="brand-name">BukuHub<span class="brand-dot">.</span></span>
          <span class="brand-tag">Perpustakaan Digital</span>
        </span>
      </a>

      <div class="auth-art-copy">
        <p class="auth-eyebrow"><span class="live-dot"></span> Ruang kerja pustakawan</p>
        <h1>Kelola koleksi, anggota, dan peminjaman dalam satu tempat.</h1>
        <p>Katalog rapi, stok akurat, denda otomatis, dan laporan yang bisa diunduh — semuanya berjalan di server Anda sendiri.</p>
      </div>

      <ul class="auth-points">
        <li>
          <span class="auth-point-icon"><svg class="icon"><use href="#i-book" /></svg></span>
          <span><strong>Katalog terkendali</strong>Stok total dan stok tersedia dipisah.</span>
        </li>
        <li>
          <span class="auth-point-icon"><svg class="icon"><use href="#i-clock" /></svg></span>
          <span><strong>Denda otomatis</strong>Dihitung saat buku terlambat dikembalikan.</span>
        </li>
        <li>
          <span class="auth-point-icon"><svg class="icon"><use href="#i-chart" /></svg></span>
          <span><strong>Laporan nyata</strong>Angka diambil langsung dari database.</span>
        </li>
      </ul>

      <p class="auth-foot">BukuHub v<?= e(config('version', '1.0.0')) ?> · PHP Native + PDO + MySQL</p>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <?= $content ?>
      </div>
      <p class="auth-legal">Akses hanya untuk petugas perpustakaan. Sesi berakhir otomatis setelah tidak aktif.</p>
    </section>
  </main>

  <?php require BASE_PATH . '/views/partials/toasts.php'; ?>
  <script src="assets/js/app.js"></script>
</body>
</html>
