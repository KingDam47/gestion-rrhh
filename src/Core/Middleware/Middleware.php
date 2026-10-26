<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

interface Middleware
{
    public function handle(Request $request, Closure $next): Response;
}
