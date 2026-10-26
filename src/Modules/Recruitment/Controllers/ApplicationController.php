<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Controllers;

use App\Core\Auth;
use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\ValidationException;
use App\Core\Flash;
use App\Core\OldInput;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Recruitment\Services\ApplicationService;
use App\Modules\Recruitment\Support\ApplicationStatus;
use App\Shared\Support\AuditLogger;

final class ApplicationController
{
    public function __construct(
        private readonly ApplicationService $applications,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        $result = $this->applications->search($this->filters($request), $this->page($request));

        return Response::html($this->view->render('recruitment::applications.index', [
            'title' => 'Postulaciones',
            'pagination' => $result,
            'filters' => $result['filters'],
            'summary' => $result['summary'],
            'vacancies' => $this->applications->filterVacancies(),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->renderForm(null, [], null, 200, [
            'candidate_id' => (string) $request->query('candidate_id', ''),
            'vacancy_id' => (string) $request->query('vacancy_id', ''),
        ]);
    }

    public function store(Request $request): Response
    {
        $input = $this->input($request);

        try {
            $application = $this->applications->create($input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(null, $exception->errors(), $exception->getMessage(), 422);
        }

        $this->audit->record(
            Auth::id(),
            'application.created',
            'application',
            (int) $application['id'],
            ['status' => ApplicationStatus::RECEIVED],
            $request->ip()
        );

        Flash::success('Postulación registrada correctamente.');

        return Response::redirect('/applications/' . $application['id']);
    }

    public function show(Request $request, string $id): Response
    {
        $application = $this->applications->find((int) $id);

        return Response::html($this->view->render('recruitment::applications.show', [
            'title' => 'Postulación de ' . $application['candidate_name_natural'],
            'application' => $application,
            'transitions' => ApplicationStatus::next((string) $application['status']),
            'isFinal' => ApplicationStatus::isFinal((string) $application['status']),
        ]));
    }

    public function status(Request $request, string $id): Response
    {
        $status = $this->value($request, 'status');
        $input = [
            'status' => $status,
            'notes' => $this->value($request, 'notes'),
        ];

        try {
            $result = $this->applications->transition((int) $id, $input, Auth::id());
        } catch (ValidationException $exception) {
            Flash::error($exception->errors()['status'] ?? $exception->getMessage());

            return Response::redirect('/applications/' . $id);
        }

        if (!$result['changed']) {
            $message = $result['reason'] === 'final'
                ? 'La postulación está en estado final y no admite más cambios.'
                : 'La postulación ya se encuentra en ese estado.';

            Flash::info($message);

            return Response::redirect('/applications/' . $id);
        }

        $this->audit->record(
            Auth::id(),
            'application.status_changed',
            'application',
            (int) $id,
            ['status' => $status],
            $request->ip()
        );

        Flash::success('Estado de la postulación actualizado.');

        return Response::redirect('/applications/' . $id);
    }

    public function hireForm(Request $request, string $id): Response
    {
        try {
            $application = $this->applications->hireContext((int) $id);
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/applications/' . $id);
        }

        return $this->renderHireForm($application, [], null);
    }

    public function hire(Request $request, string $id): Response
    {
        $input = $this->hireInput($request);

        try {
            $application = $this->applications->hireContext((int) $id);
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/applications/' . $id);
        }

        try {
            $result = $this->applications->hire((int) $id, $input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderHireForm($application, $exception->errors(), $exception->getMessage(), 422);
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/applications/' . $id);
        }

        $this->audit->record(
            Auth::id(),
            'application.hired',
            'application',
            (int) $id,
            ['status' => ApplicationStatus::SELECTED],
            $request->ip()
        );

        Flash::success('Empleado registrado y asociado a la postulación.');

        return Response::redirect('/employees/' . $result['employee']['id']);
    }

    private function renderForm(
        ?array $application,
        array $errors,
        ?string $generalError,
        int $status = 200,
        array $defaults = []
    ): Response {
        $options = $this->applications->formOptions();

        return Response::html($this->view->render('recruitment::applications.form', [
            'title' => 'Nueva postulación',
            'application' => $application,
            'candidates' => $options['candidates'],
            'vacancies' => $options['vacancies'],
            'errors' => $errors,
            'generalError' => $generalError,
            'formAction' => '/applications',
            'isEdit' => false,
            'defaults' => $defaults,
        ]), $status);
    }

    private function renderHireForm(
        array $application,
        array $errors,
        ?string $generalError,
        int $status = 200
    ): Response {
        $options = $this->applications->hireOptions();

        return Response::html($this->view->render('recruitment::applications.hire', [
            'title' => 'Alta guiada de empleado',
            'application' => $application,
            'departments' => $options['departments'],
            'positions' => $options['positions'],
            'errors' => $errors,
            'generalError' => $generalError,
            'formAction' => '/applications/' . $application['id'] . '/hire',
        ]), $status);
    }

    /**
     * @return array{q: string, vacancy_id: string, status: string}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
            'vacancy_id' => (string) $request->query('vacancy_id', ''),
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
            'candidate_id' => $this->value($request, 'candidate_id'),
            'vacancy_id' => $this->value($request, 'vacancy_id'),
            'notes' => $this->value($request, 'notes'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function hireInput(Request $request): array
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
