<?php

declare(strict_types=1);

/** @var array{
 *     month: string,
 *     month_label: string,
 *     range: array{start: string, end: string},
 *     filters: array{month: string, department_id: int|null},
 *     departments: list<array<string, mixed>>,
 *     attendance: array{total: int, present: int, late: int, absent: int},
 *     leaves: array{total: int, pending: int, approved: int, rejected: int},
 *     rows: list<array<string, mixed>>,
 *     leave_rows: list<array<string, mixed>>
 * } $report
 */

$activeTab = 'report';
require __DIR__ . '/partials/tabs.php';

$leaveStatusClasses = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
$departmentName = null;
foreach ($report['departments'] as $department) {
    if ((int) $department['id'] === (int) ($report['filters']['department_id'] ?? 0)) {
        $departmentName = (string) $department['name'];
        break;
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 mb-1">Reporte mensual</h1>
        <p class="text-body-secondary mb-0">Resumen de asistencia y permisos por mes.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/attendance" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <button type="button" class="btn btn-primary" data-print>
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<div class="d-none d-print-block mb-4">
    <h2 class="h4 mb-1">Reporte mensual de asistencia y permisos</h2>
    <p class="mb-1">Período: <strong><?= e($report['month_label']) ?></strong></p>
    <p class="small text-body-secondary mb-0">
        Generado el <?= e(date('d/m/Y H:i')) ?>
        <?php if ($report['filters']['department_id'] !== null): ?>
            · Departamento: <?= e((string) ($departmentName ?? '—')) ?>
        <?php endif; ?>
    </p>
</div>

<form method="get" action="/attendance/report" class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-sm-6 col-lg-4">
                <label for="month" class="form-label">Mes</label>
                <input type="month" class="form-control" id="month" name="month"
                       value="<?= e($report['filters']['month']) ?>" min="2000-01" max="2100-12">
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <label for="department_id" class="form-label">Departamento</label>
                <select class="form-select" id="department_id" name="department_id">
                    <option value="">Todos</option>
                    <?php foreach ($report['departments'] as $department): ?>
                        <option value="<?= (int) $department['id'] ?>"
                            <?= (int) $report['filters']['department_id'] === (int) $department['id'] ? 'selected' : '' ?>>
                            <?= e((string) $department['name']) ?><?= (int) $department['is_active'] === 1 ? '' : ' (inactivo)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
                <a href="/attendance/report" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3">
    <h2 class="h4 mb-0"><?= e($report['month_label']) ?></h2>
    <span class="text-body-secondary small">
        Del <?= e(format_date($report['range']['start'])) ?> al <?= e(format_date($report['range']['end'])) ?>
    </span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Asistencias</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $report['attendance']['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Tardanzas</p>
                <p class="display-6 fw-semibold text-warning-emphasis mb-0"><?= (int) $report['attendance']['late'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Ausencias</p>
                <p class="display-6 fw-semibold text-danger mb-0"><?= (int) $report['attendance']['absent'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Permisos pendientes</p>
                <p class="display-6 fw-semibold text-warning-emphasis mb-0"><?= (int) $report['leaves']['pending'] ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Solicitudes del mes</p>
                <p class="h3 fw-semibold mb-0"><?= (int) $report['leaves']['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Aprobadas</p>
                <p class="h3 fw-semibold text-success mb-0"><?= (int) $report['leaves']['approved'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Rechazadas</p>
                <p class="h3 fw-semibold text-danger mb-0"><?= (int) $report['leaves']['rejected'] ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0"><i class="bi bi-table text-primary"></i> Resumen por empleado</h2>
    </div>
    <?php if ($report['rows'] === []): ?>
        <div class="card-body py-4 text-center text-body-secondary">
            No hay actividad de asistencia ni permisos para el período y filtros seleccionados.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col" rowspan="2" class="align-middle">Empleado</th>
                    <th scope="col" rowspan="2" class="align-middle">Departamento</th>
                    <th scope="colgroup" colspan="4" class="text-center border-start">Asistencia</th>
                    <th scope="colgroup" colspan="4" class="text-center border-start">Permisos</th>
                </tr>
                <tr>
                    <th scope="col" class="text-end border-start">Total</th>
                    <th scope="col" class="text-end">Presente</th>
                    <th scope="col" class="text-end">Tardanza</th>
                    <th scope="col" class="text-end">Ausencia</th>
                    <th scope="col" class="text-end border-start">Total</th>
                    <th scope="col" class="text-end">Pend.</th>
                    <th scope="col" class="text-end">Aprob.</th>
                    <th scope="col" class="text-end">Rech.</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($report['rows'] as $row): ?>
                    <tr>
                        <td>
                            <span class="fw-semibold"><?= e((string) $row['employee_name']) ?></span>
                            <?php if ((string) $row['employee_status'] !== 'active'): ?>
                                <span class="badge text-bg-secondary ms-1">Inactivo</span>
                            <?php endif; ?>
                            <div class="small text-body-secondary"><?= e((string) $row['document']) ?></div>
                        </td>
                        <td><?= e((string) $row['department_name']) ?></td>
                        <td class="text-end border-start"><?= (int) $row['attendance_total'] ?></td>
                        <td class="text-end"><?= (int) $row['present'] ?></td>
                        <td class="text-end"><?= (int) $row['late'] ?></td>
                        <td class="text-end"><?= (int) $row['absent'] ?></td>
                        <td class="text-end border-start"><?= (int) $row['leave_total'] ?></td>
                        <td class="text-end"><?= (int) $row['leave_pending'] ?></td>
                        <td class="text-end"><?= (int) $row['leave_approved'] ?></td>
                        <td class="text-end"><?= (int) $row['leave_rejected'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                <tr>
                    <th colspan="2">Totales</th>
                    <th class="text-end border-start"><?= (int) $report['attendance']['total'] ?></th>
                    <th class="text-end"><?= (int) $report['attendance']['present'] ?></th>
                    <th class="text-end"><?= (int) $report['attendance']['late'] ?></th>
                    <th class="text-end"><?= (int) $report['attendance']['absent'] ?></th>
                    <th class="text-end border-start"><?= (int) $report['leaves']['total'] ?></th>
                    <th class="text-end"><?= (int) $report['leaves']['pending'] ?></th>
                    <th class="text-end"><?= (int) $report['leaves']['approved'] ?></th>
                    <th class="text-end"><?= (int) $report['leaves']['rejected'] ?></th>
                </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0"><i class="bi bi-calendar2-week text-primary"></i> Solicitudes de permiso del mes</h2>
    </div>
    <?php if ($report['leave_rows'] === []): ?>
        <div class="card-body py-4 text-center text-body-secondary">
            No hay solicitudes que inicien en el período seleccionado.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Empleado</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Período</th>
                    <th scope="col" class="text-center">Días</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Revisado por</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($report['leave_rows'] as $leave): ?>
                    <?php $statusClass = $leaveStatusClasses[(string) $leave['status']] ?? 'secondary'; ?>
                    <tr>
                        <td>
                            <?= e((string) $leave['employee_name']) ?>
                            <div class="small text-body-secondary"><?= e((string) $leave['department_name']) ?></div>
                        </td>
                        <td><?= e(status_label((string) $leave['leave_type'])) ?></td>
                        <td class="text-nowrap">
                            <?= e(format_date((string) $leave['start_date'])) ?>
                            – <?= e(format_date((string) $leave['end_date'])) ?>
                        </td>
                        <td class="text-center"><?= (int) $leave['days'] ?></td>
                        <td>
                            <span class="badge text-bg-<?= e($statusClass) ?>">
                                <?= e(status_label((string) $leave['status'])) ?>
                            </span>
                        </td>
                        <td><?= e((string) ($leave['reviewer_name'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
