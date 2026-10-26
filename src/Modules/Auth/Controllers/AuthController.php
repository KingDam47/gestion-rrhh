<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Flash;
use App\Core\OldInput;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\LoginResult;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly View $view
    ) {
    }

    public function showLogin(Request $request): Response
    {
        return Response::html($this->view->render('auth::login', [
            'title' => 'Iniciar sesión',
            'errors' => [],
            'generalError' => null,
        ], 'layouts.guest'));
    }

    public function login(Request $request): Response
    {
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        $validator = new Validator(
            ['email' => $email, 'password' => $password],
            ['email' => 'correo electrónico', 'password' => 'contraseña']
        );

        $errors = $validator->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'min:10', 'max:255'],
        ]);

        if ($errors !== []) {
            OldInput::put(['email' => $email]);

            return Response::html($this->view->render('auth::login', [
                'title' => 'Iniciar sesión',
                'errors' => $errors,
                'generalError' => null,
            ], 'layouts.guest'), 422);
        }

        $result = $this->authService->attempt($email, $password);

        if ($result === LoginResult::Success) {
            Flash::success('Bienvenido de nuevo.');
            return Response::redirect('/dashboard');
        }

        OldInput::put(['email' => $email]);

        $generalError = $result === LoginResult::TooManyAttempts
            ? 'Demasiados intentos fallidos. Espera un minuto antes de volver a intentarlo.'
            : 'Las credenciales no son válidas.';

        return Response::html($this->view->render('auth::login', [
            'title' => 'Iniciar sesión',
            'errors' => [],
            'generalError' => $generalError,
        ], 'layouts.guest'), 422);
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();
        Flash::success('Sesión cerrada correctamente.');

        return Response::redirect('/login');
    }
}
