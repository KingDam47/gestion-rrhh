<?php

declare(strict_types=1);

/** @var array<string, mixed> $pagination */
/** @var array{q: string, vacancy_id: int|null, status: string|null} $filters */
/** @var array{total: int, received: int, screening: int, interview: int, selected: int, rejected: int, pending_hire: int} $summary */
/** @var list<array<string, mixed>> $vacancies */

$activeTab = 'applications';
require dirname(__DIR__) . '/partials/tabs.php';

$statusClasses = [
    'received' => 'secondary',
    'screening' => 'info',
    'interview' => 'primary',
    'selected' => 'success',
    'rejected' => 'danger',
];
$inProcess = (int) $summary['received'] + (int) $summary['screening'] + (int) $summary['interview'];
$baseQuery = array_filter([
    'q' => $filters['q'],
    'vacancy_id' => $filters['vacancy_id'],
    'status' => $filters['status'],
], static fn (mixed $value): bool => $value !== null && $value !== '');

$pageUrl = static fn (int $page): string => '/applications?' . http_build_query($baseQuery + ['page' => $page]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Postulaciones</h1>
        <p class="text-body-secondary mb-0">Seguimiento de cada candidato dentro de su vacante.</p>
    </div>
    <a href="/applications/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nueva postulación
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Total</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $summary['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">En curso</p>
                <p class="display-6 fw-semibold text-primary mb-0"><?= $inProcess ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Seleccionadas</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $summary['selected'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Pendientes de alta</p>
                <p class="display-6 fw-semibold text-warning mb-0"><?= (int) $summary['pending_hire'] ?></p>
            </div>
        </div>
    </div>
</div>

<form method="get" action="/applications" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="q" class="form-label">Buscar</label>
                <input type="search" class="form-control" id="q" name="q"
                       value="<?= e($filters['q']) ?>" placeholder="Candidato, correo o vacante">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="vacancy_id" class="form-label">Vacante</label>
                <select class="form-select" id="vacancy_id" name="vacancy_id">
                    <option value="">Todas</option>
                    <?php foreach ($vacancies as $vacancy): ?>
                        <option value="<?= (int) $vacancy['id'] ?>"
                            <?= (int) $filters['vacancy_id'] === (int) $vacancy['id'] ? 'selected' : '' ?>>
                            <?= e((string) $vacancy['title']) ?>
                            · <?= e((string) $vacancy['department_name']) ?>
                            <?= (string) $vacancy['status'] === 'open' ? '' : ' (cerrada)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <?php foreach (['received', 'screening', 'interview', 'selected', 'rejected'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>>
                            <?= e(status_label($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/applications" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <?php if ($pagination['items'] === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-diagram-3 display-5 text-body-tertiary"></i>
            <h2 class="h5 mt-3">No hay postulaciones que coincidan</h2>
            <p class="text-body-secondary mb-4">Prueba con otros filtros o registra una postulación nueva.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="/applications" class="btn btn-outline-secondary">Limpiar filtros</a>
                <a href="/applications/create" class="btn btn-primary">Registrar postulación</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Candidato</th>
                    <th scope="col">Vacante</th>
                    <th scope="col">Postulación</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Empleado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $application): ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none"
                               href="/applications/<?= (int) $application['id'] ?>">
                                <?= e((string) $application['candidate_name_natural']) ?>
                            </a>
                            <div class="small text-body-secondary"><?= e((string) $application['candidate_email']) ?></div>
                        </td>
                        <td>
                            <?= e((string) $application['vacancy_title']) ?>
                            <div class="small text-body-secondary">
                                <?= e((string) $application['vacancy_department_name']) ?>
                            </div>
                        </td>
                        <td><?= e(format_date((string) $application['applied_at'])) ?></td>
                        <td>
                            <span class="badge text-bg-<?= e($statusClasses[(string) $application['status']] ?? 'secondary') ?>">
                                <?= e(status_label((string) $application['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($application['employee_id'] !== null): ?>
                                <a href="/employees/<?= (int) $application['employee_id'] ?>">
                                    <?= e((string) ($application['employee_name'] ?? 'Ver empleado')) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-body-secondary">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de la postulación">
                                <a href="/applications/<?= (int) $application['id'] ?>"
                                   class="btn btn-outline-secondary" title="Ver seguimiento" aria-label="Ver seguimiento">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ((string) $application['status'] === 'selected' && $application['employee_id'] === null): ?>
                                    <a href="/applications/<?= (int) $application['id'] ?>/hire"
                                       class="btn btn-outline-success" title="Dar de alta" aria-label="Dar de alta">
                                        <i class="bi bi-person-plus"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-body-secondary">
                Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?>
                de <?= (int) $pagination['total'] ?> postulaciones
            </span>

            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav aria-label="Paginación de postulaciones">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= (int) $pagination['page'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="<?= e($pageUrl(max(1, (int) $pagination['page'] - 1))) ?>"
                               <?= (int) $pagination['page'] <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                Anterior
                            </a>
                        </li>
                        <?php for ($page = 1; $page <= (int) $pagination['pages']; $page++): ?>
                            <li class="page-item <?= $page === (int) $pagination['page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e($pageUrl($page)) ?>"
                                   <?= $page === (int) $pagination['page'] ? 'aria-current="page"' : '' ?>>
                                    <?= $page ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= (int) $pagination['page'] >= (int) $pagination['pages'] ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="<?= e($pageUrl(min((int) $pagination['pages'], (int) $pagination['page'] + 1))) ?>"
                               <?= (int) $pagination['page'] >= (int) $pagination['pages'] ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                Siguiente
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
