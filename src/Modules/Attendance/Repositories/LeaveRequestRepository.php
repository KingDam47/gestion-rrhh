<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Repositories;

use App\Core\Database;
use PDO;

final class LeaveRequestRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @param array{
     *     employee_id: int|null,
     *     date_from: string|null,
     *     date_to: string|null,
     *     status: string|null
     * } $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     pages: int,
     *     from: int,
     *     to: int,
     *     summary: array{total: int, pending: int, approved: int, rejected: int}
     * }
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $countStatement = $this->database->connection()->prepare(
            'SELECT COUNT(*) FROM leave_requests l' . $where
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = $pages === 0 ? 1 : min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->database->connection()->prepare(
            'SELECT l.*
             FROM leave_requests l' . $where . '
             ORDER BY l.start_date DESC, l.id DESC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
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
            'to' => $total === 0 ? 0 : min($offset + $perPage, $total),
            'summary' => $this->summary($filters),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT l.* FROM leave_requests l WHERE l.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array{
     *     employee_id: int,
     *     leave_type: string,
     *     start_date: string,
     *     end_date: string,
     *     reason: string,
     *     created_by: int|null
     * } $data
     */
    public function create(array $data): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO leave_requests
                (employee_id, leave_type, start_date, end_date, reason, status, created_by)
             VALUES
                (:employee_id, :leave_type, :start_date, :end_date, :reason, \'pending\', :created_by)'
        );
        $statement->execute($data);

        return (int) $this->database->connection()->lastInsertId();
    }

    /**
     * Solo actualiza solicitudes pendientes. Devuelve false si ya fue revisada.
     */
    public function review(int $id, string $status, int $reviewedBy, ?string $reviewNotes): bool
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE leave_requests
             SET status = :status,
                 reviewed_by = :reviewed_by,
                 reviewed_at = UTC_TIMESTAMP(),
                 review_notes = :review_notes
             WHERE id = :id AND status = \'pending\''
        );
        $statement->execute([
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'review_notes' => $reviewNotes,
            'id' => $id,
        ]);

        return $statement->rowCount() === 1;
    }

    public function overlapExists(
        int $employeeId,
        string $startDate,
        string $endDate,
        ?int $exceptId = null
    ): bool {
        $sql = 'SELECT 1
                FROM leave_requests
                WHERE employee_id = :employee_id
                  AND status IN (\'pending\', \'approved\')
                  AND start_date <= :end_date
                  AND end_date >= :start_date';
        $parameters = [
            'employee_id' => $employeeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    /**
     * Solicitudes cuyo inicio cae dentro del mes del reporte.
     *
     * @param list<int>|null $employeeIds null = todos; [] = ninguno
     * @return list<array<string, mixed>>
     */
    public function monthRequests(string $start, string $end, ?array $employeeIds = null): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $sql = 'SELECT l.*
                FROM leave_requests l
                WHERE l.start_date BETWEEN :start AND :end';
        $parameters = ['start' => $start, 'end' => $end];

        if ($employeeIds !== null) {
            $placeholders = [];
            foreach (array_values($employeeIds) as $index => $employeeId) {
                $key = 'employee_' . $index;
                $placeholders[] = ':' . $key;
                $parameters[$key] = (int) $employeeId;
            }

            $sql .= ' AND l.employee_id IN (' . implode(', ', $placeholders) . ')';
        }

        $sql .= ' ORDER BY l.start_date DESC, l.id DESC';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /**
     * @param array{
     *     employee_id: int|null,
     *     date_from: string|null,
     *     date_to: string|null,
     *     status: string|null
     * } $filters
     * @return array{total: int, pending: int, approved: int, rejected: int}
     */
    public function summary(array $filters): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $statement = $this->database->connection()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN l.status = \'pending\' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN l.status = \'approved\' THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN l.status = \'rejected\' THEN 1 ELSE 0 END) AS rejected
             FROM leave_requests l' . $where
        );
        $statement->execute($parameters);
        $row = $statement->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
        ];
    }

    /**
     * El periodo de la solicitud se superpone con el rango del filtro.
     *
     * @param array{
     *     employee_id: int|null,
     *     date_from: string|null,
     *     date_to: string|null,
     *     status: string|null
     * } $filters
     * @return array{0: string, 1: array<string, string|int>}
     */
    private function filterClause(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $employeeId = $filters['employee_id'] ?? null;
        if ($employeeId !== null && (int) $employeeId > 0) {
            $conditions[] = 'l.employee_id = :filter_employee_id';
            $parameters['filter_employee_id'] = (int) $employeeId;
        }

        $dateFrom = $filters['date_from'] ?? null;
        if ($dateFrom !== null && (string) $dateFrom !== '') {
            $conditions[] = 'l.end_date >= :filter_date_from';
            $parameters['filter_date_from'] = (string) $dateFrom;
        }

        $dateTo = $filters['date_to'] ?? null;
        if ($dateTo !== null && (string) $dateTo !== '') {
            $conditions[] = 'l.start_date <= :filter_date_to';
            $parameters['filter_date_to'] = (string) $dateTo;
        }

        $status = $filters['status'] ?? null;
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $conditions[] = 'l.status = :filter_status';
            $parameters['filter_status'] = (string) $status;
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }
}
