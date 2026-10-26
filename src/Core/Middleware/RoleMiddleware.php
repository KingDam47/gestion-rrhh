<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Exception\ForbiddenException;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class RoleMiddleware implements Middleware
{
    /**
     * @param list<string> $roles
     */
    public function __construct(private readonly array $roles)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::hasRole(...$this->roles)) {
            throw new ForbiddenException();
        }

        return $next($request);
    }
}
