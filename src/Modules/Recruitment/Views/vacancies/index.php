<?php

declare(strict_types=1);

/** @var array<string, mixed> $pagination */
/** @var array{q: string, status: string|null, department_id: int|null} $filters */
/** @var array{total: int, open: int, closed: int} $summary */
/** @var list<array<string, mixed>> $departments */

$activeTab = 'vacancies';
require dirname(__DIR__) . '/partials/tabs.php';

$baseQuery = array_filter([
    'q' => $filters['q'],
    'status' => $filters['status'],
    'department_id' => $filters['department_id'],
], static fn (mixed $value): bool => $value !== null && $value !== '');

$pageUrl = static fn (int $page): string => '/vacancies?' . http_build_query($baseQuery + ['page' => $page]);
$statusClass = static fn (string $status): string => $status === 'open' ? 'success' : 'secondary';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Vacantes</h1>
        <p class="text-body-secondary mb-0">Publica y cierra las búsquedas activas de personal.</p>
    </div>
    <a href="/vacancies/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nueva vacante
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Total</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $summary['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Abiertas</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $summary['open'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Cerradas</p>
                <p class="display-6 fw-semibold text-body-secondary mb-0"><?= (int) $summary['closed'] ?></p>
            </div>
        </div>
    </div>
</div>

<form method="get" action="/vacancies" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="q" class="form-label">Buscar</label>
                <input type="search" class="form-control" id="q" name="q"
                       value="<?= e($filters['q']) ?>" placeholder="Título de la vacante">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="department_id" class="form-label">Departamento</label>
                <select class="form-select" id="department_id" name="department_id">
                    <option value="">Todos</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int) $department['id'] ?>"
                            <?= (int) $filters['department_id'] === (int) $department['id'] ? 'selected' : '' ?>>
                            <?= e((string) $department['name']) ?><?= (int) $department['is_active'] === 1 ? '' : ' (inactivo)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todas</option>
                    <option value="open" <?= $filters['status'] === 'open' ? 'selected' : '' ?>>Abiertas</option>
                    <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>>Cerradas</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/vacancies" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <?php if ($pagination['items'] === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-briefcase display-5 text-body-tertiary"></i>
            <h2 class="h5 mt-3">No hay vacantes que coincidan</h2>
            <p class="text-body-secondary mb-4">Prueba con otros filtros o crea una vacante nueva.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="/vacancies" class="btn btn-outline-secondary">Limpiar filtros</a>
                <a href="/vacancies/create" class="btn btn-primary">Crear vacante</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Vacante</th>
                    <th scope="col">Departamento</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Apertura</th>
                    <th scope="col">Postulaciones</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $vacancy): ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none"
                               href="/vacancies/<?= (int) $vacancy['id'] ?>">
                                <?= e((string) $vacancy['title']) ?>
                            </a>
                            <?php if ($vacancy['closing_date'] !== null): ?>
                                <div class="small text-body-secondary">
                                    Cierre: <?= e(format_date((string) $vacancy['closing_date'])) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e((string) $vacancy['department_name']) ?>
                            <?php if ((int) $vacancy['department_active'] !== 1): ?>
                                <span class="badge text-bg-warning ms-1">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($vacancy['position_name'] !== null): ?>
                                <?= e((string) $vacancy['position_name']) ?>
                                <?php if ((int) $vacancy['position_active'] !== 1): ?>
                                    <span class="badge text-bg-warning ms-1">Inactivo</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-body-secondary">Sin cargo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_date((string) $vacancy['opening_date'])) ?></td>
                        <td><?= (int) $vacancy['application_count'] ?></td>
                        <td>
                            <span class="badge text-bg-<?= e($statusClass((string) $vacancy['status'])) ?>">
                                <?= e(status_label((string) $vacancy['status'])) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de la vacante">
                                <a href="/vacancies/<?= (int) $vacancy['id'] ?>" class="btn btn-outline-secondary"
                                   title="Ver detalle" aria-label="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ((string) $vacancy['status'] === 'open'): ?>
                                    <a href="/vacancies/<?= (int) $vacancy['id'] ?>/edit"
                                       class="btn btn-outline-secondary" title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil"></i>
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
                de <?= (int) $pagination['total'] ?> vacantes
            </span>

            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav aria-label="Paginación de vacantes">
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
