<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Renderiza plantillas PHP con un layout compartido.
 *
 * Las vistas reciben solo datos preparados y escapan toda salida con e().
 */
final class View
{
    /** @var array<string, string> */
    private array $roots;

    /** @var array<string, mixed> */
    private array $shared = [];

    /**
     * @param array<string, string> $roots Origen de vistas: prefijo => directorio
     */
    public function __construct(array $roots)
    {
        $this->roots = $roots;
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?string $layout = 'layouts.app'): string
    {
        $base = $this->shared + [
            'appName' => (string) Config::get('app.name', 'Gestión RR. HH.'),
            'appEnv' => (string) Config::get('app.env', 'production'),
            'currentUser' => Auth::user(),
            'flash' => Flash::pull(),
            'navigation' => Config::get('navigation', []),
            'currentPath' => '/',
        ];

        $data = $data + $base;
        $content = $this->renderFile($this->resolve($template), $data);

        if ($layout === null) {
            OldInput::clear();

            return $content;
        }

        $html = $this->renderFile($this->resolve($layout), $data + ['content' => $content]);
        OldInput::clear();

        return $html;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();

        try {
            require $file;
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return (string) ob_get_clean();
    }

    private function resolve(string $template): string
    {
        if (str_contains($template, '::')) {
            [$prefix, $name] = explode('::', $template, 2);
        } else {
            $prefix = 'shared';
            $name = $template;
        }

        $root = $this->roots[$prefix] ?? null;

        if ($root === null) {
            throw new RuntimeException(sprintf('No existe el origen de vistas "%s".', $prefix));
        }

        $file = $root . '/' . str_replace('.', '/', $name) . '.php';

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('No se encontró la vista "%s".', $template));
        }

        return $file;
    }
}
