<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Validator;
use App\Core\View;
use ReflectionMethod;

/**
 * Induk controller: dispatch action, render view, dan validasi form.
 */
abstract class BaseController
{
    /** Kunci halaman aktif (dipakai sidebar & topbar). */
    protected string $pageKey = 'dashboard';

    /** Alias action untuk nama method yang tidak bisa dipakai di URL. */
    protected array $actionAliases = [];

    /** Jalankan method publik controller sesuai parameter ?action=. */
    public function handle(string $action): void
    {
        $action = $action !== '' ? $action : 'index';
        $action = $this->actionAliases[$action] ?? $action;

        if (!method_exists($this, $action)) {
            abort_404();
        }

        $method = new ReflectionMethod($this, $action);
        $isOwnPublicMethod = $method->isPublic()
            && $method->getDeclaringClass()->getName() === static::class;

        if (!$isOwnPublicMethod) {
            abort_404();
        }

        $this->{$action}();
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

    /** Pastikan request POST dan token CSRF valid. */
    protected function guardCsrf(): void
    {
        $this->requirePost();

        if (!verify_csrf()) {
            flash('error', 'Sesi form kedaluwarsa atau token tidak valid. Silakan ulangi.');
            redirect(url($this->pageKey));
        }
    }

    /** Simpan error validasi + input lama, lalu kembali ke halaman asal. */
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
