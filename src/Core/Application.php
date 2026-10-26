<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Ciclo de vida de una petición HTTP.
 */
final class Application
{
    public function __construct(
        private readonly Router $router,
        private readonly View $view,
        private readonly bool $debug
    ) {
    }

    public function run(): void
    {
        $request = Request::capture();
        $this->view->share('currentPath', $request->path());

        try {
            $response = $this->router->dispatch($request);
        } catch (Exception\HttpException $exception) {
            $response = $this->errorResponse($exception->statusCode(), $exception->getMessage());
        } catch (Throwable $exception) {
            Logger::error('Error no controlado', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $message = $this->debug
                ? $exception->getMessage()
                : 'Ha ocurrido un error inesperado. Inténtalo de nuevo más tarde.';

            $response = $this->errorResponse(500, $message);
        }

        $response->send();
    }

    private function errorResponse(int $status, string $message): Response
    {
        $titles = [
            403 => 'Acceso denegado',
            404 => 'Página no encontrada',
            405 => 'Método no permitido',
            500 => 'Error del servidor',
        ];

        $title = $titles[$status] ?? 'Error';

        return Response::html(
            $this->view->render('errors.http', [
                'title' => $title,
                'status' => $status,
                'message' => $message !== '' ? $message : $title,
            ]),
            $status
        );
    }
}
