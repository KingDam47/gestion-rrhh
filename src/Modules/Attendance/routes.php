<?php

declare(strict_types=1);

use App\Core\Router;
use App\Modules\Attendance\Controllers\AttendanceController;
use App\Modules\Attendance\Controllers\LeaveRequestController;
use App\Modules\Attendance\Controllers\ReportController;

return static function (Router $router): void {
    $staff = ['auth', 'active_user', 'role:admin,hr'];
    $write = ['auth', 'active_user', 'role:admin,hr', 'csrf'];

    $router->get('/attendance/report', [ReportController::class, 'index'], $staff);
    $router->get('/attendance/create', [AttendanceController::class, 'create'], $staff);
    $router->get('/attendance', [AttendanceController::class, 'index'], $staff);
    $router->post('/attendance', [AttendanceController::class, 'store'], $write);
    $router->get('/attendance/{id}/edit', [AttendanceController::class, 'edit'], $staff);
    $router->post('/attendance/{id}', [AttendanceController::class, 'update'], $write);

    $router->get('/leave-requests/create', [LeaveRequestController::class, 'create'], $staff);
    $router->get('/leave-requests', [LeaveRequestController::class, 'index'], $staff);
    $router->post('/leave-requests', [LeaveRequestController::class, 'store'], $write);
    $router->get('/leave-requests/{id}', [LeaveRequestController::class, 'show'], $staff);
    $router->post('/leave-requests/{id}/review', [LeaveRequestController::class, 'review'], $write);
};
