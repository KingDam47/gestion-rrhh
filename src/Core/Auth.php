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
     * @param array{id: int|string, name: string, email: string, role: string, password_changed_at?: string|null} $user
     */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put(self::SESSION_KEY, [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'password_changed_at' => isset($user['password_changed_at'])
                ? (string) $user['password_changed_at']
                : null,
        ]);
        Csrf::rotate();
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     role: string,
     *     password_changed_at?: string|null
     * }|null
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
     * @param array{
     *     id: int|string,
     *     name: string,
     *     email: string,
     *     role: string,
     *     password_changed_at?: string|null
     * } $user
     */
    public static function refresh(array $user): void
    {
        $current = self::user();

        if ($current === null) {
            return;
        }

        Session::put(self::SESSION_KEY, [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'password_changed_at' => array_key_exists('password_changed_at', $user)
                ? ($user['password_changed_at'] !== null ? (string) $user['password_changed_at'] : null)
                : ($current['password_changed_at'] ?? null),
        ]);
    }

    /**
     * Conserva la sesión actual tras un cambio de contraseña propio, para que
     * el usuario que la cambió no tenga que volver a iniciar sesión.
     */
    public static function refreshPasswordChangedAt(?string $changedAt): void
    {
        $user = self::user();

        if ($user === null) {
            return;
        }

        $user['password_changed_at'] = $changedAt;
        Session::put(self::SESSION_KEY, $user);
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
