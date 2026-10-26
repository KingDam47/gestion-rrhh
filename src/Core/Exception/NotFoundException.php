<?php

declare(strict_types=1);

namespace App\Core\Exception;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'La página solicitada no existe.')
    {
        parent::__construct(404, $message);
    }
}
