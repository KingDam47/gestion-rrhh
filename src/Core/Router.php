<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Router con parámetros {nombre} y middlewares declarados por ruta.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: array{0: class-string, 1: string}, middleware: list<string>}> */
    private array $routes = [];

    /**
     * @param Closure(string): object $controllerResolver
     * @param Closure(string): Middleware\Middleware $middlewareResolver
     */
    public function __construct(
        private readonly Closure $controllerResolver,
        private readonly Closure $middlewareResolver
    ) {
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (preg_match($route['regex'], $request->path(), $matches) !== 1) {
                continue;
            }

            $parameters = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $parameters[$key] = $value;
                }
            }

            return $this->runPipeline($request, $route['handler'], $route['middleware'], $parameters);
        }

        throw new Exception\NotFoundException();
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     * @param array<string, string> $parameters
     */
    private function runPipeline(
        Request $request,
        array $handler,
        array $middleware,
        array $parameters
    ): Response {
        $next = fn (Request $current): Response => $this->invoke($handler, $current, $parameters);

        foreach (array_reverse($middleware) as $definition) {
            $instance = ($this->middlewareResolver)($definition);
            $next = fn (Request $current): Response => $instance->handle($current, $next);
        }

        return $next($request);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<string, string> $parameters
     */
    private function invoke(array $handler, Request $request, array $parameters): Response
    {
        $controller = ($this->controllerResolver)($handler[0]);

        if (!method_exists($controller, $handler[1])) {
            throw new RuntimeException(sprintf(
                'El controlador %s no tiene el método %s.',
                $handler[0],
                $handler[1]
            ));
        }

        $response = $controller->{$handler[1]}($request, ...array_values($parameters));

        if (!$response instanceof Response) {
            throw new RuntimeException(sprintf(
                'La acción %s::%s debe devolver una Response.',
                $handler[0],
                $handler[1]
            ));
        }

        return $response;
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'regex' => $this->compile($pattern),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function compile(string $pattern): string
    {
        $pattern = '/' . trim($pattern, '/');
        $segments = $pattern === '/' ? [] : explode('/', trim($pattern, '/'));
        $parts = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $matches) === 1) {
                $parts[] = '(?P<' . $matches[1] . '>[^/]+)';

                continue;
            }

            $parts[] = preg_quote($segment, '#');
        }

        return '#^/' . implode('/', $parts) . '$#';
    }
}
