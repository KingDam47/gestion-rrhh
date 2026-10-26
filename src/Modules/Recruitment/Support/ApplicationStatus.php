<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Support;

/**
 * Estados y transiciones permitidas de una postulación.
 *
 * El flujo es unidireccional: una postulación seleccionada o rechazada es
 * final y no se reabre en el MVP.
 */
final class ApplicationStatus
{
    public const RECEIVED = 'received';
    public const SCREENING = 'screening';
    public const INTERVIEW = 'interview';
    public const SELECTED = 'selected';
    public const REJECTED = 'rejected';

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        self::RECEIVED => [self::SCREENING, self::REJECTED],
        self::SCREENING => [self::INTERVIEW, self::REJECTED],
        self::INTERVIEW => [self::SELECTED, self::REJECTED],
        self::SELECTED => [],
        self::REJECTED => [],
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    public static function isStatus(string $status): bool
    {
        return array_key_exists($status, self::TRANSITIONS);
    }

    public static function allows(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * @return list<string>
     */
    public static function next(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public static function isFinal(string $status): bool
    {
        return self::next($status) === [];
    }
}
