<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Attendance\Services\LeaveRequestService;
use App\Modules\Attendance\Support\AttendanceData;
use App\Modules\Employees\Contracts\EmployeeLookup;
use App\Modules\Recruitment\Services\ApplicationService;
use App\Modules\Recruitment\Services\CandidateService;
use App\Modules\Recruitment\Services\VacancyService;

/**
 * Compone los indicadores del panel a partir de servicios públicos.
 *
 * No contiene SQL ni conoce repositorios: cada módulo sigue siendo dueño de
 * sus datos y el Dashboard solo agrega los conteos para la portada.
 */
final class DashboardSummaryService
{
    public function __construct(
        private readonly EmployeeLookup $employees,
        private readonly AttendanceService $attendance,
        private readonly LeaveRequestService $leaves,
        private readonly VacancyService $vacancies,
        private readonly CandidateService $candidates,
        private readonly ApplicationService $applications
    ) {
    }

    /**
     * @return array{
     *     today: string,
     *     month: string,
     *     month_label: string,
     *     employees: array{active: int, inactive: int, total: int},
     *     attendance: array{
     *         today: array{total: int, present: int, late: int, absent: int},
     *         month: array{total: int, present: int, late: int, absent: int}
     *     },
     *     leaves: array{total: int, pending: int, approved: int, rejected: int},
     *     vacancies: array{total: int, open: int, closed: int},
     *     candidates: array{total: int},
     *     applications: array{
     *         total: int,
     *         received: int,
     *         screening: int,
     *         interview: int,
     *         selected: int,
     *         rejected: int,
     *         pending_hire: int
     *     },
     *     in_process: int
     * }
     */
    public function summary(?string $today = null): array
    {
        $date = AttendanceData::dateOrNull($today) ?? date('Y-m-d');
        $month = substr($date, 0, 7);
        $range = AttendanceData::monthRange($month);
        $attendance = [
            'today' => $this->attendance->summary($date, $date),
            'month' => $this->attendance->summary($range['start'], $range['end']),
        ];
        $applications = $this->applications->stats();

        return [
            'today' => $date,
            'month' => $month,
            'month_label' => AttendanceData::monthLabel($month),
            'employees' => $this->employees->stats(),
            'attendance' => $attendance,
            'leaves' => $this->leaves->summary(),
            'vacancies' => $this->vacancies->stats(),
            'candidates' => $this->candidates->stats(),
            'applications' => $applications,
            'in_process' => (int) $applications['received']
                + (int) $applications['screening']
                + (int) $applications['interview'],
        ];
    }
}
