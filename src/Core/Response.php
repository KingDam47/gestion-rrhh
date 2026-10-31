<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Respuesta HTTP con cabeceras de seguridad por defecto.
 */
final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $body = '',
        private readonly int $status = 200,
        private readonly array $headers = []
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public static function html(string $body, int $status = 200, array $headers = []): self
    {
        return new self($body, $status, $headers + [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->securityHeaders() as $name => $value) {
                header($name . ': ' . $value);
            }

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        if ($this->body !== '') {
            echo $this->body;
        }
    }

    /**
     * @return array<string, string>
     */
    private function securityHeaders(): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'Content-Security-Policy' => "default-src 'self'; "
                . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
                . "script-src 'self' https://cdn.jsdelivr.net; "
                . "font-src 'self' https://cdn.jsdelivr.net; "
                . "img-src 'self' data:; "
                . "base-uri 'self'; form-action 'self'; "
                . "frame-ancestors 'none'; object-src 'none'",
        ];

        if ($this->isHttps()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }

    private function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? null;

        if (is_string($https) && $https !== '' && mb_strtolower($https) !== 'off') {
            return true;
        }

        $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;

        if (is_string($forwarded)) {
            $first = explode(',', $forwarded)[0];

            return mb_strtolower(trim($first)) === 'https';
        }

        return false;
    }
}
