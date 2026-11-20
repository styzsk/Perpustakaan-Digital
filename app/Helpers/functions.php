<?php

declare(strict_types=1);

/* ==========================================================================
   Helper global BukuHub.
   Berisi fungsi kecil yang dipakai bersama oleh controller, model, dan view.
   ========================================================================== */

/* ------------------------------ Database -------------------------------- */

/** Koneksi PDO aktif. */
function db(): PDO
{
    return \App\Core\Database::pdo();
}

/** Jalankan query dengan parameter, kembalikan statement-nya. */
function db_run(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** Ambil semua baris hasil query. */
function db_all(string $sql, array $params = []): array
{
    return db_run($sql, $params)->fetchAll();
}

/** Ambil satu baris hasil query (null bila kosong). */
function db_one(string $sql, array $params = []): ?array
{
    $row = db_run($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** Ambil satu nilai dari kolom pertama. */
function db_value(string $sql, array $params = []): mixed
{
    return db_run($sql, $params)->fetchColumn();
}

/** ID dari baris yang baru disimpan. */
function db_id(): int
{
    return (int) db()->lastInsertId();
}

/** Jalankan beberapa query dalam satu transaksi. */
function db_transaction(callable $callback): mixed
{
    return \App\Core\Database::transaction($callback);
}

/**
 * Query daftar dengan LIMIT/OFFSET dan ringkasan pagination.
 *
 * @return array{items:array,total:int,page:int,perPage:int,totalPages:int,from:int,to:int}
 */
function db_paginate(
    string $countSql,
    string $dataSql,
    array $params,
    int $page,
    int $perPage
): array {
    $total = (int) db_value($countSql, $params);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $totalPages);
    $offset = ($page - 1) * $perPage;

    $stmt = db()->prepare($dataSql . ' LIMIT :limit OFFSET :offset');
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
        'from' => $total === 0 ? 0 : $offset + 1,
        'to' => min($offset + $perPage, $total),
    ];
}

/* ------------------------------ Konfigurasi ----------------------------- */

/**
 * Ambil nilai konfigurasi dengan dot notation, contoh: config('session.lifetime').
 */
function config(string $key, mixed $default = null): mixed
{
    $config = $GLOBALS['app_config'] ?? [];
    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/**
 * Tulis pesan teknis ke storage/logs/app.log tanpa data sensitif.
 */
function app_log(string $message): void
{
    $dir = BASE_PATH . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

/**
 * Catat aktivitas penting ke tabel audit_log.
 *
 * Kegagalan pencatatan tidak boleh menggagalkan aksi utama; detailnya
 * hanya ditulis ke log aplikasi.
 */
function audit_trail(
    string $aksi,
    string $entitas,
    ?int $entitasId = null,
    ?string $detail = null,
    ?int $userId = null
): void {
    try {
        $userId = $userId ?? (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $ip = null;
        }

        $stmt = db()->prepare(
            'INSERT INTO audit_log (id_user, aksi, entitas, entitas_id, detail, ip_address)
             VALUES (:id_user, :aksi, :entitas, :entitas_id, :detail, :ip)'
        );
        $stmt->execute([
            'id_user' => $userId,
            'aksi' => mb_substr($aksi, 0, 60),
            'entitas' => mb_substr($entitas, 0, 40),
            'entitas_id' => $entitasId,
            'detail' => $detail !== null ? mb_substr($detail, 0, 255) : null,
            'ip' => $ip,
        ]);
    } catch (\Throwable $exception) {
        app_log('Audit log gagal ditulis: ' . $exception->getMessage());
    }
}

/** Escape output HTML (anti-XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format rupiah tanpa desimal. */
function money(mixed $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

/** Format angka ribuan gaya Indonesia. */
function num(mixed $value): string
{
    return number_format((float) $value, 0, ',', '.');
}

function today(): string
{
    return date('Y-m-d');
}

/**
 * Selisih hari dari $from ke $to. Positif bila $to lebih lambat.
 */
function diff_days(string $from, string $to): int
{
    $start = new DateTimeImmutable($from);
    $end = new DateTimeImmutable($to);
    return (int) $start->diff($end)->format('%r%a');
}

function month_names(): array
{
    return [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
}

function month_short_names(): array
{
    return [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
}

/** Format tanggal Indonesia, contoh: 18 Nov 2026. */
function format_date(?string $date, bool $withYear = true): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }

    $short = month_short_names();
    $text = date('j', $timestamp) . ' ' . $short[(int) date('n', $timestamp)];
    return $withYear ? $text . ' ' . date('Y', $timestamp) : $text;
}

/** Format tanggal panjang Indonesia, contoh: 18 November 2026. */
function format_date_long(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }

    $months = month_names();
    return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

/** Nama bulan singkat dari nomor bulan (1-12). */
function month_label(int $month): string
{
    $short = month_short_names();
    return $short[$month] ?? '';
}

/**
 * Bangun URL internal aplikasi.
 */
function url(string $page = 'dashboard', array $params = []): string
{
    $query = array_merge(['page' => $page], $params);
    $clean = [];
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $clean[$key] = $value;
    }

    return 'index.php?' . http_build_query($clean);
}

/**
 * URL halaman saat ini dengan beberapa parameter diubah.
 */
function url_with(array $overrides): string
{
    $params = $_GET;
    $route = $params['page'] ?? 'dashboard';
    unset($params['page']);

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    return url((string) $route, $params);
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Ambil input POST/GET yang sudah di-trim. */
function input(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_POST)) {
        $value = $_POST[$key];
    } elseif (array_key_exists($key, $_GET)) {
        $value = $_GET[$key];
    } else {
        return $default;
    }

    // Parameter bersarang (mis. q[]) tidak dipakai aplikasi; anggap tidak valid.
    if (!is_scalar($value)) {
        return $default;
    }

    return is_string($value) ? trim($value) : $value;
}

function int_input(string $key, int $default = 0): int
{
    $value = input($key, $default);
    return is_numeric($value) ? (int) $value : $default;
}

/** Ambil ID positif dari query string / POST. */
function id_input(string $key = 'id'): int
{
    $id = int_input($key, 0);
    return $id > 0 ? $id : 0;
}

function redirect(string $target): void
{
    header('Location: ' . $target, true, 302);
    exit;
}

/**
 * Redirect ke halaman asal bila masih di host yang sama.
 */
function redirect_back(string $fallback): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer !== '') {
        $host = parse_url($referer, PHP_URL_HOST);
        // HTTP_HOST dapat memuat port (localhost:8080), jadi bandingkan hostname saja.
        $currentHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        if ($host !== null && $currentHost !== null && strcasecmp($host, (string) $currentHost) === 0) {
            redirect($referer);
        }
    }

    redirect($fallback);
}

