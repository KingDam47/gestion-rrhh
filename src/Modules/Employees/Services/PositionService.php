<?php

declare(strict_types=1);

namespace App\Modules\Employees\Services;

use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Logger;
use App\Core\Validator;
use App\Modules\Employees\Repositories\DepartmentRepository;
use App\Modules\Employees\Repositories\PositionRepository;
use App\Modules\Employees\Support\EmployeeData;
use PDOException;

final class PositionService
{
    public function __construct(
        private readonly PositionRepository $positions,
        private readonly DepartmentRepository $departments
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(?int $departmentId = null): array
    {
        if ($departmentId !== null && $departmentId <= 0) {
            $departmentId = null;
        }

        return $this->positions->all($departmentId);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $position = $this->positions->find($id);

        if ($position === null) {
            throw new NotFoundException('El cargo solicitado no existe.');
        }

        return $position;
    }

    /**
     * @return array<string, mixed>
     */
    public function create(array $input): array
    {
        [$data, $errors] = $this->prepare($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $id = $this->positions->create(
                $data['department_id'],
                $data['name'],
                $data['description']
            );
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, array $input): array
    {
        $position = $this->find($id);
        [$data, $errors] = $this->prepare($input, $position);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $this->positions->update(
                $id,
                $data['department_id'],
                $data['name'],
                $data['description']
            );
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array{position: array<string, mixed>, changed: bool}
     */
    public function setActive(int $id, bool $active): array
    {
        $position = $this->find($id);

        if ((bool) $position['is_active'] === $active) {
            return ['position' => $position, 'changed' => false];
        }

        if ($active) {
            $department = $this->departments->find((int) $position['department_id']);

            if ($department === null || (int) $department['is_active'] !== 1) {
                throw new BusinessRuleException(
                    'No se puede reactivar un cargo cuyo departamento está inactivo.'
                );
            }
        }

        $this->positions->setActive($id, $active);

        return ['position' => $this->find($id), 'changed' => true];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function departmentOptions(?int $currentId = null): array
    {
        return $this->departments->optionsForForm($currentId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filterDepartments(): array
    {
        return $this->departments->options();
    }

    /**
     * @return array{
     *     0: array{department_id: int, name: string, description: string|null},
     *     1: array<string, string>
     * }
     */
    private function prepare(array $input, ?array $current = null): array
    {
        $data = [
            'department_id' => trim((string) ($input['department_id'] ?? '')),
            'name' => EmployeeData::name((string) ($input['name'] ?? '')),
            'description' => EmployeeData::description($input['description'] ?? null),
        ];

        $validator = new Validator($data, [
            'department_id' => 'departamento',
            'name' => 'nombre del cargo',
            'description' => 'descripción',
        ]);

        $errors = $validator->validate([
            'department_id' => ['required', 'integer'],
            'name' => ['required', 'max:120'],
            'description' => ['nullable', 'max:1000'],
        ]);

        if ($errors !== []) {
            return [$data, $errors];
        }

        $departmentId = (int) $data['department_id'];
        $department = $this->departments->find($departmentId);

        if ($department === null) {
            $errors['department_id'] = 'El departamento seleccionado no existe.';
        } elseif (
            (int) $department['is_active'] !== 1
            && ($current === null || $departmentId !== (int) $current['department_id'])
        ) {
            $errors['department_id'] = 'El departamento seleccionado está inactivo.';
        }

        if (
            $current !== null
            && $departmentId !== (int) $current['department_id']
            && (int) $current['employee_count'] > 0
        ) {
            $errors['department_id'] = 'No se puede cambiar el departamento de un cargo con empleados asignados.';
        }

        if (
            !isset($errors['name'])
            && $this->positions->nameExistsInDepartment(
                $departmentId,
                $data['name'],
                $current !== null ? (int) $current['id'] : null
            )
        ) {
            $errors['name'] = 'Ya existe un cargo con ese nombre en el departamento seleccionado.';
        }

        $data['department_id'] = $departmentId;

        return [$data, $errors];
    }

    private function saveError(PDOException $exception): ValidationException
    {
        Logger::error('No se pudo guardar el cargo.', [
            'type' => $exception::class,
            'sql_state' => (string) $exception->getCode(),
        ]);

        return new ValidationException(
            ['name' => 'No se pudo guardar el cargo. Comprueba que el nombre no esté repetido en el departamento.'],
            'No se pudo guardar el cargo.'
        );
    }
}
