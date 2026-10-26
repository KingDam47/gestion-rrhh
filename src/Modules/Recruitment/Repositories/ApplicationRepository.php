<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Repositories;

use App\Core\Database;
use PDO;

final class ApplicationRepository
{
    private const SELECT = 'SELECT a.*,
                c.first_name AS candidate_first_name,
                c.last_name AS candidate_last_name,
                c.email AS candidate_email,
                c.phone AS candidate_phone,
                v.title AS vacancy_title,
                v.status AS vacancy_status,
                v.department_id AS vacancy_department_id,
                v.position_id AS vacancy_position_id,
                v.opening_date AS vacancy_opening_date,
                v.closing_date AS vacancy_closing_date
         FROM applications a
         INNER JOIN candidates c ON c.id = a.candidate_id
         INNER JOIN vacancies v ON v.id = a.vacancy_id';

    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @param array{q: string, vacancy_id: int|null, status: string|null} $filters
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
            'SELECT COUNT(*)
             FROM applications a
             INNER JOIN candidates c ON c.id = a.candidate_id
             INNER JOIN vacancies v ON v.id = a.vacancy_id' . $where
        );
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);
        $page = $pages === 0 ? 1 : min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->database->connection()->prepare(
            self::SELECT . $where . '
             ORDER BY a.applied_at DESC, a.id DESC
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
     * @param array{q: string, vacancy_id: int|null, status: string|null} $filters
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
    public function summary(array $filters): array
    {
        [$where, $parameters] = $this->filterClause($filters);

        $statement = $this->database->connection()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN a.status = \'received\' THEN 1 ELSE 0 END) AS received,
                    SUM(CASE WHEN a.status = \'screening\' THEN 1 ELSE 0 END) AS screening,
                    SUM(CASE WHEN a.status = \'interview\' THEN 1 ELSE 0 END) AS interview,
                    SUM(CASE WHEN a.status = \'selected\' THEN 1 ELSE 0 END) AS selected,
                    SUM(CASE WHEN a.status = \'rejected\' THEN 1 ELSE 0 END) AS rejected,
                    SUM(CASE WHEN a.status = \'selected\' AND a.employee_id IS NULL THEN 1 ELSE 0 END) AS pending_hire
             FROM applications a
             INNER JOIN candidates c ON c.id = a.candidate_id
             INNER JOIN vacancies v ON v.id = a.vacancy_id' . $where
        );
        $statement->execute($parameters);
        $row = $statement->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'received' => (int) ($row['received'] ?? 0),
            'screening' => (int) ($row['screening'] ?? 0),
            'interview' => (int) ($row['interview'] ?? 0),
            'selected' => (int) ($row['selected'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
            'pending_hire' => (int) ($row['pending_hire'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            self::SELECT . ' WHERE a.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findByCandidateVacancy(int $candidateId, int $vacancyId, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM applications
                WHERE candidate_id = :candidate_id AND vacancy_id = :vacancy_id';
        $parameters = [
            'candidate_id' => $candidateId,
            'vacancy_id' => $vacancyId,
        ];

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
            'INSERT INTO applications
                (candidate_id, vacancy_id, applied_at, status, notes, created_by, updated_by)
             VALUES
                (:candidate_id, :vacancy_id, :applied_at, :status, :notes, :created_by, :updated_by)'
        );
        $statement->execute($data);

        return (int) $this->database->connection()->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $notes, ?int $userId): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE applications
             SET status = :status, notes = :notes, updated_by = :updated_by
             WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'notes' => $notes,
            'updated_by' => $userId,
            'id' => $id,
        ]);
    }

    public function setEmployeeId(int $id, int $employeeId, ?int $userId): void
    {
        $statement = $this->database->connection()->prepare(
            'UPDATE applications
             SET employee_id = :employee_id, updated_by = :updated_by
             WHERE id = :id'
        );
        $statement->execute([
            'employee_id' => $employeeId,
            'updated_by' => $userId,
            'id' => $id,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function byVacancy(int $vacancyId): array
    {
        $statement = $this->database->connection()->prepare(
            self::SELECT . ' WHERE a.vacancy_id = :vacancy_id
             ORDER BY a.applied_at DESC, a.id DESC'
        );
        $statement->execute(['vacancy_id' => $vacancyId]);

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function byCandidate(int $candidateId): array
    {
        $statement = $this->database->connection()->prepare(
            self::SELECT . ' WHERE a.candidate_id = :candidate_id
             ORDER BY a.applied_at DESC, a.id DESC'
        );
        $statement->execute(['candidate_id' => $candidateId]);

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentSelected(int $limit = 5): array
    {
        $statement = $this->database->connection()->prepare(
            self::SELECT . ' WHERE a.status = \'selected\'
             ORDER BY a.applied_at DESC, a.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @param array{q: string, vacancy_id: int|null, status: string|null} $filters
     * @return array{0: string, 1: array<string, string|int>}
     */
    private function filterClause(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $q = $filters['q'] ?? '';
        if ($q !== '') {
            $conditions[] = '(c.first_name LIKE :filter_first_name
                OR c.last_name LIKE :filter_last_name
                OR c.email LIKE :filter_email
                OR v.title LIKE :filter_title)';
            $parameters['filter_first_name'] = '%' . $q . '%';
            $parameters['filter_last_name'] = '%' . $q . '%';
            $parameters['filter_email'] = '%' . $q . '%';
            $parameters['filter_title'] = '%' . $q . '%';
        }

        $vacancyId = $filters['vacancy_id'] ?? null;
        if ($vacancyId !== null && (int) $vacancyId > 0) {
            $conditions[] = 'a.vacancy_id = :filter_vacancy_id';
            $parameters['filter_vacancy_id'] = (int) $vacancyId;
        }

        $status = $filters['status'] ?? null;
        if (in_array($status, ['received', 'screening', 'interview', 'selected', 'rejected'], true)) {
            $conditions[] = 'a.status = :filter_status';
            $parameters['filter_status'] = (string) $status;
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $parameters,
        ];
    }
}
