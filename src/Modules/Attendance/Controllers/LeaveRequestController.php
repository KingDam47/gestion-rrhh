<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Controllers;

use App\Core\Auth;
use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\ValidationException;
use App\Core\Flash;
use App\Core\OldInput;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Attendance\Services\LeaveRequestService;
use App\Shared\Support\AuditLogger;

final class LeaveRequestController
{
    public function __construct(
        private readonly LeaveRequestService $leaves,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        $result = $this->leaves->search($this->filters($request), $this->page($request));

        return Response::html($this->view->render('attendance::leave/index', [
            'title' => 'Permisos',
            'pagination' => $result,
            'filters' => $result['filters'],
            'summary' => $result['summary'],
            'employees' => $this->leaves->filterEmployees(),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->renderForm([], null);
    }

    public function store(Request $request): Response
    {
        $input = $this->input($request);

        try {
            $leave = $this->leaves->create($input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm($exception->errors(), $exception->getMessage(), 422);
        }

        Flash::success('Solicitud de permiso creada en estado pendiente.');

        return Response::redirect('/leave-requests/' . $leave['id']);
    }

    public function show(Request $request, string $id): Response
    {
        return $this->renderShow((int) $id);
    }

    public function review(Request $request, string $id): Response
    {
        $status = $this->value($request, 'status');
        $notes = $this->value($request, 'review_notes');

        try {
            $leave = $this->leaves->review((int) $id, $status, $notes, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put(['status' => $status, 'review_notes' => $notes]);

            return $this->renderShow((int) $id, $exception->errors(), $exception->getMessage(), 422);
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/leave-requests/' . $id);
        }

        $this->audit->record(
            Auth::id(),
            $status === 'approved' ? 'leave.approved' : 'leave.rejected',
            'leave_request',
            (int) $id,
            ['status' => (string) $leave['status']],
            $request->ip()
        );

        Flash::success(
            $status === 'approved'
                ? 'Solicitud aprobada correctamente.'
                : 'Solicitud rechazada. El empleado conserva el historial.'
        );

        return Response::redirect('/leave-requests/' . $id);
    }

    private function renderForm(array $errors, ?string $generalError, int $status = 200): Response
    {
        return Response::html($this->view->render('attendance::leave/form', [
            'title' => 'Nueva solicitud de permiso',
            'employees' => $this->leaves->formEmployees(),
            'errors' => $errors,
            'generalError' => $generalError,
        ]), $status);
    }

    private function renderShow(
        int $id,
        array $errors = [],
        ?string $generalError = null,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('attendance::leave/show', [
            'title' => 'Solicitud de permiso',
            'leave' => $this->leaves->find($id),
            'errors' => $errors,
            'generalError' => $generalError,
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
            'leave_type' => $this->value($request, 'leave_type'),
            'start_date' => $this->value($request, 'start_date'),
            'end_date' => $this->value($request, 'end_date'),
            'reason' => $this->value($request, 'reason'),
        ];
    }

    private function value(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_scalar($value) ? (string) $value : '';
    }
}
