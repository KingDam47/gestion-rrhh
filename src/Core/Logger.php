<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Registro técnico en storage/logs. Nunca guarda contraseñas ni PII.
 */
final class Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function write(string $level, string $message, array $context = []): void
    {
        $storage = (string) Config::get('paths.storage', dirname(__DIR__, 2) . '/storage');
        $directory = $storage . '/logs';

        $line = sprintf('[%s] %s: %s', date('Y-m-d H:i:s'), strtoupper($level), $message);

        if ($context !== []) {
            $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded)) {
                $line .= ' ' . $encoded;
            }
        }

        try {
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            if (!is_writable($directory)) {
                return;
            }

            file_put_contents(
                $directory . '/app-' . date('Y-m-d') . '.log',
                $line . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (Throwable) {
            // Un fallo de logging no debe interrumpir la respuesta al usuario.
        }
    }
}