/**
 * Kirim CSV ke browser lalu hentikan request.
 */
function send_csv(string $filename, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $handle = fopen('php://output', 'wb');
    if ($handle === false) {
        abort_404();
    }

    // BOM agar Excel membaca UTF-8 dengan benar.
    fwrite($handle, "\xEF\xBB\xBF");
    if ($header !== []) {
        fputcsv($handle, $header, ',', '"', '');
    }
    foreach ($rows as $row) {
        fputcsv($handle, $row, ',', '"', '');
    }
    fclose($handle);
    exit;
}


/* ------------------------------- Flash ---------------------------------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

/* ---------------------------- Old input --------------------------------- */

function remember_old_input(array $data): void
{
    unset($data['_token'], $data['password'], $data['password_confirm']);
    $_SESSION['_old'] = $data;
}

function pull_old_input(): array
{
    $old = $_SESSION['_old'] ?? [];
    unset($_SESSION['_old']);
    return is_array($old) ? $old : [];
}

function old(string $key, mixed $default = ''): mixed
{
    $old = $GLOBALS['__old_input'] ?? [];
    return $old[$key] ?? $default;
}

function remember_form_errors(array $errors): void
{
    $_SESSION['_errors'] = $errors;
}

function pull_form_errors(): array
{
    $errors = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_errors']);
    return is_array($errors) ? $errors : [];
}

function reopen_modal(string $name): void
{
    $_SESSION['_open_modal'] = $name;
}

function pull_open_modal(): ?string
{
    $modal = $_SESSION['_open_modal'] ?? null;
    unset($_SESSION['_open_modal']);
    return is_string($modal) ? $modal : null;
}

/* -------------------------------- CSRF ---------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals((string) $_SESSION['csrf_token'], $token);
}

/* ------------------------------ Notifikasi ------------------------------- */

/** Angka badge notifikasi di sidebar/topbar. */
function notification_counts(): array
{
    $loans = \App\Models\LoanModel::stats();
    $fines = \App\Models\FineModel::stats();

    return [
        'active_loans' => $loans['aktif'],
        'overdue' => $loans['terlambat'],
        'unpaid_fines' => $fines['jumlah_belum'],
    ];
}

