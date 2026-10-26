<?php

declare(strict_types=1);

/** @var array<string, mixed> $pagination */
/** @var array{employee_id: int|null, date_from: string|null, date_to: string|null, status: string|null} $filters */
/** @var array{total: int, present: int, late: int, absent: int} $summary */
/** @var list<array<string, mixed>> $employees */

$activeTab = 'attendance';
require __DIR__ . '/partials/tabs.php';

$baseQuery = array_filter([
    'employee_id' => $filters['employee_id'],
    'date_from' => $filters['date_from'],
    'date_to' => $filters['date_to'],
    'status' => $filters['status'],
], static fn (mixed $value): bool => $value !== null && $value !== '');

$pageUrl = static fn (int $page): string => '/attendance?' . http_build_query($baseQuery + ['page' => $page]);

$statusClasses = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Asistencia</h1>
        <p class="text-body-secondary mb-0">Registro diario de entradas, salidas, tardanzas y ausencias.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/attendance/report" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> Reporte mensual
        </a>
        <a href="/attendance/create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo registro
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Registros</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $summary['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Presentes</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $summary['present'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Tardanzas</p>
                <p class="display-6 fw-semibold text-warning-emphasis mb-0"><?= (int) $summary['late'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Ausencias</p>
                <p class="display-6 fw-semibold text-danger mb-0"><?= (int) $summary['absent'] ?></p>
            </div>
        </div>
    </div>
</div>

<form method="get" action="/attendance" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="employee_id" class="form-label">Empleado</label>
                <select class="form-select" id="employee_id" name="employee_id">
                    <option value="">Todos</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= (int) $employee['id'] ?>"
                            <?= (int) $filters['employee_id'] === (int) $employee['id'] ? 'selected' : '' ?>>
                            <?= e((string) $employee['last_name'] . ', ' . (string) $employee['first_name']) ?>
                            · <?= e((string) $employee['document']) ?>
                            <?= (string) $employee['status'] === 'active' ? '' : ' (inactivo)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="date_from" class="form-label">Desde</label>
                <input type="date" class="form-control" id="date_from" name="date_from"
                       value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label for="date_to" class="form-label">Hasta</label>
                <input type="date" class="form-control" id="date_to" name="date_to"
                       value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="present" <?= $filters['status'] === 'present' ? 'selected' : '' ?>>Presente</option>
                    <option value="late" <?= $filters['status'] === 'late' ? 'selected' : '' ?>>Tardanza</option>
                    <option value="absent" <?= $filters['status'] === 'absent' ? 'selected' : '' ?>>Ausencia</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-2 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/attendance" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <?php if ($pagination['items'] === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-calendar-x display-5 text-body-tertiary"></i>
            <h2 class="h5 mt-3">No hay registros que coincidan</h2>
            <p class="text-body-secondary mb-4">Prueba con otros filtros o registra la asistencia de un empleado.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="/attendance" class="btn btn-outline-secondary">Limpiar filtros</a>
                <a href="/attendance/create" class="btn btn-primary">Nuevo registro</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col">Empleado</th>
                    <th scope="col">Entrada</th>
                    <th scope="col">Salida</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Observación</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $record): ?>
                    <?php
                    $recordStatus = (string) $record['status'];
                    $statusClass = $statusClasses[$recordStatus] ?? 'secondary';
                    $employeeInactive = (string) ($record['employee_status'] ?? '') !== 'active';
                    ?>
                    <tr>
                        <td class="text-nowrap"><?= e(format_date((string) $record['work_date'])) ?></td>
                        <td>
                            <span class="fw-semibold"><?= e((string) $record['employee_name']) ?></span>
                            <?php if ($employeeInactive): ?>
                                <span class="badge text-bg-secondary ms-1">Inactivo</span>
                            <?php endif; ?>
                            <div class="small text-body-secondary">
                                <?= e((string) ($record['employee_document'] ?? '')) ?>
                                <?php if (($record['department_name'] ?? null) !== null): ?>
                                    · <?= e((string) $record['department_name']) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= e(format_time($record['check_in'] !== null ? (string) $record['check_in'] : null)) ?></td>
                        <td><?= e(format_time($record['check_out'] !== null ? (string) $record['check_out'] : null)) ?></td>
                        <td>
                            <span class="badge text-bg-<?= e($statusClass) ?>">
                                <?= e(status_label($recordStatus)) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($record['notes'] !== null): ?>
                                <span class="d-inline-block text-truncate align-middle"
                                      style="max-width: 18rem;"
                                      title="<?= e((string) $record['notes']) ?>">
                                    <?= e((string) $record['notes']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-body-secondary">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/attendance/<?= (int) $record['id'] ?>/edit"
                               class="btn btn-sm btn-outline-secondary" title="Editar" aria-label="Editar registro">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-body-secondary">
                Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?>
                de <?= (int) $pagination['total'] ?> registros
            </span>

            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav aria-label="Paginación de asistencia">
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
