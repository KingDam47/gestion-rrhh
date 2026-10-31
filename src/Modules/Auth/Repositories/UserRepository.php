<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, name, email, password_hash, role, is_active, password_changed_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $userId): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, name, email, role, is_active, last_login_at, password_changed_at, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findWithHash(int $userId): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, name, email, password_hash, role, is_active, password_changed_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param list<int> $userIds
     * @return array<int, string> Nombres indexados por ID.
     */
    public function names(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $userIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($userIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($userIds), '?'));
        $statement = $this->database->connection()->prepare(
            'SELECT id, name FROM users WHERE id IN (' . $placeholders . ')'
        );
        $statement->execute($userIds);

        $names = [];
        foreach ($statement->fetchAll() as $row) {
            $names[(int) $row['id']] = (string) $row['name'];
        }

        return $names;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveById(int $userId): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, name, email, role, password_changed_at
             FROM users
             WHERE id = :id AND is_active = 1
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
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
     *     to: int
     * }
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $countStatement = $this->database->connection()->prepare(
            'SELECT COUNT(*) FROM users u' . $where
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = max(1, min($page, max(1, $pages)));
        $offset = ($page - 1) * $perPage;

        $statement = $this->database->connection()->prepare(
            'SELECT u.id, u.name, u.email, u.role, u.is_active, u.last_login_at, u.created_at, u.updated_at
             FROM users u'
            . $where
            . ' ORDER BY u.name, u.id
                LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * @return array{total: int, active: int, inactive: int, admins: int, active_admins: int, hr: int}
     */
    public function stats(): array
    {
        $row = $this->database->connection()->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(is_active = 1), 0) AS active,
                    COALESCE(SUM(is_active = 0), 0) AS inactive,
                    COALESCE(SUM(role = \'admin\'), 0) AS admins,
                    COALESCE(SUM(role = \'admin\' AND is_active = 1), 0) AS active_admins,
                    COALESCE(SUM(role = \'hr\'), 0) AS hr
             FROM users'
        )->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'admins' => (int) ($row['admins'] ?? 0),
            'active_admins' => (int) ($row['active_admins'] ?? 0),
            'hr' => (int) ($row['hr'] ?? 0),
        ];
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email';
        $parameters = ['email' => $email];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $name, string $email, string $passwordHash, string $role): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active)
             VALUES (:name, :email, :password_hash, :role, 1)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function update(int $userId, string $name, string $email, string $role): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'id' => $userId,
        ]);
    }

    public function setActive(int $userId, bool $active): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE users SET is_active = :is_active WHERE id = :id'
        );
        $statement->execute(['is_active' => $active ? 1 : 0, 'id' => $userId]);
    }

    /**
     * Bloquea las cuentas administradoras activas para que dos operaciones
     * concurrentes no puedan dejar el sistema sin administración.
     *
     * @return list<int>
     */
    public function lockActiveAdminIds(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT id
             FROM users
             WHERE role = \'admin\' AND is_active = 1
             ORDER BY id
             FOR UPDATE'
        );

        return array_map(
            static fn (array $row): int => (int) $row['id'],
            $statement->fetchAll()
        );
    }

    /**
     * Actualiza el hash y, por defecto, marca la contraseña como cambiada para
     * invalidar las sesiones abiertas con la contraseña anterior.
     *
     * @return string|null Fecha UTC del cambio, o null si no se marcó.
     */
    public function updatePasswordHash(int $userId, string $hash, bool $markChanged = true): ?string
    {
        $changedAt = $markChanged ? gmdate('Y-m-d H:i:s.u') : null;
        $sql = 'UPDATE users SET password_hash = :hash';

        if ($changedAt !== null) {
            $sql .= ', password_changed_at = :changed_at';
        }

        $sql .= ' WHERE id = :id';

        $parameters = ['hash' => $hash, 'id' => $userId];

        if ($changedAt !== null) {
            $parameters['changed_at'] = $changedAt;
        }

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($parameters);

        return $changedAt;
    }

    public function touchLastLogin(int $userId): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $statement->execute(['id' => $userId]);
    }

    /**
     * @param array{q?: string, role?: string|null, status?: string|null} $filters
     * @return array{0: string, 1: array<string, string|int>}
     */
    private function filterClause(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $conditions[] = '(u.name LIKE :search_name OR u.email LIKE :search_email)';
            $parameters['search_name'] = '%' . $search . '%';
            $parameters['search_email'] = '%' . $search . '%';
        }

        $role = $filters['role'] ?? null;
        if ($role !== null && $role !== '') {
            $conditions[] = 'u.role = :filter_role';
            $parameters['filter_role'] = (string) $role;
        }

        $status = $filters['status'] ?? null;
        if ($status !== null && $status !== '') {
            $conditions[] = 'u.is_active = :filter_status';
            $parameters['filter_status'] = $status === 'active' ? 1 : 0;
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }
}