/** Daftar notifikasi dari peminjaman terlambat dan denda belum lunas. */
function notification_feed(int $limit = 5): array
{
    $items = [];

    foreach (\App\Models\LoanModel::paginate(['tab' => 'overdue'], 1, 3)['items'] as $loan) {
        $state = loan_state($loan);
        $items[] = [
            'tone' => 'orange',
            'icon' => 'clock',
            'title' => 'Terlambat ' . $state['late'] . ' hari',
            'message' => $loan['judul'] . ' · ' . $loan['anggota_nama'],
            'time' => format_date($loan['tgl_kembali_rencana']),
            'url' => url('borrowing', ['tab' => 'overdue']),
        ];
    }

    foreach (\App\Models\FineModel::paginate(['status' => 'unpaid'], 1, 3)['items'] as $fine) {
        $items[] = [
            'tone' => 'dark',
            'icon' => 'wallet',
            'title' => 'Denda belum lunas',
            'message' => money($fine['total_denda']) . ' · ' . $fine['anggota_nama'],
            'time' => format_date($fine['tgl_kembali_real']),
            'url' => url('fines', ['status' => 'unpaid']),
        ];
    }

    return array_slice($items, 0, $limit);
}

/* ------------------------------ Tampilan -------------------------------- */

/** Inisial nama untuk avatar, maksimal 2 huruf. */
function initials(?string $name): string
{
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $parts = array_values(array_filter($parts, static fn ($part) => $part !== ''));

    if ($parts === []) {
        return '?';
    }

    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $letters;
}

/** Palet kategori, selaras dengan variabel CSS di base.css. */
function category_style(?string $category): array
{
    $palette = [
        'Fiksi' => ['bg' => '#fff1e7', 'ink' => '#b54708', 'c1' => '#e76f51', 'c2' => '#9a3412'],
        'Non-fiksi' => ['bg' => '#eaf2ff', 'ink' => '#1d4ed8', 'c1' => '#3b82f6', 'c2' => '#1e3a8a'],
        'Sains' => ['bg' => '#e9f8f0', 'ink' => '#0b7a4b', 'c1' => '#0f766e', 'c2' => '#134e4a'],
        'Sejarah' => ['bg' => '#fdf3dc', 'ink' => '#a16207', 'c1' => '#d97706', 'c2' => '#78350f'],
        'Biografi' => ['bg' => '#f3ecff', 'ink' => '#6d28d9', 'c1' => '#7c3aed', 'c2' => '#4c1d95'],
        'Teknologi' => ['bg' => '#e8f6fb', 'ink' => '#0e7490', 'c1' => '#0891b2', 'c2' => '#164e63'],
    ];

    return $palette[(string) $category] ?? [
        'bg' => '#f1f2f5', 'ink' => '#4b5563', 'c1' => '#64748b', 'c2' => '#1e293b',
    ];
}

function avatar_style(int $id): array
{
    $palette = [
        ['#ffe4d6', '#b54708'], ['#e0f2fe', '#0369a1'], ['#dcfce7', '#166534'],
        ['#fae8ff', '#86198f'], ['#fef3c7', '#92400e'], ['#cffafe', '#0e7490'],
        ['#ede9fe', '#5b21b6'], ['#ffedd5', '#9a3412'], ['#f1f5f9', '#334155'],
    ];

    return $palette[abs($id) % count($palette)];
}

/** Warna donut kategori (dipakai chart dashboard). */
function category_color(int $index): string
{
    $colors = ['#ff6b2c', '#ffb37a', '#3b82f6', '#12b76a', '#8b5cf6', '#f59e0b', '#0ea5e9'];
    return $colors[$index % count($colors)];
}

/** Status peminjaman turunan (aktif / terlambat / jatuh tempo / kembali). */
function loan_state(array $loan): array
{
    if (($loan['status'] ?? '') === 'dikembalikan') {
        return ['key' => 'returned', 'label' => 'Dikembalikan', 'class' => 'tag--returned', 'late' => 0];
    }

    $late = diff_days((string) $loan['tgl_kembali_rencana'], today());

    if ($late > 0) {
        return ['key' => 'overdue', 'label' => 'Terlambat', 'class' => 'tag--overdue', 'late' => $late];
    }

    if ($late >= -2) {
        return ['key' => 'due', 'label' => 'Jatuh tempo', 'class' => 'tag--due', 'late' => 0];
    }

    return ['key' => 'active', 'label' => 'Dipinjam', 'class' => 'tag--ontime', 'late' => 0];
}

/** Denda berjalan untuk peminjaman aktif yang terlambat. */
function projected_fine(array $loan): int
{
    if (($loan['status'] ?? '') === 'dikembalikan') {
        return 0;
    }

    $late = diff_days((string) $loan['tgl_kembali_rencana'], today());
    if ($late <= 0) {
        return 0;
    }

    $rate = (int) ($loan['denda_per_hari'] ?? config('defaults.tarif_denda', 1000));
    return $late * $rate;
}

function loan_badge(array $loan): string
{
    $state = loan_state($loan);
    return '<span class="tag ' . $state['class'] . '">' . e($state['label']) . '</span>';
}

