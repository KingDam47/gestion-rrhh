<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Auth;
use App\Core\Exception\ValidationException;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Modules\Auth\Services\UserService;
use App\Shared\Support\AuditLogger;

final class ProfileController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuditLogger $audit,
        private readonly View $view
    ) {
    }

    public function editPassword(Request $request): Response
    {
        return $this->render(['errors' => [], 'generalError' => null]);
    }

    public function updatePassword(Request $request): Response
    {
        $input = [
            'current_password' => $this->value($request, 'current_password'),
            'password' => $this->value($request, 'password'),
            'password_confirmation' => $this->value($request, 'password_confirmation'),
        ];

        try {
            $this->users->changeOwnPassword((int) Auth::id(), $input);
        } catch (ValidationException $exception) {
            return $this->render([
                'errors' => $exception->errors(),
                'generalError' => $exception->getMessage(),
            ], 422);
        }

        Session::regenerate();

        $this->audit->record(
            Auth::id(),
            'user.password_changed',
            'user',
            Auth::id(),
            [],
            $request->ip()
        );

        Flash::success('Contraseña actualizada correctamente.');

        return Response::redirect('/account/password');
    }

    /**
     * @param array{errors: array<string, string>, generalError: string|null} $data
     */
    private function render(array $data, int $status = 200): Response
    {
        return Response::html($this->view->render('auth::account.password', [
            'title' => 'Mi cuenta',
            'errors' => $data['errors'],
            'generalError' => $data['generalError'],
        ]), $status);
    }

    private function value(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_scalar($value) ? (string) $value : '';
    }
}
