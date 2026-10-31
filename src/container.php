<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Container;
use App\Core\Database;
use App\Core\View;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\AuditController;
use App\Modules\Auth\Controllers\ProfileController;
use App\Modules\Auth\Controllers\UserController;
use App\Modules\Auth\Contracts\UserLookup;
use App\Modules\Auth\Middleware\ActiveUserMiddleware;
use App\Modules\Auth\Repositories\AuditLogRepository;
use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\Auth\Services\AuditLogService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\LoginThrottle;
use App\Modules\Auth\Services\UserLookupService;
use App\Modules\Auth\Services\UserService;
use App\Modules\Attendance\Controllers\AttendanceController;
use App\Modules\Attendance\Controllers\LeaveRequestController;
use App\Modules\Attendance\Controllers\ReportController;
use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\Attendance\Repositories\LeaveRequestRepository;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Attendance\Services\LeaveRequestService;
use App\Modules\Attendance\Services\MonthlyReportService;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Dashboard\Services\DashboardSummaryService;
use App\Modules\Employees\Contracts\EmployeeLookup;
use App\Modules\Employees\Contracts\EmployeeOnboarding;
use App\Modules\Employees\Controllers\DepartmentController;
use App\Modules\Employees\Controllers\EmployeeController;
use App\Modules\Employees\Controllers\PositionController;
use App\Modules\Employees\Repositories\DepartmentRepository;
use App\Modules\Employees\Repositories\EmployeeRepository;
use App\Modules\Employees\Repositories\PositionRepository;
use App\Modules\Employees\Services\DepartmentService;
use App\Modules\Employees\Services\EmployeeLookupService;
use App\Modules\Employees\Services\EmployeeService;
use App\Modules\Employees\Services\PositionService;
use App\Modules\Recruitment\Controllers\ApplicationController;
use App\Modules\Recruitment\Controllers\CandidateController;
use App\Modules\Recruitment\Controllers\RecruitmentController;
use App\Modules\Recruitment\Controllers\VacancyController;
use App\Modules\Recruitment\Repositories\ApplicationRepository;
use App\Modules\Recruitment\Repositories\CandidateRepository;
use App\Modules\Recruitment\Repositories\VacancyRepository;
use App\Modules\Recruitment\Services\ApplicationService;
use App\Modules\Recruitment\Services\CandidateService;
use App\Modules\Recruitment\Services\VacancyService;
use App\Shared\Support\AuditLogger;

$container = new Container();

$container->singleton(Database::class, static fn (): Database => new Database(
    (array) Config::get('database', [])
));

$container->singleton(View::class, static fn (): View => new View([
    'shared' => (string) Config::get('paths.views_shared'),
    'auth' => (string) Config::get('paths.views_modules') . '/Auth/Views',
    'dashboard' => (string) Config::get('paths.views_modules') . '/Dashboard/Views',
    'employees' => (string) Config::get('paths.views_modules') . '/Employees/Views',
    'attendance' => (string) Config::get('paths.views_modules') . '/Attendance/Views',
    'recruitment' => (string) Config::get('paths.views_modules') . '/Recruitment/Views',
]));

$container->set(UserRepository::class, static fn (Container $c): UserRepository => new UserRepository(
    $c->get(Database::class)
));

$container->set(LoginThrottle::class, static fn (Container $c): LoginThrottle => new LoginThrottle(
    (string) Config::get('paths.storage') . '/rate-limits'
));

$container->set(AuthService::class, static fn (Container $c): AuthService => new AuthService(
    $c->get(UserRepository::class),
    $c->get(LoginThrottle::class)
));

$container->set(UserLookup::class, static fn (Container $c): UserLookup => new UserLookupService(
    $c->get(UserRepository::class)
));

$container->set(ActiveUserMiddleware::class, static fn (Container $c): ActiveUserMiddleware => new ActiveUserMiddleware(
    $c->get(UserRepository::class)
));

$container->set(AuthController::class, static fn (Container $c): AuthController => new AuthController(
    $c->get(AuthService::class),
    $c->get(View::class)
));

$container->set(UserService::class, static fn (Container $c): UserService => new UserService(
    $c->get(UserRepository::class),
    $c->get(Database::class)
));

