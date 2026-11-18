<?php
/**
 * Halaman masuk.
 */
$errors = $formErrors ?? [];
?>
<div class="auth-form-head">
  <span class="auth-badge"><svg class="icon"><use href="#i-lock" /></svg> Akses petugas</span>
  <h2>Masuk ke BukuHub<span class="brand-dot">.</span></h2>
  <p>Gunakan akun pustakawan untuk mengelola koleksi dan sirkulasi.</p>
</div>

<form class="auth-form" method="post" action="<?= e(url('login', ['action' => 'login'])) ?>" novalidate>
  <?= csrf_field() ?>

  <div class="<?= field_class($errors, 'username') ?>">
    <label for="loginUsername">Username</label>
    <div class="auth-input">
      <svg class="icon"><use href="#i-user" /></svg>
      <input id="loginUsername" name="username" type="text" maxlength="60" required autofocus
        autocomplete="username" value="<?= e(old('username')) ?>" placeholder="admin">
    </div>
    <?= field_error($errors, 'username') ?>
  </div>

  <div class="<?= field_class($errors, 'password') ?>">
    <label for="loginPassword">Password</label>
    <div class="auth-input">
      <svg class="icon"><use href="#i-lock" /></svg>
      <input id="loginPassword" name="password" type="password" required
        autocomplete="current-password" placeholder="••••••••">
      <button class="auth-reveal" type="button" data-password-toggle="#loginPassword" aria-label="Tampilkan password">
        <svg class="icon"><use href="#i-eye" /></svg>
      </button>
    </div>
    <?= field_error($errors, 'password') ?>
  </div>

  <button class="btn-add auth-submit" type="submit">
    <svg class="icon"><use href="#i-arrow-right" /></svg> Masuk ke dashboard
  </button>
</form>

<div class="auth-demo">
  <div>
    <strong>Akun demo</strong>
    <span>Username <code>admin</code> · Password <code>admin123</code></span>
  </div>
  <span class="auth-demo-note">Ganti password setelah instalasi.</span>
</div>

<p class="auth-security">
  <svg class="icon"><use href="#i-info" /></svg>
  Sesi berakhir setelah 30 menit tanpa aktivitas. Percobaan login dibatasi untuk mencegah brute force.
</p>
