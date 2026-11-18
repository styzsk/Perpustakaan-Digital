<?php
/**
 * Daftar anggota.
 *
 * @var array $filters
 * @var array $members
 * @var array $stats
 */
$q = (string) ($filters['q'] ?? '');
$statusFilter = (string) ($filters['status_anggota'] ?? '');
$typeFilter = (string) ($filters['filter'] ?? '');
$sort = (string) ($filters['sort'] ?? 'nama');
$dir = (string) ($filters['dir'] ?? 'asc');

$typeOptions = [
    '' => 'Semua aktivitas',
    'active' => 'Sedang meminjam',
    'idle' => 'Tidak meminjam',
    'limit' => 'Mencapai batas',
    'fines' => 'Punya denda',
];
?>
<section class="view is-active" aria-labelledby="membersHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Data pemustaka</p>
      <h2 class="module-title" id="membersHeading">Anggota perpustakaan</h2>
      <p class="module-sub">Kelola profil, batas pinjam, dan status keanggotaan. Anggota dengan riwayat transaksi tidak dapat dihapus.</p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('members', ['action' => 'export'])) ?>">
        <svg class="icon"><use href="#i-download" /></svg> Ekspor CSV
      </a>
      <button class="btn-add btn-add--sm" type="button" data-modal-open="member">
        <svg class="icon"><use href="#i-plus" /></svg> Tambah anggota
      </button>
    </div>
  </div>

  <div class="mini-stats">
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-users" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Total anggota</span>
        <p class="mini-stat-value"><?= num($stats['total'] ?? 0) ?></p>
        <span class="mini-stat-meta">Terdaftar di perpustakaan</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--green"><svg class="icon"><use href="#i-check" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Status aktif</span>
        <p class="mini-stat-value"><?= num($stats['aktif'] ?? 0) ?></p>
        <span class="mini-stat-meta">Boleh membuat peminjaman</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--dark"><svg class="icon"><use href="#i-repeat" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Sedang meminjam</span>
        <p class="mini-stat-value"><?= num($stats['sedang_meminjam'] ?? 0) ?></p>
        <span class="mini-stat-meta">Anggota dengan pinjaman aktif</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-user" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Nonaktif</span>
        <p class="mini-stat-value"><?= num($stats['nonaktif'] ?? 0) ?></p>
        <span class="mini-stat-meta">Tidak dapat meminjam</span>
      </div>
    </div>
  </div>

  <div class="card module-card">
    <form class="module-toolbar" method="get" action="index.php" data-filter-form>
      <input type="hidden" name="page" value="members">
      <label class="field-search">
        <svg class="icon"><use href="#i-search" /></svg>
        <span class="visually-hidden">Cari anggota</span>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, NIM/NIK, atau email…" autocomplete="off">
      </label>

      <select class="field-select" name="status_anggota" aria-label="Filter status" data-auto-submit>
        <option value="">Semua status</option>
        <option value="aktif"<?= $statusFilter === 'aktif' ? ' selected' : '' ?>>Aktif</option>
        <option value="nonaktif"<?= $statusFilter === 'nonaktif' ? ' selected' : '' ?>>Nonaktif</option>
      </select>

      <select class="field-select" name="filter" aria-label="Filter aktivitas" data-auto-submit>
        <?php foreach ($typeOptions as $value => $label): ?>
          <option value="<?= e($value) ?>"<?= $typeFilter === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>

      <select class="field-select" name="sort" aria-label="Urutkan" data-auto-submit>
        <option value="nama"<?= $sort === 'nama' ? ' selected' : '' ?>>Nama</option>
        <option value="nim"<?= $sort === 'nim' ? ' selected' : '' ?>>NIM/NIK</option>
        <option value="pinjaman"<?= $sort === 'pinjaman' ? ' selected' : '' ?>>Pinjaman aktif</option>
        <option value="terbaru"<?= $sort === 'terbaru' ? ' selected' : '' ?>>Terbaru</option>
      </select>

      <select class="field-select field-select--dir" name="dir" aria-label="Arah urutan" data-auto-submit>
        <option value="asc"<?= $dir === 'asc' ? ' selected' : '' ?>>A–Z</option>
        <option value="desc"<?= $dir === 'desc' ? ' selected' : '' ?>>Z–A</option>
      </select>

      <button class="btn-secondary btn-secondary--sm" type="submit">
        <svg class="icon"><use href="#i-filter" /></svg> Terapkan
      </button>
      <a class="link-btn" href="<?= e(url('members')) ?>">Reset</a>
      <span class="result-count"><?= num($members['total'] ?? 0) ?> anggota</span>
    </form>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Anggota</th>
            <th>Kontak</th>
            <th>Pinjaman</th>
            <th>Denda</th>
            <th>Batas</th>
            <th>Status</th>
            <th class="th-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($members['items'])): ?>
            <?= empty_row(7, 'Anggota tidak ditemukan', 'Coba kata kunci lain atau ubah filter aktivitas.') ?>
          <?php else: ?>
            <?php foreach ($members['items'] as $member): ?>
              <?php
                $avatar = avatar_style((int) $member['id_anggota']);
                $activeLoans = (int) ($member['active_loans'] ?? 0);
                $limit = (int) ($member['maks_pinjam'] ?? 3);
                $unpaid = (int) ($member['unpaid_fines'] ?? 0);
              ?>
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
                <td>
                  <span class="mono-cell"><?= e($member['email']) ?></span>
                  <span class="sub-cell"><?= e($member['telepon'] ?: 'Telepon belum diisi') ?></span>
                </td>
                <td>
                  <div class="limit-cell">
                    <span class="count-pill<?= $activeLoans >= $limit ? ' count-pill--warn' : '' ?>"><?= $activeLoans ?> / <?= $limit ?></span>
                    <span class="sub-cell"><?= num($member['total_loans'] ?? 0) ?> total transaksi</span>
                  </div>
                </td>
                <td>
                  <?php if ($unpaid > 0): ?>
                    <span class="money-cell money-cell--due"><?= money($unpaid) ?></span>
                    <span class="sub-cell">Belum lunas</span>
                  <?php else: ?>
                    <span class="muted-cell">Tidak ada</span>
                  <?php endif; ?>
                </td>
                <td><span class="count-pill"><?= $limit ?> buku</span></td>
                <td>
                  <span class="status-chip <?= ($member['status'] ?? '') === 'aktif' ? 'status-chip--in' : 'status-chip--out' ?>">
                    <?= e(ucfirst($member['status'] ?? 'aktif')) ?>
                  </span>
                </td>
                <td>
                  <div class="row-actions">
                    <a class="row-btn" href="<?= e(member_url((int) $member['id_anggota'])) ?>" aria-label="Detail <?= e($member['nama']) ?>" title="Detail">
                      <svg class="icon"><use href="#i-eye" /></svg>
                    </a>
                    <button class="row-btn" type="button" data-member-edit
                      data-member="<?= e(json_encode($member, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                      aria-label="Ubah <?= e($member['nama']) ?>" title="Ubah">
                      <svg class="icon"><use href="#i-edit" /></svg>
                    </button>
                    <a class="row-btn" href="<?= e(url('borrowing', ['q' => $member['nama']])) ?>" aria-label="Riwayat pinjaman <?= e($member['nama']) ?>" title="Riwayat pinjaman">
                      <svg class="icon"><use href="#i-repeat" /></svg>
                    </a>
                    <button class="row-btn" type="button"
                      data-confirm
                      data-url="<?= e(url('members', ['action' => 'delete'])) ?>"
                      data-id="<?= (int) $member['id_anggota'] ?>"
                      data-title="Hapus anggota?"
                      data-sub="Anggota dengan riwayat peminjaman akan ditolak oleh server."
                      data-ok="Hapus anggota"
                      data-danger="1"
                      data-message="&lt;strong&gt;<?= e($member['nama']) ?>&lt;/strong&gt; akan dihapus dari daftar anggota."
                      aria-label="Hapus <?= e($member['nama']) ?>" title="Hapus">
                      <svg class="icon"><use href="#i-trash" /></svg>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?= render_pager($members, 'anggota') ?>
  </div>
</section>
