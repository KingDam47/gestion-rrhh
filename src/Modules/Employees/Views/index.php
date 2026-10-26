<?php

declare(strict_types=1);

/** @var array<string, mixed> $pagination */
/** @var array{q: string, department_id: int|null, status: string|null} $filters */
/** @var list<array<string, mixed>> $departments */
/** @var array{active: int, inactive: int, total: int} $stats */

$activeTab = 'employees';
require __DIR__ . '/partials/tabs.php';

$baseQuery = array_filter([
    'q' => $filters['q'],
    'department_id' => $filters['department_id'],
    'status' => $filters['status'],
], static fn (mixed $value): bool => $value !== null && $value !== '');

$pageUrl = static fn (int $page): string => '/employees?' . http_build_query($baseQuery + ['page' => $page]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Empleados</h1>
        <p class="text-body-secondary mb-0">Registro y consulta del personal de la organización.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/employees/report" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> Reporte
        </a>
        <a href="/employees/create" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Nuevo empleado
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Total registrados</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $stats['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Activos</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $stats['active'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Inactivos</p>
                <p class="display-6 fw-semibold text-body-secondary mb-0"><?= (int) $stats['inactive'] ?></p>
            </div>
        </div>
    </div>
</div>

<form method="get" action="/employees" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="q" class="form-label">Buscar</label>
                <input type="search" class="form-control" id="q" name="q"
                       value="<?= e($filters['q']) ?>"
                       placeholder="Nombre, documento o correo">
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
                    <option value="">Todos</option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/employees" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <?php if ($pagination['items'] === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-search display-5 text-body-tertiary"></i>
            <h2 class="h5 mt-3">No hay empleados que coincidan</h2>
            <p class="text-body-secondary mb-4">Prueba con otros filtros o registra un empleado nuevo.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="/employees" class="btn btn-outline-secondary">Limpiar filtros</a>
                <a href="/employees/create" class="btn btn-primary">Registrar empleado</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Empleado</th>
                    <th scope="col">Departamento</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Ingreso</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $employee): ?>
                    <?php $statusClass = (string) $employee['status'] === 'active' ? 'success' : 'secondary'; ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none"
                               href="/employees/<?= (int) $employee['id'] ?>">
                                <?= e((string) $employee['last_name'] . ', ' . (string) $employee['first_name']) ?>
                            </a>
                            <div class="small text-body-secondary">
                                <i class="bi bi-person-vcard"></i> <?= e((string) $employee['document']) ?>
                            </div>
                        </td>
                        <td>
                            <?= e((string) $employee['department_name']) ?>
                            <?php if ((int) $employee['department_active'] !== 1): ?>
                                <span class="badge text-bg-warning ms-1">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($employee['position_name'] !== null): ?>
                                <?= e((string) $employee['position_name']) ?>
                                <?php if ((int) $employee['position_active'] !== 1): ?>
                                    <span class="badge text-bg-warning ms-1">Inactivo</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-body-secondary">Sin cargo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_date($employee['hire_date'] !== null ? (string) $employee['hire_date'] : null)) ?></td>
                        <td>
                            <span class="badge text-bg-<?= e($statusClass) ?>">
                                <?= e(status_label((string) $employee['status'])) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Acciones del empleado">
                                <a href="/employees/<?= (int) $employee['id'] ?>" class="btn btn-outline-secondary"
                                   title="Ver ficha" aria-label="Ver ficha">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="/employees/<?= (int) $employee['id'] ?>/edit" class="btn btn-outline-secondary"
                                   title="Editar" aria-label="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
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
                de <?= (int) $pagination['total'] ?> empleados
            </span>

            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav aria-label="Paginación de empleados">
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
