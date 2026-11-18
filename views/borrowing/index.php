<?php
/**
 * Peminjaman & pengembalian.
 *
 * @var array $filters
 * @var array $loans
 * @var array $tabs
 * @var array $stats
 * @var int   $duration
 * @var int   $tariff
 */
$q = (string) ($filters['q'] ?? '');
$activeTab = (string) ($filters['tab'] ?? 'all');
$tabLabels = [
    'all' => 'Semua',
    'active' => 'Aktif',
    'overdue' => 'Terlambat',
    'returned' => 'Dikembalikan',
];
?>
<section class="view is-active" aria-labelledby="borrowingHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Sirkulasi</p>
      <h2 class="module-title" id="borrowingHeading">Peminjaman &amp; pengembalian</h2>
      <p class="module-sub">Setiap peminjaman mengurangi stok dan setiap pengembalian mengembalikannya. Denda dihitung otomatis saat buku terlambat.</p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('borrowing', ['action' => 'export'])) ?>">
        <svg class="icon"><use href="#i-download" /></svg> Ekspor CSV
      </a>
      <button class="btn-add btn-add--sm" type="button" data-modal-open="loan">
        <svg class="icon"><use href="#i-plus" /></svg> Peminjaman baru
      </button>
    </div>
  </div>

  <div class="mini-stats">
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-repeat" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Peminjaman aktif</span>
        <p class="mini-stat-value"><?= num($stats['aktif'] ?? 0) ?></p>
        <span class="mini-stat-meta">Buku belum kembali</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--dark"><svg class="icon"><use href="#i-clock" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Terlambat</span>
        <p class="mini-stat-value"><?= num($stats['terlambat'] ?? 0) ?></p>
        <span class="mini-stat-meta">Melewati jatuh tempo</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--green"><svg class="icon"><use href="#i-check" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Dikembalikan</span>
        <p class="mini-stat-value"><?= num($stats['kembali'] ?? 0) ?></p>
        <span class="mini-stat-meta">Transaksi selesai</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-wallet" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Aturan aktif</span>
        <p class="mini-stat-value"><?= $duration ?> hari</p>
        <span class="mini-stat-meta">Denda <?= money($tariff) ?>/hari</span>
      </div>
    </div>
  </div>

  <div class="card module-card">
    <div class="module-toolbar">
      <div class="filter-tabs">
        <?php foreach ($tabLabels as $key => $label): ?>
          <a class="filter-btn<?= $activeTab === $key ? ' is-active' : '' ?>"
            href="<?= e(url('borrowing', ['tab' => $key, 'q' => $q ?: null])) ?>">
            <?= e($label) ?> <span class="tab-count"><?= num($tabs[$key] ?? 0) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <form class="toolbar-search" method="get" action="index.php">
        <input type="hidden" name="page" value="borrowing">
        <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
        <label class="field-search field-search--compact">
          <svg class="icon"><use href="#i-search" /></svg>
          <span class="visually-hidden">Cari transaksi</span>
          <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari buku, anggota, NIM…" autocomplete="off">
        </label>
      </form>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Buku</th>
            <th>Anggota</th>
            <th>Pinjam</th>
            <th>Jatuh tempo</th>
            <th>Status</th>
            <th>Denda</th>
            <th class="th-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($loans['items'])): ?>
            <?= empty_row(7, 'Tidak ada transaksi', 'Ubah tab status atau kata kunci pencarian.') ?>
          <?php else: ?>
            <?php foreach ($loans['items'] as $loan): ?>
              <?php
                $style = category_style($loan['kategori'] ?? '');
                $avatar = avatar_style((int) $loan['id_anggota']);
                $state = loan_state($loan);
                $fine = (int) ($loan['total_denda'] ?? 0);
              ?>
              <tr>
                <td>
                  <div class="book-cell">
                    <span class="book-cover" style="--c1: <?= e($style['c1']) ?>; --c2: <?= e($style['c2']) ?>"><?= e(mb_substr($loan['judul'], 0, 2)) ?></span>
                    <span class="book-meta">
                      <a class="book-title" href="<?= e(book_url((int) $loan['id_buku'])) ?>"><?= e($loan['judul']) ?></a>
                      <span class="book-author"><?= e($loan['pengarang']) ?></span>
                    </span>
                  </div>
                </td>
                <td>
                  <div class="member-cell">
                    <span class="member-avatar" style="--av-bg: <?= e($avatar[0]) ?>; --av-ink: <?= e($avatar[1]) ?>"><?= e(initials($loan['anggota_nama'])) ?></span>
                    <span class="member-meta">
                      <a class="member-name" href="<?= e(member_url((int) $loan['id_anggota'])) ?>"><?= e($loan['anggota_nama']) ?></a>
                      <span class="member-id"><?= e($loan['nim_nik']) ?></span>
                    </span>
                  </div>
                </td>
                <td class="date-cell">
                  <span class="date-main"><?= e(format_date($loan['tgl_pinjam'])) ?></span>
                  <span class="date-note">#<?= (int) $loan['id_peminjaman'] ?></span>
                </td>
                <td class="date-cell">
                  <span class="date-main"><?= e(format_date($loan['tgl_kembali_rencana'])) ?></span>
                  <?php if ($state['key'] === 'overdue'): ?>
                    <span class="date-note is-late"><?= (int) $state['late'] ?> hari lewat</span>
                  <?php elseif ($state['key'] === 'due'): ?>
                    <span class="date-note is-soon">Jatuh tempo dekat</span>
                  <?php elseif ($state['key'] === 'returned'): ?>
                    <span class="date-note">Kembali <?= e(format_date($loan['tgl_kembali_real'])) ?></span>
                  <?php else: ?>
                    <span class="date-note"><?= max(0, diff_days(today(), (string) $loan['tgl_kembali_rencana'])) ?> hari lagi</span>
                  <?php endif; ?>
                </td>
                <td><?= loan_badge($loan) ?></td>
                <td>
                  <?php if ($fine > 0): ?>
                    <span class="money-cell money-cell--due"><?= money($fine) ?></span>
                    <span class="sub-cell"><?= e(($loan['status_denda'] ?? '') === 'lunas' ? 'Lunas' : 'Belum lunas') ?></span>
                  <?php elseif ($state['key'] === 'overdue'): ?>
                    <span class="money-cell money-cell--due"><?= money(projected_fine($loan)) ?></span>
                    <span class="sub-cell">Berjalan <?= (int) $state['late'] ?> hari</span>
                  <?php else: ?>
                    <span class="muted-cell">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="row-actions">
                    <?php if ($state['key'] === 'returned'): ?>
                      <a class="row-btn" href="<?= e(book_url((int) $loan['id_buku'])) ?>" aria-label="Lihat buku" title="Lihat buku">
                        <svg class="icon"><use href="#i-eye" /></svg>
                      </a>
                    <?php else: ?>
                      <button class="row-btn row-btn--text" type="button"
                        data-confirm
                        data-url="<?= e(url('borrowing', ['action' => 'return'])) ?>"
                        data-id="<?= (int) $loan['id_peminjaman'] ?>"
                        data-title="Kembalikan buku?"
                        data-sub="Stok bertambah satu dan denda dihitung bila terlambat."
                        data-ok="Konfirmasi kembali"
                        data-danger="0"
                        data-message="&lt;strong&gt;<?= e($loan['judul']) ?>&lt;/strong&gt; dipinjam oleh &lt;strong&gt;<?= e($loan['anggota_nama']) ?>&lt;/strong&gt;.<?= $state['key'] === 'overdue' ? ' Denda berjalan &lt;strong&gt;' . e(money(projected_fine($loan))) . '&lt;/strong&gt;.' : ' Belum melewati jatuh tempo.' ?>">
                        <svg class="icon"><use href="#i-refresh" /></svg> Kembalikan
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?= render_pager($loans, 'transaksi') ?>
  </div>
</section>
