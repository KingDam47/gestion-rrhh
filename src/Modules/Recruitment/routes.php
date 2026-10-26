<?php

declare(strict_types=1);

use App\Core\Router;
use App\Modules\Recruitment\Controllers\ApplicationController;
use App\Modules\Recruitment\Controllers\CandidateController;
use App\Modules\Recruitment\Controllers\RecruitmentController;
use App\Modules\Recruitment\Controllers\VacancyController;

return static function (Router $router): void {
    $staff = ['auth', 'active_user', 'role:admin,hr'];
    $write = ['auth', 'active_user', 'role:admin,hr', 'csrf'];

    $router->get('/recruitment', [RecruitmentController::class, 'index'], $staff);

    $router->get('/vacancies/create', [VacancyController::class, 'create'], $staff);
    $router->get('/vacancies', [VacancyController::class, 'index'], $staff);
    $router->post('/vacancies', [VacancyController::class, 'store'], $write);
    $router->get('/vacancies/{id}/edit', [VacancyController::class, 'edit'], $staff);
    $router->post('/vacancies/{id}/close', [VacancyController::class, 'close'], $write);
    $router->post('/vacancies/{id}', [VacancyController::class, 'update'], $write);
    $router->get('/vacancies/{id}', [VacancyController::class, 'show'], $staff);

    $router->get('/candidates/create', [CandidateController::class, 'create'], $staff);
    $router->get('/candidates', [CandidateController::class, 'index'], $staff);
    $router->post('/candidates', [CandidateController::class, 'store'], $write);
    $router->get('/candidates/{id}/edit', [CandidateController::class, 'edit'], $staff);
    $router->post('/candidates/{id}', [CandidateController::class, 'update'], $write);
    $router->get('/candidates/{id}', [CandidateController::class, 'show'], $staff);

    $router->get('/applications/create', [ApplicationController::class, 'create'], $staff);
    $router->get('/applications', [ApplicationController::class, 'index'], $staff);
    $router->post('/applications', [ApplicationController::class, 'store'], $write);
    $router->get('/applications/{id}/hire', [ApplicationController::class, 'hireForm'], $staff);
    $router->post('/applications/{id}/hire', [ApplicationController::class, 'hire'], $write);
    $router->post('/applications/{id}/status', [ApplicationController::class, 'status'], $write);
    $router->get('/applications/{id}', [ApplicationController::class, 'show'], $staff);
};
