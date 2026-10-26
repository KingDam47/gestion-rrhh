<?php

declare(strict_types=1);

namespace App\Modules\Employees\Contracts;

/**
 * Caso de uso público de alta de empleado para otros módulos.
 *
 * Recruitment lo usa en el alta guiada de un candidato seleccionado. La
 * validación y la creación siguen perteneciendo a Employees.
 */
interface EmployeeOnboarding
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function create(array $input, ?int $userId): array;
}
