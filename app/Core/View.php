<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Render view PHP dengan layout. Tidak memakai template engine tambahan.
 */
final class View
{
    public static function render(string $template, array $data = [], string $layout = 'app'): void
    {
        $viewFile = BASE_PATH . '/views/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('View tidak ditemukan: ' . $template);
        }

        $shared = [];
        if ($layout === 'app') {
            $user = Auth::user();
            $shared = [
                'user' => $user ?? [],
                'notifications' => \App\Services\NotificationService::feed(),
                'navCounts' => \App\Services\NotificationService::navCounts(),
                'openModal' => pull_open_modal(),
                'old' => pull_old_input(),
                'formErrors' => pull_form_errors(),
            ];
        } elseif ($layout === 'auth') {
            $shared = [
                'openModal' => null,
                'old' => pull_old_input(),
                'formErrors' => pull_form_errors(),
            ];
        }

        $vars = array_merge($shared, $data);

        $GLOBALS['__old_input'] = $shared['old'] ?? [];
        $GLOBALS['__form_errors'] = $shared['formErrors'] ?? [];

        extract($vars, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require BASE_PATH . '/views/layouts/' . $layout . '.php';
    }
}
