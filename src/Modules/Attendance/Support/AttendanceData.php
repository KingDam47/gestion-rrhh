<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Support;

use DateTimeImmutable;

/**
 * Normalización y cálculos simples del módulo.
 *
 * Las reglas de negocio viven en los servicios; aquí solo se transforman
 * fechas, horas y textos para que todos los flujos usen el mismo formato.
 */
final class AttendanceData
{
    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

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

    /**
     * Devuelve la hora en formato HH:MM:SS o null si está vacía.
     */
    public static function time(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        return $value;
    }

    public static function notes(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public static function timeToMinutes(?string $time): ?int
    {
        if (
            $time === null
            || preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', $time, $matches) !== 1
        ) {
            return null;
        }

        return ((int) $matches[1]) * 60 + (int) $matches[2];
    }

    /**
     * Comprueba que la hora final sea igual o posterior a la inicial.
     * Si alguna hora no es válida, la validación del formulario ya la rechaza.
     */
    public static function timeIsAfterOrEqual(?string $start, ?string $end): bool
    {
        $startMinutes = self::timeToMinutes($start);
        $endMinutes = self::timeToMinutes($end);

        if ($startMinutes === null || $endMinutes === null) {
            return true;
        }

        return $endMinutes >= $startMinutes;
    }

    /**
     * Normaliza un mes AAAA-MM. Si no es válido, devuelve el mes actual.
     */
    public static function month(?string $value): string
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $matches) === 1) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];

            if ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12) {
                return sprintf('%04d-%02d', $year, $month);
            }
        }

        return date('Y-m');
    }

    /**
     * @return array{start: string, end: string}
     */
    public static function monthRange(string $month): array
    {
        $month = self::month($month);
        $start = new DateTimeImmutable($month . '-01');

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $start->modify('last day of this month')->format('Y-m-d'),
        ];
    }

    public static function monthLabel(string $month): string
    {
        $month = self::month($month);
        [$year, $number] = array_map('intval', explode('-', $month));

        return (self::MONTHS[$number] ?? $month) . ' ' . $year;
    }

    /**
     * Días naturales incluidos entre dos fechas. Devuelve 0 si no son válidas.
     */
    public static function daysBetween(?string $start, ?string $end): int
    {
        $start = self::dateOrNull($start);
        $end = self::dateOrNull($end);

        if ($start === null || $end === null || $end < $start) {
            return 0;
        }

        $from = new DateTimeImmutable($start);
        $to = new DateTimeImmutable($end);

        return (int) $from->diff($to)->days + 1;
    }
}
