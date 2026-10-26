<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Database;
use App\Core\Exception\BusinessRuleException;
use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Logger;
use App\Core\Validator;
use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\Auth\Support\UserData;
use PDOException;
use Throwable;

final class UserService
{
    private const PER_PAGE = 10;
    private const ROLES = ['admin', 'hr'];

    public function __construct(
        private readonly UserRepository $users,
        private readonly Database $database
    ) {
    }

    /**
     * @param array{q?: string, role?: string|null, status?: string|null} $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     pages: int,
     *     from: int,
     *     to: int,
     *     filters: array{q: string, role: string|null, status: string|null},
     *     stats: array{total: int, active: int, inactive: int, admins: int, active_admins: int, hr: int}
     * }
     */
    public function search(array $filters, int $page): array
    {
        $filters = $this->normalizeFilters($filters);
        $result = $this->users->paginate($filters, max(1, $page), self::PER_PAGE);
        $result['filters'] = $filters;
        $result['stats'] = $this->users->stats();

        return $result;
    }

    /**
     * @return array{total: int, active: int, inactive: int, admins: int, active_admins: int, hr: int}
     */
    public function stats(): array
    {
        return $this->users->stats();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $user = $this->users->find($id);

        if ($user === null) {
            throw new NotFoundException('El usuario solicitado no existe.');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public function create(array $input): array
    {
        [$data, $errors] = $this->prepare($input);

        $password = (string) ($input['password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? '');
        $errors += $this->validatePassword($password, $confirmation);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $id = $this->users->create(
                $data['name'],
                $data['email'],
                password_hash($password, PASSWORD_DEFAULT),
                $data['role']
            );
        } catch (PDOException $exception) {
            throw $this->saveError($exception);
        }

        return $this->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, array $input): array
    {
        $pdo = $this->database->connection();
        $pdo->beginTransaction();

        try {
            $activeAdminIds = $this->users->lockActiveAdminIds();
            $current = $this->users->find($id);

            if ($current === null) {
                $pdo->rollBack();
                throw new NotFoundException('El usuario solicitado no existe.');
            }

            [$data, $errors] = $this->prepare($input, $id);

            if ($errors === []) {
                $willBeActive = (int) $current['is_active'] === 1;

                if ($this->wouldRemoveLastAdmin($current, $data['role'], $willBeActive, $activeAdminIds)) {
                    $errors['role'] = 'No puedes quitar el rol de administrador al último administrador activo.';
                }
            }

            if ($errors !== []) {
                $pdo->rollBack();
                throw new ValidationException($errors);
            }

            $this->users->update($id, $data['name'], $data['email'], $data['role']);
            $pdo->commit();
        } catch (ValidationException|NotFoundException $exception) {
            $this->rollBack($pdo);
            throw $exception;
        } catch (PDOException $exception) {
            $this->rollBack($pdo);
            throw $this->saveError($exception);
        } catch (Throwable $exception) {
            $this->rollBack($pdo);
            throw $exception;
        }

        return $this->find($id);
    }

    /**
     * @return array{user: array<string, mixed>, changed: bool}
     */
    public function setActive(int $id, bool $active, int $actorId): array
    {
        $current = $this->find($id);

        if ((bool) $current['is_active'] === $active) {
            return ['user' => $current, 'changed' => false];
        }

        $pdo = $this->database->connection();
        $pdo->beginTransaction();

        try {
            $activeAdminIds = $this->users->lockActiveAdminIds();
            $current = $this->users->find($id);

            if ($current === null) {
                $pdo->rollBack();
                throw new NotFoundException('El usuario solicitado no existe.');
            }

            if (!$active && $id === $actorId) {
                $pdo->rollBack();
                throw new BusinessRuleException('No puedes desactivar tu propia cuenta.');
            }

            if ($this->wouldRemoveLastAdmin($current, (string) $current['role'], $active, $activeAdminIds)) {
                $pdo->rollBack();
                throw new BusinessRuleException('No puedes desactivar al último administrador activo.');
            }

            $this->users->setActive($id, $active);
            $pdo->commit();
        } catch (BusinessRuleException|NotFoundException $exception) {
            $this->rollBack($pdo);
            throw $exception;
        } catch (Throwable $exception) {
            $this->rollBack($pdo);
            throw $exception;
        }

        return ['user' => $this->find($id), 'changed' => true];
    }

    /**
     * @return array<string, mixed>
     */
    public function resetPassword(int $id, array $input): array
    {
        $user = $this->find($id);
        $password = (string) ($input['password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? '');
        $errors = $this->validatePassword($password, $confirmation);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $this->users->updatePasswordHash($id, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $exception) {
            throw $this->saveError($exception, null);
        }

        return $user;
    }

    public function changeOwnPassword(int $id, array $input): void
    {
        $user = $this->users->findWithHash($id);

        if ($user === null) {
            throw new NotFoundException('La cuenta solicitada no existe.');
        }

        $currentPassword = (string) ($input['current_password'] ?? '');
        $password = (string) ($input['password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? '');

        $validator = new Validator(
            [
                'current_password' => $currentPassword,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'current_password' => 'contraseña actual',
                'password' => 'nueva contraseña',
                'password_confirmation' => 'confirmación de la nueva contraseña',
            ]
        );

        $errors = $validator->validate([
            'current_password' => ['required', 'max:255'],
            'password' => ['required', 'min:10', 'max:255'],
            'password_confirmation' => ['required', 'same:password'],
        ]);

        if (!isset($errors['current_password']) && !password_verify($currentPassword, (string) $user['password_hash'])) {
            $errors['current_password'] = 'La contraseña actual no es correcta.';
        }

        if (
            !isset($errors['password'])
            && password_verify($password, (string) $user['password_hash'])
        ) {
            $errors['password'] = 'La nueva contraseña debe ser diferente de la actual.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        try {
            $this->users->updatePasswordHash($id, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $exception) {
            throw $this->saveError($exception, null);
        }
    }

    /**
     * @param array{q?: string, role?: string|null, status?: string|null} $filters
     * @return array{q: string, role: string|null, status: string|null}
     */
    private function normalizeFilters(array $filters): array
    {
        $q = is_scalar($filters['q'] ?? null) ? (string) $filters['q'] : '';
        $role = is_scalar($filters['role'] ?? null) ? (string) $filters['role'] : null;
        $status = is_scalar($filters['status'] ?? null) ? (string) $filters['status'] : null;

        return [
            'q' => mb_substr(trim($q), 0, 100),
            'role' => in_array($role, self::ROLES, true) ? $role : null,
            'status' => in_array($status, ['active', 'inactive'], true) ? $status : null,
        ];
    }

    /**
     * @return array{0: array{name: string, email: string, role: string}, 1: array<string, string>}
     */
    private function prepare(array $input, ?int $exceptId = null): array
    {
        $data = [
            'name' => UserData::name((string) ($input['name'] ?? '')),
            'email' => UserData::email((string) ($input['email'] ?? '')),
            'role' => UserData::role((string) ($input['role'] ?? '')),
        ];

        $validator = new Validator($data, [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'role' => 'rol',
        ]);

        $errors = $validator->validate([
            'name' => ['required', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', 'in:admin,hr'],
        ]);

        if (
            !isset($errors['email'])
            && $data['email'] !== ''
            && $this->users->emailExists($data['email'], $exceptId)
        ) {
            $errors['email'] = 'Ya existe una cuenta con ese correo electrónico.';
        }

        return [$data, $errors];
    }

    /**
     * @return array<string, string>
     */
    private function validatePassword(string $password, string $confirmation): array
    {
        $validator = new Validator(
            ['password' => $password, 'password_confirmation' => $confirmation],
            [
                'password' => 'contraseña',
                'password_confirmation' => 'confirmación de la contraseña',
            ]
        );

        return $validator->validate([
            'password' => ['required', 'min:10', 'max:255'],
            'password_confirmation' => ['required', 'same:password'],
        ]);
    }

    /**
     * @param list<int> $activeAdminIds
     */
    private function wouldRemoveLastAdmin(
        array $current,
        string $newRole,
        bool $newActive,
        array $activeAdminIds
    ): bool {
        $isActiveAdmin = (string) $current['role'] === 'admin' && (int) $current['is_active'] === 1;

        if (!$isActiveAdmin || ($newRole === 'admin' && $newActive)) {
            return false;
        }

        foreach ($activeAdminIds as $activeAdminId) {
            if ($activeAdminId !== (int) $current['id']) {
                return false;
            }
        }

        return true;
    }

    private function saveError(PDOException $exception, ?string $field = 'email'): ValidationException
    {
        Logger::error('No se pudo guardar el usuario.', [
            'type' => $exception::class,
            'sql_state' => (string) $exception->getCode(),
        ]);

        $errors = $field === null
            ? []
            : [$field => 'No se pudo guardar la cuenta. Comprueba que el correo no esté repetido.'];

        return new ValidationException($errors, 'No se pudo guardar la cuenta.');
    }

    private function rollBack(\PDO $pdo): void
    {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
