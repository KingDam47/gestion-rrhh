<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class AuthMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            Flash::warning('Inicia sesión para continuar.');

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
