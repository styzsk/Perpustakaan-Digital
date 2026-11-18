<?php
/**
 * Detail anggota.
 *
 * @var array $member
 * @var array $loans
 * @var int   $unpaidFines
 */
$avatar = avatar_style((int) $member['id_anggota']);
$activeLoans = 0;
$returnedLoans = 0;
foreach ($loans as $loan) {
    if (($loan['status'] ?? '') === 'dipinjam') {
        $activeLoans++;
    } elseif (($loan['status'] ?? '') === 'dikembalikan') {
        $returnedLoans++;
    }
}
$limit = (int) ($member['maks_pinjam'] ?? 3);
$quotaPct = $limit > 0 ? min(100, (int) round(($activeLoans / $limit) * 100)) : 0;
?>
<section class="view is-active" aria-labelledby="memberDetailHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Profil anggota</p>
      <h2 class="module-title" id="memberDetailHeading"><?= e($member['nama']) ?></h2>
      <p class="module-sub"><?= e($member['nim_nik']) ?> · <?= e($member['email']) ?></p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('members')) ?>">
        <svg class="icon"><use href="#i-arrow-left" /></svg> Kembali
      </a>
      <button class="btn-secondary" type="button" data-member-edit
        data-member="<?= e(json_encode($member, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
        <svg class="icon"><use href="#i-edit" /></svg> Ubah profil
      </button>
      <?php if (($member['status'] ?? '') === 'aktif' && $activeLoans < $limit): ?>
        <a class="btn-add btn-add--sm" href="<?= e(url('borrowing', ['member' => $member['id_anggota']])) ?>">
          <svg class="icon"><use href="#i-repeat" /></svg> Pinjamkan buku
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="detail-grid">
    <article class="card member-hero">
      <div class="member-hero-top">
        <span class="member-avatar member-avatar--lg" style="--av-bg: <?= e($avatar[0]) ?>; --av-ink: <?= e($avatar[1]) ?>"><?= e(initials($member['nama'])) ?></span>
        <div class="member-hero-meta">
          <div class="member-hero-tags">
            <span class="status-chip <?= ($member['status'] ?? '') === 'aktif' ? 'status-chip--in' : 'status-chip--out' ?>">
              <?= e(ucfirst($member['status'] ?? 'aktif')) ?>
            </span>
            <span class="pill-soft">Batas <?= $limit ?> buku</span>
          </div>
          <h3><?= e($member['nama']) ?></h3>
          <p><?= e($member['nim_nik']) ?></p>
        </div>
      </div>

      <dl class="meta-list">
        <div><dt>Email</dt><dd><?= e($member['email']) ?></dd></div>
        <div><dt>Telepon</dt><dd><?= e($member['telepon'] ?: '—') ?></dd></div>
        <div><dt>Alamat</dt><dd><?= e($member['alamat'] ?: '—') ?></dd></div>
        <div><dt>Terdaftar sejak</dt><dd><?= e(format_date($member['created_at'] ?? null)) ?></dd></div>
      </dl>

      <div class="stock-block">
        <div class="stock-block-head">
          <span>Kuota peminjaman</span>
          <strong><?= $activeLoans ?> / <?= $limit ?></strong>
        </div>
        <div class="stock-meter stock-meter--wide"><i style="width: <?= $quotaPct ?>%"></i></div>
        <p class="detail-note"><?= max(0, $limit - $activeLoans) ?> slot peminjaman tersisa.</p>
      </div>

      <div class="detail-actions">
        <button class="btn-danger" type="button"
          data-confirm
          data-url="<?= e(url('members', ['action' => 'delete'])) ?>"
          data-id="<?= (int) $member['id_anggota'] ?>"
          data-title="Hapus anggota?"
          data-sub="Anggota dengan riwayat peminjaman akan ditolak oleh server."
          data-ok="Hapus anggota"
          data-danger="1"
          data-message="&lt;strong&gt;<?= e($member['nama']) ?>&lt;/strong&gt; akan dihapus dari daftar anggota.">
          <svg class="icon"><use href="#i-trash" /></svg> Hapus anggota
        </button>
        <a class="btn-secondary" href="<?= e(url('fines', ['q' => $member['nama']])) ?>">
          <svg class="icon"><use href="#i-wallet" /></svg> Lihat denda
        </a>
      </div>
    </article>

    <aside class="card detail-aside">
      <div class="detail-aside-head">
        <h3>Ringkasan anggota</h3>
        <p>Data sirkulasi terkini.</p>
      </div>
      <div class="detail-stat-row">
        <span>Total peminjaman</span>
        <strong><?= num(count($loans)) ?></strong>
      </div>
      <div class="detail-stat-row">
        <span>Sedang dipinjam</span>
        <strong><?= num($activeLoans) ?></strong>
      </div>
      <div class="detail-stat-row">
        <span>Sudah dikembalikan</span>
        <strong><?= num($returnedLoans) ?></strong>
      </div>
      <div class="detail-stat-row">
        <span>Denda belum lunas</span>
        <strong class="<?= $unpaidFines > 0 ? 'is-danger' : '' ?>"><?= money($unpaidFines) ?></strong>
      </div>
      <?php if ($unpaidFines > 0): ?>
        <p class="detail-alert">
          <svg class="icon"><use href="#i-alert" /></svg>
          Anggota ini masih memiliki denda terbuka. Pembayaran dicatat dari menu Denda.
        </p>
      <?php endif; ?>
      <a class="btn-secondary detail-aside-cta" href="<?= e(url('borrowing', ['q' => $member['nama']])) ?>">
        <svg class="icon"><use href="#i-search" /></svg> Cari transaksi anggota
      </a>
    </aside>
  </div>

  <div class="card table-card">
    <div class="card-head table-head">
      <div class="head-copy">
        <h2>Riwayat peminjaman</h2>
        <p>Seluruh transaksi anggota ini, terbaru di atas.</p>
      </div>
      <span class="pill-soft"><?= num(count($loans)) ?> transaksi</span>
    </div>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Buku</th>
            <th>Tanggal pinjam</th>
            <th>Jatuh tempo</th>
            <th>Dikembalikan</th>
            <th>Status</th>
            <th>Denda</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($loans)): ?>
            <?= empty_row(6, 'Belum ada peminjaman', 'Anggota ini belum pernah meminjam buku.') ?>
          <?php else: ?>
            <?php foreach ($loans as $loan): ?>
              <?php $style = category_style($loan['kategori'] ?? ''); ?>
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
                <td class="date-cell"><span class="date-main"><?= e(format_date($loan['tgl_pinjam'])) ?></span></td>
                <td class="date-cell"><span class="date-main"><?= e(format_date($loan['tgl_kembali_rencana'])) ?></span></td>
                <td class="date-cell"><span class="date-main"><?= e(format_date($loan['tgl_kembali_real'])) ?></span></td>
                <td><?= loan_badge($loan) ?></td>
                <td>
                  <?php if (!empty($loan['total_denda'])): ?>
                    <span class="money-cell money-cell--due"><?= money($loan['total_denda']) ?></span>
                    <span class="sub-cell"><?= e(($loan['status_denda'] ?? '') === 'lunas' ? 'Lunas' : 'Belum lunas') ?></span>
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
