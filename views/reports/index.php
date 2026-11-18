<?php
/**
 * Laporan & analitik.
 *
 * @var array $period
 * @var array $summary
 * @var array $series
 * @var array $topBooks
 * @var array $categories
 * @var array $memberActivity
 * @var array $fines
 */
$maxChart = 1;
foreach ($series as $month) {
    $maxChart = max($maxChart, (int) $month['current'], (int) $month['previous']);
}
$paidPct = (int) ($fines['nilai_total'] ?? 0) > 0
    ? (int) round(((int) $fines['nilai_lunas'] / (int) $fines['nilai_total']) * 100)
    : 0;
$maxBook = 1;
foreach ($topBooks as $book) {
    $maxBook = max($maxBook, (int) $book['total']);
}
?>
<section class="view is-active" aria-labelledby="reportsHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Analitik</p>
      <h2 class="module-title" id="reportsHeading">Laporan &amp; analitik</h2>
      <p class="module-sub">Periode aktif: <strong><?= e($period['label']) ?></strong> (<?= e(format_date($period['from'])) ?> – <?= e(format_date($period['to'])) ?>).</p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('reports', ['action' => 'export', 'preset' => $period['preset'], 'from' => $period['from'], 'to' => $period['to']])) ?>">
        <svg class="icon"><use href="#i-download" /></svg> Ekspor laporan
      </a>
      <a class="btn-add btn-add--sm" href="<?= e(url('borrowing')) ?>">
        <svg class="icon"><use href="#i-repeat" /></svg> Kelola transaksi
      </a>
    </div>
  </div>

  <form class="card report-filter" method="get" action="index.php" data-report-filter>
    <input type="hidden" name="page" value="reports">
    <div class="report-filter-group">
      <label for="reportPreset">Periode</label>
      <select class="field-select" id="reportPreset" name="preset" data-auto-submit>
        <option value="30"<?= $period['preset'] === '30' ? ' selected' : '' ?>>30 hari terakhir</option>
        <option value="90"<?= $period['preset'] === '90' ? ' selected' : '' ?>>90 hari terakhir</option>
        <option value="ytd"<?= $period['preset'] === 'ytd' ? ' selected' : '' ?>>Tahun berjalan</option>
        <option value="all"<?= $period['preset'] === 'all' ? ' selected' : '' ?>>Semua data</option>
        <option value="custom"<?= $period['preset'] === 'custom' ? ' selected' : '' ?>>Rentang khusus</option>
      </select>
    </div>
    <div class="report-filter-group">
      <label for="reportFrom">Dari</label>
      <input class="field-input" id="reportFrom" type="date" name="from" value="<?= e($period['from']) ?>" data-report-date>
    </div>
    <div class="report-filter-group">
      <label for="reportTo">Sampai</label>
      <input class="field-input" id="reportTo" type="date" name="to" value="<?= e($period['to']) ?>" data-report-date>
    </div>
    <button class="btn-secondary" type="submit">
      <svg class="icon"><use href="#i-filter" /></svg> Terapkan
    </button>
    <span class="result-count">Data diambil dari database</span>
  </form>

  <div class="mini-stats">
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-repeat" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Peminjaman periode</span>
        <p class="mini-stat-value"><?= num($summary['dipinjam'] ?? 0) ?></p>
        <span class="mini-stat-meta">Tanggal pinjam dalam periode</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--green"><svg class="icon"><use href="#i-check" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Pengembalian</span>
        <p class="mini-stat-value"><?= num($summary['dikembalikan'] ?? 0) ?></p>
        <span class="mini-stat-meta">Dikembalikan dalam periode</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--dark"><svg class="icon"><use href="#i-book" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Aktif saat ini</span>
        <p class="mini-stat-value"><?= num($summary['aktif'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($summary['terlambat'] ?? 0) ?> terlambat</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-wallet" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Denda periode</span>
        <p class="mini-stat-value"><?= money($summary['denda_total'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= money($summary['denda_belum'] ?? 0) ?> belum lunas</span>
      </div>
    </div>
  </div>

  <div class="card chart-card">
    <div class="card-head chart-head">
      <div class="head-copy">
        <div class="title-row">
          <h2>Tren peminjaman 12 bulan</h2>
          <span class="head-pill">Database</span>
        </div>
        <p>Jumlah transaksi per bulan dibandingkan tahun sebelumnya.</p>
      </div>
      <div class="chart-tools">
        <div class="legend">
          <span class="legend-item"><span class="legend-dot legend-dot--current"></span> Tahun ini</span>
          <span class="legend-item"><span class="legend-dot legend-dot--previous"></span> Tahun lalu</span>
        </div>
      </div>
    </div>

    <div class="chart-wrap">
      <div class="chart-y" aria-hidden="true">
        <span><?= num($maxChart) ?></span>
        <span><?= num((int) round($maxChart * 0.75)) ?></span>
        <span><?= num((int) round($maxChart * 0.5)) ?></span>
        <span><?= num((int) round($maxChart * 0.25)) ?></span>
        <span>0</span>
      </div>
      <div class="chart-main">
        <div class="chart-plot">
          <div class="chart-grid" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
          <div class="chart-bars">
            <?php foreach ($series as $month): ?>
              <?php
                $currentHeight = (int) $month['current'] > 0 ? max(3, round(((int) $month['current'] / $maxChart) * 100, 1)) : 0;
                $previousHeight = (int) $month['previous'] > 0 ? max(3, round(((int) $month['previous'] / $maxChart) * 100, 1)) : 0;
              ?>
              <div class="bar-group" tabindex="0" role="img"
                data-month="<?= e($month['label'] . ' ' . $month['year']) ?>"
                data-current="<?= (int) $month['current'] ?>"
                data-previous="<?= (int) $month['previous'] ?>"
                aria-label="<?= e($month['label'] . ' ' . $month['year']) ?>: <?= (int) $month['current'] ?> peminjaman">
                <div class="bar-pair">
                  <span class="bar bar--prev" style="height: <?= $previousHeight ?>%"></span>
                  <span class="bar bar--current" style="height: <?= $currentHeight ?>%"></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="chart-tooltip" id="chartTooltip" aria-hidden="true">
            <span class="tt-month">—</span>
            <span class="tt-row"><span class="tt-dot tt-dot--current"></span> Tahun ini <strong>0</strong></span>
            <span class="tt-row"><span class="tt-dot tt-dot--previous"></span> Tahun lalu <strong>0</strong></span>
          </div>
        </div>
        <div class="chart-x">
          <?php foreach ($series as $month): ?>
            <span class="<?= !empty($month['is_current']) ? 'is-current' : '' ?>"><?= e($month['label']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="report-grid">
    <div class="card report-card">
      <div class="report-head">
        <div>
          <h2 class="module-title module-title--sm">Buku terpopuler</h2>
          <p class="module-sub">Berdasarkan peminjaman pada periode aktif.</p>
        </div>
        <span class="pill-soft"><?= num(count($topBooks)) ?> judul</span>
      </div>
      <?php if (empty($topBooks)): ?>
        <p class="fine-foot">Tidak ada peminjaman pada periode ini.</p>
      <?php else: ?>
        <ul class="rank-list">
          <?php foreach ($topBooks as $index => $book): ?>
            <?php $style = category_style($book['kategori']); ?>
            <li class="rank-item">
              <span class="rank-num"><?= $index + 1 ?></span>
              <span class="book-cover book-cover--sm" style="--c1: <?= e($style['c1']) ?>; --c2: <?= e($style['c2']) ?>"><?= e(mb_substr($book['judul'], 0, 2)) ?></span>
              <a class="rank-meta" href="<?= e(book_url((int) $book['id_buku'])) ?>">
                <strong><?= e($book['judul']) ?></strong>
                <span><?= e($book['pengarang']) ?> · <?= e($book['kategori']) ?></span>
              </a>
              <span class="rank-count"><?= num($book['total']) ?>×</span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="card report-card">
      <div class="report-head">
        <div>
          <h2 class="module-title module-title--sm">Distribusi kategori</h2>
          <p class="module-sub">Peminjaman per kategori pada periode aktif.</p>
        </div>
        <span class="pill-soft"><?= num(count($categories)) ?> kategori</span>
      </div>
      <?php if (empty($categories)): ?>
        <p class="fine-foot">Tidak ada data kategori pada periode ini.</p>
      <?php else: ?>
        <div class="report-cats">
          <?php foreach ($categories as $category): ?>
            <div class="report-cat-row">
              <span class="report-cat-name"><i style="background: <?= e($category['color']) ?>"></i><?= e($category['kategori']) ?></span>
              <span class="report-cat-bar"><i style="width: <?= max(3, (float) $category['pct']) ?>%; background: <?= e($category['color']) ?>"></i></span>
              <span class="report-cat-value"><?= e((string) $category['pct']) ?>%</span>
              <span class="report-cat-count"><?= num($category['total']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="report-grid">
    <div class="card table-card">
      <div class="card-head table-head">
        <div class="head-copy">
          <h2>Aktivitas anggota</h2>
          <p>Anggota paling aktif pada periode <?= e($period['label']) ?>.</p>
        </div>
        <a class="link-btn" href="<?= e(url('members')) ?>">Data anggota <svg class="icon"><use href="#i-arrow-up-right" /></svg></a>
      </div>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Anggota</th>
              <th>Peminjaman</th>
              <th>Aktif</th>
              <th>Denda terbuka</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($memberActivity)): ?>
              <?= empty_row(4, 'Belum ada aktivitas', 'Tambahkan anggota dan transaksi.') ?>
            <?php else: ?>
              <?php foreach ($memberActivity as $member): ?>
                <?php $avatar = avatar_style((int) $member['id_anggota']); ?>
                <tr>
                  <td>
                    <div class="member-cell">
                      <span class="member-avatar" style="--av-bg: <?= e($avatar[0]) ?>; --av-ink: <?= e($avatar[1]) ?>"><?= e(initials($member['nama'])) ?></span>
                      <span class="member-meta">
                        <a class="member-name" href="<?= e(member_url((int) $member['id_anggota'])) ?>"><?= e($member['nama']) ?></a>
                        <span class="member-id"><?= e($member['nim_nik']) ?></span>
                      </span>
                    </div>
                  </td>
                  <td><span class="count-pill"><?= num($member['pinjaman']) ?></span></td>
                  <td><span class="count-pill<?= (int) $member['aktif'] > 0 ? ' count-pill--warn' : '' ?>"><?= num($member['aktif']) ?></span></td>
                  <td>
                    <?php if ((int) $member['denda'] > 0): ?>
                      <span class="money-cell money-cell--due"><?= money($member['denda']) ?></span>
                    <?php else: ?>
                      <span class="muted-cell">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card report-card">
      <div class="report-head">
        <div>
          <h2 class="module-title module-title--sm">Rekap denda</h2>
          <p class="module-sub">Seluruh denda, bukan hanya periode aktif.</p>
        </div>
        <span class="pill-soft"><?= $paidPct ?>% lunas</span>
      </div>
      <div class="fine-progress">
        <span>Terbayar</span>
        <strong><?= money($fines['nilai_lunas'] ?? 0) ?></strong>
      </div>
      <div class="fine-track"><i style="width: <?= $paidPct ?>%"></i></div>
      <div class="fine-summary">
        <div class="fine-summary-row">
          <span class="fine-summary-label">Total denda</span>
          <span class="fine-summary-value"><?= money($fines['nilai_total'] ?? 0) ?></span>
        </div>
        <div class="fine-summary-row">
          <span class="fine-summary-label">Sudah dibayar</span>
          <span class="fine-summary-value fine-summary-value--paid"><?= money($fines['nilai_lunas'] ?? 0) ?></span>
        </div>
        <div class="fine-summary-row">
          <span class="fine-summary-label">Belum dibayar</span>
          <span class="fine-summary-value fine-summary-value--due"><?= money($fines['nilai_belum'] ?? 0) ?></span>
        </div>
        <div class="fine-summary-row">
          <span class="fine-summary-label">Jumlah catatan</span>
          <span class="fine-summary-value"><?= num($fines['total'] ?? 0) ?></span>
        </div>
      </div>
      <a class="btn-secondary detail-aside-cta" href="<?= e(url('fines')) ?>">
        <svg class="icon"><use href="#i-wallet" /></svg> Buka menu denda
      </a>
    </div>
  </div>
</section>
