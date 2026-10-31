<?php

declare(strict_types=1);

namespace App\Modules\Auth\Middleware;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Middleware\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Auth\Repositories\UserRepository;
use Closure;

/**
 * Revalida contra la base que la cuenta siga activa y conserve su rol.
 * Una cuenta desactivada pierde la sesión en la siguiente petición.
 */
final class ActiveUserMiddleware implements Middleware
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $sessionUser = Auth::user();

        if ($sessionUser === null) {
            return $next($request);
        }

        $currentUser = $this->users->findActiveById($sessionUser['id']);

        if ($currentUser === null) {
            Auth::logout();
            Flash::warning('Tu cuenta ya no está activa. Inicia sesión de nuevo.');

            return Response::redirect('/login');
        }

        if ((string) $currentUser['role'] !== $sessionUser['role']) {
            Auth::logout();
            Flash::warning('Tus permisos cambiaron. Inicia sesión de nuevo.');

            return Response::redirect('/login');
        }

        $currentPasswordChangedAt = $currentUser['password_changed_at'] !== null
            ? (string) $currentUser['password_changed_at']
            : '';
        $sessionPasswordChangedAt = isset($sessionUser['password_changed_at'])
            ? (string) $sessionUser['password_changed_at']
            : '';

        if ($currentPasswordChangedAt !== $sessionPasswordChangedAt) {
            Auth::logout();
            Flash::warning('Tu contraseña cambió. Inicia sesión de nuevo.');

            return Response::redirect('/login');
        }

        Auth::refresh($currentUser);

        return $next($request);
    }
}
