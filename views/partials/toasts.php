<?php
/**
 * Toast dari flash session. Dipanggil sekali per request.
 */
$flashes = take_flashes();
?>
<div class="toast-stack" id="toastStack" role="status" aria-live="polite" aria-atomic="false">
  <?php foreach ($flashes as $flash): ?>
    <?php
      $type = (string) ($flash['type'] ?? 'info');
      $icon = flash_icon($type);
    ?>
    <div class="app-toast app-toast--<?= e($type) ?>" data-toast>
      <span class="app-toast-icon"><svg class="icon"><use href="#i-<?= e($icon) ?>" /></svg></span>
      <p><?= e($flash['message'] ?? '') ?></p>
      <button class="app-toast-close" type="button" aria-label="Tutup notifikasi">
        <svg class="icon"><use href="#i-x" /></svg>
      </button>
    </div>
  <?php endforeach; ?>
</div>
