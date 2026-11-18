<?php
/**
 * Layout utama BukuHub.
 *
 * @var string $content
 * @var string $pageKey
 * @var string $pageTitle
 * @var array  $user
 * @var array  $notifications
 * @var array  $navCounts
 * @var string|null $openModal
 */
$documentTitle = ($pageTitle ?? 'Dashboard') . ' · BukuHub';
$bodyPage = $pageKey ?? 'dashboard';

$modalToOpen = (string) ($openModal ?? '');
if ($modalToOpen === '' && (!empty($prefillBook) || !empty($prefillMember))) {
    $modalToOpen = 'loan';
}
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
  <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/widgets.css">
  <link rel="stylesheet" href="assets/css/modules.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body data-page="<?= e($bodyPage) ?>" data-open-modal="<?= e($modalToOpen) ?>">
  <?php require BASE_PATH . '/views/partials/icons.php'; ?>

  <a class="skip-link" href="#mainContent">Lewati ke konten utama</a>

  <div class="app">
    <?php require BASE_PATH . '/views/partials/sidebar.php'; ?>

    <div class="main">
      <?php require BASE_PATH . '/views/partials/topbar.php'; ?>

      <main class="content" id="mainContent">
        <?= $content ?>

        <footer class="page-foot">
          <span>&copy; <?= date('Y') ?> BukuHub. Perpustakaan digital, tanpa CDN.</span>
          <span class="page-foot-links">
            <a href="<?= e(url('help')) ?>">Pusat bantuan</a>
            <a href="<?= e(url('settings')) ?>">Pengaturan</a>
            <span>PHP Native + MySQL</span>
          </span>
        </footer>
      </main>
    </div>
  </div>

  <?php require BASE_PATH . '/views/partials/modals.php'; ?>
  <?php require BASE_PATH . '/views/partials/toasts.php'; ?>

  <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
