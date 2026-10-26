<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Services;

use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Logger;
use App\Core\Validator;
use App\Modules\Attendance\Repositories\LeaveRequestRepository;
use App\Modules\Attendance\Support\AttendanceData;
use App\Modules\Auth\Contracts\UserLookup;
use App\Modules\Employees\Contracts\EmployeeLookup;
use PDOException;

final class LeaveRequestService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly LeaveRequestRepository $requests,
        private readonly EmployeeLookup $employees,
        private readonly UserLookup $users
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
     *     summary: array{total: int, pending: int, approved: int, rejected: int},
     *     filters: array{employee_id: int|null, date_from: string|null, date_to: string|null, status: string|null}
     * }
     */
    public function search(array $filters, int $page): array
    {
        $filters = $this->normalizeFilters($filters);
        $result = $this->requests->paginate($filters, max(1, $page), self::PER_PAGE);
        $result['items'] = $this->enrich($result['items']);
        $result['filters'] = $filters;

        return $result;
    }

    /**
     * Resumen general de solicitudes para indicadores del panel.
     *
     * @return array{total: int, pending: int, approved: int, rejected: int}
     */
    public function summary(): array
    {
        return $this->requests->summary([
            'employee_id' => null,
            'date_from' => null,
            'date_to' => null,
            'status' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $request = $this->requests->find($id);

        if ($request === null) {
            throw new NotFoundException('La solicitud de permiso solicitada no existe.');
        }

        return $this->enrich([$request])[0];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function formEmployees(): array
    {
        return $this->employees->activeEmployees();
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
        [$data, $errors] = $this->prepare($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $id = $this->requests->create($data + ['created_by' => $userId]);
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function review(int $id, string $status, ?string $notes, ?int $userId): array
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new BusinessRuleException('La decisión debe ser aprobar o rechazar la solicitud.');
        }

        $request = $this->find($id);

        if ((string) $request['status'] !== 'pending') {
            throw new BusinessRuleException('Solo se pueden revisar solicitudes pendientes.');
        }

        $notes = AttendanceData::notes($notes);

        if ($notes !== null && mb_strlen($notes, 'UTF-8') > 500) {
            throw new ValidationException([
                'review_notes' => 'La observación de revisión no debe superar los 500 caracteres.',
            ]);
        }

        if (!$this->requests->review($id, $status, (int) $userId, $notes)) {
            throw new BusinessRuleException('La solicitud ya fue revisada por otro usuario.');
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
            'status' => in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : null,
        ];
    }

    /**
     * @param list<array<string, mixed>> $requests
     * @return list<array<string, mixed>>
     */
    private function enrich(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $employees = $this->employees->findMany(array_map(
            static fn (array $request): int => (int) $request['employee_id'],
            $requests
        ));
        $reviewerIds = [];
        foreach ($requests as $request) {
            if ($request['reviewed_by'] !== null) {
                $reviewerIds[] = (int) $request['reviewed_by'];
            }
        }
        $reviewerNames = $this->users->names($reviewerIds);

        foreach ($requests as &$request) {
            $employee = $employees[(int) $request['employee_id']] ?? null;
            $request['employee_name'] = $employee === null
                ? 'Empleado no disponible'
                : trim((string) $employee['last_name'] . ', ' . (string) $employee['first_name']);
            $request['employee_document'] = $employee === null ? null : (string) $employee['document'];
            $request['employee_status'] = $employee === null ? null : (string) $employee['status'];
            $request['department_name'] = $employee === null ? null : (string) $employee['department_name'];
            $request['days'] = AttendanceData::daysBetween(
                $request['start_date'] !== null ? (string) $request['start_date'] : null,
                $request['end_date'] !== null ? (string) $request['end_date'] : null
            );
            $request['reviewer_name'] = $request['reviewed_by'] === null
                ? null
                : ($reviewerNames[(int) $request['reviewed_by']] ?? 'Usuario no disponible');
        }
        unset($request);

        return $requests;
    }

    /**
     * @return array{
     *     0: array{
     *         employee_id: int,
     *         leave_type: string,
     *         start_date: string,
     *         end_date: string,
     *         reason: string
     *     },
     *     1: array<string, string>
     * }
     */
    private function prepare(array $input): array
    {
        $data = [
            'employee_id' => trim((string) ($input['employee_id'] ?? '')),
            'leave_type' => trim((string) ($input['leave_type'] ?? '')),
            'start_date' => AttendanceData::date($input['start_date'] ?? null),
            'end_date' => AttendanceData::date($input['end_date'] ?? null),
            'reason' => trim((string) ($input['reason'] ?? '')),
        ];

        $validator = new Validator($data, [
            'employee_id' => 'empleado',
            'leave_type' => 'tipo de permiso',
            'start_date' => 'fecha inicial',
            'end_date' => 'fecha final',
            'reason' => 'motivo',
        ]);

        $errors = $validator->validate([
            'employee_id' => ['required', 'integer'],
            'leave_type' => ['required', 'in:vacation,personal,sick,other'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'reason' => ['required', 'max:500'],
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

        if ((string) $employee['status'] !== 'active') {
            $errors['employee_id'] = 'Solo se pueden crear solicitudes para empleados activos.';

            return [$data, $errors];
        }

        if ($data['end_date'] < $data['start_date']) {
            $errors['end_date'] = 'La fecha final no puede ser anterior a la fecha inicial.';

            return [$data, $errors];
        }

        if ($this->requests->overlapExists(
            $data['employee_id'],
            $data['start_date'],
            $data['end_date']
        )) {
            $errors['start_date'] = 'Ya existe una solicitud pendiente o aprobada que se superpone con ese período.';
        }

        return [$data, $errors];
    }

    private function saveError(PDOException $exception): ValidationException
    {
        Logger::error('No se pudo guardar la solicitud de permiso.', [
            'type' => $exception::class,
            'sql_state' => (string) $exception->getCode(),
        ]);

        return new ValidationException(
            [],
            'No se pudo guardar la solicitud de permiso. Inténtalo de nuevo.'
        );
    }
}
