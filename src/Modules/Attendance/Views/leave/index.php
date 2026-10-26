<?php

declare(strict_types=1);

/** @var array<string, mixed> $pagination */
/** @var array{employee_id: int|null, date_from: string|null, date_to: string|null, status: string|null} $filters */
/** @var array{total: int, pending: int, approved: int, rejected: int} $summary */
/** @var list<array<string, mixed>> $employees */

$activeTab = 'leaves';
require __DIR__ . '/../partials/tabs.php';

$baseQuery = array_filter([
    'employee_id' => $filters['employee_id'],
    'date_from' => $filters['date_from'],
    'date_to' => $filters['date_to'],
    'status' => $filters['status'],
], static fn (mixed $value): bool => $value !== null && $value !== '');

$pageUrl = static fn (int $page): string => '/leave-requests?' . http_build_query($baseQuery + ['page' => $page]);

$statusClasses = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Permisos</h1>
        <p class="text-body-secondary mb-0">Solicitudes de vacaciones, permisos personales e incapacidades.</p>
    </div>
    <a href="/leave-requests/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nueva solicitud
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Solicitudes</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $summary['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Pendientes</p>
                <p class="display-6 fw-semibold text-warning-emphasis mb-0"><?= (int) $summary['pending'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Aprobadas</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $summary['approved'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Rechazadas</p>
                <p class="display-6 fw-semibold text-danger mb-0"><?= (int) $summary['rejected'] ?></p>
            </div>
        </div>
    </div>
</div>

<form method="get" action="/leave-requests" class="card border-0 shadow-sm mb-4">
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
                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Aprobada</option>
                    <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>Rechazada</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-2 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/leave-requests" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
        <p class="form-text mb-0 mt-2">
            Los filtros de fecha incluyen las solicitudes cuyo período se superpone con el rango indicado.
        </p>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <?php if ($pagination['items'] === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-calendar2-x display-5 text-body-tertiary"></i>
            <h2 class="h5 mt-3">No hay solicitudes que coincidan</h2>
            <p class="text-body-secondary mb-4">Prueba con otros filtros o registra una nueva solicitud.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="/leave-requests" class="btn btn-outline-secondary">Limpiar filtros</a>
                <a href="/leave-requests/create" class="btn btn-primary">Nueva solicitud</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Empleado</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Período</th>
                    <th scope="col" class="text-center">Días</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Revisado por</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $leave): ?>
                    <?php
                    $leaveStatus = (string) $leave['status'];
                    $statusClass = $statusClasses[$leaveStatus] ?? 'secondary';
                    ?>
                    <tr>
                        <td>
                            <a href="/leave-requests/<?= (int) $leave['id'] ?>" class="fw-semibold text-decoration-none">
                                <?= e((string) $leave['employee_name']) ?>
                            </a>
                            <?php if ((string) ($leave['employee_status'] ?? '') !== 'active'): ?>
                                <span class="badge text-bg-secondary ms-1">Inactivo</span>
                            <?php endif; ?>
                            <div class="small text-body-secondary">
                                <?= e((string) ($leave['employee_document'] ?? '')) ?>
                                <?php if (($leave['department_name'] ?? null) !== null): ?>
                                    · <?= e((string) $leave['department_name']) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= e(status_label((string) $leave['leave_type'])) ?></td>
                        <td class="text-nowrap">
                            <?= e(format_date((string) $leave['start_date'])) ?>
                            – <?= e(format_date((string) $leave['end_date'])) ?>
                        </td>
                        <td class="text-center"><?= (int) $leave['days'] ?></td>
                        <td>
                            <span class="badge text-bg-<?= e($statusClass) ?>">
                                <?= e(status_label($leaveStatus)) ?>
                            </span>
                        </td>
                        <td>
                            <?= $leave['reviewer_name'] !== null
                                ? e((string) $leave['reviewer_name'])
                                : '<span class="text-body-secondary">—</span>' ?>
                        </td>
                        <td class="text-end">
                            <a href="/leave-requests/<?= (int) $leave['id'] ?>"
                               class="btn btn-sm btn-outline-secondary" title="Ver solicitud" aria-label="Ver solicitud">
                                <i class="bi bi-eye"></i>
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
                de <?= (int) $pagination['total'] ?> solicitudes
            </span>

            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav aria-label="Paginación de permisos">
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
