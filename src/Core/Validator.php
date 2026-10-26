<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Validación de datos en servidor. JavaScript solo mejora la experiencia.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $labels
     */
    public function __construct(
        private readonly array $data,
        private readonly array $labels = []
    ) {
    }

    /**
     * @param array<string, list<string>> $rules
     * @return array<string, string> Primer error por campo
     */
    public function validate(array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $isNullable = in_array('nullable', $fieldRules, true);
            $isEmpty = $value === null || (is_string($value) && trim($value) === '');

            if ($isEmpty && $isNullable) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                [$name, $parameter] = $this->parseRule($rule);

                if ($this->fails($name, $value, $parameter, $isEmpty)) {
                    $this->errors[$field] = $this->message($name, $field, $parameter);
                    break;
                }
            }
        }

        return $this->errors;
    }

    private function fails(string $rule, mixed $value, ?string $parameter, bool $isEmpty): bool
    {
        return match ($rule) {
            'required' => $isEmpty,
            'email' => !$isEmpty && filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false,
            'max' => !$isEmpty && mb_strlen((string) $value) > (int) $parameter,
            'min' => !$isEmpty && mb_strlen((string) $value) < (int) $parameter,
            'date' => !$isEmpty && !$this->isValidDate((string) $value),
            'time' => !$isEmpty && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $value) !== 1,
            'integer' => !$isEmpty && filter_var((string) $value, FILTER_VALIDATE_INT) === false,
            'numeric' => !$isEmpty && !is_numeric((string) $value),
            'in' => !$isEmpty && !in_array((string) $value, explode(',', (string) $parameter), true),
            'same' => !$isEmpty && (string) ($this->data[(string) $parameter] ?? '') !== (string) $value,
            'regex' => !$isEmpty && preg_match((string) $parameter, (string) $value) !== 1,
            default => throw new InvalidArgumentException(sprintf('Regla de validación desconocida: %s.', $rule)),
        };
    }

    private function message(string $rule, string $field, ?string $parameter): string
    {
        $label = $this->labels[$field] ?? str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => sprintf('El campo %s es obligatorio.', $label),
            'email' => sprintf('El campo %s debe ser un correo electrónico válido.', $label),
            'max' => sprintf('El campo %s no debe superar los %d caracteres.', $label, (int) $parameter),
            'min' => sprintf('El campo %s debe tener al menos %d caracteres.', $label, (int) $parameter),
            'date' => sprintf('El campo %s debe ser una fecha válida (AAAA-MM-DD).', $label),
            'time' => sprintf('El campo %s debe ser una hora válida (HH:MM).', $label),
            'integer' => sprintf('El campo %s debe ser un número entero.', $label),
            'numeric' => sprintf('El campo %s debe ser un número.', $label),
            'in' => sprintf('El valor seleccionado en %s no es válido.', $label),
            'same' => sprintf('El campo %s no coincide.', $label),
            'regex' => sprintf('El campo %s tiene un formato no válido.', $label),
            default => sprintf('El campo %s no es válido.', $label),
        };
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function parseRule(string $rule): array
    {
        $parts = explode(':', $rule, 2);

        return [$parts[0], $parts[1] ?? null];
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
    }
}
