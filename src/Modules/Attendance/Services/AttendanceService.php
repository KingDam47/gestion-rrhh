<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Services;

use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Logger;
use App\Core\Validator;
use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\Attendance\Support\AttendanceData;
use App\Modules\Employees\Contracts\EmployeeLookup;
use PDOException;

final class AttendanceService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly AttendanceRepository $records,
        private readonly EmployeeLookup $employees
    ) {
    }

    /**
     * @param array{
     *     employee_id?: string|int|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     status?: string|null
     * } $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     pages: int,
     *     from: int,
     *     to: int,
     *     summary: array{total: int, present: int, late: int, absent: int},
     *     filters: array{employee_id: int|null, date_from: string|null, date_to: string|null, status: string|null}
     * }
     */
    public function search(array $filters, int $page): array
    {
        $filters = $this->normalizeFilters($filters);
        $result = $this->records->paginate($filters, max(1, $page), self::PER_PAGE);
        $result['items'] = $this->enrich($result['items']);
        $result['filters'] = $filters;

        return $result;
    }

    /**
     * Resumen agregado de un período para indicadores del panel.
     *
     * @return array{total: int, present: int, late: int, absent: int}
     */
    public function summary(string $dateFrom, string $dateTo): array
    {
        $from = AttendanceData::dateOrNull($dateFrom);
        $to = AttendanceData::dateOrNull($dateTo);

        if ($from === null || $to === null) {
            return ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return $this->records->summary([
            'employee_id' => null,
            'date_from' => $from,
            'date_to' => $to,
            'status' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $record = $this->records->find($id);

        if ($record === null) {
            throw new NotFoundException('El registro de asistencia solicitado no existe.');
        }

        return $this->enrich([$record])[0];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function formEmployees(?array $current = null): array
    {
        if ($current === null) {
            return $this->employees->activeEmployees();
        }

        $employee = $this->employees->find((int) $current['employee_id']);

        if ($employee === null) {
            return [];
        }

        return [$employee + [
            'full_name' => trim($employee['first_name'] . ' ' . $employee['last_name']),
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filterEmployees(): array
    {
        return $this->employees->directory();
    }

    /**
     * @return array<string, mixed>
     */
    public function create(array $input, ?int $userId): array
    {
        [$data, $errors] = $this->prepare($input, true);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $id = $this->records->create($data + [
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
        $input['employee_id'] = (string) $current['employee_id'];
        [$data, $errors] = $this->prepare($input, false, $current);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $this->records->update($id, [
                'work_date' => $data['work_date'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'status' => $data['status'],
                'notes' => $data['notes'],
                'updated_by' => $userId,
            ]);
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @param array{
     *     employee_id?: string|int|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     status?: string|null
     * } $filters
     * @return array{employee_id: int|null, date_from: string|null, date_to: string|null, status: string|null}
     */
    private function normalizeFilters(array $filters): array
    {
        $employeeId = is_scalar($filters['employee_id'] ?? null)
            ? (int) $filters['employee_id']
            : 0;
        $dateFrom = AttendanceData::dateOrNull(
            is_scalar($filters['date_from'] ?? null) ? (string) $filters['date_from'] : null
        );
        $dateTo = AttendanceData::dateOrNull(
            is_scalar($filters['date_to'] ?? null) ? (string) $filters['date_to'] : null
        );

        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $status = is_scalar($filters['status'] ?? null)
            ? (string) $filters['status']
            : null;

        return [
            'employee_id' => $employeeId > 0 ? $employeeId : null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => in_array($status, ['present', 'late', 'absent'], true) ? $status : null,
        ];
    }

    /**
     * @param list<array<string, mixed>> $records
     * @return list<array<string, mixed>>
     */
    private function enrich(array $records): array
    {
        if ($records === []) {
            return [];
        }

        $employees = $this->employees->findMany(array_map(
            static fn (array $record): int => (int) $record['employee_id'],
            $records
        ));

        foreach ($records as &$record) {
            $employee = $employees[(int) $record['employee_id']] ?? null;

            if ($employee === null) {
                $record['employee_name'] = 'Empleado no disponible';
                $record['employee_document'] = null;
                $record['employee_status'] = null;
                $record['department_name'] = null;
                continue;
            }

            $record['employee_name'] = trim(
                (string) $employee['last_name'] . ', ' . (string) $employee['first_name']
            );
            $record['employee_document'] = (string) $employee['document'];
            $record['employee_status'] = (string) $employee['status'];
            $record['department_name'] = (string) $employee['department_name'];
        }
        unset($record);

        return $records;
    }

    /**
     * @return array{
     *     0: array{
     *         employee_id: int,
     *         work_date: string,
     *         check_in: string|null,
     *         check_out: string|null,
     *         status: string,
     *         notes: string|null
     *     },
     *     1: array<string, string>
     * }
     */
    private function prepare(array $input, bool $creating, ?array $current = null): array
    {
        $data = [
            'employee_id' => trim((string) ($input['employee_id'] ?? '')),
            'work_date' => AttendanceData::date($input['work_date'] ?? null),
            'check_in' => AttendanceData::time($input['check_in'] ?? null),
            'check_out' => AttendanceData::time($input['check_out'] ?? null),
            'status' => trim((string) ($input['status'] ?? 'present')),
            'notes' => AttendanceData::notes($input['notes'] ?? null),
        ];

        $validator = new Validator($data, [
            'employee_id' => 'empleado',
            'work_date' => 'fecha de trabajo',
            'check_in' => 'hora de entrada',
            'check_out' => 'hora de salida',
            'status' => 'estado',
            'notes' => 'observación',
        ]);

        $errors = $validator->validate([
            'employee_id' => ['required', 'integer'],
            'work_date' => ['required', 'date'],
            'check_in' => ['nullable', 'time'],
            'check_out' => ['nullable', 'time'],
            'status' => ['required', 'in:present,late,absent'],
            'notes' => ['nullable', 'max:500'],
        ]);

        if ($errors !== []) {
            return [$data, $errors];
        }

        $data['employee_id'] = (int) $data['employee_id'];
        $employee = $this->employees->find($data['employee_id']);

        if ($employee === null) {
            $errors['employee_id'] = 'El empleado seleccionado no existe.';

            return [$data, $errors];
        }

        if ($creating && (string) $employee['status'] !== 'active') {
            $errors['employee_id'] = 'Solo se puede registrar asistencia para empleados activos.';

            return [$data, $errors];
        }

        if ($data['work_date'] > date('Y-m-d')) {
            $errors['work_date'] = 'No se puede registrar asistencia para una fecha futura.';
        }

        if ($data['status'] === 'absent') {
            if ($data['check_in'] !== null || $data['check_out'] !== null) {
                $errors['check_in'] = 'Una ausencia no debe registrar horas de entrada o salida.';
            }

            if ($data['notes'] === null) {
                $errors['notes'] = 'Describe el motivo de la ausencia.';
            }
        } else {
            if ($data['check_in'] === null) {
                $errors['check_in'] = 'La hora de entrada es obligatoria para un registro presente o con tardanza.';
            } elseif (
                $data['check_out'] !== null
                && !AttendanceData::timeIsAfterOrEqual($data['check_in'], $data['check_out'])
            ) {
                $errors['check_out'] = 'La hora de salida no puede ser anterior a la hora de entrada.';
            }
        }

        $exceptId = $current !== null ? (int) $current['id'] : null;

        if ($this->records->existsForEmployeeDate(
            $data['employee_id'],
            $data['work_date'],
            $exceptId
        )) {
            $errors['work_date'] = 'Ya existe un registro de asistencia para ese empleado en esa fecha.';
        }

        return [$data, $errors];
    }

    private function saveError(PDOException $exception): ValidationException
    {
        Logger::error('No se pudo guardar el registro de asistencia.', [
            'type' => $exception::class,
            'sql_state' => (string) $exception->getCode(),
        ]);

        return new ValidationException(
            [],
            'No se pudo guardar el registro. Comprueba que no exista otro registro para esa fecha.'
        );
    }
}
