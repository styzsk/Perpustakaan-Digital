<?php
/**
 * Denda.
 *
 * @var array $filters
 * @var array $fines
 * @var array $stats
 * @var array $payments
 * @var int   $tariff
 */
$q = (string) ($filters['q'] ?? '');
$status = (string) ($filters['status'] ?? 'all');
$tabLabels = ['all' => 'Semua', 'unpaid' => 'Belum lunas', 'paid' => 'Lunas'];
$paidPct = (int) ($stats['nilai_total'] ?? 0) > 0
    ? (int) round(((int) $stats['nilai_lunas'] / (int) $stats['nilai_total']) * 100)
    : 0;
?>
<section class="view is-active" aria-labelledby="finesHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Keuangan</p>
      <h2 class="module-title" id="finesHeading">Denda keterlambatan</h2>
      <p class="module-sub">Denda dibuat otomatis saat pengembalian melewati jatuh tempo. Pembayaran ganda dicegah dan setiap pelunasan dicatat.</p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('fines', ['action' => 'export', 'status' => $status])) ?>">
        <svg class="icon"><use href="#i-download" /></svg> Ekspor CSV
      </a>
      <?php if ((int) ($stats['jumlah_belum'] ?? 0) > 0): ?>
        <a class="btn-add btn-add--sm" href="<?= e(url('fines', ['status' => 'unpaid'])) ?>">
          <svg class="icon"><use href="#i-wallet" /></svg> Tagihan terbuka
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="mini-stats">
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--dark"><svg class="icon"><use href="#i-wallet" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Total denda tercatat</span>
        <p class="mini-stat-value"><?= money($stats['nilai_total'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($stats['total'] ?? 0) ?> catatan denda</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-alert" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Belum lunas</span>
        <p class="mini-stat-value"><?= money($stats['nilai_belum'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($stats['jumlah_belum'] ?? 0) ?> tagihan terbuka</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--green"><svg class="icon"><use href="#i-check" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Sudah dibayar</span>
        <p class="mini-stat-value"><?= money($stats['nilai_lunas'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($stats['jumlah_lunas'] ?? 0) ?> pelunasan</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-clock" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Tarif aktif</span>
        <p class="mini-stat-value"><?= money($tariff) ?></p>
        <span class="mini-stat-meta">Per hari keterlambatan</span>
      </div>
    </div>
  </div>

  <div class="card module-card">
    <div class="module-toolbar">
      <div class="filter-tabs">
        <?php foreach ($tabLabels as $key => $label): ?>
          <a class="filter-btn<?= $status === $key ? ' is-active' : '' ?>"
            href="<?= e(url('fines', ['status' => $key, 'q' => $q ?: null])) ?>">
            <?= e($label) ?>
            <?php if ($key === 'unpaid'): ?>
              <span class="tab-count"><?= num($stats['jumlah_belum'] ?? 0) ?></span>
            <?php elseif ($key === 'paid'): ?>
              <span class="tab-count"><?= num($stats['jumlah_lunas'] ?? 0) ?></span>
            <?php else: ?>
              <span class="tab-count"><?= num($stats['total'] ?? 0) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
      <form class="toolbar-search" method="get" action="index.php">
        <input type="hidden" name="page" value="fines">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <label class="field-search field-search--compact">
          <svg class="icon"><use href="#i-search" /></svg>
          <span class="visually-hidden">Cari denda</span>
          <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari anggota atau buku…" autocomplete="off">
        </label>
      </form>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Anggota</th>
            <th>Buku</th>
            <th>Dikembalikan</th>
            <th>Hari</th>
            <th>Total denda</th>
            <th>Status</th>
            <th class="th-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($fines['items'])): ?>
            <?= empty_row(7, 'Tidak ada data denda', 'Denda muncul saat ada pengembalian yang melewati jatuh tempo.') ?>
          <?php else: ?>
            <?php foreach ($fines['items'] as $fine): ?>
              <?php
                $avatar = avatar_style((int) $fine['id_anggota']);
                $paid = ($fine['status_bayar'] ?? '') === 'lunas';
              ?>
              <tr>
                <td>
                  <div class="member-cell">
                    <span class="member-avatar" style="--av-bg: <?= e($avatar[0]) ?>; --av-ink: <?= e($avatar[1]) ?>"><?= e(initials($fine['anggota_nama'])) ?></span>
                    <span class="member-meta">
                      <a class="member-name" href="<?= e(member_url((int) $fine['id_anggota'])) ?>"><?= e($fine['anggota_nama']) ?></a>
                      <span class="member-id"><?= e($fine['nim_nik']) ?></span>
                    </span>
                  </div>
                </td>
                <td>
                  <a class="book-title" href="<?= e(book_url((int) $fine['id_buku'])) ?>"><?= e($fine['judul']) ?></a>
                  <span class="sub-cell"><?= e($fine['pengarang']) ?></span>
                </td>
                <td class="date-cell">
                  <span class="date-main"><?= e(format_date($fine['tgl_kembali_real'])) ?></span>
                  <span class="date-note">Jatuh tempo <?= e(format_date($fine['tgl_kembali_rencana'])) ?></span>
                </td>
                <td><span class="count-pill count-pill--warn"><?= (int) $fine['jumlah_hari'] ?> hari</span></td>
                <td>
                  <span class="money-cell<?= $paid ? '' : ' money-cell--due' ?>"><?= money($fine['total_denda']) ?></span>
                  <span class="sub-cell"><?= money($fine['tarif_per_hari']) ?>/hari</span>
                </td>
                <td>
                  <span class="status-chip <?= $paid ? 'status-chip--paid' : 'status-chip--due' ?>">
                    <?= $paid ? 'Lunas' : 'Belum lunas' ?>
                  </span>
                  <?php if ($paid): ?>
                    <span class="sub-cell"><?= e(format_date($fine['tgl_bayar'])) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="row-actions">
                    <?php if (!$paid): ?>
                      <button class="row-btn row-btn--text" type="button"
                        data-confirm
                        data-url="<?= e(url('fines', ['action' => 'pay'])) ?>"
                        data-id="<?= (int) $fine['id_denda'] ?>"
                        data-title="Tandai denda lunas?"
                        data-sub="Catatan denda tetap tersimpan sebagai riwayat."
                        data-ok="Tandai lunas"
                        data-danger="0"
                        data-message="Denda &lt;strong&gt;<?= e(money($fine['total_denda'])) ?>&lt;/strong&gt; dari &lt;strong&gt;<?= e($fine['anggota_nama']) ?>&lt;/strong&gt; akan ditandai lunas.">
                        <svg class="icon"><use href="#i-check" /></svg> Bayar
                      </button>
                    <?php else: ?>
                      <span class="muted-cell">Selesai</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?= render_pager($fines, 'denda') ?>
  </div>

  <div class="report-grid">
    <div class="card report-card">
      <div class="report-head">
        <div>
          <h2 class="module-title module-title--sm">Pembayaran terakhir</h2>
          <p class="module-sub">Lima pelunasan terbaru yang tercatat.</p>
        </div>
        <span class="pill-soft"><?= num($stats['jumlah_lunas'] ?? 0) ?> lunas</span>
      </div>
      <div class="fine-summary">
        <?php if (empty($payments)): ?>
          <p class="fine-foot">Belum ada pembayaran denda.</p>
        <?php else: ?>
          <?php foreach ($payments as $payment): ?>
            <div class="fine-summary-row">
              <span>
                <span class="fine-summary-label"><?= e($payment['anggota_nama']) ?></span>
                <span class="sub-cell"><?= e($payment['judul']) ?> · <?= e(format_date($payment['tgl_bayar'])) ?></span>
              </span>
              <span class="fine-summary-value fine-summary-value--paid"><?= money($payment['total_denda']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card report-card">
      <div class="report-head">
        <div>
          <h2 class="module-title module-title--sm">Rasio pelunasan</h2>
          <p class="module-sub">Perbandingan denda terbayar dan terbuka.</p>
        </div>
        <span class="pill-soft"><?= $paidPct ?>%</span>
      </div>
      <div class="fine-progress">
        <span>Terbayar</span>
        <strong><?= money($stats['nilai_lunas'] ?? 0) ?></strong>
      </div>
      <div class="fine-track"><i style="width: <?= $paidPct ?>%"></i></div>
      <div class="fine-summary">
        <div class="fine-summary-row">
          <span class="fine-summary-label">Total denda</span>
          <span class="fine-summary-value"><?= money($stats['nilai_total'] ?? 0) ?></span>
        </div>
        <div class="fine-summary-row">
          <span class="fine-summary-label">Belum lunas</span>
          <span class="fine-summary-value fine-summary-value--due"><?= money($stats['nilai_belum'] ?? 0) ?></span>
        </div>
        <div class="fine-summary-row">
          <span class="fine-summary-label">Sudah dibayar</span>
          <span class="fine-summary-value fine-summary-value--paid"><?= money($stats['nilai_lunas'] ?? 0) ?></span>
        </div>
      </div>
      <p class="fine-foot">Tarif dan durasi peminjaman dapat diubah di menu Pengaturan. Tarif baru hanya berlaku untuk transaksi berikutnya.</p>
    </div>
  </div>
</section>
