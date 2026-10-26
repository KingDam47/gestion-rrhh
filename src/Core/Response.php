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
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; "
                . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
                . "script-src 'self' https://cdn.jsdelivr.net; "
                . "font-src 'self' https://cdn.jsdelivr.net; "
                . "img-src 'self' data:; "
                . "base-uri 'self'; form-action 'self'",
        ];
    }
}
