<?php
/**
 * Detail buku.
 *
 * @var array $book
 * @var array $loans
 * @var array $categories
 */
$style = category_style($book['kategori']);
$available = (int) $book['stok_tersedia'];
$total = (int) $book['jumlah_eksemplar'];
$stock = stock_status($available, $total);
$percent = $total > 0 ? (int) round(($available / $total) * 100) : 0;
$onLoan = max(0, $total - $available);
$activeLoans = 0;
foreach ($loans as $loan) {
    if (($loan['status'] ?? '') === 'dipinjam') {
        $activeLoans++;
    }
}
?>
<section class="view is-active" aria-labelledby="bookDetailHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Detail koleksi</p>
      <h2 class="module-title" id="bookDetailHeading"><?= e($book['judul']) ?></h2>
      <p class="module-sub"><?= e($book['pengarang']) ?> · <?= e($book['kategori']) ?><?= !empty($book['tahun_terbit']) ? ' · ' . e((string) $book['tahun_terbit']) : '' ?></p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('books')) ?>">
        <svg class="icon"><use href="#i-arrow-left" /></svg> Kembali
      </a>
      <button class="btn-secondary" type="button" data-book-edit
        data-book="<?= e(json_encode($book, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
        <svg class="icon"><use href="#i-edit" /></svg> Ubah
      </button>
      <?php if ($available > 0): ?>
        <a class="btn-add btn-add--sm" href="<?= e(url('borrowing', ['book' => $book['id_buku']])) ?>">
          <svg class="icon"><use href="#i-repeat" /></svg> Pinjamkan buku
        </a>
      <?php else: ?>
        <button class="btn-add btn-add--sm" type="button" disabled title="Stok habis">
          <svg class="icon"><use href="#i-repeat" /></svg> Stok habis
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="detail-grid">
    <article class="card book-hero">
      <div class="book-hero-top">
        <span class="book-cover book-cover--lg" style="--c1: <?= e($style['c1']) ?>; --c2: <?= e($style['c2']) ?>">
          <?= e(mb_substr($book['judul'], 0, 2)) ?>
        </span>
        <div class="book-hero-meta">
          <?= category_chip($book['kategori']) ?>
          <h3><?= e($book['judul']) ?></h3>
          <p><?= e($book['pengarang']) ?></p>
          <span class="status-chip <?= e($stock['class']) ?>"><?= e($stock['label']) ?></span>
        </div>
      </div>

      <dl class="meta-list">
        <div><dt>Penerbit</dt><dd><?= e($book['penerbit'] ?: '—') ?></dd></div>
        <div><dt>ISBN</dt><dd><?= e($book['isbn'] ?: '—') ?></dd></div>
        <div><dt>Tahun terbit</dt><dd><?= e($book['tahun_terbit'] ?: '—') ?></dd></div>
        <div><dt>Total eksemplar</dt><dd><?= num($total) ?></dd></div>
        <div><dt>Tersedia</dt><dd><?= num($available) ?></dd></div>
        <div><dt>Sedang dipinjam</dt><dd><?= num($onLoan) ?></dd></div>
      </dl>

      <div class="stock-block">
        <div class="stock-block-head">
          <span>Ketersediaan stok</span>
          <strong><?= num($available) ?> / <?= num($total) ?></strong>
        </div>
        <div class="stock-meter stock-meter--wide"><i style="width: <?= $percent ?>%"></i></div>
      </div>

      <div class="book-synopsis">
        <h4>Sinopsis</h4>
        <p><?= nl2br(e($book['sinopsis'] ?: 'Belum ada sinopsis untuk buku ini.')) ?></p>
      </div>

      <div class="detail-actions">
        <button class="btn-danger" type="button"
          data-confirm
          data-url="<?= e(url('books', ['action' => 'delete'])) ?>"
          data-id="<?= (int) $book['id_buku'] ?>"
          data-title="Hapus buku?"
          data-sub="Buku dengan riwayat peminjaman akan ditolak oleh server."
          data-ok="Hapus buku"
          data-danger="1"
          data-message="&lt;strong&gt;<?= e($book['judul']) ?>&lt;/strong&gt; akan dihapus permanen dari katalog.">
          <svg class="icon"><use href="#i-trash" /></svg> Hapus buku
        </button>
        <span class="detail-note">Ditambahkan <?= e(format_date($book['created_at'] ?? null)) ?></span>
      </div>
    </article>

    <aside class="card detail-aside">
      <div class="detail-aside-head">
        <h3>Ringkasan sirkulasi</h3>
        <p>Angka dihitung dari transaksi nyata.</p>
      </div>
      <div class="detail-stat-row">
        <span>Total transaksi</span>
        <strong><?= num(count($loans)) ?></strong>
      </div>
      <div class="detail-stat-row">
        <span>Peminjaman aktif</span>
        <strong><?= num($activeLoans) ?></strong>
      </div>
      <div class="detail-stat-row">
        <span>Stok tersedia</span>
        <strong><?= num($available) ?></strong>
      </div>
      <div class="detail-stat-row">
        <span>Denda terkait</span>
        <strong>
          <?php
            $fineTotal = 0;
            foreach ($loans as $loan) {
                $fineTotal += (int) ($loan['total_denda'] ?? 0);
            }
            echo money($fineTotal);
          ?>
        </strong>
      </div>
      <a class="btn-secondary detail-aside-cta" href="<?= e(url('borrowing', ['q' => $book['judul']])) ?>">
        <svg class="icon"><use href="#i-search" /></svg> Cari di peminjaman
      </a>
    </aside>
  </div>

  <div class="card table-card">
    <div class="card-head table-head">
      <div class="head-copy">
        <h2>Riwayat peminjaman</h2>
        <p>Semua transaksi untuk buku ini, terbaru di atas.</p>
      </div>
      <span class="pill-soft"><?= num(count($loans)) ?> transaksi</span>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Anggota</th>
            <th>Tanggal pinjam</th>
            <th>Jatuh tempo</th>
            <th>Dikembalikan</th>
            <th>Status</th>
            <th>Denda</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($loans)): ?>
            <?= empty_row(6, 'Belum pernah dipinjam', 'Buku ini belum memiliki riwayat transaksi.') ?>
          <?php else: ?>
            <?php foreach ($loans as $loan): ?>
              <?php $avatar = avatar_style((int) $loan['id_anggota']); ?>
              <tr>
                <td>
                  <div class="member-cell">
                    <span class="member-avatar" style="--av-bg: <?= e($avatar[0]) ?>; --av-ink: <?= e($avatar[1]) ?>"><?= e(initials($loan['anggota_nama'])) ?></span>
                    <span class="member-meta">
                      <a class="member-name" href="<?= e(member_url((int) $loan['id_anggota'])) ?>"><?= e($loan['anggota_nama']) ?></a>
                      <span class="member-id"><?= e($loan['nim_nik']) ?></span>
                    </span>
                  </div>
                </td>
                <td class="date-cell"><span class="date-main"><?= e(format_date($loan['tgl_pinjam'])) ?></span></td>
                <td class="date-cell"><span class="date-main"><?= e(format_date($loan['tgl_kembali_rencana'])) ?></span></td>
                <td class="date-cell"><span class="date-main"><?= e(format_date($loan['tgl_kembali_real'])) ?></span></td>
                <td><?= loan_badge($loan) ?></td>
                <td>
                  <?php if (!empty($loan['total_denda'])): ?>
                    <span class="money-cell money-cell--due"><?= money($loan['total_denda']) ?></span>
                    <span class="sub-cell"><?= e($loan['status_denda'] === 'lunas' ? 'Lunas' : 'Belum lunas') ?></span>
                  <?php elseif (($loan['status'] ?? '') === 'dipinjam'): ?>
                    <span class="money-cell"><?= money(projected_fine($loan)) ?></span>
                    <span class="sub-cell">Denda berjalan</span>
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
</section>
