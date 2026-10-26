<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Controllers;

use App\Core\Auth;
use App\Core\Exception\ValidationException;
use App\Core\Flash;
use App\Core\OldInput;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Recruitment\Services\ApplicationService;
use App\Modules\Recruitment\Services\CandidateService;

final class CandidateController
{
    public function __construct(
        private readonly CandidateService $candidates,
        private readonly ApplicationService $applications,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        $result = $this->candidates->search($this->filters($request), $this->page($request));

        return Response::html($this->view->render('recruitment::candidates.index', [
            'title' => 'Candidatos',
            'pagination' => $result,
            'filters' => $result['filters'],
            'summary' => $result['summary'],
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
            $candidate = $this->candidates->create($input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(null, $exception->errors(), $exception->getMessage(), 422);
        }

        Flash::success('Candidato registrado correctamente.');

        return Response::redirect('/candidates/' . $candidate['id']);
    }

    public function show(Request $request, string $id): Response
    {
        $candidate = $this->candidates->find((int) $id);

        return Response::html($this->view->render('recruitment::candidates.show', [
            'title' => $candidate['first_name'] . ' ' . $candidate['last_name'],
            'candidate' => $candidate,
            'applications' => $this->applications->forCandidate((int) $id),
        ]));
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->renderForm($this->candidates->find((int) $id), [], null);
    }

    public function update(Request $request, string $id): Response
    {
        $candidate = $this->candidates->find((int) $id);
        $input = $this->input($request);

        try {
            $this->candidates->update((int) $id, $input, Auth::id());
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(
                $candidate,
                $exception->errors(),
                $exception->getMessage(),
                422
            );
        }

        Flash::success('Candidato actualizado correctamente.');

        return Response::redirect('/candidates/' . $id);
    }

    private function renderForm(
        ?array $candidate,
        array $errors,
        ?string $generalError,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('recruitment::candidates.form', [
            'title' => $candidate === null ? 'Nuevo candidato' : 'Editar candidato',
            'candidate' => $candidate,
            'errors' => $errors,
            'generalError' => $generalError,
            'formAction' => $candidate === null ? '/candidates' : '/candidates/' . $candidate['id'],
            'isEdit' => $candidate !== null,
        ]), $status);
    }

    /**
     * @return array{q: string}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
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
            'first_name' => $this->value($request, 'first_name'),
            'last_name' => $this->value($request, 'last_name'),
            'email' => $this->value($request, 'email'),
            'phone' => $this->value($request, 'phone'),
            'notes' => $this->value($request, 'notes'),
        ];
    }

    private function value(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_scalar($value) ? (string) $value : '';
    }
}
