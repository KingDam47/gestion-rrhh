<?php

declare(strict_types=1);

namespace App\Core\Exception;

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'No tienes permisos para acceder a esta sección.')
    {
        parent::__construct(403, $message);
    }
}
