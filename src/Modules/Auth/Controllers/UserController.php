<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Auth;
use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\ValidationException;
use App\Core\Flash;
use App\Core\OldInput;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Auth\Services\UserService;
use App\Shared\Support\AuditLogger;

final class UserController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->query('q', ''),
            'role' => $request->query('role'),
            'status' => $request->query('status'),
        ];

        return $this->renderIndex(
            $this->users->search($filters, (int) ($request->query('page', '1') ?? 1))
        );
    }

    public function create(Request $request): Response
    {
        return $this->renderForm(null);
    }

    public function store(Request $request): Response
    {
        $input = $this->input($request);

        try {
            $user = $this->users->create($input);
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm(null, $exception->errors(), $exception->getMessage(), 422);
        }

        $this->audit->record(
            Auth::id(),
            'user.created',
            'user',
            (int) $user['id'],
            ['role' => (string) $user['role']],
            $request->ip()
        );

        Flash::success('Usuario creado correctamente.');

        return Response::redirect('/users');
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->renderForm($this->users->find((int) $id));
    }

    public function update(Request $request, string $id): Response
    {
        $user = $this->users->find((int) $id);
        $input = $this->input($request);

        try {
            $updated = $this->users->update((int) $id, $input);
        } catch (ValidationException $exception) {
            OldInput::put($input);

            return $this->renderForm($user, $exception->errors(), $exception->getMessage(), 422);
        }

        $this->audit->record(
            Auth::id(),
            'user.updated',
            'user',
            (int) $id,
            ['role' => (string) $updated['role']],
            $request->ip()
        );

        Flash::success('Usuario actualizado correctamente.');

        return Response::redirect('/users');
    }

    public function status(Request $request, string $id): Response
    {
        $status = $this->value($request, 'status');

        if (!in_array($status, ['active', 'inactive'], true)) {
            Flash::error('El estado solicitado no es válido.');

            return Response::redirect('/users');
        }

        $active = $status === 'active';

        try {
            $result = $this->users->setActive((int) $id, $active, (int) Auth::id());
        } catch (BusinessRuleException $exception) {
            Flash::error($exception->getMessage());

            return Response::redirect('/users');
        }

        if (!$result['changed']) {
            Flash::info('La cuenta ya se encuentra en ese estado.');

            return Response::redirect('/users');
        }

        $user = $result['user'];

        $this->audit->record(
            Auth::id(),
            $active ? 'user.activated' : 'user.deactivated',
            'user',
            (int) $id,
            ['status' => $status, 'role' => (string) $user['role']],
            $request->ip()
        );

        Flash::success($active ? 'Usuario reactivado correctamente.' : 'Usuario desactivado correctamente.');

        return Response::redirect('/users');
    }

    public function passwordForm(Request $request, string $id): Response
    {
        return $this->renderPassword($this->users->find((int) $id));
    }

    public function resetPassword(Request $request, string $id): Response
    {
        $user = $this->users->find((int) $id);
        $input = [
            'password' => $this->value($request, 'password'),
            'password_confirmation' => $this->value($request, 'password_confirmation'),
        ];

        try {
            $this->users->resetPassword((int) $id, $input);
        } catch (ValidationException $exception) {
            return $this->renderPassword($user, $exception->errors(), $exception->getMessage(), 422);
        }

        $this->audit->record(
            Auth::id(),
            'user.password_reset',
            'user',
            (int) $id,
            [],
            $request->ip()
        );

        Flash::success('Contraseña restablecida correctamente.');

        return Response::redirect('/users');
    }

    /**
     * @param array<string, mixed> $result
     */
    private function renderIndex(
        array $result,
        array $errors = [],
        ?string $generalError = null,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('auth::users.index', [
            'title' => 'Usuarios',
            'pagination' => $result,
            'filters' => $result['filters'],
            'stats' => $result['stats'],
            'errors' => $errors,
            'generalError' => $generalError,
        ]), $status);
    }

    /**
     * @param array<string, mixed>|null $user
     */
    private function renderForm(
        ?array $user,
        array $errors = [],
        ?string $generalError = null,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('auth::users.form', [
            'title' => $user === null ? 'Nuevo usuario' : 'Editar usuario',
            'user' => $user,
            'isEdit' => $user !== null,
            'formAction' => $user === null ? '/users' : '/users/' . (int) $user['id'],
            'stats' => $this->users->stats(),
            'errors' => $errors,
            'generalError' => $generalError,
        ]), $status);
    }

    /**
     * @param array<string, mixed> $user
     */
    private function renderPassword(
        array $user,
        array $errors = [],
        ?string $generalError = null,
        int $status = 200
    ): Response {
        return Response::html($this->view->render('auth::users.password', [
            'title' => 'Restablecer contraseña',
            'user' => $user,
            'errors' => $errors,
            'generalError' => $generalError,
        ]), $status);
    }

    /**
     * @return array{name: string, email: string, role: string, password: string, password_confirmation: string}
     */
    private function input(Request $request): array
    {
        return [
            'name' => $this->value($request, 'name'),
            'email' => $this->value($request, 'email'),
            'role' => $this->value($request, 'role'),
            'password' => $this->value($request, 'password'),
            'password_confirmation' => $this->value($request, 'password_confirmation'),
        ];
    }

    private function value(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_scalar($value) ? (string) $value : '';
    }
}
