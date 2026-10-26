<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Repuebla formularios tras un error de validación.
 * Nunca conserva contraseñas ni tokens.
 */
final class OldInput
{
    private const SESSION_KEY = '_old_input';

    /**
     * @param array<string, mixed> $input
     */
    public static function put(array $input): void
    {
        unset($input['password'], $input['password_confirmation'], $input['_token']);

        $clean = [];
        foreach ($input as $key => $value) {
            if (is_scalar($value)) {
                $clean[(string) $key] = (string) $value;
            }
        }

        Session::put(self::SESSION_KEY, $clean);
    }

    public static function get(string $key, string $default = ''): string
    {
        $input = Session::get(self::SESSION_KEY, []);
        $value = is_array($input) ? ($input[$key] ?? $default) : $default;

        return is_string($value) ? $value : $default;
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
