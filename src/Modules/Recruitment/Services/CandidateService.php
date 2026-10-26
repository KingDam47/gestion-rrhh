<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Services;

use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Logger;
use App\Core\Validator;
use App\Modules\Recruitment\Repositories\CandidateRepository;
use App\Modules\Recruitment\Support\RecruitmentData;
use PDOException;

final class CandidateService
{
    private const PER_PAGE = 10;

    public function __construct(private readonly CandidateRepository $candidates)
    {
    }

    /**
     * @param array{q?: string} $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     pages: int,
     *     from: int,
     *     to: int,
     *     filters: array{q: string},
     *     summary: array{total: int, with_applications: int, selected: int}
     * }
     */
    public function search(array $filters, int $page): array
    {
        $normalized = [
            'q' => mb_substr(trim(is_scalar($filters['q'] ?? null) ? (string) $filters['q'] : ''), 0, 100),
        ];

        $result = $this->candidates->paginate($normalized, max(1, $page), self::PER_PAGE);
        $result['filters'] = $normalized;
        $result['summary'] = $this->candidates->summary($normalized);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $candidate = $this->candidates->find($id);

        if ($candidate === null) {
            throw new NotFoundException('El candidato solicitado no existe.');
        }

        return $candidate;
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

        try {
            $id = $this->candidates->create($data + [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, array $input, ?int $userId): array
    {
        $current = $this->find($id);
        [$data, $errors] = $this->prepare($input, $current);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $this->candidates->update($id, $data + ['updated_by' => $userId]);
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array{total: int}
     */
    public function stats(): array
    {
        return $this->candidates->counts();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function options(): array
    {
        return $this->candidates->options();
    }

    /**
     * @return array{
     *     0: array{
     *         first_name: string,
     *         last_name: string,
     *         email: string|null,
     *         phone: string|null,
     *         notes: string|null
     *     },
     *     1: array<string, string>
     * }
     */
    private function prepare(array $input, ?array $current = null): array
    {
        $data = [
            'first_name' => RecruitmentData::name($input['first_name'] ?? null),
            'last_name' => RecruitmentData::name($input['last_name'] ?? null),
            'email' => RecruitmentData::email($input['email'] ?? null),
            'phone' => RecruitmentData::phone($input['phone'] ?? null),
            'notes' => RecruitmentData::notes($input['notes'] ?? null),
        ];

        $validator = new Validator($data, [
            'first_name' => 'nombres',
            'last_name' => 'apellidos',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'notes' => 'observaciones',
        ]);

        $errors = $validator->validate([
            'first_name' => ['required', 'max:80'],
            'last_name' => ['required', 'max:80'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'max:30', 'regex:/^[0-9+().\s-]{6,30}$/u'],
            'notes' => ['nullable', 'max:1000'],
        ]);

        if ($errors !== []) {
            return [$data, $errors];
        }

        $exceptId = $current !== null ? (int) $current['id'] : null;

        if ($this->candidates->emailExists((string) $data['email'], $exceptId)) {
            $errors['email'] = 'Ya existe un candidato con ese correo electrónico.';
        }

        return [$data, $errors];
    }

    private function saveError(PDOException $exception): ValidationException
    {
        Logger::error('No se pudo guardar el candidato.', [
            'type' => $exception::class,
            'sql_state' => (string) $exception->getCode(),
        ]);

        return new ValidationException([
            'email' => 'Ya existe un candidato con ese correo electrónico.',
        ]);
    }
}