$container->set(UserController::class, static fn (Container $c): UserController => new UserController(
    $c->get(UserService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

$container->set(ProfileController::class, static fn (Container $c): ProfileController => new ProfileController(
    $c->get(UserService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

$container->set(AuditLogRepository::class, static fn (Container $c): AuditLogRepository => new AuditLogRepository(
    $c->get(Database::class)
));

$container->set(AuditLogService::class, static fn (Container $c): AuditLogService => new AuditLogService(
    $c->get(AuditLogRepository::class)
));

$container->set(AuditController::class, static fn (Container $c): AuditController => new AuditController(
    $c->get(AuditLogService::class),
    $c->get(View::class)
));

$container->set(DashboardController::class, static fn (Container $c): DashboardController => new DashboardController(
    $c->get(DashboardSummaryService::class),
    $c->get(View::class)
));

$container->set(DashboardSummaryService::class, static fn (Container $c): DashboardSummaryService => new DashboardSummaryService(
    $c->get(EmployeeLookup::class),
    $c->get(AttendanceService::class),
    $c->get(LeaveRequestService::class),
    $c->get(VacancyService::class),
    $c->get(CandidateService::class),
    $c->get(ApplicationService::class)
));

$container->set(AuditLogger::class, static fn (Container $c): AuditLogger => new AuditLogger(
    $c->get(Database::class)
));

$container->set(DepartmentRepository::class, static fn (Container $c): DepartmentRepository => new DepartmentRepository(
    $c->get(Database::class)
));

$container->set(PositionRepository::class, static fn (Container $c): PositionRepository => new PositionRepository(
    $c->get(Database::class)
));

$container->set(EmployeeRepository::class, static fn (Container $c): EmployeeRepository => new EmployeeRepository(
    $c->get(Database::class)
));

$container->set(DepartmentService::class, static fn (Container $c): DepartmentService => new DepartmentService(
    $c->get(DepartmentRepository::class)
));

$container->set(PositionService::class, static fn (Container $c): PositionService => new PositionService(
    $c->get(PositionRepository::class),
    $c->get(DepartmentRepository::class)
));

$container->set(EmployeeService::class, static fn (Container $c): EmployeeService => new EmployeeService(
    $c->get(EmployeeRepository::class),
    $c->get(DepartmentRepository::class),
    $c->get(PositionRepository::class)
));

$container->set(EmployeeLookup::class, static fn (Container $c): EmployeeLookup => new EmployeeLookupService(
    $c->get(EmployeeRepository::class),
    $c->get(DepartmentRepository::class),
    $c->get(PositionRepository::class)
));

$container->set(EmployeeOnboarding::class, static fn (Container $c): EmployeeOnboarding => $c->get(EmployeeService::class));

$container->set(EmployeeController::class, static fn (Container $c): EmployeeController => new EmployeeController(
    $c->get(EmployeeService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

$container->set(DepartmentController::class, static fn (Container $c): DepartmentController => new DepartmentController(
    $c->get(DepartmentService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

$container->set(PositionController::class, static fn (Container $c): PositionController => new PositionController(
    $c->get(PositionService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

$container->set(AttendanceRepository::class, static fn (Container $c): AttendanceRepository => new AttendanceRepository(
    $c->get(Database::class)
));

$container->set(LeaveRequestRepository::class, static fn (Container $c): LeaveRequestRepository => new LeaveRequestRepository(
    $c->get(Database::class)
));

$container->set(AttendanceService::class, static fn (Container $c): AttendanceService => new AttendanceService(
    $c->get(AttendanceRepository::class),
    $c->get(EmployeeLookup::class)
));

$container->set(LeaveRequestService::class, static fn (Container $c): LeaveRequestService => new LeaveRequestService(
    $c->get(LeaveRequestRepository::class),
    $c->get(EmployeeLookup::class),
    $c->get(UserLookup::class)
));

$container->set(MonthlyReportService::class, static fn (Container $c): MonthlyReportService => new MonthlyReportService(
    $c->get(AttendanceRepository::class),
    $c->get(LeaveRequestRepository::class),
    $c->get(EmployeeLookup::class),
    $c->get(UserLookup::class)
));

$container->set(AttendanceController::class, static fn (Container $c): AttendanceController => new AttendanceController(
    $c->get(AttendanceService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

$container->set(LeaveRequestController::class, static fn (Container $c): LeaveRequestController => new LeaveRequestController(
    $c->get(LeaveRequestService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

$container->set(ReportController::class, static fn (Container $c): ReportController => new ReportController(
    $c->get(MonthlyReportService::class),
    $c->get(View::class)
));

$container->set(VacancyRepository::class, static fn (Container $c): VacancyRepository => new VacancyRepository(
    $c->get(Database::class)
));

$container->set(CandidateRepository::class, static fn (Container $c): CandidateRepository => new CandidateRepository(
    $c->get(Database::class)
));

$container->set(ApplicationRepository::class, static fn (Container $c): ApplicationRepository => new ApplicationRepository(
    $c->get(Database::class)
));

$container->set(VacancyService::class, static fn (Container $c): VacancyService => new VacancyService(
    $c->get(VacancyRepository::class),
    $c->get(EmployeeLookup::class)
));

$container->set(CandidateService::class, static fn (Container $c): CandidateService => new CandidateService(
    $c->get(CandidateRepository::class)
));

$container->set(ApplicationService::class, static fn (Container $c): ApplicationService => new ApplicationService(
    $c->get(ApplicationRepository::class),
    $c->get(VacancyRepository::class),
    $c->get(CandidateRepository::class),
    $c->get(EmployeeLookup::class),
    $c->get(EmployeeOnboarding::class),
    $c->get(Database::class)
));

$container->set(RecruitmentController::class, static fn (Container $c): RecruitmentController => new RecruitmentController(
    $c->get(VacancyService::class),
    $c->get(CandidateService::class),
    $c->get(ApplicationService::class),
    $c->get(View::class)
));

$container->set(VacancyController::class, static fn (Container $c): VacancyController => new VacancyController(
    $c->get(VacancyService::class),
    $c->get(ApplicationService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

$container->set(CandidateController::class, static fn (Container $c): CandidateController => new CandidateController(
    $c->get(CandidateService::class),
    $c->get(ApplicationService::class),
    $c->get(View::class)
));

$container->set(ApplicationController::class, static fn (Container $c): ApplicationController => new ApplicationController(
    $c->get(ApplicationService::class),
    $c->get(AuditLogger::class),
    $c->get(View::class)
));

return $container;
