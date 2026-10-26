<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Repositories;

use App\Core\Database;
use PDO;

final class AttendanceRepository
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
     *     summary: array{total: int, present: int, late: int, absent: int}
     * }
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $countStatement = $this->database->connection()->prepare(
            'SELECT COUNT(*) FROM attendance_records a' . $where
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = $pages === 0 ? 1 : min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->database->connection()->prepare(
            'SELECT a.*
             FROM attendance_records a' . $where . '
             ORDER BY a.work_date DESC, a.id DESC
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
            'SELECT a.* FROM attendance_records a WHERE a.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function existsForEmployeeDate(int $employeeId, string $workDate, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM attendance_records
                WHERE employee_id = :employee_id AND work_date = :work_date';
        $parameters = [
            'employee_id' => $employeeId,
            'work_date' => $workDate,
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
     * @param array{
     *     employee_id: int,
     *     work_date: string,
     *     check_in: string|null,
     *     check_out: string|null,
     *     status: string,
     *     notes: string|null,
     *     created_by: int|null,
     *     updated_by: int|null
     * } $data
     */
    public function create(array $data): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO attendance_records
                (employee_id, work_date, check_in, check_out, status, notes, created_by, updated_by)
             VALUES
                (:employee_id, :work_date, :check_in, :check_out, :status, :notes, :created_by, :updated_by)'
        );
        $statement->execute($data);

        return (int) $this->database->connection()->lastInsertId();
    }

    /**
     * @param array{
     *     work_date: string,
     *     check_in: string|null,
     *     check_out: string|null,
     *     status: string,
     *     notes: string|null,
     *     updated_by: int|null
     * } $data
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE attendance_records
             SET work_date = :work_date,
                 check_in = :check_in,
                 check_out = :check_out,
                 status = :status,
                 notes = :notes,
                 updated_by = :updated_by
             WHERE id = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    /**
     * @param array{
     *     employee_id: int|null,
     *     date_from: string|null,
     *     date_to: string|null,
     *     status: string|null
     * } $filters
     * @return array{total: int, present: int, late: int, absent: int}
     */
    public function summary(array $filters): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $statement = $this->database->connection()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN a.status = \'present\' THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN a.status = \'late\' THEN 1 ELSE 0 END) AS late,
                    SUM(CASE WHEN a.status = \'absent\' THEN 1 ELSE 0 END) AS absent
             FROM attendance_records a' . $where
        );
        $statement->execute($parameters);
        $row = $statement->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'present' => (int) ($row['present'] ?? 0),
            'late' => (int) ($row['late'] ?? 0),
            'absent' => (int) ($row['absent'] ?? 0),
        ];
    }

    /**
     * Registros de un mes para el reporte.
     *
     * @param list<int>|null $employeeIds null = todos; [] = ninguno
     * @return list<array<string, mixed>>
     */
    public function monthRecords(string $start, string $end, ?array $employeeIds = null): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $sql = 'SELECT a.*
                FROM attendance_records a
                WHERE a.work_date BETWEEN :start AND :end';
        $parameters = ['start' => $start, 'end' => $end];

        if ($employeeIds !== null) {
            $placeholders = [];
            foreach (array_values($employeeIds) as $index => $employeeId) {
                $key = 'employee_' . $index;
                $placeholders[] = ':' . $key;
                $parameters[$key] = (int) $employeeId;
            }

            $sql .= ' AND a.employee_id IN (' . implode(', ', $placeholders) . ')';
        }

        $sql .= ' ORDER BY a.work_date, a.id';

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
     * @return array{0: string, 1: array<string, string|int>}
     */
    private function filterClause(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $employeeId = $filters['employee_id'] ?? null;
        if ($employeeId !== null && (int) $employeeId > 0) {
            $conditions[] = 'a.employee_id = :filter_employee_id';
            $parameters['filter_employee_id'] = (int) $employeeId;
        }

        $dateFrom = $filters['date_from'] ?? null;
        if ($dateFrom !== null && (string) $dateFrom !== '') {
            $conditions[] = 'a.work_date >= :filter_date_from';
            $parameters['filter_date_from'] = (string) $dateFrom;
        }

        $dateTo = $filters['date_to'] ?? null;
        if ($dateTo !== null && (string) $dateTo !== '') {
            $conditions[] = 'a.work_date <= :filter_date_to';
            $parameters['filter_date_to'] = (string) $dateTo;
        }

        $status = $filters['status'] ?? null;
        if (in_array($status, ['present', 'late', 'absent'], true)) {
            $conditions[] = 'a.status = :filter_status';
            $parameters['filter_status'] = (string) $status;
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }
}
