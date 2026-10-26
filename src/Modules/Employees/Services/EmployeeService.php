<?php

declare(strict_types=1);

namespace App\Modules\Employees\Services;

use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Logger;
use App\Core\Validator;
use App\Modules\Employees\Contracts\EmployeeOnboarding;
use App\Modules\Employees\Repositories\DepartmentRepository;
use App\Modules\Employees\Repositories\EmployeeRepository;
use App\Modules\Employees\Repositories\PositionRepository;
use App\Modules\Employees\Support\EmployeeData;
use PDOException;

final class EmployeeService implements EmployeeOnboarding
{
    private const PER_PAGE = 8;

    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly DepartmentRepository $departments,
        private readonly PositionRepository $positions
    ) {
    }

    /**
     * @param array{q?: string, department_id?: string|int|null, status?: string|null} $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     pages: int,
     *     from: int,
     *     to: int,
     *     filters: array{q: string, department_id: int|null, status: string|null}
     * }
     */
    public function search(array $filters, int $page): array
    {
        $filters = $this->normalizeFilters($filters);
        $result = $this->employees->paginate($filters, max(1, $page), self::PER_PAGE);
        $result['filters'] = $filters;

        return $result;
    }

    /**
     * @param array{q?: string, department_id?: string|int|null, status?: string|null} $filters
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     total: int,
     *     active: int,
     *     inactive: int,
     *     by_department: list<array<string, mixed>>,
     *     filters: array{q: string, department_id: int|null, status: string|null}
     * }
     */
    public function report(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $rows = $this->employees->report($filters);
        $active = count(array_filter(
            $rows,
            static fn (array $row): bool => (string) $row['status'] === 'active'
        ));

        return [
            'rows' => $rows,
            'total' => count($rows),
            'active' => $active,
            'inactive' => count($rows) - $active,
            'by_department' => $this->departments->summary(),
            'filters' => $filters,
        ];
    }

    /**
     * @return array{active: int, inactive: int, total: int}
     */
    public function stats(): array
    {
        return $this->employees->stats();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $employee = $this->employees->find($id);

        if ($employee === null) {
            throw new NotFoundException('El empleado solicitado no existe.');
        }

        return $employee;
    }

    /**
     * @return array{
     *     departments: list<array<string, mixed>>,
     *     positions: list<array<string, mixed>>
     * }
     */
    public function formOptions(?array $employee = null): array
    {
        return [
            'departments' => $this->departments->optionsForForm(
                $employee !== null ? (int) $employee['department_id'] : null
            ),
            'positions' => $this->positions->optionsForForm(
                $employee !== null && $employee['position_id'] !== null
                    ? (int) $employee['position_id']
                    : null
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filterDepartments(): array
    {
        return $this->departments->options();
    }

    /**
     * @return array<string, mixed>
     */
    public function create(array $input, ?int $userId): array
    {
        [$data, $errors] = $this->prepare($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $id = $this->employees->create($data + [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, array $input, ?int $userId): array
    {
        $current = $this->find($id);
        [$data, $errors] = $this->prepare($input, $current);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $this->employees->update($id, $data + ['updated_by' => $userId]);
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array{employee: array<string, mixed>, changed: bool}
     */
    public function setStatus(int $id, string $status, ?int $userId): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new BusinessRuleException('El estado solicitado no es válido.');
        }

        $employee = $this->find($id);

        if ((string) $employee['status'] === $status) {
            return ['employee' => $employee, 'changed' => false];
        }

        if ($status === 'active') {
            $department = $this->departments->find((int) $employee['department_id']);

            if ($department === null || (int) $department['is_active'] !== 1) {
                throw new BusinessRuleException(
                    'No se puede reactivar un empleado cuyo departamento está inactivo.'
                );
            }
        }

        $this->employees->setStatus($id, $status, $userId);

        return ['employee' => $this->find($id), 'changed' => true];
    }

    /**
     * @param array{q?: string, department_id?: string|int|null, status?: string|null} $filters
     * @return array{q: string, department_id: int|null, status: string|null}
     */
    private function normalizeFilters(array $filters): array
    {
        $q = is_scalar($filters['q'] ?? null) ? (string) $filters['q'] : '';
        $departmentId = is_scalar($filters['department_id'] ?? null)
            ? (int) $filters['department_id']
            : 0;
        $status = is_scalar($filters['status'] ?? null)
            ? (string) $filters['status']
            : null;

        return [
            'q' => mb_substr(trim($q), 0, 100),
            'department_id' => $departmentId > 0 ? $departmentId : null,
            'status' => in_array($status, ['active', 'inactive'], true) ? $status : null,
        ];
    }

    /**
     * @return array{
     *     0: array{
     *         document: string,
     *         first_name: string,
     *         last_name: string,
     *         email: string|null,
     *         phone: string|null,
     *         hire_date: string,
     *         department_id: int|string,
     *         position_id: int|string|null
     *     },
     *     1: array<string, string>
     * }
     */
    private function prepare(array $input, ?array $current = null): array
    {
        $data = [
            'document' => EmployeeData::document((string) ($input['document'] ?? '')),
            'first_name' => EmployeeData::name((string) ($input['first_name'] ?? '')),
            'last_name' => EmployeeData::name((string) ($input['last_name'] ?? '')),
            'email' => EmployeeData::email($input['email'] ?? null),
            'phone' => EmployeeData::phone($input['phone'] ?? null),
            'hire_date' => trim((string) ($input['hire_date'] ?? '')),
            'department_id' => trim((string) ($input['department_id'] ?? '')),
            'position_id' => trim((string) ($input['position_id'] ?? '')),
        ];

        $validator = new Validator($data, [
            'document' => 'documento',
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'hire_date' => 'fecha de ingreso',
            'department_id' => 'departamento',
            'position_id' => 'cargo',
        ]);

        $errors = $validator->validate([
            'document' => ['required', 'max:30'],
            'first_name' => ['required', 'max:80'],
            'last_name' => ['required', 'max:80'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'max:30', 'regex:/^[0-9+().\s-]{6,30}$/u'],
            'hire_date' => ['required', 'date'],
            'department_id' => ['required', 'integer'],
            'position_id' => ['nullable', 'integer'],
        ]);

        if ($errors !== []) {
            return [$data, $errors];
        }

        $departmentId = (int) $data['department_id'];
        $positionId = $data['position_id'] === '' ? null : (int) $data['position_id'];
        $data['department_id'] = $departmentId;
        $data['position_id'] = $positionId;

        $exceptId = $current !== null ? (int) $current['id'] : null;

        if ($this->employees->documentExists($data['document'], $exceptId)) {
            $errors['document'] = 'Ya existe un empleado con ese documento.';
        }

        if ($data['email'] !== null && $this->employees->emailExists($data['email'], $exceptId)) {
            $errors['email'] = 'Ya existe un empleado con ese correo electrónico.';
        }

        $department = $this->departments->find($departmentId);

        if ($department === null) {
            $errors['department_id'] = 'El departamento seleccionado no existe.';
        } elseif (
            (int) $department['is_active'] !== 1
            && ($current === null || $departmentId !== (int) $current['department_id'])
        ) {
            $errors['department_id'] = 'El departamento seleccionado está inactivo.';
        }

        if ($positionId !== null) {
            $position = $this->positions->find($positionId);

            if ($position === null) {
                $errors['position_id'] = 'El cargo seleccionado no existe.';
            } elseif ((int) $position['department_id'] !== $departmentId) {
                $errors['position_id'] = 'El cargo no pertenece al departamento seleccionado.';
            } elseif (
                (int) $position['is_active'] !== 1
                && ($current === null || $positionId !== (int) $current['position_id'])
            ) {
                $errors['position_id'] = 'El cargo seleccionado está inactivo.';
            }
        }

        return [$data, $errors];
    }

    private function saveError(PDOException $exception): ValidationException
    {
        Logger::error('No se pudo guardar el empleado.', [
            'type' => $exception::class,
            'sql_state' => (string) $exception->getCode(),
        ]);

        return new ValidationException(
            [],
            'No se pudo guardar el empleado. Comprueba que el documento y el correo no estén repetidos.'
        );
    }
}
