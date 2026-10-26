<?php

declare(strict_types=1);

namespace App\Modules\Employees\Contracts;

/**
 * Contrato público de solo lectura para otros módulos.
 *
 * Attendance y Recruitment deben depender de esta interfaz, nunca de los
 * repositorios ni de las tablas internas de Employees.
 */
interface EmployeeLookup
{
    /**
     * Devuelve el empleado exista o no activo. Se usa para mostrar historial.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $employeeId): ?array;

    /**
     * Conteos de personal para indicadores agregados.
     *
     * @return array{active: int, inactive: int, total: int}
     */
    public function stats(): array;

    /**
     * @param list<int> $employeeIds
     * @return array<int, array<string, mixed>> Indexado por ID.
     */
    public function findMany(array $employeeIds): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function activeEmployees(): array;

    /**
     * Directorio completo (activos e inactivos) para filtros y reportes.
     *
     * @return list<array<string, mixed>>
     */
    public function directory(): array;

    /**
     * Departamentos completos (activos e inactivos) para filtros y reportes.
     *
     * @return list<array<string, mixed>>
     */
    public function departments(): array;

    /**
     * Cargos completos (activos e inactivos) para filtros y formularios.
     *
     * @return list<array<string, mixed>>
     */
    public function positions(?int $departmentId = null): array;
}
