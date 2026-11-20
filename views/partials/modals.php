<?php
/**
 * Kumpulan modal global: buku, anggota, peminjaman, dan konfirmasi.
 *
 * @var array $formErrors
 * @var array $old
 * @var string|null $openModal
 */
$errors = $formErrors ?? [];
$showBookModal = (bool) ($showBookModal ?? false);
$showMemberModal = (bool) ($showMemberModal ?? false);
$showLoanModal = (bool) ($showLoanModal ?? false);

$categories = $categoriesList ?? $categories ?? [];
$members = $members ?? [];
$books = $books ?? [];
$duration = (int) ($duration ?? config('defaults.durasi_hari', 7));
$tariff = (int) ($tariff ?? config('defaults.tarif_denda', 1000));

$prefillBook = (int) ($prefillBook ?? 0);
$prefillMember = (int) ($prefillMember ?? 0);
$selectedMember = (string) old('id_anggota', $prefillMember > 0 ? (string) $prefillMember : '');
$selectedBook = (string) old('id_buku', $prefillBook > 0 ? (string) $prefillBook : '');
?>

<?php if ($showBookModal): ?>
  <div class="modal fade modal-app" id="bookModal" tabindex="-1" aria-labelledby="bookModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <form method="post" action="<?= e(url('books', ['action' => 'save'])) ?>" id="bookForm" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="id" id="bookId" value="<?= (int) old('id', 0) ?>">

          <div class="modal-header">
            <div class="modal-heading">
              <span class="modal-icon"><svg class="icon"><use href="#i-book" /></svg></span>
              <div>
                <h2 id="bookModalTitle">Tambah buku baru</h2>
                <p id="bookModalSub">Lengkapi metadata dan jumlah eksemplar.</p>
              </div>
            </div>
            <button class="modal-close" type="button" data-bs-dismiss="modal" aria-label="Tutup">
              <svg class="icon"><use href="#i-x" /></svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="form-grid">
              <div class="<?= field_class($errors, 'judul') ?> form-field--full">
                <label for="bookTitle">Judul <span class="req">*</span></label>
                <input id="bookTitle" name="judul" type="text" maxlength="180" required
                  value="<?= e(old('judul')) ?>" placeholder="Contoh: Bumi Manusia">
                <?= field_error($errors, 'judul') ?>
              </div>

              <div class="<?= field_class($errors, 'pengarang') ?>">
                <label for="bookAuthor">Pengarang <span class="req">*</span></label>
                <input id="bookAuthor" name="pengarang" type="text" maxlength="120" required
                  value="<?= e(old('pengarang')) ?>" placeholder="Nama pengarang">
                <?= field_error($errors, 'pengarang') ?>
              </div>

              <div class="<?= field_class($errors, 'kategori') ?>">
                <label for="bookCategory">Kategori <span class="req">*</span></label>
                <input id="bookCategory" name="kategori" type="text" maxlength="40" required
                  value="<?= e(old('kategori')) ?>" list="bookCategories" placeholder="Fiksi, Sains, Sejarah…">
                <datalist id="bookCategories">
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
                <?= field_error($errors, 'kategori') ?>
              </div>

              <div class="<?= field_class($errors, 'isbn') ?>">
                <label for="bookIsbn">ISBN</label>
                <input id="bookIsbn" name="isbn" type="text" maxlength="30"
                  value="<?= e(old('isbn')) ?>" placeholder="978-…">
                <?= field_error($errors, 'isbn') ?>
              </div>

              <div class="<?= field_class($errors, 'penerbit') ?>">
                <label for="bookPublisher">Penerbit</label>
                <input id="bookPublisher" name="penerbit" type="text" maxlength="120"
                  value="<?= e(old('penerbit')) ?>" placeholder="Nama penerbit">
                <?= field_error($errors, 'penerbit') ?>
              </div>

              <div class="<?= field_class($errors, 'tahun_terbit') ?>">
                <label for="bookYear">Tahun terbit</label>
                <input id="bookYear" name="tahun_terbit" type="number" inputmode="numeric"
                  min="1900" max="<?= (int) date('Y') + 1 ?>" value="<?= e(old('tahun_terbit')) ?>" placeholder="<?= date('Y') ?>">
                <?= field_error($errors, 'tahun_terbit') ?>
              </div>

              <div class="<?= field_class($errors, 'jumlah_eksemplar') ?>">
                <label for="bookCopies">Jumlah eksemplar <span class="req">*</span></label>
                <input id="bookCopies" name="jumlah_eksemplar" type="number" inputmode="numeric"
                  min="1" max="999" required value="<?= e(old('jumlah_eksemplar', 1)) ?>">
                <?= field_error($errors, 'jumlah_eksemplar') ?>
              </div>

              <div class="<?= field_class($errors, 'sinopsis') ?> form-field--full">
                <label for="bookSynopsis">Sinopsis</label>
                <textarea id="bookSynopsis" name="sinopsis" rows="3" maxlength="2000"
                  placeholder="Ringkasan singkat buku (opsional)"><?= e(old('sinopsis')) ?></textarea>
                <?= field_error($errors, 'sinopsis') ?>
              </div>
            </div>

            <p class="form-note">
              <svg class="icon"><use href="#i-info" /></svg>
              <span>Stok tersedia dihitung otomatis: total eksemplar dikurangi buku yang sedang dipinjam.</span>
            </p>
          </div>

          <div class="modal-footer">
            <button class="btn-ghost" type="button" data-bs-dismiss="modal">Batal</button>
            <button class="btn-add btn-add--modal" type="submit" id="bookSubmit">
              <svg class="icon"><use href="#i-check" /></svg> Simpan buku
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($showMemberModal): ?>
  <div class="modal fade modal-app" id="memberModal" tabindex="-1" aria-labelledby="memberModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <form method="post" action="<?= e(url('members', ['action' => 'save'])) ?>" id="memberForm" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="id" id="memberId" value="<?= (int) old('id', 0) ?>">

          <div class="modal-header">
            <div class="modal-heading">
              <span class="modal-icon"><svg class="icon"><use href="#i-users" /></svg></span>
              <div>
                <h2 id="memberModalTitle">Tambah anggota baru</h2>
                <p id="memberModalSub">Daftarkan anggota dan tentukan batas pinjam.</p>
              </div>
            </div>
            <button class="modal-close" type="button" data-bs-dismiss="modal" aria-label="Tutup">
              <svg class="icon"><use href="#i-x" /></svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="form-grid">
              <div class="<?= field_class($errors, 'nama') ?> form-field--full">
                <label for="memberName">Nama lengkap <span class="req">*</span></label>
                <input id="memberName" name="nama" type="text" maxlength="100" required
                  value="<?= e(old('nama')) ?>" placeholder="Nama anggota">
                <?= field_error($errors, 'nama') ?>
              </div>

              <div class="<?= field_class($errors, 'nim_nik') ?>">
                <label for="memberNim">NIM / NIK <span class="req">*</span></label>
                <input id="memberNim" name="nim_nik" type="text" maxlength="30" required
                  value="<?= e(old('nim_nik')) ?>" placeholder="20240001">
                <?= field_error($errors, 'nim_nik') ?>
              </div>

              <div class="<?= field_class($errors, 'email') ?>">
                <label for="memberEmail">Email <span class="req">*</span></label>
                <input id="memberEmail" name="email" type="email" maxlength="120" required
                  value="<?= e(old('email')) ?>" placeholder="nama@kampus.ac.id">
                <?= field_error($errors, 'email') ?>
              </div>

              <div class="<?= field_class($errors, 'telepon') ?>">
                <label for="memberPhone">Telepon</label>
                <input id="memberPhone" name="telepon" type="text" maxlength="20"
                  value="<?= e(old('telepon')) ?>" placeholder="08xxxxxxxxxx">
                <?= field_error($errors, 'telepon') ?>
              </div>

              <div class="<?= field_class($errors, 'maks_pinjam') ?>">
                <label for="memberLimit">Batas pinjam <span class="req">*</span></label>
                <input id="memberLimit" name="maks_pinjam" type="number" inputmode="numeric"
                  min="1" max="10" required value="<?= e(old('maks_pinjam', $defaultLimit ?? 3)) ?>">
                <?= field_error($errors, 'maks_pinjam') ?>
              </div>

              <div class="<?= field_class($errors, 'status') ?>">
                <label for="memberStatus">Status</label>
                <select id="memberStatus" name="status">
                  <option value="aktif"<?= old('status', 'aktif') === 'aktif' ? ' selected' : '' ?>>Aktif</option>
                  <option value="nonaktif"<?= old('status') === 'nonaktif' ? ' selected' : '' ?>>Nonaktif</option>
                </select>
                <?= field_error($errors, 'status') ?>
              </div>

              <div class="<?= field_class($errors, 'alamat') ?> form-field--full">
                <label for="memberAddress">Alamat</label>
                <textarea id="memberAddress" name="alamat" rows="2" maxlength="255"
                  placeholder="Alamat domisili (opsional)"><?= e(old('alamat')) ?></textarea>
                <?= field_error($errors, 'alamat') ?>
              </div>
            </div>

            <p class="form-note">
              <svg class="icon"><use href="#i-info" /></svg>
              <span>NIM/NIK dan email harus unik. Anggota nonaktif tidak dapat membuat peminjaman baru.</span>
            </p>
          </div>

          <div class="modal-footer">
            <button class="btn-ghost" type="button" data-bs-dismiss="modal">Batal</button>
            <button class="btn-add btn-add--modal" type="submit" id="memberSubmit">
              <svg class="icon"><use href="#i-check" /></svg> Simpan anggota
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($showLoanModal): ?>
  <div class="modal fade modal-app" id="loanModal" tabindex="-1" aria-labelledby="loanModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="<?= e(url('borrowing', ['action' => 'store'])) ?>" id="loanForm" novalidate
          data-duration="<?= $duration ?>" data-tariff="<?= $tariff ?>">
          <?= csrf_field() ?>

          <div class="modal-header">
            <div class="modal-heading">
              <span class="modal-icon"><svg class="icon"><use href="#i-repeat" /></svg></span>
              <div>
                <h2 id="loanModalTitle">Peminjaman baru</h2>
                <p>Jatuh tempo otomatis <?= $duration ?> hari setelah tanggal pinjam.</p>
              </div>
            </div>
            <button class="modal-close" type="button" data-bs-dismiss="modal" aria-label="Tutup">
              <svg class="icon"><use href="#i-x" /></svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="form-grid">
              <div class="<?= field_class($errors, 'id_anggota') ?> form-field--full">
                <label for="loanMember">Anggota <span class="req">*</span></label>
                <select id="loanMember" name="id_anggota" required>
                  <option value="">Pilih anggota…</option>
                  <?php foreach ($members as $member): ?>
                    <?php
                      $activeLoans = (int) ($member['active_loans'] ?? 0);
                      $limit = (int) ($member['maks_pinjam'] ?? 3);
                      $full = $activeLoans >= $limit;
                    ?>
                    <option value="<?= (int) $member['id_anggota'] ?>"
                      data-active="<?= $activeLoans ?>" data-limit="<?= $limit ?>"
                      data-name="<?= e($member['nama']) ?>"
                      <?= $selectedMember === (string) $member['id_anggota'] ? ' selected' : '' ?>
                      <?= $full ? ' disabled' : '' ?>>
                      <?= e($member['nama']) ?> — <?= $activeLoans ?>/<?= $limit ?> aktif<?= $full ? ' (batas)' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'id_anggota') ?>
              </div>

              <div class="<?= field_class($errors, 'id_buku') ?> form-field--full">
                <label for="loanBook">Buku <span class="req">*</span></label>
                <select id="loanBook" name="id_buku" required>
                  <option value="">Pilih buku yang tersedia…</option>
                  <?php foreach ($books as $book): ?>
                    <option value="<?= (int) $book['id_buku'] ?>"
                      data-stock="<?= (int) $book['stok_tersedia'] ?>"
                      data-title="<?= e($book['judul']) ?>"
                      <?= $selectedBook === (string) $book['id_buku'] ? ' selected' : '' ?>>
                      <?= e($book['judul']) ?> — <?= (int) $book['stok_tersedia'] ?> tersedia
                    </option>
                  <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'id_buku') ?>
              </div>

              <div class="<?= field_class($errors, 'tgl_pinjam') ?> form-field--full">
                <label for="loanDate">Tanggal pinjam <span class="req">*</span></label>
                <input id="loanDate" name="tgl_pinjam" type="date" required
                  min="<?= e(date('Y-m-d', strtotime('-30 days'))) ?>" max="<?= e(today()) ?>"
                  value="<?= e(old('tgl_pinjam', today())) ?>">
                <?= field_error($errors, 'tgl_pinjam') ?>
              </div>
            </div>

            <div class="loan-preview" id="loanPreview">
              <span class="loan-preview-hint">Pilih anggota dan buku untuk melihat pratinjau peminjaman.</span>
            </div>

            <p class="form-note">
              <svg class="icon"><use href="#i-wallet" /></svg>
              <span>Denda keterlambatan <strong><?= e(money($tariff)) ?>/hari</strong>. Stok berkurang otomatis saat transaksi disimpan.</span>
            </p>
          </div>

          <div class="modal-footer">
            <button class="btn-ghost" type="button" data-bs-dismiss="modal">Batal</button>
            <button class="btn-add btn-add--modal" type="submit">
              <svg class="icon"><use href="#i-check" /></svg> Buat peminjaman
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="modal fade modal-app" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog--sm">
    <div class="modal-content">
      <form method="post" action="#" id="confirmForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="confirmId" value="">

        <div class="modal-header">
          <div class="modal-heading">
            <span class="modal-icon modal-icon--danger" id="confirmIcon">
              <svg class="icon"><use href="#i-alert" /></svg>
            </span>
            <div>
              <h2 id="confirmTitle">Konfirmasi tindakan</h2>
              <p id="confirmSub">Tindakan ini akan langsung diproses.</p>
            </div>
          </div>
          <button class="modal-close" type="button" data-bs-dismiss="modal" aria-label="Tutup">
            <svg class="icon"><use href="#i-x" /></svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="confirm-message" id="confirmMessage">Apakah Anda yakin?</div>
        </div>

        <div class="modal-footer">
          <button class="btn-ghost" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn-danger" type="submit" id="confirmOk">Ya, lanjutkan</button>
        </div>
      </form>
    </div>
  </div>
</div>
