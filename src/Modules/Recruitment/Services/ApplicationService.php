<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Services;

use App\Core\Database;
use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Logger;
use App\Core\Validator;
use App\Modules\Employees\Contracts\EmployeeLookup;
use App\Modules\Employees\Contracts\EmployeeOnboarding;
use App\Modules\Recruitment\Repositories\ApplicationRepository;
use App\Modules\Recruitment\Repositories\CandidateRepository;
use App\Modules\Recruitment\Repositories\VacancyRepository;
use App\Modules\Recruitment\Support\ApplicationStatus;
use App\Modules\Recruitment\Support\RecruitmentData;
use PDOException;
use Throwable;

final class ApplicationService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly ApplicationRepository $applications,
        private readonly VacancyRepository $vacancies,
        private readonly CandidateRepository $candidates,
        private readonly EmployeeLookup $employees,
        private readonly EmployeeOnboarding $onboarding,
        private readonly Database $database
    ) {
    }

    /**
     * @param array{q?: string, vacancy_id?: string|int|null, status?: string|null} $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     pages: int,
     *     from: int,
     *     to: int,
     *     filters: array{q: string, vacancy_id: int|null, status: string|null},
     *     summary: array{total: int, received: int, screening: int, interview: int, selected: int, rejected: int, pending_hire: int}
     * }
     */
    public function search(array $filters, int $page): array
    {
        $filters = $this->normalizeFilters($filters);
        $result = $this->applications->paginate($filters, max(1, $page), self::PER_PAGE);
        $result['items'] = $this->enrich($result['items']);
        $result['filters'] = $filters;
        $result['summary'] = $this->applications->summary($filters);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $application = $this->applications->find($id);

        if ($application === null) {
            throw new NotFoundException('La postulación solicitada no existe.');
        }

        return $this->enrich([$application])[0];
    }

    /**
     * @return array{
     *     candidates: list<array<string, mixed>>,
     *     vacancies: list<array<string, mixed>>
     * }
     */
    public function formOptions(): array
    {
        return [
            'candidates' => $this->candidates->options(),
            'vacancies' => $this->enrichVacancyOptions($this->vacancies->options(true)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filterVacancies(): array
    {
        return $this->enrichVacancyOptions($this->vacancies->options());
    }

    /**
     * @return array{
     *     departments: list<array<string, mixed>>,
     *     positions: list<array<string, mixed>>
     * }
     */
    public function hireOptions(): array
    {
        $departments = array_values(array_filter(
            $this->employees->departments(),
            static fn (array $department): bool => (int) $department['is_active'] === 1
        ));

        $activeDepartmentIds = array_map(
            static fn (array $department): int => (int) $department['id'],
            $departments
        );

        $positions = array_values(array_filter(
            $this->employees->positions(),
            static fn (array $position): bool => (int) $position['is_active'] === 1
                && in_array((int) $position['department_id'], $activeDepartmentIds, true)
        ));

        return [
            'departments' => $departments,
            'positions' => $positions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function create(array $input, ?int $userId): array
    {
        $data = [
            'candidate_id' => trim((string) ($input['candidate_id'] ?? '')),
            'vacancy_id' => trim((string) ($input['vacancy_id'] ?? '')),
            'notes' => RecruitmentData::notes($input['notes'] ?? null),
        ];

        $validator = new Validator($data, [
            'candidate_id' => 'candidato',
            'vacancy_id' => 'vacante',
            'notes' => 'observaciones',
        ]);

        $errors = $validator->validate([
            'candidate_id' => ['required', 'integer'],
            'vacancy_id' => ['required', 'integer'],
            'notes' => ['nullable', 'max:1000'],
        ]);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $candidateId = (int) $data['candidate_id'];
        $vacancyId = (int) $data['vacancy_id'];

        if ($this->candidates->find($candidateId) === null) {
            $errors['candidate_id'] = 'El candidato seleccionado no existe.';
        }

        $vacancy = $this->vacancies->find($vacancyId);

        if ($vacancy === null) {
            $errors['vacancy_id'] = 'La vacante seleccionada no existe.';
        } elseif ((string) $vacancy['status'] !== 'open') {
            $errors['vacancy_id'] = 'Solo se aceptan postulaciones para vacantes abiertas.';
        } elseif ($this->applications->findByCandidateVacancy($candidateId, $vacancyId)) {
            $errors['vacancy_id'] = 'Ya existe una postulación de este candidato para esa vacante.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $id = $this->applications->create([
                'candidate_id' => $candidateId,
                'vacancy_id' => $vacancyId,
                'applied_at' => date('Y-m-d H:i:s'),
                'status' => ApplicationStatus::RECEIVED,
                'notes' => $data['notes'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        } catch (PDOException $exception) {
            Logger::error('No se pudo guardar la postulación.', [
                'type' => $exception::class,
                'sql_state' => (string) $exception->getCode(),
            ]);

            throw new ValidationException([
                'vacancy_id' => 'Ya existe una postulación de este candidato para esa vacante.',
            ]);
        }

        return $this->find($id);
    }

    /**
     * @return array{
     *     application: array<string, mixed>,
     *     changed: bool,
     *     reason: string|null
     * }
     */
    public function transition(int $id, array $input, ?int $userId): array
    {
        $application = $this->find($id);
        $currentStatus = (string) $application['status'];

        $data = [
            'status' => trim((string) ($input['status'] ?? '')),
            'notes' => RecruitmentData::notes($input['notes'] ?? null),
        ];

        $validator = new Validator($data, [
            'status' => 'estado',
            'notes' => 'observaciones',
        ]);

        $errors = $validator->validate([
            'status' => ['required', 'in:received,screening,interview,selected,rejected'],
            'notes' => ['nullable', 'max:1000'],
        ]);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $newStatus = (string) $data['status'];

        if ($newStatus === $currentStatus) {
            return ['application' => $application, 'changed' => false, 'reason' => 'same'];
        }

        if (ApplicationStatus::isFinal($currentStatus)) {
            return ['application' => $application, 'changed' => false, 'reason' => 'final'];
        }

        if (!ApplicationStatus::allows($currentStatus, $newStatus)) {
            throw new ValidationException([
                'status' => 'La transición solicitada no es válida para el estado actual.',
            ]);
        }

        $this->applications->updateStatus($id, $newStatus, $data['notes'], $userId);

        return ['application' => $this->find($id), 'changed' => true, 'reason' => null];
    }

    /**
     * @return array<string, mixed>
     */
    public function hireContext(int $id): array
    {
        $application = $this->find($id);
        $this->assertHireable($application);

        return $application;
    }

    /**
     * @return array{employee: array<string, mixed>, application: array<string, mixed>}
     */
    public function hire(int $id, array $input, ?int $userId): array
    {
        $application = $this->find($id);
        $this->assertHireable($application);

        $pdo = $this->database->connection();
        $pdo->beginTransaction();

        try {
            $employee = $this->onboarding->create($input, $userId);
            $this->applications->setEmployeeId($id, (int) $employee['id'], $userId);
            $pdo->commit();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            Logger::error('No se pudo completar el alta guiada.', [
                'type' => $exception::class,
                'sql_state' => (string) $exception->getCode(),
            ]);

            throw new ValidationException(
                [],
                'No se pudo completar el alta. Comprueba los datos e inténtalo de nuevo.'
            );
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        return [
            'employee' => $employee,
            'application' => $this->find($id),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forVacancy(int $vacancyId): array
    {
        return $this->enrich($this->applications->byVacancy($vacancyId));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forCandidate(int $candidateId): array
    {
        return $this->enrich($this->applications->byCandidate($candidateId));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentSelected(int $limit = 5): array
    {
        return $this->enrich($this->applications->recentSelected($limit));
    }

    /**
     * @return array{
     *     total: int,
     *     received: int,
     *     screening: int,
     *     interview: int,
     *     selected: int,
     *     rejected: int,
     *     pending_hire: int
     * }
     */
    public function stats(): array
    {
        return $this->applications->summary([
            'q' => '',
            'vacancy_id' => null,
            'status' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $application
     */
    private function assertHireable(array $application): void
    {
        if ((string) $application['status'] !== ApplicationStatus::SELECTED) {
            throw new BusinessRuleException(
                'Solo se puede crear un empleado desde una postulación seleccionada.'
            );
        }

        if ($application['employee_id'] !== null) {
            throw new BusinessRuleException(
                'Esta postulación ya tiene un empleado asociado.'
            );
        }
    }

    /**
     * @param array{q?: string, vacancy_id?: string|int|null, status?: string|null} $filters
     * @return array{q: string, vacancy_id: int|null, status: string|null}
     */
    private function normalizeFilters(array $filters): array
    {
        $q = is_scalar($filters['q'] ?? null) ? (string) $filters['q'] : '';
        $vacancyId = is_scalar($filters['vacancy_id'] ?? null)
            ? (int) $filters['vacancy_id']
            : 0;
        $status = is_scalar($filters['status'] ?? null) ? (string) $filters['status'] : null;

        return [
            'q' => mb_substr(trim($q), 0, 100),
            'vacancy_id' => $vacancyId > 0 ? $vacancyId : null,
            'status' => in_array($status, ApplicationStatus::all(), true) ? $status : null,
        ];
    }

    /**
     * @param list<array<string, mixed>> $applications
     * @return list<array<string, mixed>>
     */
    private function enrich(array $applications): array
    {
        if ($applications === []) {
            return [];
        }

        $departments = [];
        foreach ($this->employees->departments() as $department) {
            $departments[(int) $department['id']] = $department;
        }

        $positions = [];
        foreach ($this->employees->positions() as $position) {
            $positions[(int) $position['id']] = $position;
        }

        $employeeIds = [];
        foreach ($applications as $application) {
            if ($application['employee_id'] !== null) {
                $employeeIds[] = (int) $application['employee_id'];
            }
        }

        $employees = $this->employees->findMany($employeeIds);

        foreach ($applications as &$application) {
            $department = $departments[(int) $application['vacancy_department_id']] ?? null;
            $position = $application['vacancy_position_id'] !== null
                ? ($positions[(int) $application['vacancy_position_id']] ?? null)
                : null;
            $employee = $application['employee_id'] !== null
                ? ($employees[(int) $application['employee_id']] ?? null)
                : null;

            $application['candidate_name'] = trim(
                (string) $application['candidate_last_name'] . ', '
                . (string) $application['candidate_first_name']
            );
            $application['candidate_name_natural'] = trim(
                (string) $application['candidate_first_name'] . ' '
                . (string) $application['candidate_last_name']
            );
            $application['vacancy_department_name'] = $department['name']
                ?? 'Departamento no disponible';
            $application['vacancy_position_name'] = $position['name'] ?? null;
            $application['employee_name'] = $employee !== null
                ? trim((string) $employee['last_name'] . ', ' . (string) $employee['first_name'])
                : null;
            $application['employee_document'] = $employee['document'] ?? null;
        }
        unset($application);

        return $applications;
    }

    /**
     * @param list<array<string, mixed>> $vacancies
     * @return list<array<string, mixed>>
     */
    private function enrichVacancyOptions(array $vacancies): array
    {
        if ($vacancies === []) {
            return [];
        }

        $departments = [];
        foreach ($this->employees->departments() as $department) {
            $departments[(int) $department['id']] = $department;
        }

        foreach ($vacancies as &$vacancy) {
            $vacancy['department_name'] = $departments[(int) $vacancy['department_id']]['name']
                ?? 'Departamento no disponible';
        }
        unset($vacancy);

        return $vacancies;
    }
}
