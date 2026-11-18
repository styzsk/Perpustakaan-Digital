<?php
/** @var string $pageKey */
/** @var array $user */
/** @var array $navCounts */
?>
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
  <div class="sidebar-head">
    <a class="brand" href="<?= e(url('dashboard')) ?>" aria-label="BukuHub — beranda">
      <span class="brand-mark">
        <svg class="icon"><use href="#i-book-open" /></svg>
      </span>
      <span class="brand-text">
        <span class="brand-name">BukuHub<span class="brand-dot">.</span></span>
        <span class="brand-tag">Perpustakaan Digital</span>
      </span>
    </a>
    <button class="icon-btn sidebar-close" id="sidebarClose" type="button" aria-label="Tutup menu">
      <svg class="icon"><use href="#i-x" /></svg>
    </button>
  </div>

  <nav class="side-nav">
    <p class="side-label">Menu Utama</p>

    <a class="side-link<?= nav_active($pageKey, 'dashboard') ?>" href="<?= e(url('dashboard')) ?>"
      data-route="dashboard"<?= $pageKey === 'dashboard' ? ' aria-current="page"' : '' ?>>
      <svg class="icon"><use href="#i-grid" /></svg><span>Dashboard</span>
    </a>

    <a class="side-link<?= nav_active($pageKey, 'books') ?>" href="<?= e(url('books')) ?>"
      data-route="books"<?= $pageKey === 'books' ? ' aria-current="page"' : '' ?>>
      <svg class="icon"><use href="#i-book" /></svg><span>Katalog Buku</span>
    </a>

    <a class="side-link<?= nav_active($pageKey, 'members') ?>" href="<?= e(url('members')) ?>"
      data-route="members"<?= $pageKey === 'members' ? ' aria-current="page"' : '' ?>>
      <svg class="icon"><use href="#i-users" /></svg><span>Anggota</span>
    </a>

    <a class="side-link<?= nav_active($pageKey, 'borrowing') ?>" href="<?= e(url('borrowing')) ?>"
      data-route="borrowing"<?= $pageKey === 'borrowing' ? ' aria-current="page"' : '' ?>>
      <svg class="icon"><use href="#i-repeat" /></svg><span>Peminjaman</span>
      <span class="side-badge"><?= (int) ($navCounts['active_loans'] ?? 0) ?></span>
    </a>

    <a class="side-link<?= nav_active($pageKey, 'fines') ?>" href="<?= e(url('fines')) ?>"
      data-route="fines"<?= $pageKey === 'fines' ? ' aria-current="page"' : '' ?>>
      <svg class="icon"><use href="#i-wallet" /></svg><span>Denda</span>
      <?php if ((int) ($navCounts['unpaid_fines'] ?? 0) > 0): ?>
        <span class="side-badge"><?= (int) $navCounts['unpaid_fines'] ?></span>
      <?php endif; ?>
    </a>

    <a class="side-link<?= nav_active($pageKey, 'reports') ?>" href="<?= e(url('reports')) ?>"
      data-route="reports"<?= $pageKey === 'reports' ? ' aria-current="page"' : '' ?>>
      <svg class="icon"><use href="#i-chart" /></svg><span>Laporan</span>
    </a>

    <p class="side-label side-label--spaced">Sistem</p>

    <a class="side-link<?= nav_active($pageKey, 'settings') ?>" href="<?= e(url('settings')) ?>"
      data-route="settings"<?= $pageKey === 'settings' ? ' aria-current="page"' : '' ?>>
      <svg class="icon"><use href="#i-settings" /></svg><span>Pengaturan</span>
    </a>

    <a class="side-link<?= nav_active($pageKey, 'help') ?>" href="<?= e(url('help')) ?>"
      data-route="help"<?= $pageKey === 'help' ? ' aria-current="page"' : '' ?>>
      <svg class="icon"><use href="#i-help" /></svg><span>Pusat Bantuan</span>
    </a>
  </nav>

  <div class="sidebar-foot">
    <div class="side-profile">
      <span class="avatar avatar--sidebar"><?= e(initials($user['nama'] ?? 'Admin')) ?></span>
      <span class="side-profile-meta">
        <strong><?= e($user['nama'] ?? 'Pustakawan') ?></strong>
        <span><?= e(ucfirst($user['role'] ?? 'pustakawan')) ?></span>
      </span>
      <form method="post" action="<?= e(url('login', ['action' => 'logout'])) ?>" class="logout-form">
        <?= csrf_field() ?>
        <button class="icon-btn icon-btn--ghost" type="submit" aria-label="Keluar" title="Keluar">
          <svg class="icon"><use href="#i-logout" /></svg>
        </button>
      </form>
    </div>
    <p class="sidebar-version">BukuHub v<?= e(config('version', '1.0.0')) ?> · PHP Native</p>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
