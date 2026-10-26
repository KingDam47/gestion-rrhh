<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Token CSRF por sesión. Se regenera al iniciar sesión.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '">';
    }

    public static function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $sessionToken = Session::get(self::SESSION_KEY);

        return is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }

    public static function rotate(): void
    {
        Session::put(self::SESSION_KEY, bin2hex(random_bytes(32)));
    }
}
