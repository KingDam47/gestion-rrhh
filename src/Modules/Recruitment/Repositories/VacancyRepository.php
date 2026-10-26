<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Repositories;

use App\Core\Database;
use PDO;

final class VacancyRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @param array{q: string, status: string|null, department_id: int|null} $filters
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
            'SELECT COUNT(*) FROM vacancies v' . $where
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = $pages === 0 ? 1 : min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->database->connection()->prepare(
            'SELECT v.*,
                    (SELECT COUNT(*) FROM applications a WHERE a.vacancy_id = v.id) AS application_count
             FROM vacancies v' . $where . '
             ORDER BY v.opening_date DESC, v.id DESC
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
     * @param array{q: string, status: string|null, department_id: int|null} $filters
     * @return array{total: int, open: int, closed: int}
     */
    public function summary(array $filters): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $statement = $this->database->connection()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN v.status = \'open\' THEN 1 ELSE 0 END) AS open,
                    SUM(CASE WHEN v.status = \'closed\' THEN 1 ELSE 0 END) AS closed
             FROM vacancies v' . $where
        );
        $statement->execute($parameters);
        $row = $statement->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'open' => (int) ($row['open'] ?? 0),
            'closed' => (int) ($row['closed'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT v.*,
                    (SELECT COUNT(*) FROM applications a WHERE a.vacancy_id = v.id) AS application_count
             FROM vacancies v
             WHERE v.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO vacancies
                (title, description, department_id, position_id, opening_date, closing_date,
                 status, created_by, updated_by)
             VALUES
                (:title, :description, :department_id, :position_id, :opening_date, :closing_date,
                 :status, :created_by, :updated_by)'
        );
        $statement->execute($data);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE vacancies
             SET title = :title,
                 description = :description,
                 department_id = :department_id,
                 position_id = :position_id,
                 opening_date = :opening_date,
                 closing_date = :closing_date,
                 updated_by = :updated_by
             WHERE id = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    public function close(int $id, string $closingDate, ?int $userId): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE vacancies
             SET status = \'closed\', closing_date = :closing_date, updated_by = :updated_by
             WHERE id = :id'
        );
        $statement->execute([
            'closing_date' => $closingDate,
            'updated_by' => $userId,
            'id' => $id,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function options(bool $onlyOpen = false): array
    {
        $sql = 'SELECT v.id, v.title, v.status, v.department_id, v.position_id, v.opening_date
                FROM vacancies v';

        if ($onlyOpen) {
            $sql .= ' WHERE v.status = \'open\'';
        }

        $sql .= ' ORDER BY v.opening_date DESC, v.id DESC';

        return $this->database->connection()->query($sql)->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentOpen(int $limit = 5): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT v.*,
                    (SELECT COUNT(*) FROM applications a WHERE a.vacancy_id = v.id) AS application_count
             FROM vacancies v
             WHERE v.status = \'open\'
             ORDER BY v.opening_date DESC, v.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return array{total: int, open: int, closed: int}
     */
    public function counts(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = \'open\' THEN 1 ELSE 0 END) AS open,
                    SUM(CASE WHEN status = \'closed\' THEN 1 ELSE 0 END) AS closed
             FROM vacancies'
        );
        $row = $statement->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'open' => (int) ($row['open'] ?? 0),
            'closed' => (int) ($row['closed'] ?? 0),
        ];
    }

    /**
     * @param array{q: string, status: string|null, department_id: int|null} $filters
     * @return array{0: string, 1: array<string, string|int>}
     */
    private function filterClause(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $q = $filters['q'] ?? '';
        if ($q !== '') {
            $conditions[] = 'v.title LIKE :filter_q';
            $parameters['filter_q'] = '%' . $q . '%';
        }

        $status = $filters['status'] ?? null;
        if (in_array($status, ['open', 'closed'], true)) {
            $conditions[] = 'v.status = :filter_status';
            $parameters['filter_status'] = (string) $status;
        }

        $departmentId = $filters['department_id'] ?? null;
        if ($departmentId !== null && (int) $departmentId > 0) {
            $conditions[] = 'v.department_id = :filter_department_id';
            $parameters['filter_department_id'] = (int) $departmentId;
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }
}
