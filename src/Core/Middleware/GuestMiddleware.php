<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class GuestMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return Response::redirect('/dashboard');
        }

        return $next($request);
    }
}
