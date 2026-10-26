<?php

declare(strict_types=1);

namespace App\Modules\Employees\Controllers;

use App\Core\Auth;
use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\ValidationException;
use App\Core\Flash;
use App\Core\OldInput;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Employees\Services\EmployeeService;
use App\Shared\Support\AuditLogger;

final class EmployeeController
{
    public function __construct(
        private readonly EmployeeService $employees,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->renderIndex($this->filters($request), $this->page($request));
    }

    public function create(Request $request): Response
    {
        return $this->renderForm(null, [], null);
    }

    public function store(Request $request): Response
    {
        $input = $this->input($request);

        try {
            $employee = $this->employees->create($input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(null, $exception->errors(), $exception->getMessage(), 422);
        }

        Flash::success('Empleado registrado correctamente.');

        return Response::redirect('/employees/' . $employee['id']);
    }

    public function show(Request $request, string $id): Response
    {
        $employee = $this->employees->find((int) $id);

        return Response::html($this->view->render('employees::show', [
            'title' => $employee['first_name'] . ' ' . $employee['last_name'],
            'employee' => $employee,
        ]));
    }

    public function edit(Request $request, string $id): Response
    {
        $employee = $this->employees->find((int) $id);

        return $this->renderForm($employee, [], null);
    }

    public function update(Request $request, string $id): Response
    {
        $employee = $this->employees->find((int) $id);
        $input = $this->input($request);

        try {
            $this->employees->update((int) $id, $input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm($employee, $exception->errors(), $exception->getMessage(), 422);
        }

        Flash::success('Empleado actualizado correctamente.');

        return Response::redirect('/employees/' . $id);
    }

    public function status(Request $request, string $id): Response
    {
        $status = $this->value($request, 'status');

        try {
            $result = $this->employees->setStatus((int) $id, $status, Auth::id());
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/employees/' . $id);
        }

        if (!$result['changed']) {
            Flash::info('El empleado ya se encuentra en ese estado.');

            return Response::redirect('/employees/' . $id);
        }

        $this->audit->record(
            Auth::id(),
            $status === 'active' ? 'employee.reactivated' : 'employee.deactivated',
            'employee',
            (int) $id,
            ['status' => $status],
            $request->ip()
        );

        Flash::success(
            $status === 'active'
                ? 'Empleado reactivado correctamente.'
                : 'Empleado desactivado. Conserva su historial y puede reactivarse.'
        );

        return Response::redirect('/employees/' . $id);
    }

    public function report(Request $request): Response
    {
        return Response::html($this->view->render('employees::report', [
            'title' => 'Reporte de personal',
            'report' => $this->employees->report($this->filters($request)),
            'departments' => $this->employees->filterDepartments(),
        ]));
    }

    private function renderIndex(array $filters, int $page): Response
    {
        $result = $this->employees->search($filters, $page);

        return Response::html($this->view->render('employees::index', [
            'title' => 'Empleados',
            'pagination' => $result,
            'filters' => $result['filters'],
            'departments' => $this->employees->filterDepartments(),
            'stats' => $this->employees->stats(),
        ]));
    }

    private function renderForm(
        ?array $employee,
        array $errors,
        ?string $generalError,
        int $status = 200
    ): Response {
        $options = $this->employees->formOptions($employee);

        return Response::html($this->view->render('employees::form', [
            'title' => $employee === null ? 'Nuevo empleado' : 'Editar empleado',
            'employee' => $employee,
            'departments' => $options['departments'],
            'positions' => $options['positions'],
            'errors' => $errors,
            'generalError' => $generalError,
            'formAction' => $employee === null ? '/employees' : '/employees/' . $employee['id'],
            'isEdit' => $employee !== null,
        ]), $status);
    }

    /**
     * @return array{q: string, department_id: string, status: string}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
            'department_id' => (string) $request->query('department_id', ''),
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
            'document' => $this->value($request, 'document'),
            'first_name' => $this->value($request, 'first_name'),
            'last_name' => $this->value($request, 'last_name'),
            'email' => $this->value($request, 'email'),
            'phone' => $this->value($request, 'phone'),
            'hire_date' => $this->value($request, 'hire_date'),
            'department_id' => $this->value($request, 'department_id'),
            'position_id' => $this->value($request, 'position_id'),
        ];
    }

    private function value(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_scalar($value) ? (string) $value : '';
    }
}
