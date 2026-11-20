<?php

declare(strict_types=1);

/**
 * BukuHub — front controller.
 * Semua halaman diakses lewat file ini: index.php?page=books
 */

require __DIR__ . '/app/bootstrap.php';

$routes = require __DIR__ . '/routes/web.php';

$page = (string) ($_GET['page'] ?? 'dashboard');
$controllerClass = $routes[$page] ?? null;

if ($controllerClass === null || !class_exists($controllerClass)) {
    abort_404();
}

// Semua halaman selain login membutuhkan autentikasi.
if ($page !== 'login') {
    \App\Core\Auth::requireLogin();
}

$controller = new $controllerClass();
$controller->handle((string) ($_GET['action'] ?? 'index'));
