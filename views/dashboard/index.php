<?php
/**
 * Dashboard BukuHub.
 *
 * @var array $bookStats
 * @var array $memberStats
 * @var array $loanStats
 * @var array $fineStats
 * @var array $series
 * @var array $categories
 * @var int   $categoryTotal
 * @var array $peak
 * @var int   $average
 * @var array $popular
 * @var array $recent
 * @var array $tabCounts
 * @var string $activeTab
 * @var array $recentFines
 */
$maxChart = 1;
foreach ($series as $month) {
    $maxChart = max($maxChart, (int) $month['current'], (int) $month['previous']);
}

$paidPct = (int) ($fineStats['nilai_total'] ?? 0) > 0
    ? (int) round(((int) $fineStats['nilai_lunas'] / (int) $fineStats['nilai_total']) * 100)
    : 0;

$donutRadius = 70;
$donutCircumference = 2 * M_PI * $donutRadius;
$donutOffset = 0.0;
$topCategory = $categories[0] ?? null;
$tabs = [
    'all' => 'Semua',
    'active' => 'Aktif',
    'overdue' => 'Terlambat',
    'returned' => 'Dikembalikan',
];
?>
<section class="view is-active" aria-labelledby="dashboardHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Ringkasan hari ini</p>
      <h2 class="module-title" id="dashboardHeading">Selamat datang, <?= e(explode(' ', (string) ($user['nama'] ?? 'Pustakawan'))[0]) ?>.</h2>
      <p class="module-sub">Data di bawah dihitung langsung dari database perpustakaan. Klik kartu untuk membuka detail.</p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('reports')) ?>">
        <svg class="icon"><use href="#i-chart" /></svg> Buka laporan
      </a>
      <button class="btn-add btn-add--sm" type="button" data-modal-open="loan">
        <svg class="icon"><use href="#i-repeat" /></svg> Peminjaman baru
      </button>
    </div>
  </div>

  <div class="kpi-grid">
    <a class="card kpi-card" href="<?= e(url('books')) ?>">
      <div class="kpi-top">
        <span class="kpi-icon"><svg class="icon"><use href="#i-book" /></svg></span>
        <span class="kpi-delta"><svg class="icon"><use href="#i-library" /></svg> Katalog</span>
      </div>
      <p class="kpi-label">Judul dalam koleksi</p>
      <div class="kpi-value-row">
        <span class="kpi-value"><?= num($bookStats['total_judul'] ?? 0) ?></span>
        <span class="kpi-unit">judul</span>
      </div>
      <div class="kpi-foot">
        <span class="kpi-note"><?= num($bookStats['total_eksemplar'] ?? 0) ?> eksemplar · <?= num($bookStats['stok_tersedia'] ?? 0) ?> tersedia</span>
      </div>
    </a>

    <a class="card kpi-card" href="<?= e(url('members')) ?>">
      <div class="kpi-top">
        <span class="kpi-icon"><svg class="icon"><use href="#i-users" /></svg></span>
        <span class="kpi-delta"><svg class="icon"><use href="#i-check" /></svg> <?= num($memberStats['sedang_meminjam'] ?? 0) ?> meminjam</span>
      </div>
      <p class="kpi-label">Anggota aktif</p>
      <div class="kpi-value-row">
        <span class="kpi-value"><?= num($memberStats['aktif'] ?? 0) ?></span>
        <span class="kpi-unit">dari <?= num($memberStats['total'] ?? 0) ?> anggota</span>
      </div>
      <div class="kpi-foot">
        <span class="kpi-note"><?= num($memberStats['nonaktif'] ?? 0) ?> anggota nonaktif</span>
      </div>
    </a>

    <a class="card kpi-card" href="<?= e(url('borrowing', ['tab' => 'active'])) ?>">
      <div class="kpi-top">
        <span class="kpi-icon"><svg class="icon"><use href="#i-repeat" /></svg></span>
        <span class="kpi-delta<?= (int) ($loanStats['terlambat'] ?? 0) > 0 ? ' kpi-delta--danger' : '' ?>">
          <svg class="icon"><use href="#i-clock" /></svg> <?= num($loanStats['terlambat'] ?? 0) ?> terlambat
        </span>
      </div>
      <p class="kpi-label">Peminjaman aktif</p>
      <div class="kpi-value-row">
        <span class="kpi-value"><?= num($loanStats['aktif'] ?? 0) ?></span>
        <span class="kpi-unit">transaksi</span>
      </div>
      <div class="kpi-foot">
        <span class="kpi-note"><?= num($loanStats['bulan_ini'] ?? 0) ?> dibuat bulan ini</span>
      </div>
    </a>
  </div>

  <div class="mini-stats">
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-wallet" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Denda belum lunas</span>
        <p class="mini-stat-value"><?= money($fineStats['nilai_belum'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($fineStats['jumlah_belum'] ?? 0) ?> catatan menunggu pembayaran</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--green"><svg class="icon"><use href="#i-check" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Denda sudah dibayar</span>
        <p class="mini-stat-value"><?= money($fineStats['nilai_lunas'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($fineStats['jumlah_lunas'] ?? 0) ?> pembayaran tercatat</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--dark"><svg class="icon"><use href="#i-repeat" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Total transaksi</span>
        <p class="mini-stat-value"><?= num($loanStats['total'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($loanStats['kembali'] ?? 0) ?> sudah dikembalikan</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-trending" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Rata-rata / bulan</span>
        <p class="mini-stat-value"><?= num($average) ?></p>
        <span class="mini-stat-meta">Puncak <?= e($peak['label']) ?> (<?= num($peak['value']) ?>)</span>
      </div>
    </div>
  </div>

  <div class="split-grid">
    <div class="card chart-card">
      <div class="card-head chart-head">
        <div class="head-copy">
          <div class="title-row">
            <h2>Arus peminjaman</h2>
            <span class="head-pill">12 bulan</span>
          </div>
          <p>Perbandingan jumlah peminjaman bulan ini dengan periode yang sama tahun lalu.</p>
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
            <div class="chart-grid" aria-hidden="true">
              <span></span><span></span><span></span><span></span><span></span>
            </div>
            <div class="chart-bars" id="loanChart">
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

      <div class="chart-foot">
        <svg class="icon"><use href="#i-info" /></svg>
        <span>Total <strong><?= num(array_sum(array_column($series, 'current'))) ?></strong> peminjaman dalam 12 bulan terakhir.</span>
        <span class="chart-foot-meta">Sumber: tabel peminjaman</span>
      </div>
    </div>

    <div class="ebook-card">
      <span class="ebook-glow ebook-glow--one"></span>
      <span class="ebook-glow ebook-glow--two"></span>
      <div class="ebook-head">
        <span class="ebook-chip"><svg class="icon"><use href="#i-wallet" /></svg> Kas denda</span>
        <span class="ebook-mark"><svg class="icon"><use href="#i-sparkles" /></svg></span>
      </div>
      <p class="ebook-eyebrow">Denda belum lunas</p>
      <div class="ebook-number">
        <span><?= money($fineStats['nilai_belum'] ?? 0) ?></span>
      </div>
      <p class="ebook-copy">Denda tercatat otomatis saat buku dikembalikan melewati jatuh tempo. Tandai lunas setelah pembayaran diterima.</p>
      <div class="ebook-stats">
        <span class="ebook-delta"><svg class="icon"><use href="#i-check" /></svg> <?= $paidPct ?>% terbayar</span>
        <span>dari <?= money($fineStats['nilai_total'] ?? 0) ?></span>
      </div>
      <div class="ebook-progress">
        <div class="ebook-progress-head">
          <span>Pembayaran terkumpul</span>
          <strong><?= money($fineStats['nilai_lunas'] ?? 0) ?></strong>
        </div>
        <div class="ebook-track"><span style="width: <?= $paidPct ?>%"></span></div>
      </div>
      <div class="ebook-foot">
        <span class="ebook-readers-text"><?= num($fineStats['jumlah_belum'] ?? 0) ?> denda terbuka<br>dari <?= num($fineStats['total'] ?? 0) ?> catatan</span>
        <a class="ebook-cta" href="<?= e(url('fines', ['status' => 'unpaid'])) ?>">
          Kelola denda <svg class="icon"><use href="#i-arrow-right" /></svg>
        </a>
      </div>
    </div>
  </div>

  <div class="bottom-grid">
    <div class="card table-card">
      <div class="card-head table-head">
        <div class="head-copy">
          <h2>Peminjaman terbaru</h2>
          <p>Aktivitas sirkulasi terakhir beserta status jatuh temponya.</p>
        </div>
        <a class="link-btn" href="<?= e(url('borrowing')) ?>">Lihat semua <svg class="icon"><use href="#i-arrow-up-right" /></svg></a>
      </div>

      <div class="table-toolbar">
        <div class="filter-tabs">
          <?php foreach ($tabs as $key => $label): ?>
            <a class="filter-btn<?= $activeTab === $key ? ' is-active' : '' ?>"
              href="<?= e(url('dashboard', ['loan_filter' => $key])) ?>">
              <?= e($label) ?> <span class="tab-count"><?= num($tabCounts[$key] ?? 0) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <span class="result-count"><?= num($recent['total'] ?? 0) ?> transaksi</span>
      </div>

      <div class="table-wrap">
        <table class="loans-table">
          <thead>
            <tr>
              <th>Buku</th>
              <th>Anggota</th>
              <th>Jatuh tempo</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent['items'])): ?>
              <?= empty_row(5, 'Belum ada peminjaman', 'Transaksi baru akan muncul di sini.') ?>
            <?php else: ?>
              <?php foreach ($recent['items'] as $loan): ?>
                <?php
                  $style = category_style($loan['kategori'] ?? '');
                  $state = loan_state($loan);
                ?>
                <tr>
                  <td>
                    <div class="book-cell">
                      <span class="book-cover" style="--c1: <?= e($style['c1']) ?>; --c2: <?= e($style['c2']) ?>">
                        <?= e(mb_substr($loan['judul'], 0, 2)) ?>
                      </span>
                      <span class="book-meta">
                        <a class="book-title" href="<?= e(book_url((int) $loan['id_buku'])) ?>"><?= e($loan['judul']) ?></a>
                        <span class="book-author"><?= e($loan['pengarang']) ?></span>
                      </span>
                    </div>
                  </td>
                  <td>
                    <?php $avatar = avatar_style((int) $loan['id_anggota']); ?>
                    <div class="member-cell">
                      <span class="member-avatar" style="--av-bg: <?= e($avatar[0]) ?>; --av-ink: <?= e($avatar[1]) ?>"><?= e(initials($loan['anggota_nama'])) ?></span>
                      <span class="member-meta">
                        <a class="member-name" href="<?= e(member_url((int) $loan['id_anggota'])) ?>"><?= e($loan['anggota_nama']) ?></a>
                        <span class="member-id"><?= e($loan['nim_nik']) ?></span>
                      </span>
                    </div>
                  </td>
                  <td class="date-cell">
                    <span class="date-main"><?= e(format_date($loan['tgl_kembali_rencana'])) ?></span>
                    <?php if ($state['key'] === 'overdue'): ?>
                      <span class="date-note is-late"><?= (int) $state['late'] ?> hari terlambat</span>
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
                    <div class="row-actions">
                      <?php if ($state['key'] === 'overdue' || $state['key'] === 'due' || $state['key'] === 'active'): ?>
                        <button class="row-btn row-btn--text" type="button"
                          data-confirm
                          data-url="<?= e(url('borrowing', ['action' => 'return'])) ?>"
                          data-id="<?= (int) $loan['id_peminjaman'] ?>"
                          data-title="Kembalikan buku?"
                          data-sub="Stok akan bertambah satu eksemplar."
                          data-ok="Konfirmasi kembali"
                          data-danger="0"
                          data-message="&lt;strong&gt;<?= e($loan['judul']) ?>&lt;/strong&gt; dipinjam oleh &lt;strong&gt;<?= e($loan['anggota_nama']) ?>&lt;/strong&gt;.<?= $state['key'] === 'overdue' ? ' Denda berjalan &lt;strong&gt;' . e(money(projected_fine($loan))) . '&lt;/strong&gt;.' : ' Belum melewati jatuh tempo, tidak ada denda.' ?>">
                          <svg class="icon"><use href="#i-refresh" /></svg> Kembali
                        </button>
                      <?php else: ?>
                        <a class="row-btn" href="<?= e(book_url((int) $loan['id_buku'])) ?>" aria-label="Lihat buku">
                          <svg class="icon"><use href="#i-eye" /></svg>
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="module-foot">
        <span class="pager-summary">Menampilkan <?= count($recent['items'] ?? []) ?> transaksi terbaru</span>
        <a class="link-btn" href="<?= e(url('borrowing')) ?>">Kelola peminjaman</a>
      </div>
    </div>

    <div class="categories-card">
      <div class="categories-head">
        <div>
          <h2>Kategori populer</h2>
          <p>Distribusi peminjaman 12 bulan terakhir.</p>
        </div>
        <span class="dark-pill"><?= num($categoryTotal) ?> pinjaman</span>
      </div>

      <div class="donut-wrap">
        <svg class="donut" viewBox="0 0 190 190" role="img" aria-label="Distribusi peminjaman per kategori">
          <circle class="donut-track" cx="95" cy="95" r="<?= $donutRadius ?>"></circle>
          <?php foreach ($categories as $index => $category): ?>
            <?php
              $length = $categoryTotal > 0 ? ($category['total'] / $categoryTotal) * $donutCircumference : 0;
              $gap = 3;
              $dash = max(0, $length - $gap);
            ?>
            <circle class="donut-seg" cx="95" cy="95" r="<?= $donutRadius ?>"
              stroke="<?= e($category['color']) ?>"
              stroke-dasharray="<?= round($dash, 2) ?> <?= round($donutCircumference - $dash, 2) ?>"
              stroke-dashoffset="<?= round(-$donutOffset, 2) ?>"
              transform="rotate(-90 95 95)"
              data-cat="<?= e($category['kategori']) ?>"
              data-pct="<?= e((string) $category['pct']) ?>"
              tabindex="0"></circle>
            <?php $donutOffset += $length; ?>
          <?php endforeach; ?>
        </svg>
        <div class="donut-center">
          <strong><?= num($categoryTotal) ?></strong>
          <span>Total pinjaman</span>
        </div>
      </div>

      <ul class="cat-list">
        <?php if (empty($categories)): ?>
          <li class="cat-row"><span class="cat-name">Belum ada data kategori.</span></li>
        <?php else: ?>
          <?php foreach ($categories as $category): ?>
            <li>
              <a class="cat-row" href="<?= e(url('books', ['kategori' => $category['kategori']])) ?>">
                <span class="cat-dot" style="--c: <?= e($category['color']) ?>"></span>
                <span class="cat-name"><?= e($category['kategori']) ?></span>
                <span class="cat-pct"><?= e((string) $category['pct']) ?>%</span>
                <span class="cat-count"><?= num($category['total']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>

      <div class="cat-insight">
        <span class="cat-insight-icon"><svg class="icon"><use href="#i-sparkles" /></svg></span>
        <p>
          <?php if ($topCategory): ?>
            <strong><?= e($topCategory['kategori']) ?></strong> memimpin dengan <?= e((string) $topCategory['pct']) ?>% dari seluruh peminjaman.
          <?php else: ?>
            Belum ada peminjaman pada periode ini.
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>

  <div class="report-grid">
    <div class="card report-card">
      <div class="report-head">
        <div>
          <h2 class="module-title module-title--sm">Buku paling dipinjam</h2>
          <p class="module-sub">Berdasarkan seluruh riwayat transaksi.</p>
        </div>
        <a class="pill-soft" href="<?= e(url('books', ['sort' => 'terbaru', 'dir' => 'desc'])) ?>">Katalog</a>
      </div>
      <ul class="rank-list">
        <?php if (empty($popular)): ?>
          <li class="rank-item"><span class="rank-meta"><strong>Belum ada data</strong><span>Tambahkan buku dan transaksi.</span></span></li>
        <?php else: ?>
          <?php foreach ($popular as $index => $book): ?>
            <?php $style = category_style($book['kategori']); ?>
            <li class="rank-item">
              <span class="rank-num"><?= $index + 1 ?></span>
              <span class="book-cover book-cover--sm" style="--c1: <?= e($style['c1']) ?>; --c2: <?= e($style['c2']) ?>"><?= e(mb_substr($book['judul'], 0, 2)) ?></span>
              <a class="rank-meta" href="<?= e(book_url((int) $book['id_buku'])) ?>">
                <strong><?= e($book['judul']) ?></strong>
                <span><?= e($book['pengarang']) ?></span>
              </a>
              <span class="rank-count"><?= num($book['total']) ?>×</span>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>

    <div class="card report-card">
      <div class="report-head">
        <div>
          <h2 class="module-title module-title--sm">Pembayaran denda terakhir</h2>
          <p class="module-sub">Riwayat pembayaran yang sudah lunas.</p>
        </div>
        <a class="pill-soft" href="<?= e(url('fines', ['status' => 'paid'])) ?>">Semua</a>
      </div>
      <div class="fine-summary">
        <?php if (empty($recentFines)): ?>
          <p class="fine-foot">Belum ada pembayaran denda yang tercatat.</p>
        <?php else: ?>
          <?php foreach ($recentFines as $fine): ?>
            <div class="fine-summary-row">
              <span>
                <span class="fine-summary-label"><?= e($fine['anggota_nama']) ?></span>
                <span class="sub-cell"><?= e($fine['judul']) ?> · <?= e(format_date($fine['tgl_bayar'])) ?></span>
              </span>
              <span class="fine-summary-value fine-summary-value--paid"><?= money($fine['total_denda']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <p class="fine-foot">Denda hanya dibuat saat pengembalian melewati jatuh tempo. Tarif aktif <?= e(money(\App\Models\SettingsModel::tariff())) ?>/hari.</p>
    </div>
  </div>
</section>
