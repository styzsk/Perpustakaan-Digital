<?php
/**
 * Topbar: judul halaman, pencarian katalog, notifikasi, dan aksi utama.
 *
 * @var string $pageKey
 * @var string $pageTitle
 * @var array  $notifications
 * @var array  $navCounts
 * @var array  $user
 */
$notificationCount = count($notifications ?? []);
$unread = $notificationCount;

$topbarAction = null;
switch ($pageKey) {
    case 'dashboard':
    case 'books':
        $topbarAction = ['type' => 'modal', 'modal' => 'book', 'label' => 'Tambah Buku', 'icon' => 'plus'];
        break;
    case 'members':
        $topbarAction = ['type' => 'modal', 'modal' => 'member', 'label' => 'Tambah Anggota', 'icon' => 'users'];
        break;
    case 'borrowing':
        $topbarAction = ['type' => 'modal', 'modal' => 'loan', 'label' => 'Peminjaman Baru', 'icon' => 'repeat'];
        break;
    case 'fines':
        $topbarAction = ['type' => 'link', 'href' => url('fines', ['action' => 'export']), 'label' => 'Ekspor CSV', 'icon' => 'download'];
        break;
    case 'reports':
        $topbarAction = ['type' => 'link', 'href' => url('reports', [
            'action' => 'export',
            'preset' => input('preset', '30'),
            'from' => input('from', ''),
            'to' => input('to', ''),
        ]), 'label' => 'Ekspor Laporan', 'icon' => 'download'];
        break;
    default:
        $topbarAction = null;
}

$searchValue = $pageKey === 'books' ? (string) (($filters['q'] ?? '')) : (string) ($_GET['q'] ?? '');
?>
<header class="topbar">
  <div class="topbar-left">
    <button class="icon-btn menu-btn" id="sidebarOpen" type="button" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
      <svg class="icon"><use href="#i-menu" /></svg>
    </button>
    <div class="topbar-titles">
      <p class="eyebrow"><span class="eyebrow-dot"></span> BukuHub · <?= e(format_date_long(today())) ?></p>
      <h1 class="page-title" id="pageTitle"><?= e($pageTitle ?? 'Dashboard') ?></h1>
    </div>
  </div>

  <div class="topbar-actions">
    <form class="search" id="searchWrap" method="get" action="index.php" role="search">
      <input type="hidden" name="page" value="books">
      <svg class="icon search-icon"><use href="#i-search" /></svg>
      <input type="search" id="globalSearch" name="q" value="<?= e($searchValue) ?>"
        placeholder="Cari judul, pengarang, ISBN…" aria-label="Cari di katalog buku" autocomplete="off">
      <kbd class="search-kbd">/</kbd>
      <button class="search-clear" id="searchClear" type="button" aria-label="Bersihkan pencarian">
        <svg class="icon"><use href="#i-x" /></svg>
      </button>
    </form>

    <?php if ($topbarAction && $topbarAction['type'] === 'modal'): ?>
      <button class="btn-add" type="button" data-modal-open="<?= e($topbarAction['modal']) ?>"
        aria-label="<?= e($topbarAction['label']) ?>" id="topbarAction">
        <svg class="icon"><use href="#i-<?= e($topbarAction['icon']) ?>" /></svg>
        <span id="topbarActionLabel"><?= e($topbarAction['label']) ?></span>
      </button>
    <?php elseif ($topbarAction): ?>
      <a class="btn-add" href="<?= e($topbarAction['href']) ?>" id="topbarAction" aria-label="<?= e($topbarAction['label']) ?>">
        <svg class="icon"><use href="#i-<?= e($topbarAction['icon']) ?>" /></svg>
        <span id="topbarActionLabel"><?= e($topbarAction['label']) ?></span>
      </a>
    <?php endif; ?>

    <div class="dropdown">
      <button class="icon-btn bell-btn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
        aria-expanded="false" aria-label="Notifikasi, <?= $unread ?> item">
        <svg class="icon"><use href="#i-bell" /></svg>
        <span class="bell-dot<?= $unread === 0 ? ' is-hidden' : '' ?>" id="notifDot"></span>
      </button>

      <div class="dropdown-menu dropdown-menu-end notif-menu">
        <div class="notif-head">
          <div>
            <strong>Notifikasi</strong>
            <span id="notifSummary"><?= $unread > 0 ? $unread . ' hal perlu perhatian' : 'Semua aman' ?></span>
          </div>
          <button class="notif-read" id="markAllRead" type="button">Tandai dibaca</button>
        </div>

        <div class="notif-list">
          <?php if ($notificationCount === 0): ?>
            <div class="notif-empty">
              <svg class="icon"><use href="#i-check" /></svg>
              <p>Tidak ada keterlambatan atau denda tertunda.</p>
            </div>
          <?php else: ?>
            <?php foreach ($notifications as $item): ?>
              <a class="notif-item is-unread" href="<?= e($item['url']) ?>">
                <span class="notif-icon notif-icon--<?= e($item['tone']) ?>">
                  <svg class="icon"><use href="#i-<?= e($item['icon']) ?>" /></svg>
                </span>
                <span class="notif-body">
                  <strong><?= e($item['title']) ?></strong>
                  <span><?= e($item['message']) ?></span>
                  <small><?= e($item['time']) ?></small>
                </span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <a class="notif-foot" href="<?= e(url('borrowing', ['tab' => 'overdue'])) ?>">
          Lihat peminjaman terlambat
          <svg class="icon"><use href="#i-arrow-right" /></svg>
        </a>
      </div>
    </div>
  </div>
</header>
