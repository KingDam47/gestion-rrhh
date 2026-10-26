<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Usuario autenticado guardado en sesión. Los datos vienen de users,
 * nunca de la ficha de empleado.
 */
final class Auth
{
    private const SESSION_KEY = '_auth_user';

    /**
     * @param array{id: int|string, name: string, email: string, role: string} $user
     */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put(self::SESSION_KEY, [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ]);
        Csrf::rotate();
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}|null
     */
    public static function user(): ?array
    {
        $user = Session::get(self::SESSION_KEY);

        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Actualiza nombre y correo desde la base sin rotar la sesión.
     *
     * @param array{id: int|string, name: string, email: string, role: string} $user
     */
    public static function refresh(array $user): void
    {
        if (!self::check()) {
            return;
        }

        Session::put(self::SESSION_KEY, [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ]);
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user['id'] ?? null;
    }

    public static function role(): ?string
    {
        $user = self::user();

        return $user['role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $role = self::role();

        return $role !== null && in_array($role, $roles, true);
    }

    public static function logout(): void
    {
        Session::destroy();
        Session::start((array) Config::get('app.session', []));
        Session::regenerate();
    }
}
