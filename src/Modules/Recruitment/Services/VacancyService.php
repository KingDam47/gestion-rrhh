<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Services;

use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Validator;
use App\Modules\Employees\Contracts\EmployeeLookup;
use App\Modules\Recruitment\Repositories\VacancyRepository;
use App\Modules\Recruitment\Support\RecruitmentData;

final class VacancyService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly VacancyRepository $vacancies,
        private readonly EmployeeLookup $employees
    ) {
    }

    /**
     * @param array{q?: string, status?: string|null, department_id?: string|int|null} $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     pages: int,
     *     from: int,
     *     to: int,
     *     filters: array{q: string, status: string|null, department_id: int|null},
     *     summary: array{total: int, open: int, closed: int}
     * }
     */
    public function search(array $filters, int $page): array
    {
        $filters = $this->normalizeFilters($filters);
        $result = $this->vacancies->paginate($filters, max(1, $page), self::PER_PAGE);
        $result['items'] = $this->enrich($result['items']);
        $result['filters'] = $filters;
        $result['summary'] = $this->vacancies->summary($filters);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $vacancy = $this->vacancies->find($id);

        if ($vacancy === null) {
            throw new NotFoundException('La vacante solicitada no existe.');
        }

        return $this->enrich([$vacancy])[0];
    }

    /**
     * @return array{
     *     departments: list<array<string, mixed>>,
     *     positions: list<array<string, mixed>>
     * }
     */
    public function formOptions(): array
    {
        return [
            'departments' => $this->employees->departments(),
            'positions' => $this->employees->positions(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filterDepartments(): array
    {
        return $this->employees->departments();
    }

    /**
     * @return array<string, mixed>
     */
    public function create(array $input, ?int $userId): array
    {
        [$data, $errors] = $this->prepare($input);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $id = $this->vacancies->create($data + [
            'status' => 'open',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return $this->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, array $input, ?int $userId): array
    {
        $current = $this->find($id);

        if ((string) $current['status'] === 'closed') {
            throw new BusinessRuleException(
                'Una vacante cerrada no se puede editar. Crea una vacante nueva si necesitas continuar el proceso.'
            );
        }

        [$data, $errors] = $this->prepare($input, $current);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->vacancies->update($id, $data + ['updated_by' => $userId]);

        return $this->find($id);
    }

    /**
     * @return array{vacancy: array<string, mixed>, changed: bool}
     */
    public function close(int $id, ?int $userId): array
    {
        $vacancy = $this->find($id);

        if ((string) $vacancy['status'] === 'closed') {
            return ['vacancy' => $vacancy, 'changed' => false];
        }

        $this->vacancies->close($id, date('Y-m-d'), $userId);

        return ['vacancy' => $this->find($id), 'changed' => true];
    }

    /**
     * @return array{total: int, open: int, closed: int}
     */
    public function stats(): array
    {
        return $this->vacancies->counts();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentOpen(int $limit = 5): array
    {
        return $this->enrich($this->vacancies->recentOpen($limit));
    }

    /**
     * @param array{q?: string, status?: string|null, department_id?: string|int|null} $filters
     * @return array{q: string, status: string|null, department_id: int|null}
     */
    private function normalizeFilters(array $filters): array
    {
        $q = is_scalar($filters['q'] ?? null) ? (string) $filters['q'] : '';
        $status = is_scalar($filters['status'] ?? null) ? (string) $filters['status'] : null;
        $departmentId = is_scalar($filters['department_id'] ?? null)
            ? (int) $filters['department_id']
            : 0;

        return [
            'q' => mb_substr(trim($q), 0, 100),
            'status' => in_array($status, ['open', 'closed'], true) ? $status : null,
            'department_id' => $departmentId > 0 ? $departmentId : null,
        ];
    }

    /**
     * @return array{
     *     0: array{
     *         title: string,
     *         description: string|null,
     *         department_id: int|string,
     *         position_id: int|string|null,
     *         opening_date: string,
     *         closing_date: string|null
     *     },
     *     1: array<string, string>
     * }
     */
    private function prepare(array $input, ?array $current = null): array
    {
        $closingDate = RecruitmentData::date($input['closing_date'] ?? null);

        $data = [
            'title' => RecruitmentData::name($input['title'] ?? null),
            'description' => RecruitmentData::notes($input['description'] ?? null),
            'department_id' => trim((string) ($input['department_id'] ?? '')),
            'position_id' => trim((string) ($input['position_id'] ?? '')),
            'opening_date' => RecruitmentData::date($input['opening_date'] ?? null),
            'closing_date' => $closingDate,
        ];

        $validator = new Validator($data, [
            'title' => 'título',
            'description' => 'descripción',
            'department_id' => 'departamento',
            'position_id' => 'cargo',
            'opening_date' => 'fecha de apertura',
            'closing_date' => 'fecha de cierre',
        ]);

        $errors = $validator->validate([
            'title' => ['required', 'max:150'],
            'description' => ['nullable', 'max:2000'],
            'department_id' => ['required', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'opening_date' => ['required', 'date'],
            'closing_date' => ['nullable', 'date'],
        ]);

        if ($errors !== []) {
            return [$data, $errors];
        }

        $departmentId = (int) $data['department_id'];
        $positionId = $data['position_id'] === '' ? null : (int) $data['position_id'];
        $data['department_id'] = $departmentId;
        $data['position_id'] = $positionId;
        $data['closing_date'] = $closingDate === '' ? null : $closingDate;

        $departments = $this->departmentMap();
        $department = $departments[$departmentId] ?? null;

        if ($department === null) {
            $errors['department_id'] = 'El departamento seleccionado no existe.';
        } elseif (
            (int) $department['is_active'] !== 1
            && ($current === null || $departmentId !== (int) $current['department_id'])
        ) {
            $errors['department_id'] = 'El departamento seleccionado está inactivo.';
        }

        if ($positionId !== null) {
            $positions = $this->positionMap();
            $position = $positions[$positionId] ?? null;

            if ($position === null) {
                $errors['position_id'] = 'El cargo seleccionado no existe.';
            } elseif ((int) $position['department_id'] !== $departmentId) {
                $errors['position_id'] = 'El cargo no pertenece al departamento seleccionado.';
            } elseif (
                (int) $position['is_active'] !== 1
                && ($current === null || $positionId !== (int) $current['position_id'])
            ) {
                $errors['position_id'] = 'El cargo seleccionado está inactivo.';
            }
        }

        if (
            $data['closing_date'] !== null
            && $data['closing_date'] < $data['opening_date']
        ) {
            $errors['closing_date'] = 'La fecha de cierre no puede ser anterior a la fecha de apertura.';
        }

        return [$data, $errors];
    }

    /**
     * @param list<array<string, mixed>> $vacancies
     * @return list<array<string, mixed>>
     */
    private function enrich(array $vacancies): array
    {
        if ($vacancies === []) {
            return [];
        }

        $departments = $this->departmentMap();
        $positions = $this->positionMap();

        foreach ($vacancies as &$vacancy) {
            $department = $departments[(int) $vacancy['department_id']] ?? null;
            $position = $vacancy['position_id'] !== null
                ? ($positions[(int) $vacancy['position_id']] ?? null)
                : null;

            $vacancy['department_name'] = $department['name'] ?? 'Departamento no disponible';
            $vacancy['department_active'] = $department['is_active'] ?? 0;
            $vacancy['position_name'] = $position['name'] ?? null;
            $vacancy['position_active'] = $position['is_active'] ?? null;
        }
        unset($vacancy);

        return $vacancies;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function departmentMap(): array
    {
        $map = [];
        foreach ($this->employees->departments() as $department) {
            $map[(int) $department['id']] = $department;
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function positionMap(): array
    {
        $map = [];
        foreach ($this->employees->positions() as $position) {
            $map[(int) $position['id']] = $position;
        }

        return $map;
    }
}
