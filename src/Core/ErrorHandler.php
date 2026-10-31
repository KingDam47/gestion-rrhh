<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Convierte avisos en excepciones y deja un manejador final para errores
 * que escapen del ciclo normal de la aplicación.
 */
final class ErrorHandler
{
    public static function register(bool $debug): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        $storage = (string) Config::get('paths.storage', dirname(__DIR__, 2) . '/storage');
        $logDirectory = $storage . '/logs';

        if (!is_dir($logDirectory)) {
            @mkdir($logDirectory, 0775, true);
        }

        if (is_dir($logDirectory)) {
            ini_set('error_log', $logDirectory . '/php-error.log');
        }

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }

            if (($severity & (E_DEPRECATED | E_USER_DEPRECATED)) !== 0) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $exception) use ($debug): void {
            Logger::error('Excepción no capturada', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=UTF-8');
                header('X-Content-Type-Options: nosniff');
                header('Cache-Control: no-store, private');
            }

            if ($debug) {
                echo '<h1>Error no controlado</h1>';
                echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
                return;
            }

            echo 'Ha ocurrido un error inesperado. Inténtalo de nuevo más tarde.';
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();

            if ($error === null) {
                return;
            }

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

            if (!in_array($error['type'], $fatalTypes, true)) {
                return;
            }

            Logger::error('Error fatal de PHP', [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        });
    }
}
