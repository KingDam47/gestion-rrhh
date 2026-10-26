<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sesión PHP con cookies seguras y expiración por inactividad.
 */
final class Session
{
    private const LAST_ACTIVITY_KEY = '_last_activity';

    /**
     * @param array{
     *     name: string,
     *     lifetime: int,
     *     secure: bool,
     *     same_site?: string
     * } $config
     */
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($config['name'] ?? 'gestion_rrhh_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (bool) ($config['secure'] ?? false),
            'httponly' => true,
            'samesite' => $config['same_site'] ?? 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) max(60, ((int) ($config['lifetime'] ?? 120)) * 60));

        session_start();

        $lifetime = max(60, ((int) ($config['lifetime'] ?? 120)) * 60);
        $lastActivity = $_SESSION[self::LAST_ACTIVITY_KEY] ?? null;

        if (is_int($lastActivity) && (time() - $lastActivity) > $lifetime) {
            self::destroy();
            session_start();
        }

        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION[self::LAST_ACTIVITY_KEY] = time();
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42_000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
