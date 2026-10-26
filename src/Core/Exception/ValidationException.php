<?php

declare(strict_types=1);

namespace App\Core\Exception;

use RuntimeException;

/**
 * Error de validación controlado. Los controladores lo usan para volver a
 * mostrar el formulario con los errores por campo y el input del usuario.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Revisa los datos del formulario.'
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
