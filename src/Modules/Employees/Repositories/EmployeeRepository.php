<?php

declare(strict_types=1);

namespace App\Modules\Employees\Repositories;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

final class EmployeeRepository
{
    private const EXISTS_COLUMNS = ['document', 'email'];

    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @param array{q?: string, department_id?: int|null, status?: string|null} $filters
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

        $countStatement = $this->database->connection()->prepare(
            'SELECT COUNT(*) FROM employees e' . $where
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = max(1, min($page, max(1, $pages)));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT e.*,
                       d.name AS department_name,
                       d.is_active AS department_active,
                       p.name AS position_name,
                       p.is_active AS position_active
                FROM employees e
                INNER JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id'
                . $where
                . ' ORDER BY e.last_name, e.first_name
                    LIMIT :limit OFFSET :offset';

        $statement = $this->database->connection()->prepare($sql);

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
     * @param array{q?: string, department_id?: int|null, status?: string|null} $filters
     * @return list<array<string, mixed>>
     */
    public function report(array $filters): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $statement = $this->database->connection()->prepare(
            'SELECT e.*,
                    d.name AS department_name,
                    d.is_active AS department_active,
                    p.name AS position_name
             FROM employees e
             INNER JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id'
             . $where
             . ' ORDER BY d.name, e.last_name, e.first_name'
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT e.*,
                    d.name AS department_name,
                    d.is_active AS department_active,
                    p.name AS position_name,
                    p.is_active AS position_active
             FROM employees e
             INNER JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             WHERE e.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, mixed>> Indexado por ID.
     */
    public function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $statement = $this->database->connection()->prepare(
            'SELECT e.*,
                    d.name AS department_name,
                    d.is_active AS department_active,
                    p.name AS position_name,
                    p.is_active AS position_active
             FROM employees e
             INNER JOIN departments d ON d.id = e.department_id
             LEFT JOIN positions p ON p.id = e.position_id
             WHERE e.id IN (' . $placeholders . ')'
        );
        $statement->execute($ids);

        $employees = [];
        foreach ($statement->fetchAll() as $row) {
            $employees[(int) $row['id']] = $row;
        }

        return $employees;
    }

    /**
     * Directorio mínimo de empleados, activos e inactivos.
     *
     * @return list<array<string, mixed>>
     */
    public function directory(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT e.id, e.document, e.first_name, e.last_name, e.status,
                    e.department_id,
                    d.name AS department_name,
                    d.is_active AS department_active
             FROM employees e
             INNER JOIN departments d ON d.id = e.department_id
             ORDER BY e.last_name, e.first_name'
        );

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeOptions(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT id, document, first_name, last_name, email, department_id, position_id
             FROM employees
             WHERE status = \'active\'
             ORDER BY last_name, first_name'
        );

        return $statement->fetchAll();
    }

    /**
     * @return array{active: int, inactive: int, total: int}
     */
    public function stats(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT status, COUNT(*) AS total FROM employees GROUP BY status'
        );

        $stats = ['active' => 0, 'inactive' => 0, 'total' => 0];

        foreach ($statement->fetchAll() as $row) {
            $status = (string) $row['status'];
            $total = (int) $row['total'];

            if (array_key_exists($status, $stats)) {
                $stats[$status] = $total;
            }

            $stats['total'] += $total;
        }

        return $stats;
    }

    public function documentExists(string $document, ?int $exceptId = null): bool
    {
        return $this->valueExists('document', $document, $exceptId);
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        return $this->valueExists('email', $email, $exceptId);
    }

    /**
     * @param array{
     *     document: string,
     *     first_name: string,
     *     last_name: string,
     *     email: string|null,
     *     phone: string|null,
     *     hire_date: string,
     *     department_id: int,
     *     position_id: int|null,
     *     created_by: int|null,
     *     updated_by: int|null
     * } $data
     */
    public function create(array $data): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO employees
                (document, first_name, last_name, email, phone, hire_date,
                 department_id, position_id, status, created_by, updated_by)
             VALUES
                (:document, :first_name, :last_name, :email, :phone, :hire_date,
                 :department_id, :position_id, \'active\', :created_by, :updated_by)'
        );
        $statement->execute($data);

        return (int) $this->database->connection()->lastInsertId();
    }

    /**
     * @param array{
     *     document: string,
     *     first_name: string,
     *     last_name: string,
     *     email: string|null,
     *     phone: string|null,
     *     hire_date: string,
     *     department_id: int,
     *     position_id: int|null,
     *     updated_by: int|null
     * } $data
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE employees
             SET document = :document,
                 first_name = :first_name,
                 last_name = :last_name,
                 email = :email,
                 phone = :phone,
                 hire_date = :hire_date,
                 department_id = :department_id,
                 position_id = :position_id,
                 updated_by = :updated_by
             WHERE id = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    public function setStatus(int $id, string $status, ?int $updatedBy): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE employees SET status = :status, updated_by = :updated_by WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'updated_by' => $updatedBy,
            'id' => $id,
        ]);
    }

    private function valueExists(string $column, string $value, ?int $exceptId): bool
    {
        if (!in_array($column, self::EXISTS_COLUMNS, true)) {
            throw new InvalidArgumentException(sprintf('Columna no permitida para comprobar duplicados: %s.', $column));
        }

        $sql = sprintf('SELECT 1 FROM employees WHERE %s = :value', $column);
        $parameters = ['value' => $value];

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
     * @param array{q?: string, department_id?: int|null, status?: string|null} $filters
     * @return array{0: string, 1: array<string, string|int>}
     */
    private function filterClause(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions[] = '(e.document LIKE :q_document
                              OR e.first_name LIKE :q_first_name
                              OR e.last_name LIKE :q_last_name
                              OR e.email LIKE :q_email)';
            $parameters['q_document'] = $like;
            $parameters['q_first_name'] = $like;
            $parameters['q_last_name'] = $like;
            $parameters['q_email'] = $like;
        }

        $departmentId = $filters['department_id'] ?? null;
        if ($departmentId !== null && (int) $departmentId > 0) {
            $conditions[] = 'e.department_id = :filter_department_id';
            $parameters['filter_department_id'] = (int) $departmentId;
        }

        $status = $filters['status'] ?? null;
        if (in_array($status, ['active', 'inactive'], true)) {
            $conditions[] = 'e.status = :filter_status';
            $parameters['filter_status'] = (string) $status;
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }
}
