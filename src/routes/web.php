<?php

declare(strict_types=1);

use App\Core\Router;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Dashboard\Controllers\DashboardController;

return static function (Router $router): void {
    $router->get('/', [DashboardController::class, 'home']);

    $router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
    $router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf']);
    $router->post('/logout', [AuthController::class, 'logout'], ['auth', 'active_user', 'csrf']);

    $router->get('/dashboard', [DashboardController::class, 'index'], ['auth', 'active_user']);

    (require __DIR__ . '/../Modules/Auth/routes.php')($router);
    (require __DIR__ . '/../Modules/Employees/routes.php')($router);
    (require __DIR__ . '/../Modules/Attendance/routes.php')($router);
    (require __DIR__ . '/../Modules/Recruitment/routes.php')($router);
};
