<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BookController;
use App\Controllers\DashboardController;
use App\Controllers\FineController;
use App\Controllers\HelpController;
use App\Controllers\LoanController;
use App\Controllers\MemberController;
use App\Controllers\ReportController;
use App\Controllers\SettingsController;

/**
 * Daftar halaman yang boleh diakses.
 * Hanya controller di whitelist ini yang dapat dipanggil dari URL.
 */
return [
    'login' => AuthController::class,
    'dashboard' => DashboardController::class,
    'books' => BookController::class,
    'members' => MemberController::class,
    'borrowing' => LoanController::class,
    'fines' => FineController::class,
    'reports' => ReportController::class,
    'settings' => SettingsController::class,
    'help' => HelpController::class,
];
