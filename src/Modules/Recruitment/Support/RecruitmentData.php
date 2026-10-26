<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Support;

use DateTimeImmutable;

/**
 * Normalización de datos del módulo de reclutamiento.
 *
 * Las reglas de negocio viven en los servicios; aquí solo se unifican
 * formatos de texto y fechas.
 */
final class RecruitmentData
{
    public static function name(?string $value): string
    {
        $value = trim((string) $value);

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

    public static function notes(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public static function date(?string $value): string
    {
        return trim((string) $value);
    }

    public static function dateOrNull(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value
            ? $value
            : null;
    }
}
