<?php

declare(strict_types=1);

/** @var array{
 *     rows: list<array<string, mixed>>,
 *     total: int,
 *     active: int,
 *     inactive: int,
 *     by_department: list<array<string, mixed>>,
 *     filters: array{q: string, department_id: int|null, status: string|null}
 * } $report
 * @var list<array<string, mixed>> $departments
 */
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 mb-1">Reporte de personal</h1>
        <p class="text-body-secondary mb-0">Resumen imprimible de empleados y distribución por departamento.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/employees" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <button type="button" class="btn btn-primary" data-print>
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<div class="d-none d-print-block mb-4">
    <h2 class="h4 mb-1">Reporte de personal</h2>
    <p class="small text-body-secondary mb-0">Generado el <?= e(date('d/m/Y H:i')) ?></p>
</div>

<form method="get" action="/employees/report" class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="q" class="form-label">Buscar</label>
                <input type="search" class="form-control" id="q" name="q"
                       value="<?= e($report['filters']['q']) ?>"
                       placeholder="Nombre, documento o correo">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="department_id" class="form-label">Departamento</label>
                <select class="form-select" id="department_id" name="department_id">
                    <option value="">Todos</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int) $department['id'] ?>"
                            <?= (int) $report['filters']['department_id'] === (int) $department['id'] ? 'selected' : '' ?>>
                            <?= e((string) $department['name']) ?><?= (int) $department['is_active'] === 1 ? '' : ' (inactivo)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="active" <?= $report['filters']['status'] === 'active' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactive" <?= $report['filters']['status'] === 'inactive' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <a href="/employees/report" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Empleados en el reporte</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $report['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Activos</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $report['active'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Inactivos</p>
                <p class="display-6 fw-semibold text-body-secondary mb-0"><?= (int) $report['inactive'] ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0"><i class="bi bi-table text-primary"></i> Detalle de empleados</h2>
    </div>
    <?php if ($report['rows'] === []): ?>
        <div class="card-body py-4 text-center text-body-secondary">
            No hay empleados que coincidan con los filtros seleccionados.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Documento</th>
                    <th scope="col">Empleado</th>
                    <th scope="col">Departamento</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Ingreso</th>
                    <th scope="col">Estado</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($report['rows'] as $employee): ?>
                    <tr>
                        <td><?= e((string) $employee['document']) ?></td>
                        <td><?= e((string) $employee['last_name'] . ', ' . (string) $employee['first_name']) ?></td>
                        <td><?= e((string) $employee['department_name']) ?></td>
                        <td><?= e((string) ($employee['position_name'] ?? 'Sin cargo')) ?></td>
                        <td><?= e(format_date($employee['hire_date'] !== null ? (string) $employee['hire_date'] : null)) ?></td>
                        <td><?= e(status_label((string) $employee['status'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0"><i class="bi bi-diagram-3 text-primary"></i> Resumen general por departamento</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th scope="col">Departamento</th>
                <th scope="col">Estado</th>
                <th scope="col" class="text-end">Total</th>
                <th scope="col" class="text-end">Activos</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($report['by_department'] as $department): ?>
                <tr>
                    <td><?= e((string) $department['name']) ?></td>
                    <td><?= e(status_label((int) $department['is_active'] === 1 ? 'active' : 'inactive')) ?></td>
                    <td class="text-end"><?= (int) $department['total'] ?></td>
                    <td class="text-end"><?= (int) $department['active_total'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