/** Chip kategori dengan warna sesuai palet. */
function category_chip(?string $category): string
{
    $style = category_style($category);
    return '<span class="cat-chip" style="--chip-bg:' . $style['bg'] . ';--chip-ink:' . $style['ink'] . '">'
        . e($category) . '</span>';
}

/** Label ketersediaan stok. */
function stock_status(int $available, int $total): array
{
    if ($available <= 0) {
        return ['key' => 'out', 'label' => 'Habis', 'class' => 'status-chip--out'];
    }
    if ($available <= max(1, (int) floor($total * 0.25))) {
        return ['key' => 'low', 'label' => 'Terbatas', 'class' => 'status-chip--low'];
    }
    return ['key' => 'in', 'label' => 'Tersedia', 'class' => 'status-chip--in'];
}

/** URL halaman detail buku. */
function book_url(int $id, string $action = 'show'): string
{
    return url('books', ['action' => $action, 'id' => $id]);
}

function member_url(int $id, string $action = 'show'): string
{
    return url('members', ['action' => $action, 'id' => $id]);
}

/**
 * Render komponen pagination (class .module-foot).
 *
 * @param array{page:int,totalPages:int,total:int,from:int,to:int,perPage:int} $result
 */
function render_pager(array $result, string $unit = 'data'): string
{
    $total = (int) $result['total'];
    $page = (int) $result['page'];
    $totalPages = (int) $result['totalPages'];

    if ($total === 0) {
        return '<div class="module-foot"><span class="pager-summary">Tidak ada ' . e($unit) . ' untuk ditampilkan.</span></div>';
    }

    $summary = 'Menampilkan <strong>' . $result['from'] . '–' . $result['to'] . '</strong> dari ' . $total . ' ' . e($unit);

    $html = '<div class="module-foot"><span class="pager-summary">' . $summary . '</span>';

    if ($totalPages > 1) {
        $html .= '<div class="pager-controls">';
        $html .= '<a class="page-btn" href="' . e(url_with(['p' => max(1, $page - 1)])) . '" aria-label="Halaman sebelumnya"'
            . ($page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '') . '>'
            . '<svg class="icon"><use href="#i-chevron-left"/></svg></a>';

        foreach (pager_pages($page, $totalPages) as $item) {
            if ($item === '…') {
                $html .= '<span class="pager-gap">…</span>';
                continue;
            }
            $number = (int) $item;
            $html .= '<a class="page-btn' . ($number === $page ? ' is-active' : '') . '" href="'
                . e(url_with(['p' => $number])) . '"'
                . ($number === $page ? ' aria-current="page"' : '') . '>' . $number . '</a>';
        }

        $html .= '<a class="page-btn" href="' . e(url_with(['p' => min($totalPages, $page + 1)])) . '" aria-label="Halaman berikutnya"'
            . ($page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '') . '>'
            . '<svg class="icon"><use href="#i-chevron-right"/></svg></a>';
        $html .= '</div>';
    }

    return $html . '</div>';
}

function pager_pages(int $current, int $totalPages): array
{
    if ($totalPages <= 7) {
        return range(1, $totalPages);
    }

    $pages = [1];
    if ($current > 3) {
        $pages[] = '…';
    }

    $start = max(2, $current - 1);
    $end = min($totalPages - 1, $current + 1);

    for ($i = $start; $i <= $end; $i++) {
        $pages[] = $i;
    }

    if ($current < $totalPages - 2) {
        $pages[] = '…';
    }

    $pages[] = $totalPages;

    return $pages;
}

/** Baris kosong untuk tabel (empty state). */
function empty_row(int $colspan, string $title, string $hint = 'Coba ubah kata kunci atau filter.'): string
{
    return '<tr><td colspan="' . $colspan . '"><div class="empty-state">'
        . '<span class="empty-state-icon"><svg class="icon"><use href="#i-search"/></svg></span>'
        . '<h3>' . e($title) . '</h3><p>' . e($hint) . '</p></div></td></tr>';
}

/** Kelas form-field + pesan error. */
function field_class(array $errors, string $field): string
{
    return isset($errors[$field]) ? 'form-field is-invalid' : 'form-field';
}

function field_error(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<span class="field-error">' . e($errors[$field]) . '</span>'
        : '';
}

/** Ikon toast berdasarkan tipe flash. */
function flash_icon(string $type): string
{
    return match ($type) {
        'success' => 'check',
        'error', 'danger' => 'alert',
        'warning' => 'alert',
        default => 'sparkles',
    };
}

/** Inisial brand / navbar. */
function nav_active(string $current, string $target): string
{
    return $current === $target ? ' is-active' : '';
}
