<?php

declare(strict_types=1);

namespace App\Modules\Employees\Repositories;

use App\Core\Database;

final class PositionRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(?int $departmentId = null): array
    {
        $sql = 'SELECT p.*,
                       d.name AS department_name,
                       d.is_active AS department_active,
                       (SELECT COUNT(*) FROM employees e WHERE e.position_id = p.id) AS employee_count,
                       (SELECT COUNT(*) FROM employees e
                         WHERE e.position_id = p.id AND e.status = \'active\') AS active_employee_count
                FROM positions p
                INNER JOIN departments d ON d.id = p.department_id';

        $parameters = [];

        if ($departmentId !== null) {
            $sql .= ' WHERE p.department_id = :department_id';
            $parameters['department_id'] = $departmentId;
        }

        $sql .= ' ORDER BY d.name, p.name';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT p.*,
                    d.name AS department_name,
                    d.is_active AS department_active,
                    (SELECT COUNT(*) FROM employees e WHERE e.position_id = p.id) AS employee_count,
                    (SELECT COUNT(*) FROM employees e
                      WHERE e.position_id = p.id AND e.status = \'active\') AS active_employee_count
             FROM positions p
             INNER JOIN departments d ON d.id = p.department_id
             WHERE p.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function nameExistsInDepartment(int $departmentId, string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM positions WHERE department_id = :department_id AND name = :name';
        $parameters = ['department_id' => $departmentId, 'name' => $name];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function create(int $departmentId, string $name, ?string $description): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO positions (department_id, name, description, is_active)
             VALUES (:department_id, :name, :description, 1)'
        );
        $statement->execute([
            'department_id' => $departmentId,
            'name' => $name,
            'description' => $description,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function update(int $id, int $departmentId, string $name, ?string $description): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE positions
             SET department_id = :department_id, name = :name, description = :description
             WHERE id = :id'
        );
        $statement->execute([
            'department_id' => $departmentId,
            'name' => $name,
            'description' => $description,
            'id' => $id,
        ]);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE positions SET is_active = :is_active WHERE id = :id'
        );
        $statement->execute(['is_active' => $active ? 1 : 0, 'id' => $id]);
    }

    /**
     * Cargos para formularios: activos de departamentos activos más el actual.
     *
     * @return list<array<string, mixed>>
     */
    public function optionsForForm(?int $currentId = null): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT p.id, p.department_id, p.name, p.is_active, d.name AS department_name
             FROM positions p
             INNER JOIN departments d ON d.id = p.department_id
             WHERE (p.is_active = 1 AND d.is_active = 1) OR p.id = :current_id
             ORDER BY d.name, p.name'
        );
        $statement->execute(['current_id' => $currentId ?? 0]);

        return $statement->fetchAll();
    }

    /**
     * Cargos completos para filtros y formularios de otros módulos.
     *
     * @return list<array<string, mixed>>
     */
    public function options(?int $departmentId = null): array
    {
        $sql = 'SELECT p.id, p.department_id, p.name, p.is_active, d.name AS department_name
                FROM positions p
                INNER JOIN departments d ON d.id = p.department_id';

        $parameters = [];

        if ($departmentId !== null) {
            $sql .= ' WHERE p.department_id = :department_id';
            $parameters['department_id'] = $departmentId;
        }

        $sql .= ' ORDER BY d.name, p.name';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }
}
