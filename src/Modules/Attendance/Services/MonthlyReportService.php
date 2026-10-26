<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\Attendance\Repositories\LeaveRequestRepository;
use App\Modules\Attendance\Support\AttendanceData;
use App\Modules\Auth\Contracts\UserLookup;
use App\Modules\Employees\Contracts\EmployeeLookup;

final class MonthlyReportService
{
    public function __construct(
        private readonly AttendanceRepository $attendance,
        private readonly LeaveRequestRepository $leaves,
        private readonly EmployeeLookup $employees,
        private readonly UserLookup $users
    ) {
    }

    /**
     * @param array{month?: string|null, department_id?: string|int|null} $filters
     * @return array{
     *     month: string,
     *     month_label: string,
     *     range: array{start: string, end: string},
     *     filters: array{month: string, department_id: int|null},
     *     departments: list<array<string, mixed>>,
     *     attendance: array{total: int, present: int, late: int, absent: int},
     *     leaves: array{total: int, pending: int, approved: int, rejected: int},
     *     rows: list<array<string, mixed>>,
     *     leave_rows: list<array<string, mixed>>
     * }
     */
    public function report(array $filters): array
    {
        $month = AttendanceData::month(
            is_scalar($filters['month'] ?? null) ? (string) $filters['month'] : null
        );
        $departments = $this->employees->departments();
        $departmentId = is_scalar($filters['department_id'] ?? null)
            ? (int) $filters['department_id']
            : 0;

        $departmentExists = false;
        foreach ($departments as $department) {
            if ((int) $department['id'] === $departmentId) {
                $departmentExists = true;
                break;
            }
        }

        if ($departmentId <= 0 || !$departmentExists) {
            $departmentId = 0;
        }

        $directory = $this->employees->directory();

        if ($departmentId > 0) {
            $directory = array_values(array_filter(
                $directory,
                static fn (array $employee): bool => (int) $employee['department_id'] === $departmentId
            ));
        }

        $employeeIds = array_map(
            static fn (array $employee): int => (int) $employee['id'],
            $directory
        );
        $range = AttendanceData::monthRange($month);
        $scopedIds = $departmentId > 0 ? $employeeIds : null;
        $attendanceRecords = $this->attendance->monthRecords($range['start'], $range['end'], $scopedIds);
        $leaveRequests = $this->leaves->monthRequests($range['start'], $range['end'], $scopedIds);

        $rows = [];
        foreach ($directory as $employee) {
            $rows[(int) $employee['id']] = [
                'employee_id' => (int) $employee['id'],
                'employee_name' => trim(
                    (string) $employee['last_name'] . ', ' . (string) $employee['first_name']
                ),
                'document' => (string) $employee['document'],
                'department_name' => (string) $employee['department_name'],
                'employee_status' => (string) $employee['status'],
                'attendance_total' => 0,
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'leave_total' => 0,
                'leave_pending' => 0,
                'leave_approved' => 0,
                'leave_rejected' => 0,
            ];
        }

        $attendanceTotals = ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];

        foreach ($attendanceRecords as $record) {
            $employeeId = (int) $record['employee_id'];
            $status = (string) $record['status'];

            if (!isset($rows[$employeeId]) || !in_array($status, ['present', 'late', 'absent'], true)) {
                continue;
            }

            $rows[$employeeId]['attendance_total']++;
            $rows[$employeeId][$status]++;
            $attendanceTotals['total']++;
            $attendanceTotals[$status]++;
        }

        $leaveTotals = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        $reviewerIds = [];
        foreach ($leaveRequests as $request) {
            if ($request['reviewed_by'] !== null) {
                $reviewerIds[] = (int) $request['reviewed_by'];
            }
        }
        $reviewerNames = $this->users->names($reviewerIds);

        $leaveRows = [];
        foreach ($leaveRequests as $request) {
            $employeeId = (int) $request['employee_id'];
            $status = (string) $request['status'];

            if (!isset($rows[$employeeId]) || !in_array($status, ['pending', 'approved', 'rejected'], true)) {
                continue;
            }

            $rows[$employeeId]['leave_total']++;
            $rows[$employeeId]['leave_' . $status]++;
            $leaveTotals['total']++;
            $leaveTotals[$status]++;

            $request['employee_name'] = $rows[$employeeId]['employee_name'];
            $request['department_name'] = $rows[$employeeId]['department_name'];
            $request['days'] = AttendanceData::daysBetween(
                $request['start_date'] !== null ? (string) $request['start_date'] : null,
                $request['end_date'] !== null ? (string) $request['end_date'] : null
            );
            $request['reviewer_name'] = $request['reviewed_by'] === null
                ? null
                : ($reviewerNames[(int) $request['reviewed_by']] ?? 'Usuario no disponible');
            $leaveRows[] = $request;
        }

        $visibleRows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => (int) $row['attendance_total'] > 0
                || (int) $row['leave_total'] > 0
        ));

        return [
            'month' => $month,
            'month_label' => AttendanceData::monthLabel($month),
            'range' => $range,
            'filters' => [
                'month' => $month,
                'department_id' => $departmentId > 0 ? $departmentId : null,
            ],
            'departments' => $departments,
            'attendance' => $attendanceTotals,
            'leaves' => $leaveTotals,
            'rows' => $visibleRows,
            'leave_rows' => $leaveRows,
        ];
    }
}
