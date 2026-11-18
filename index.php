<?php

declare(strict_types=1);

/**
 * BukuHub — front controller.
 *
 * Semua halaman diakses melalui file ini:
 *   index.php?page=books
 *   index.php?page=borrowing&action=return
 */

require __DIR__ . '/app/bootstrap.php';

$routes = require __DIR__ . '/routes/web.php';

$page = (string) ($_GET['page'] ?? 'dashboard');

if (!isset($routes[$page])) {
    abort_404();
}

// Semua halaman selain login membutuhkan autentikasi.
if ($page !== 'login') {
    \App\Core\Auth::requireLogin();
}

$controllerClass = $routes[$page];

if (!class_exists($controllerClass)) {
    app_log('Controller tidak ditemukan: ' . $controllerClass);
    abort_404();
}

$controller = new $controllerClass();
$action = (string) ($_GET['action'] ?? 'index');

$controller->handle($action);
