<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

/**
 * Normalización de los datos de una cuenta de acceso.
 */
final class UserData
{
    public static function name(string $value): string
    {
        $value = trim($value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    public static function email(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }

    public static function role(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }
}
