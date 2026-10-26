<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Controllers;

use App\Core\Auth;
use App\Core\Exception\ValidationException;
use App\Core\Flash;
use App\Core\OldInput;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Attendance\Services\AttendanceService;
use App\Shared\Support\AuditLogger;

final class AttendanceController
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        $result = $this->attendance->search($this->filters($request), $this->page($request));

        return Response::html($this->view->render('attendance::index', [
            'title' => 'Asistencia',
            'pagination' => $result,
            'filters' => $result['filters'],
            'summary' => $result['summary'],
            'employees' => $this->attendance->filterEmployees(),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->renderForm(null, [], null);
    }

    public function store(Request $request): Response
    {
        $input = $this->input($request);

        try {
            $record = $this->attendance->create($input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(null, $exception->errors(), $exception->getMessage(), 422);
        }

        $this->audit->record(
            Auth::id(),
            'attendance.created',
            'attendance_record',
            (int) $record['id'],
            ['status' => (string) $record['status']],
            $request->ip()
        );

        Flash::success('Registro de asistencia guardado correctamente.');

        return Response::redirect('/attendance');
    }

    public function edit(Request $request, string $id): Response
    {
        $record = $this->attendance->find((int) $id);

        return $this->renderForm($record, [], null);
    }

    public function update(Request $request, string $id): Response
    {
        $record = $this->attendance->find((int) $id);
        $input = $this->input($request);

        try {
            $updated = $this->attendance->update((int) $id, $input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm($record, $exception->errors(), $exception->getMessage(), 422);
        }

        $this->audit->record(
            Auth::id(),
            'attendance.updated',
            'attendance_record',
            (int) $id,
            ['status' => (string) $updated['status']],
            $request->ip()
        );

        Flash::success('Registro de asistencia actualizado correctamente.');

        return Response::redirect('/attendance');
    }

    private function renderForm(
        ?array $record,
        array $errors,
        ?string $generalError,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('attendance::form', [
            'title' => $record === null ? 'Nuevo registro de asistencia' : 'Editar asistencia',
            'record' => $record,
            'employees' => $this->attendance->formEmployees($record),
            'errors' => $errors,
            'generalError' => $generalError,
            'formAction' => $record === null ? '/attendance' : '/attendance/' . $record['id'],
            'isEdit' => $record !== null,
        ]), $status);
    }

    /**
     * @return array{employee_id: string, date_from: string, date_to: string, status: string}
     */
    private function filters(Request $request): array
    {
        return [
            'employee_id' => (string) $request->query('employee_id', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'status' => (string) $request->query('status', ''),
        ];
    }

    private function page(Request $request): int
    {
        return max(1, (int) $request->query('page', '1'));
    }

    /**
     * @return array<string, string>
     */
    private function input(Request $request): array
    {
        return [
            'employee_id' => $this->value($request, 'employee_id'),
            'work_date' => $this->value($request, 'work_date'),
            'check_in' => $this->value($request, 'check_in'),
            'check_out' => $this->value($request, 'check_out'),
            'status' => $this->value($request, 'status'),
            'notes' => $this->value($request, 'notes'),
        ];
    }

    private function value(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_scalar($value) ? (string) $value : '';
    }
}
