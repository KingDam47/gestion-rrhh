<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

enum LoginResult
{
    case Success;
    case InvalidCredentials;
    case TooManyAttempts;
}
