<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Exception untuk kesalahan bisnis yang aman ditampilkan ke pengguna.
 */
class AppException extends RuntimeException
{
}
