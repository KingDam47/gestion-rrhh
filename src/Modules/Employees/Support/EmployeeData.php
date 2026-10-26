<?php

declare(strict_types=1);

namespace App\Modules\Employees\Support;

/**
 * Normalización de datos de entrada del módulo.
 *
 * Centralizarla evita que cada controlador o servicio aplique reglas
 * distintas al documento, al correo o a los espacios sobrantes.
 */
final class EmployeeData
{
    public static function document(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return mb_strtoupper($value, 'UTF-8');
    }

    public static function name(string $value): string
    {
        $value = trim($value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    public static function email(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');

        return $value === '' ? null : $value;
    }

    public static function phone(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public static function description(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
