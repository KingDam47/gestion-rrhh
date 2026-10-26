<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Core\Database;
use PDO;

final class AuditLogRepository
{
    public function __construct(private readonly Database $database)
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
     *     to: int
     * }
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$where, $parameters] = $this->filterClause($filters);
        $from = ' FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id' . $where;

        $countStatement = $this->database->connection()->prepare('SELECT COUNT(*)' . $from);
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = max(1, min($page, max(1, $pages)));
        $offset = ($page - 1) * $perPage;

        $statement = $this->database->connection()->prepare(
            'SELECT a.id, a.user_id, a.action, a.entity_type, a.entity_id, a.metadata,
                    a.ip_address, a.created_at, u.name AS user_name'
            . $from
            . ' ORDER BY a.created_at DESC, a.id DESC
                LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * @return list<string>
     */
    public function entityTypes(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type'
        );

        return array_map(
            static fn (array $row): string => (string) $row['entity_type'],
            $statement->fetchAll()
        );
    }

    /**
     * @param array{q?: string, entity_type?: string|null, date_from?: string|null, date_to?: string|null} $filters
     * @return array{0: string, 1: array<string, string|int>}
     */
    private function filterClause(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $conditions[] = '(a.action LIKE :search_action
                OR a.entity_type LIKE :search_entity
                OR u.name LIKE :search_user)';
            $parameters['search_action'] = '%' . $search . '%';
            $parameters['search_entity'] = '%' . $search . '%';
            $parameters['search_user'] = '%' . $search . '%';
        }

        $entityType = trim((string) ($filters['entity_type'] ?? ''));
        if ($entityType !== '') {
            $conditions[] = 'a.entity_type = :filter_entity_type';
            $parameters['filter_entity_type'] = $entityType;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $conditions[] = 'a.created_at >= :filter_date_from';
            $parameters['filter_date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $conditions[] = 'a.created_at <= :filter_date_to';
            $parameters['filter_date_to'] = $dateTo . ' 23:59:59';
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }
}
