<?php

declare(strict_types=1);

namespace App\Core\Exception;

use RuntimeException;

/**
 * Una regla de negocio impide completar la operación solicitada.
 * El mensaje está pensado para mostrarse al usuario.
 */
final class BusinessRuleException extends RuntimeException
{
}
