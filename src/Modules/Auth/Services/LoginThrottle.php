<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Logger;
use Closure;

/**
 * Límite de intentos de login compartido entre peticiones.
 *
 * Usa archivos con bloqueo para no depender de una tabla adicional y para
 * funcionar igual en local y en InfinityFree. Si el almacenamiento falla, el
 * limitador deja pasar el intento (fail-open) y registra el problema: un fallo
 * de disco no debe dejar fuera a todos los usuarios.
 */
final class LoginThrottle
{
    private const EMAIL_PREFIX = 'email:';
    private const IP_PREFIX = 'ip:';

    private readonly Closure $clock;

    public function __construct(
        private readonly string $directory,
        private readonly int $emailLimit = 5,
        private readonly int $ipLimit = 20,
        private readonly int $windowSeconds = 900,
        ?Closure $clock = null
    ) {
        $this->clock = $clock ?? static fn (): int => time();
    }

    /**
     * Indica si el correo o la IP ya agotaron su ventana de intentos.
     */
    public function tooManyAttempts(string $email, string $ip): bool
    {
        $now = $this->now();

        return $this->isLimited(
            $this->bucket(self::EMAIL_PREFIX . $this->normalizeEmail($email)),
            $this->emailLimit,
            $now
        ) || $this->isLimited(
            $this->bucket(self::IP_PREFIX . $this->normalizeIp($ip)),
            $this->ipLimit,
            $now
        );
    }

    /**
     * Registra un intento fallido para el correo y para la IP.
     */
    public function hit(string $email, string $ip): void
    {
        $now = $this->now();

        $this->increment(self::EMAIL_PREFIX . $this->normalizeEmail($email), $now);
        $this->increment(self::IP_PREFIX . $this->normalizeIp($ip), $now);
        $this->prune($now);
    }

    /**
     * Limpia el contador del correo tras un login correcto. El contador de la
     * IP se conserva para que iniciar sesión con una cuenta propia no reinicie
     * el límite de fuerza bruta desde esa dirección.
     */
    public function clearEmail(string $email): void
    {
        $path = $this->path(self::EMAIL_PREFIX . $this->normalizeEmail($email));

        if (!is_file($path)) {
            return;
        }

        $handle = @fopen($path, 'c+b');

        if ($handle === false) {
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode(['count' => 0, 'reset_at' => 0]));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{count: int, reset_at: int}
     */
    private function bucket(string $key): array
    {
        $path = $this->path($key);

        if (!is_file($path)) {
            return ['count' => 0, 'reset_at' => 0];
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return ['count' => 0, 'reset_at' => 0];
        }

        $contents = '';

        try {
            if (flock($handle, LOCK_SH)) {
                $contents = (string) stream_get_contents($handle);
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }

        return $this->decode($contents);
    }

    private function increment(string $key, int $now): void
    {
        $this->ensureDirectory();
        $path = $this->path($key);
        $handle = @fopen($path, 'c+b');

        if ($handle === false) {
            Logger::warning('No se pudo abrir el almacén del limitador de login.', [
                'file' => basename($path),
            ]);

            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            $bucket = $this->decode((string) stream_get_contents($handle));

            if ($bucket['reset_at'] <= $now) {
                $bucket = ['count' => 0, 'reset_at' => $now + $this->windowSeconds];
            }

            $bucket['count']++;

            $encoded = json_encode($bucket);

            if ($encoded === false) {
                return;
            }

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $encoded);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array{count: int, reset_at: int} $bucket
     */
    private function isLimited(array $bucket, int $limit, int $now): bool
    {
        return $bucket['reset_at'] > $now && $bucket['count'] >= $limit;
    }

    /**
     * @return array{count: int, reset_at: int}
     */
    private function decode(string $contents): array
    {
        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return ['count' => 0, 'reset_at' => 0];
        }

        return [
            'count' => max(0, (int) ($data['count'] ?? 0)),
            'reset_at' => max(0, (int) ($data['reset_at'] ?? 0)),
        ];
    }

    private function ensureDirectory(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        if (!@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            Logger::warning('No se pudo crear el directorio del limitador de login.', [
                'directory' => $this->directory,
            ]);
        }
    }

    /**
     * Borra de forma oportunista los archivos caducados para que el directorio
     * no crezca indefinidamente. No es crítico si no se ejecuta.
     */
    private function prune(int $now): void
    {
        if (mt_rand(1, 100) !== 1) {
            return;
        }

        $files = glob(rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . '*.json');

        if ($files === false) {
            return;
        }

        $threshold = $now - ($this->windowSeconds * 2);

        foreach ($files as $file) {
            $modified = @filemtime($file);

            if ($modified !== false && $modified < $threshold) {
                @unlink($file);
            }
        }
    }

    private function path(string $key): string
    {
        return rtrim($this->directory, '/\\')
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.json';
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function normalizeIp(string $ip): string
    {
        return trim($ip);
    }

    private function now(): int
    {
        return (int) ($this->clock)();
    }
}
