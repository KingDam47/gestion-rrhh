<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Config;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Core\Middleware\GuestMiddleware;
use App\Core\Middleware\Middleware;
use App\Core\Middleware\RoleMiddleware;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Modules\Auth\Middleware\ActiveUserMiddleware;

require __DIR__ . '/autoload.php';
require __DIR__ . '/Shared/Support/helpers.php';

$root = dirname(__DIR__);

Env::load($root . '/.env');

Config::load([
    'app' => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
    'navigation' => require $root . '/config/navigation.php',
    'paths' => [
        'root' => $root,
        'storage' => $root . '/storage',
        'views_shared' => $root . '/src/Shared/Views',
        'views_modules' => $root . '/src/Modules',
    ],
]);

date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));
ErrorHandler::register((bool) Config::get('app.debug', false));
Session::start((array) Config::get('app.session', []));

$container = require __DIR__ . '/container.php';

$router = new Router(
    static fn (string $class): object => $container->get($class),
    function (string $definition) use ($container): Middleware {
        [$name, $arguments] = array_pad(explode(':', $definition, 2), 2, '');
        $arguments = $arguments === '' ? [] : array_map('trim', explode(',', $arguments));

        return match ($name) {
            'auth' => new AuthMiddleware(),
            'guest' => new GuestMiddleware(),
            'csrf' => new CsrfMiddleware(),
            'active_user' => $container->get(ActiveUserMiddleware::class),
            'role' => new RoleMiddleware($arguments),
            default => throw new RuntimeException(sprintf('Middleware no registrado: %s.', $name)),
        };
    }
);

(require __DIR__ . '/routes/web.php')($router);

return new Application(
    $router,
    $container->get(View::class),
    (bool) Config::get('app.debug', false)
);
