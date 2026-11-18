<?php
/**
 * Halaman 404.
 * @var string|null $message
 */
$safeMessage = $message ?? 'Halaman yang Anda cari tidak tersedia atau sudah dipindahkan.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 · BukuHub</title>
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="error-body">
  <main class="error-card">
    <a class="brand error-brand" href="index.php?page=dashboard">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      </span>
      <span class="brand-text">
        <span class="brand-name" style="color:#171a20">BukuHub<span class="brand-dot">.</span></span>
        <span class="brand-tag" style="color:#8b93a1">Perpustakaan Digital</span>
      </span>
    </a>
    <p class="error-code">404</p>
    <h1>Halaman tidak ditemukan</h1>
    <p class="error-copy"><?= e($safeMessage) ?></p>
    <div class="error-actions">
      <a class="btn-add" href="index.php?page=dashboard">Kembali ke dashboard</a>
      <a class="btn-secondary" href="index.php?page=books">Buka katalog</a>
    </div>
  </main>
</body>
</html>
