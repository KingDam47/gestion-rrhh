<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Repositories;

use App\Core\Database;
use PDO;

final class CandidateRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @param array{q: string} $filters
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
            'SELECT COUNT(*) FROM candidates c' . $where
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = $pages === 0 ? 1 : min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->database->connection()->prepare(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM applications a WHERE a.candidate_id = c.id) AS application_count,
                    (SELECT COUNT(*) FROM applications a
                      WHERE a.candidate_id = c.id AND a.status = \'selected\') AS selected_count
             FROM candidates c' . $where . '
             ORDER BY c.last_name, c.first_name
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
            'to' => $total === 0 ? 0 : min($offset + $perPage, $total),
        ];
    }

    /**
     * @param array{q: string} $filters
     * @return array{total: int, with_applications: int, selected: int}
     */
    public function summary(array $filters): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $statement = $this->database->connection()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM applications a WHERE a.candidate_id = c.id
                    ) THEN 1 ELSE 0 END) AS with_applications,
                    SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM applications a
                        WHERE a.candidate_id = c.id AND a.status = \'selected\'
                    ) THEN 1 ELSE 0 END) AS selected
             FROM candidates c' . $where
        );
        $statement->execute($parameters);
        $row = $statement->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'with_applications' => (int) ($row['with_applications'] ?? 0),
            'selected' => (int) ($row['selected'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM applications a WHERE a.candidate_id = c.id) AS application_count
             FROM candidates c
             WHERE c.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $parameters = [];
        foreach (array_values($ids) as $index => $id) {
            $key = 'candidate_' . $index;
            $placeholders[] = ':' . $key;
            $parameters[$key] = (int) $id;
        }

        $statement = $this->database->connection()->prepare(
            'SELECT * FROM candidates WHERE id IN (' . implode(', ', $placeholders) . ')'
        );
        $statement->execute($parameters);

        $result = [];
        foreach ($statement->fetchAll() as $row) {
            $result[(int) $row['id']] = $row;
        }

        return $result;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM candidates WHERE email = :email';
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

    public function create(array $data): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO candidates
                (first_name, last_name, email, phone, notes, created_by, updated_by)
             VALUES
                (:first_name, :last_name, :email, :phone, :notes, :created_by, :updated_by)'
        );
        $statement->execute($data);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE candidates
             SET first_name = :first_name,
                 last_name = :last_name,
                 email = :email,
                 phone = :phone,
                 notes = :notes,
                 updated_by = :updated_by
             WHERE id = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function options(): array
    {
        return $this->database->connection()->query(
            'SELECT id, first_name, last_name, email FROM candidates ORDER BY last_name, first_name'
        )->fetchAll();
    }

    /**
     * @return array{total: int}
     */
    public function counts(): array
    {
        $total = (int) $this->database->connection()
            ->query('SELECT COUNT(*) FROM candidates')
            ->fetchColumn();

        return ['total' => $total];
    }

    /**
     * @param array{q: string} $filters
     * @return array{0: string, 1: array<string, string>}
     */
    private function filterClause(array $filters): array
    {
        $q = $filters['q'] ?? '';

        if ($q === '') {
            return ['', []];
        }

        return [
            ' WHERE (c.first_name LIKE :filter_first_name
                OR c.last_name LIKE :filter_last_name
                OR c.email LIKE :filter_email)',
            [
                'filter_first_name' => '%' . $q . '%',
                'filter_last_name' => '%' . $q . '%',
                'filter_email' => '%' . $q . '%',
            ],
        ];
    }
}
