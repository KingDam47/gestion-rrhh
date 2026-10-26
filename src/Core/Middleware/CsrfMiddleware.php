<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Csrf;
use App\Core\Exception\ForbiddenException;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isPost()) {
            return $next($request);
        }

        $token = $request->input('_token');
        $token = is_string($token) ? $token : null;

        if (!Csrf::validate($token)) {
            throw new ForbiddenException('El formulario expiró o no es válido. Recarga la página e inténtalo de nuevo.');
        }

        return $next($request);
    }
}
