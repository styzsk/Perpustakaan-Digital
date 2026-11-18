<?php
/**
 * Katalog buku.
 *
 * @var array $filters
 * @var array $books
 * @var array $categories
 * @var array $stats
 */
$q = (string) ($filters['q'] ?? '');
$activeCategory = (string) ($filters['kategori'] ?? '');
$availability = (string) ($filters['ketersediaan'] ?? '');
$sort = (string) ($filters['sort'] ?? 'judul');
$dir = (string) ($filters['dir'] ?? 'asc');
?>
<section class="view is-active" aria-labelledby="booksHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Manajemen koleksi</p>
      <h2 class="module-title" id="booksHeading">Katalog buku</h2>
      <p class="module-sub">Cari, filter, dan kelola metadata buku. Stok tersedia berubah hanya melalui transaksi peminjaman dan pengembalian.</p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('books', ['action' => 'export'])) ?>">
        <svg class="icon"><use href="#i-download" /></svg> Ekspor CSV
      </a>
      <button class="btn-add btn-add--sm" type="button" data-modal-open="book">
        <svg class="icon"><use href="#i-plus" /></svg> Tambah buku
      </button>
    </div>
  </div>

  <div class="mini-stats">
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-book" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Total judul</span>
        <p class="mini-stat-value"><?= num($stats['total_judul'] ?? 0) ?></p>
        <span class="mini-stat-meta"><?= num($stats['total_eksemplar'] ?? 0) ?> eksemplar terdaftar</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--green"><svg class="icon"><use href="#i-check" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Siap dipinjam</span>
        <p class="mini-stat-value"><?= num($stats['stok_tersedia'] ?? 0) ?></p>
        <span class="mini-stat-meta">Eksemplar tersedia di rak</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--dark"><svg class="icon"><use href="#i-repeat" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Sedang dipinjam</span>
        <p class="mini-stat-value"><?= num($stats['dipinjam'] ?? 0) ?></p>
        <span class="mini-stat-meta">Eksemplar keluar dari rak</span>
      </div>
    </div>
    <div class="mini-stat">
      <span class="mini-stat-icon mini-stat-icon--orange"><svg class="icon"><use href="#i-search" /></svg></span>
      <div class="mini-stat-body">
        <span class="mini-stat-label">Hasil filter</span>
        <p class="mini-stat-value"><?= num($books['total'] ?? 0) ?></p>
        <span class="mini-stat-meta">Judul sesuai pencarian</span>
      </div>
    </div>
  </div>

  <div class="card module-card">
    <form class="module-toolbar" method="get" action="index.php" data-filter-form>
      <input type="hidden" name="page" value="books">
      <label class="field-search">
        <svg class="icon"><use href="#i-search" /></svg>
        <span class="visually-hidden">Cari buku</span>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari judul, pengarang, ISBN…" autocomplete="off">
      </label>

      <select class="field-select" name="kategori" aria-label="Filter kategori" data-auto-submit>
        <option value="">Semua kategori</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= e($category) ?>"<?= $activeCategory === $category ? ' selected' : '' ?>><?= e($category) ?></option>
        <?php endforeach; ?>
      </select>

      <select class="field-select" name="ketersediaan" aria-label="Filter ketersediaan" data-auto-submit>
        <option value="">Semua stok</option>
        <option value="available"<?= $availability === 'available' ? ' selected' : '' ?>>Tersedia</option>
        <option value="loaned"<?= $availability === 'loaned' ? ' selected' : '' ?>>Sedang dipinjam</option>
        <option value="out"<?= $availability === 'out' ? ' selected' : '' ?>>Stok habis</option>
      </select>

      <select class="field-select" name="sort" aria-label="Urutkan" data-auto-submit>
        <option value="judul"<?= $sort === 'judul' ? ' selected' : '' ?>>Judul</option>
        <option value="pengarang"<?= $sort === 'pengarang' ? ' selected' : '' ?>>Pengarang</option>
        <option value="kategori"<?= $sort === 'kategori' ? ' selected' : '' ?>>Kategori</option>
        <option value="stok"<?= $sort === 'stok' ? ' selected' : '' ?>>Stok tersedia</option>
        <option value="terbaru"<?= $sort === 'terbaru' ? ' selected' : '' ?>>Terbaru</option>
      </select>

      <select class="field-select field-select--dir" name="dir" aria-label="Arah urutan" data-auto-submit>
        <option value="asc"<?= $dir === 'asc' ? ' selected' : '' ?>>A–Z</option>
        <option value="desc"<?= $dir === 'desc' ? ' selected' : '' ?>>Z–A</option>
      </select>

      <button class="btn-secondary btn-secondary--sm" type="submit">
        <svg class="icon"><use href="#i-filter" /></svg> Terapkan
      </button>
      <a class="link-btn" href="<?= e(url('books')) ?>">Reset</a>
      <span class="result-count"><?= num($books['total'] ?? 0) ?> judul</span>
    </form>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Buku</th>
            <th>Kategori</th>
            <th>ISBN</th>
            <th>Stok</th>
            <th>Peminjaman</th>
            <th class="th-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($books['items'])): ?>
            <?= empty_row(6, 'Buku tidak ditemukan', 'Coba kata kunci lain atau reset filter.') ?>
          <?php else: ?>
            <?php foreach ($books['items'] as $book): ?>
              <?php
                $style = category_style($book['kategori']);
                $available = (int) $book['stok_tersedia'];
                $total = (int) $book['jumlah_eksemplar'];
                $stock = stock_status($available, $total);
                $percent = $total > 0 ? (int) round(($available / $total) * 100) : 0;
              ?>
              <tr>
                <td>
                  <div class="book-cell">
                    <span class="book-cover" style="--c1: <?= e($style['c1']) ?>; --c2: <?= e($style['c2']) ?>">
                      <?= e(mb_substr($book['judul'], 0, 2)) ?>
                    </span>
                    <span class="book-meta">
                      <a class="book-title" href="<?= e(book_url((int) $book['id_buku'])) ?>"><?= e($book['judul']) ?></a>
                      <span class="book-author"><?= e($book['pengarang']) ?><?= !empty($book['tahun_terbit']) ? ' · ' . e((string) $book['tahun_terbit']) : '' ?></span>
                    </span>
                  </div>
                </td>
                <td><?= category_chip($book['kategori']) ?></td>
                <td><span class="mono-cell"><?= e($book['isbn'] ?: '—') ?></span></td>
                <td>
                  <div class="stock-cell">
                    <span class="stock-num"><?= num($available) ?></span>
                    <span class="stock-of">/ <?= num($total) ?></span>
                    <span class="stock-meter"><i style="width: <?= $percent ?>%"></i></span>
                  </div>
                  <span class="status-chip <?= e($stock['class']) ?>"><?= e($stock['label']) ?></span>
                </td>
                <td>
                  <span class="count-pill<?= (int) ($book['dipinjam'] ?? 0) > 0 ? ' count-pill--warn' : '' ?>"><?= num($book['dipinjam'] ?? 0) ?></span>
                  <span class="sub-cell">eksemplar keluar</span>
                </td>
                <td>
                  <div class="row-actions">
                    <a class="row-btn" href="<?= e(book_url((int) $book['id_buku'])) ?>" aria-label="Lihat detail <?= e($book['judul']) ?>" title="Detail">
                      <svg class="icon"><use href="#i-eye" /></svg>
                    </a>
                    <button class="row-btn" type="button" data-book-edit
                      data-book="<?= e(json_encode($book, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                      aria-label="Ubah <?= e($book['judul']) ?>" title="Ubah">
                      <svg class="icon"><use href="#i-edit" /></svg>
                    </button>
                    <?php if ($available > 0): ?>
                      <a class="row-btn" href="<?= e(url('borrowing', ['book' => $book['id_buku']])) ?>" aria-label="Pinjamkan <?= e($book['judul']) ?>" title="Pinjamkan">
                        <svg class="icon"><use href="#i-repeat" /></svg>
                      </a>
                    <?php endif; ?>
                    <button class="row-btn" type="button"
                      data-confirm
                      data-url="<?= e(url('books', ['action' => 'delete'])) ?>"
                      data-id="<?= (int) $book['id_buku'] ?>"
                      data-title="Hapus buku?"
                      data-sub="Buku dengan riwayat peminjaman akan ditolak oleh server."
                      data-ok="Hapus buku"
                      data-danger="1"
                      data-message="&lt;strong&gt;<?= e($book['judul']) ?>&lt;/strong&gt; akan dihapus dari katalog."
                      aria-label="Hapus <?= e($book['judul']) ?>" title="Hapus">
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

    <?= render_pager($books, 'judul') ?>
  </div>
</section>
