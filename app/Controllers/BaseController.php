<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Validator;
use App\Core\View;

/**
 * Induk controller: dispatch action whitelist, render view, guard CSRF.
 */
abstract class BaseController
{
    /** Kunci halaman aktif (dipakai sidebar & topbar). */
    protected string $pageKey = 'dashboard';

    /**
     * Daftar action yang boleh dipanggil, contoh: ['store' => 'store'].
     *
     * @return array<string, string>
     */
    abstract protected function actions(): array;

    public function handle(string $action): void
    {
        $action = $action === '' ? 'index' : $action;
        $map = $this->actions();

        if (!isset($map[$action])) {
            abort_404();
        }

        $method = $map[$action];
        $this->{$method}();
    }

    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        View::render($view, array_merge(['pageKey' => $this->pageKey], $data), $layout);
    }

    protected function requirePost(): void
    {
        if (!is_post()) {
            abort_404();
        }
    }

    /**
     * Validasi token CSRF untuk semua aksi yang mengubah data.
     */
    protected function guardCsrf(): void
    {
        $this->requirePost();

        if (!verify_csrf()) {
            flash('error', 'Sesi form kedaluwarsa atau token tidak valid. Silakan ulangi.');
            redirect(url($this->pageKey));
        }
    }

    /**
     * Simpan error validasi + input lama, lalu kembali ke halaman asal.
     */
    protected function failValidation(Validator $validator, array $old, string $modal = ''): void
    {
        remember_form_errors($validator->errors());
        remember_old_input($old);
        if ($modal !== '') {
            reopen_modal($modal);
        }
        flash('error', $validator->firstError());
        redirect_back(url($this->pageKey));
    }

    protected function pageNumber(): int
    {
        return max(1, int_input('p', 1));
    }

    protected function perPage(): int
    {
        return (int) config('per_page', 8);
    }
}
