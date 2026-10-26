<?php

declare(strict_types=1);

use App\Core\Router;
use App\Modules\Auth\Controllers\AuditController;
use App\Modules\Auth\Controllers\ProfileController;
use App\Modules\Auth\Controllers\UserController;

return static function (Router $router): void {
    $admin = ['auth', 'active_user', 'role:admin'];
    $adminWrite = ['auth', 'active_user', 'role:admin', 'csrf'];
    $account = ['auth', 'active_user'];
    $accountWrite = ['auth', 'active_user', 'csrf'];

    $router->get('/users/create', [UserController::class, 'create'], $admin);
    $router->get('/users', [UserController::class, 'index'], $admin);
    $router->post('/users', [UserController::class, 'store'], $adminWrite);
    $router->get('/users/{id}/edit', [UserController::class, 'edit'], $admin);
    $router->post('/users/{id}', [UserController::class, 'update'], $adminWrite);
    $router->post('/users/{id}/status', [UserController::class, 'status'], $adminWrite);
    $router->get('/users/{id}/password', [UserController::class, 'passwordForm'], $admin);
    $router->post('/users/{id}/password', [UserController::class, 'resetPassword'], $adminWrite);

    $router->get('/account/password', [ProfileController::class, 'editPassword'], $account);
    $router->post('/account/password', [ProfileController::class, 'updatePassword'], $accountWrite);

    $router->get('/audit', [AuditController::class, 'index'], $admin);
};
