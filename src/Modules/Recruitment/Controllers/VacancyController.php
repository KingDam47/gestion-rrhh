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
use App\Modules\Recruitment\Services\VacancyService;
use App\Shared\Support\AuditLogger;

final class VacancyController
{
    public function __construct(
        private readonly VacancyService $vacancies,
        private readonly ApplicationService $applications,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        $result = $this->vacancies->search($this->filters($request), $this->page($request));

        return Response::html($this->view->render('recruitment::vacancies.index', [
            'title' => 'Vacantes',
            'pagination' => $result,
            'filters' => $result['filters'],
            'summary' => $result['summary'],
            'departments' => $this->vacancies->filterDepartments(),
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
            $vacancy = $this->vacancies->create($input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(null, $exception->errors(), $exception->getMessage(), 422);
        }

        Flash::success('Vacante creada correctamente.');

        return Response::redirect('/vacancies/' . $vacancy['id']);
    }

    public function show(Request $request, string $id): Response
    {
        $vacancy = $this->vacancies->find((int) $id);

        return Response::html($this->view->render('recruitment::vacancies.show', [
            'title' => $vacancy['title'],
            'vacancy' => $vacancy,
            'applications' => $this->applications->forVacancy((int) $id),
        ]));
    }

    public function edit(Request $request, string $id): Response
    {
        $vacancy = $this->vacancies->find((int) $id);

        if ((string) $vacancy['status'] === 'closed') {
            Flash::error('Una vacante cerrada no se puede editar.');

            return Response::redirect('/vacancies/' . $id);
        }

        return $this->renderForm($vacancy, [], null);
    }

    public function update(Request $request, string $id): Response
    {
        $vacancy = $this->vacancies->find((int) $id);
        $input = $this->input($request);

        try {
            $this->vacancies->update((int) $id, $input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(
                $vacancy,
                $exception->errors(),
                $exception->getMessage(),
                422
            );
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/vacancies/' . $id);
        }

        Flash::success('Vacante actualizada correctamente.');

        return Response::redirect('/vacancies/' . $id);
    }

    public function close(Request $request, string $id): Response
    {
        $result = $this->vacancies->close((int) $id, Auth::id());

        if (!$result['changed']) {
            Flash::info('La vacante ya está cerrada.');

            return Response::redirect('/vacancies/' . $id);
        }

        $this->audit->record(
            Auth::id(),
            'vacancy.closed',
            'vacancy',
            (int) $id,
            ['status' => 'closed'],
            $request->ip()
        );

        Flash::success('Vacante cerrada. Ya no acepta nuevas postulaciones.');

        return Response::redirect('/vacancies/' . $id);
    }

    private function renderForm(
        ?array $vacancy,
        array $errors,
        ?string $generalError,
        int $status = 200
    ): Response {
        $options = $this->vacancies->formOptions();

        return Response::html($this->view->render('recruitment::vacancies.form', [
            'title' => $vacancy === null ? 'Nueva vacante' : 'Editar vacante',
            'vacancy' => $vacancy,
            'departments' => $options['departments'],
            'positions' => $options['positions'],
            'errors' => $errors,
            'generalError' => $generalError,
            'formAction' => $vacancy === null ? '/vacancies' : '/vacancies/' . $vacancy['id'],
            'isEdit' => $vacancy !== null,
        ]), $status);
    }

    /**
     * @return array{q: string, status: string, department_id: string}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
            'status' => (string) $request->query('status', ''),
            'department_id' => (string) $request->query('department_id', ''),
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
            'title' => $this->value($request, 'title'),
            'description' => $this->value($request, 'description'),
            'department_id' => $this->value($request, 'department_id'),
            'position_id' => $this->value($request, 'position_id'),
            'opening_date' => $this->value($request, 'opening_date'),
            'closing_date' => $this->value($request, 'closing_date'),
        ];
    }

    private function value(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_scalar($value) ? (string) $value : '';
    }
}
