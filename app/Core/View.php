<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Render view PHP dengan layout.
 */
final class View
{
    public static function render(string $template, array $data = [], string $layout = 'app'): void
    {
        $viewFile = BASE_PATH . '/views/' . str_replace('.', '/', $template) . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException('View tidak ditemukan: ' . $template);
        }

        $shared = ['old' => pull_old_input(), 'formErrors' => pull_form_errors()];

        if ($layout === 'app') {
            $shared['user'] = Auth::user() ?? [];
            $shared['notifications'] = notification_feed();
            $shared['navCounts'] = notification_counts();
            $shared['openModal'] = pull_open_modal();
        }

        $vars = array_merge($shared, $data);
        $GLOBALS['__old_input'] = $vars['old'] ?? [];
        $GLOBALS['__form_errors'] = $vars['formErrors'] ?? [];

        extract($vars, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require BASE_PATH . '/views/layouts/' . $layout . '.php';
    }
}
