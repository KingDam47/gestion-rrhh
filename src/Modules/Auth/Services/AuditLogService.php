<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Repositories\AuditLogRepository;
use DateTimeImmutable;

final class AuditLogService
{
    private const PER_PAGE = 20;

    public function __construct(private readonly AuditLogRepository $logs)
    {
    }

    /**
     * @param array{q?: string, entity_type?: string|null, date_from?: string|null, date_to?: string|null} $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     pages: int,
     *     from: int,
     *     to: int,
     *     filters: array{q: string, entity_type: string|null, date_from: string|null, date_to: string|null}
     * }
     */
    public function search(array $filters, int $page): array
    {
        $filters = $this->normalizeFilters($filters);
        $result = $this->logs->paginate($filters, max(1, $page), self::PER_PAGE);

        $result['items'] = array_map(
            fn (array $row): array => $this->decorate($row),
            $result['items']
        );
        $result['filters'] = $filters;

        return $result;
    }

    /**
     * @return list<string>
     */
    public function entityTypes(): array
    {
        return $this->logs->entityTypes();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decorate(array $row): array
    {
        $metadata = [];

        if (is_string($row['metadata'] ?? null) && trim((string) $row['metadata']) !== '') {
            $decoded = json_decode((string) $row['metadata'], true);

            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    $metadata[] = [
                        'key' => is_int($key) ? 'valor' : (string) $key,
                        'value' => $this->metadataValue($value),
                    ];
                }
            } elseif (is_scalar($decoded) || $decoded === null) {
                $metadata[] = [
                    'key' => 'valor',
                    'value' => $this->metadataValue($decoded),
                ];
            }
        }

        $row['metadata_items'] = $metadata;

        return $row;
    }

    private function metadataValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'sí' : 'no';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '—' : $encoded;
    }

    /**
     * @param array{q?: string, entity_type?: string|null, date_from?: string|null, date_to?: string|null} $filters
     * @return array{q: string, entity_type: string|null, date_from: string|null, date_to: string|null}
     */
    private function normalizeFilters(array $filters): array
    {
        $q = is_scalar($filters['q'] ?? null) ? (string) $filters['q'] : '';
        $entityType = is_scalar($filters['entity_type'] ?? null) ? (string) $filters['entity_type'] : '';
        $dateFrom = $this->validDate(is_scalar($filters['date_from'] ?? null) ? (string) $filters['date_from'] : '');
        $dateTo = $this->validDate(is_scalar($filters['date_to'] ?? null) ? (string) $filters['date_to'] : '');

        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'q' => mb_substr(trim($q), 0, 100),
            'entity_type' => $entityType === '' ? null : mb_substr(trim($entityType), 0, 80),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function validDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value
            ? $value
            : null;
    }
}
