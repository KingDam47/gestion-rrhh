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
use App\Modules\Employees\Services\DepartmentService;
use App\Shared\Support\AuditLogger;

final class DepartmentController
{
    public function __construct(
        private readonly DepartmentService $departments,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->renderIndex();
    }

    public function store(Request $request): Response
    {
        $input = $this->input($request);

        try {
            $this->departments->create($input);
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderIndex($exception->errors(), $exception->getMessage(), 422);
        }

        Flash::success('Departamento creado correctamente.');

        return Response::redirect('/departments');
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->renderForm($this->departments->find((int) $id));
    }

    public function update(Request $request, string $id): Response
    {
        $department = $this->departments->find((int) $id);
        $input = $this->input($request);

        try {
            $this->departments->update((int) $id, $input);
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(
                $department,
                $exception->errors(),
                $exception->getMessage(),
                422
            );
        }

        Flash::success('Departamento actualizado correctamente.');

        return Response::redirect('/departments');
    }

    public function status(Request $request, string $id): Response
    {
        $status = $this->value($request, 'status');

        if (!in_array($status, ['active', 'inactive'], true)) {
            Flash::error('El estado solicitado no es válido.');

            return Response::redirect('/departments');
        }

        try {
            $result = $this->departments->setActive((int) $id, $status === 'active');
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/departments');
        }

        if (!$result['changed']) {
            Flash::info('El departamento ya se encuentra en ese estado.');

            return Response::redirect('/departments');
        }

        $department = $result['department'];

        $this->audit->record(
            Auth::id(),
            $status === 'active' ? 'department.activated' : 'department.deactivated',
            'department',
            (int) $id,
            ['status' => $status],
            $request->ip()
        );

        if ($status === 'active') {
            Flash::success('Departamento reactivado correctamente.');
        } else {
            Flash::success(sprintf(
                'Departamento desactivado. Conserva %d empleados y %d cargos asociados.',
                (int) $department['employee_count'],
                (int) $department['position_count']
            ));
        }

        return Response::redirect('/departments');
    }

    private function renderIndex(
        array $errors = [],
        ?string $generalError = null,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('employees::departments.index', [
            'title' => 'Departamentos',
            'departments' => $this->departments->all(),
            'errors' => $errors,
            'generalError' => $generalError,
        ]), $status);
    }

    private function renderForm(
        array $department,
        array $errors = [],
        ?string $generalError = null,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('employees::departments.form', [
            'title' => 'Editar departamento',
            'department' => $department,
            'errors' => $errors,
            'generalError' => $generalError,
            'formAction' => '/departments/' . $department['id'],
        ]), $status);
    }

    /**
     * @return array{name: string, description: string}
     */
    private function input(Request $request): array
    {
        return [
            'name' => $this->value($request, 'name'),
            'description' => $this->value($request, 'description'),
        ];
    }

    private function value(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_scalar($value) ? (string) $value : '';
    }
}
