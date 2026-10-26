<?php

declare(strict_types=1);

namespace App\Modules\Employees\Repositories;

use App\Core\Database;

final class DepartmentRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT d.*,
                    (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id) AS employee_count,
                    (SELECT COUNT(*) FROM employees e
                      WHERE e.department_id = d.id AND e.status = \'active\') AS active_employee_count,
                    (SELECT COUNT(*) FROM positions p WHERE p.department_id = d.id) AS position_count
             FROM departments d
             ORDER BY d.name'
        );

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT d.*,
                    (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id) AS employee_count,
                    (SELECT COUNT(*) FROM employees e
                      WHERE e.department_id = d.id AND e.status = \'active\') AS active_employee_count,
                    (SELECT COUNT(*) FROM positions p WHERE p.department_id = d.id) AS position_count
             FROM departments d
             WHERE d.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM departments WHERE name = :name';
        $parameters = ['name' => $name];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $name, ?string $description): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO departments (name, description, is_active) VALUES (:name, :description, 1)'
        );
        $statement->execute(['name' => $name, 'description' => $description]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function update(int $id, string $name, ?string $description): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE departments SET name = :name, description = :description WHERE id = :id'
        );
        $statement->execute(['name' => $name, 'description' => $description, 'id' => $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE departments SET is_active = :is_active WHERE id = :id'
        );
        $statement->execute(['is_active' => $active ? 1 : 0, 'id' => $id]);
    }

    /**
     * Departamentos para filtros: incluye los inactivos para no ocultar
     * empleados históricos.
     *
     * @return list<array<string, mixed>>
     */
    public function options(): array
    {
        return $this->database->connection()
            ->query('SELECT id, name, is_active FROM departments ORDER BY name')
            ->fetchAll();
    }

    /**
     * Departamentos para formularios: activos más el actual aunque esté inactivo.
     *
     * @return list<array<string, mixed>>
     */
    public function optionsForForm(?int $currentId = null): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, name, is_active
             FROM departments
             WHERE is_active = 1 OR id = :current_id
             ORDER BY name'
        );
        $statement->execute(['current_id' => $currentId ?? 0]);

        return $statement->fetchAll();
    }

    /**
     * Totales por departamento para el reporte de personal.
     *
     * @return list<array<string, mixed>>
     */
    public function summary(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT d.id,
                    d.name,
                    d.is_active,
                    COUNT(e.id) AS total,
                    COALESCE(SUM(CASE WHEN e.status = \'active\' THEN 1 ELSE 0 END), 0) AS active_total
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id
             GROUP BY d.id, d.name, d.is_active
             ORDER BY d.name'
        );

        return $statement->fetchAll();
    }
}
