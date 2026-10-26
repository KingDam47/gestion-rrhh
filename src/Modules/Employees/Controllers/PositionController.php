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
use App\Modules\Employees\Services\PositionService;
use App\Shared\Support\AuditLogger;

final class PositionController
{
    public function __construct(
        private readonly PositionService $positions,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->renderIndex($this->departmentFilter($request));
    }

    public function store(Request $request): Response
    {
        $input = $this->input($request);

        try {
            $this->positions->create($input);
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderIndex(
                ((int) $input['department_id']) > 0 ? (int) $input['department_id'] : null,
                $exception->errors(),
                $exception->getMessage(),
                422
            );
        }

        Flash::success('Cargo creado correctamente.');

        return Response::redirect('/positions');
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->renderForm($this->positions->find((int) $id));
    }

    public function update(Request $request, string $id): Response
    {
        $position = $this->positions->find((int) $id);
        $input = $this->input($request);

        try {
            $this->positions->update((int) $id, $input);
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(
                $position,
                $exception->errors(),
                $exception->getMessage(),
                422
            );
        }

        Flash::success('Cargo actualizado correctamente.');

        return Response::redirect('/positions');
    }

    public function status(Request $request, string $id): Response
    {
        $status = $this->value($request, 'status');

        if (!in_array($status, ['active', 'inactive'], true)) {
            Flash::error('El estado solicitado no es válido.');

            return Response::redirect('/positions');
        }

        try {
            $result = $this->positions->setActive((int) $id, $status === 'active');
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/positions');
        }

        if (!$result['changed']) {
            Flash::info('El cargo ya se encuentra en ese estado.');

            return Response::redirect('/positions');
        }

        $this->audit->record(
            Auth::id(),
            $status === 'active' ? 'position.activated' : 'position.deactivated',
            'position',
            (int) $id,
            ['status' => $status],
            $request->ip()
        );

        Flash::success(
            $status === 'active'
                ? 'Cargo reactivado correctamente.'
                : 'Cargo desactivado. Los empleados asociados conservan su historial.'
        );

        return Response::redirect('/positions');
    }

    private function renderIndex(
        ?int $departmentId = null,
        array $errors = [],
        ?string $generalError = null,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('employees::positions.index', [
            'title' => 'Cargos',
            'positions' => $this->positions->all($departmentId),
            'departments' => $this->positions->filterDepartments(),
            'departmentFilter' => $departmentId,
            'errors' => $errors,
            'generalError' => $generalError,
            'formDepartments' => $this->positions->departmentOptions(),
        ]), $status);
    }

    private function renderForm(
        array $position,
        array $errors = [],
        ?string $generalError = null,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('employees::positions.form', [
            'title' => 'Editar cargo',
            'position' => $position,
            'departments' => $this->positions->departmentOptions((int) $position['department_id']),
            'errors' => $errors,
            'generalError' => $generalError,
            'formAction' => '/positions/' . $position['id'],
        ]), $status);
    }

    private function departmentFilter(Request $request): ?int
    {
        $value = $request->query('department_id', '');
        $value = $value === null ? 0 : (int) $value;

        return $value > 0 ? $value : null;
    }

    /**
     * @return array{department_id: string, name: string, description: string}
     */
    private function input(Request $request): array
    {
        return [
            'department_id' => $this->value($request, 'department_id'),
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
