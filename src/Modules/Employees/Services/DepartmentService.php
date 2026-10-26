<?php

declare(strict_types=1);

namespace App\Modules\Employees\Services;

use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Logger;
use App\Core\Validator;
use App\Modules\Employees\Repositories\DepartmentRepository;
use App\Modules\Employees\Support\EmployeeData;
use PDOException;

final class DepartmentService
{
    public function __construct(private readonly DepartmentRepository $departments)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->departments->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $department = $this->departments->find($id);

        if ($department === null) {
            throw new NotFoundException('El departamento solicitado no existe.');
        }

        return $department;
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
            $id = $this->departments->create($data['name'], $data['description']);
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
        $this->find($id);
        [$data, $errors] = $this->prepare($input, $id);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $this->departments->update($id, $data['name'], $data['description']);
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array{department: array<string, mixed>, changed: bool}
     */
    public function setActive(int $id, bool $active): array
    {
        $department = $this->find($id);

        if ((bool) $department['is_active'] === $active) {
            return ['department' => $department, 'changed' => false];
        }

        $this->departments->setActive($id, $active);

        return ['department' => $this->find($id), 'changed' => true];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summary(): array
    {
        return $this->departments->summary();
    }

    /**
     * @return array{0: array{name: string, description: string|null}, 1: array<string, string>}
     */
    private function prepare(array $input, ?int $exceptId = null): array
    {
        $data = [
            'name' => EmployeeData::name((string) ($input['name'] ?? '')),
            'description' => EmployeeData::description($input['description'] ?? null),
        ];

        $validator = new Validator($data, [
            'name' => 'nombre del departamento',
            'description' => 'descripción',
        ]);

        $errors = $validator->validate([
            'name' => ['required', 'max:100'],
            'description' => ['nullable', 'max:1000'],
        ]);

        if (!isset($errors['name']) && $this->departments->nameExists($data['name'], $exceptId)) {
            $errors['name'] = 'Ya existe un departamento con ese nombre.';
        }

        return [$data, $errors];
    }

    private function saveError(PDOException $exception): ValidationException
    {
        Logger::error('No se pudo guardar el departamento.', [
            'type' => $exception::class,
            'sql_state' => (string) $exception->getCode(),
        ]);

        return new ValidationException(
            ['name' => 'No se pudo guardar el departamento. Comprueba que el nombre no esté repetido.'],
            'No se pudo guardar el departamento.'
        );
    }
}
