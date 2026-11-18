<?php
/**
 * Pengaturan aplikasi.
 *
 * @var array $settings
 * @var array $system
 * @var array $bookStats
 * @var array $memberStats
 * @var array $loanStats
 * @var array $fineStats
 */
$appName = (string) ($settings['nama_aplikasi'] ?? 'BukuHub');
$institution = (string) ($settings['nama_instansi'] ?? 'Perpustakaan Digital BukuHub');
?>
<section class="view is-active" aria-labelledby="settingsHeading">
  <div class="module-head">
    <div>
      <p class="module-eyebrow">Konfigurasi</p>
      <h2 class="module-title" id="settingsHeading">Pengaturan perpustakaan</h2>
      <p class="module-sub">Identitas aplikasi, aturan peminjaman, dan tarif denda. Perubahan langsung dipakai untuk transaksi berikutnya.</p>
    </div>
    <div class="module-actions">
      <a class="btn-secondary" href="<?= e(url('help')) ?>">
        <svg class="icon"><use href="#i-help" /></svg> Panduan
      </a>
    </div>
  </div>

  <div class="settings-grid">
    <form class="card settings-card" method="post" action="<?= e(url('settings', ['action' => 'update'])) ?>">
      <?= csrf_field() ?>

      <div class="settings-head">
        <span class="settings-icon"><svg class="icon"><use href="#i-settings" /></svg></span>
        <div>
          <h3 class="module-title module-title--sm">Identitas &amp; aturan</h3>
          <p class="module-sub">Nama aplikasi tampil di sidebar dan halaman masuk.</p>
        </div>
      </div>

      <div class="settings-form">
        <div class="<?= field_class($formErrors ?? [], 'nama_aplikasi') ?>">
          <label for="settingAppName">Nama aplikasi <span class="req">*</span></label>
          <input id="settingAppName" name="nama_aplikasi" type="text" maxlength="60" required
            value="<?= e(old('nama_aplikasi', $appName)) ?>">
          <?= field_error($formErrors ?? [], 'nama_aplikasi') ?>
        </div>

        <div class="<?= field_class($formErrors ?? [], 'nama_instansi') ?>">
          <label for="settingInstitution">Nama instansi <span class="req">*</span></label>
          <input id="settingInstitution" name="nama_instansi" type="text" maxlength="120" required
            value="<?= e(old('nama_instansi', $institution)) ?>">
          <?= field_error($formErrors ?? [], 'nama_instansi') ?>
        </div>

        <div class="form-grid">
          <div class="<?= field_class($formErrors ?? [], 'tarif_denda') ?>">
            <label for="settingRate">Tarif denda / hari (Rp) <span class="req">*</span></label>
            <input id="settingRate" name="tarif_denda" type="number" inputmode="numeric" min="0" max="100000" required
              value="<?= e(old('tarif_denda', $settings['tarif_denda'] ?? 1000)) ?>">
            <?= field_error($formErrors ?? [], 'tarif_denda') ?>
          </div>

          <div class="<?= field_class($formErrors ?? [], 'durasi_hari') ?>">
            <label for="settingDuration">Durasi pinjam (hari) <span class="req">*</span></label>
            <input id="settingDuration" name="durasi_hari" type="number" inputmode="numeric" min="1" max="60" required
              value="<?= e(old('durasi_hari', $settings['durasi_hari'] ?? 7)) ?>">
            <?= field_error($formErrors ?? [], 'durasi_hari') ?>
          </div>

          <div class="<?= field_class($formErrors ?? [], 'maks_pinjam_default') ?>">
            <label for="settingLimit">Batas pinjam default <span class="req">*</span></label>
            <input id="settingLimit" name="maks_pinjam_default" type="number" inputmode="numeric" min="1" max="10" required
              value="<?= e(old('maks_pinjam_default', $settings['maks_pinjam_default'] ?? 3)) ?>">
            <?= field_error($formErrors ?? [], 'maks_pinjam_default') ?>
          </div>

          <div class="<?= field_class($formErrors ?? [], 'email_kontak') ?>">
            <label for="settingEmail">Email kontak</label>
            <input id="settingEmail" name="email_kontak" type="email" maxlength="120"
              value="<?= e(old('email_kontak', $settings['email_kontak'] ?? '')) ?>" placeholder="halo@bukuhub.id">
            <?= field_error($formErrors ?? [], 'email_kontak') ?>
          </div>
        </div>

        <div class="<?= field_class($formErrors ?? [], 'alamat') ?>">
          <label for="settingAddress">Alamat instansi</label>
          <textarea id="settingAddress" name="alamat" rows="2" maxlength="255"
            placeholder="Alamat perpustakaan"><?= e(old('alamat', $settings['alamat'] ?? '')) ?></textarea>
          <?= field_error($formErrors ?? [], 'alamat') ?>
        </div>
      </div>

      <p class="form-note">
        <svg class="icon"><use href="#i-info" /></svg>
        <span>Durasi pinjam dipakai saat membuat peminjaman baru. Tarif denda disimpan pada setiap transaksi agar riwayat tidak berubah saat tarif diperbarui.</span>
      </p>

      <div class="settings-actions">
        <button class="btn-add btn-add--sm" type="submit">
          <svg class="icon"><use href="#i-save" /></svg> Simpan pengaturan
        </button>
      </div>
    </form>

    <div class="card settings-card">
      <div class="settings-head">
        <span class="settings-icon settings-icon--dark"><svg class="icon"><use href="#i-info" /></svg></span>
        <div>
          <h3 class="module-title module-title--sm">Informasi sistem</h3>
          <p class="module-sub">Lingkungan server yang sedang menjalankan BukuHub.</p>
        </div>
      </div>

      <div class="storage-panel">
        <div class="storage-row"><span>Versi aplikasi</span><strong>BukuHub <?= e(config('version', '1.0.0')) ?></strong></div>
        <div class="storage-row"><span>PHP</span><code><?= e($system['php'] ?? PHP_VERSION) ?></code></div>
        <div class="storage-row"><span>MySQL / MariaDB</span><code><?= e($system['mysql'] ?? '—') ?></code></div>
        <div class="storage-row"><span>Nama database</span><code><?= e($system['database'] ?? '—') ?></code></div>
        <div class="storage-row"><span>Zona waktu</span><strong><?= e($system['timezone'] ?? '—') ?></strong></div>
        <div class="storage-row"><span>Akun pengelola</span><strong><?= num($system['users'] ?? 0) ?> user</strong></div>
      </div>

      <div class="storage-panel">
        <div class="storage-row"><span>Judul buku</span><strong><?= num($bookStats['total_judul'] ?? 0) ?></strong></div>
        <div class="storage-row"><span>Eksemplar</span><strong><?= num($bookStats['total_eksemplar'] ?? 0) ?></strong></div>
        <div class="storage-row"><span>Anggota</span><strong><?= num($memberStats['total'] ?? 0) ?></strong></div>
        <div class="storage-row"><span>Transaksi</span><strong><?= num($loanStats['total'] ?? 0) ?></strong></div>
        <div class="storage-row"><span>Denda tercatat</span><strong><?= money($fineStats['nilai_total'] ?? 0) ?></strong></div>
      </div>

      <p class="fine-foot">Direktori aplikasi: <code><?= e(BASE_PATH) ?></code>. Log teknis tersimpan di <code>storage/logs/app.log</code> dan tidak ditampilkan ke pengguna.</p>

      <a class="btn-secondary detail-aside-cta" href="<?= e(url('reports')) ?>">
        <svg class="icon"><use href="#i-chart" /></svg> Lihat laporan
      </a>
    </div>
  </div>
</section>
