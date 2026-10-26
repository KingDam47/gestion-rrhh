<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mensajes temporales para la siguiente renderización.
 */
final class Flash
{
    private const SESSION_KEY = '_flash_messages';

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('danger', $message);
    }

    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    private static function add(string $type, string $message): void
    {
        $messages = Session::get(self::SESSION_KEY, []);
        $messages = is_array($messages) ? $messages : [];
        $messages[] = ['type' => $type, 'message' => $message];
        Session::put(self::SESSION_KEY, $messages);
    }

    /**
     * @return list<array{type: string, message: string}>
     */
    public static function pull(): array
    {
        $messages = Session::get(self::SESSION_KEY, []);
        Session::forget(self::SESSION_KEY);

        return is_array($messages) ? array_values($messages) : [];
    }
}
