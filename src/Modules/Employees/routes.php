<?php

declare(strict_types=1);

use App\Core\Router;
use App\Modules\Employees\Controllers\DepartmentController;
use App\Modules\Employees\Controllers\EmployeeController;
use App\Modules\Employees\Controllers\PositionController;

return static function (Router $router): void {
    $staff = ['auth', 'active_user', 'role:admin,hr'];
    $write = ['auth', 'active_user', 'role:admin,hr', 'csrf'];

    $router->get('/employees/report', [EmployeeController::class, 'report'], $staff);
    $router->get('/employees/create', [EmployeeController::class, 'create'], $staff);
    $router->get('/employees', [EmployeeController::class, 'index'], $staff);
    $router->post('/employees', [EmployeeController::class, 'store'], $write);
    $router->get('/employees/{id}/edit', [EmployeeController::class, 'edit'], $staff);
    $router->post('/employees/{id}/status', [EmployeeController::class, 'status'], $write);
    $router->post('/employees/{id}', [EmployeeController::class, 'update'], $write);
    $router->get('/employees/{id}', [EmployeeController::class, 'show'], $staff);

    $router->get('/departments', [DepartmentController::class, 'index'], $staff);
    $router->post('/departments', [DepartmentController::class, 'store'], $write);
    $router->get('/departments/{id}/edit', [DepartmentController::class, 'edit'], $staff);
    $router->post('/departments/{id}/status', [DepartmentController::class, 'status'], $write);
    $router->post('/departments/{id}', [DepartmentController::class, 'update'], $write);

    $router->get('/positions', [PositionController::class, 'index'], $staff);
    $router->post('/positions', [PositionController::class, 'store'], $write);
    $router->get('/positions/{id}/edit', [PositionController::class, 'edit'], $staff);
    $router->post('/positions/{id}/status', [PositionController::class, 'status'], $write);
    $router->post('/positions/{id}', [PositionController::class, 'update'], $write);
};
