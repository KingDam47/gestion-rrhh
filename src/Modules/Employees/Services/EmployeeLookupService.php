<?php

declare(strict_types=1);

namespace App\Modules\Employees\Services;

use App\Modules\Employees\Contracts\EmployeeLookup;
use App\Modules\Employees\Repositories\DepartmentRepository;
use App\Modules\Employees\Repositories\EmployeeRepository;
use App\Modules\Employees\Repositories\PositionRepository;

final class EmployeeLookupService implements EmployeeLookup
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly DepartmentRepository $departments,
        private readonly PositionRepository $positions
    ) {
    }

    public function find(int $employeeId): ?array
    {
        return $this->employees->find($employeeId);
    }

    public function stats(): array
    {
        return $this->employees->stats();
    }

    public function findMany(array $employeeIds): array
    {
        return $this->employees->findMany($employeeIds);
    }

    public function activeEmployees(): array
    {
        return array_map(
            static fn (array $employee): array => $employee + [
                'full_name' => trim($employee['first_name'] . ' ' . $employee['last_name']),
            ],
            $this->employees->activeOptions()
        );
    }

    public function directory(): array
    {
        return array_map(
            static fn (array $employee): array => $employee + [
                'full_name' => trim($employee['first_name'] . ' ' . $employee['last_name']),
            ],
            $this->employees->directory()
        );
    }

    public function departments(): array
    {
        return array_map(
            static fn (array $department): array => [
                'id' => (int) $department['id'],
                'name' => (string) $department['name'],
                'is_active' => (int) $department['is_active'],
            ],
            $this->departments->options()
        );
    }

    public function positions(?int $departmentId = null): array
    {
        return $this->positions->options($departmentId);
    }
}
