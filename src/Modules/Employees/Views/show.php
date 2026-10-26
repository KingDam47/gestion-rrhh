<?php

declare(strict_types=1);

/** @var array<string, mixed> $employee */

$isActive = (string) $employee['status'] === 'active';
$statusClass = $isActive ? 'success' : 'secondary';
$departmentActive = (int) $employee['department_active'] === 1;
$positionActive = $employee['position_name'] === null || (int) $employee['position_active'] === 1;
$canReactivate = $departmentActive;
$fullName = trim((string) $employee['first_name'] . ' ' . (string) $employee['last_name']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h1 class="h3 mb-0"><?= e($fullName) ?></h1>
            <span class="badge text-bg-<?= e($statusClass) ?>"><?= e(status_label((string) $employee['status'])) ?></span>
        </div>
        <p class="text-body-secondary mb-0">
            <i class="bi bi-person-vcard"></i> <?= e((string) $employee['document']) ?>
            · <?= e((string) $employee['department_name']) ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/employees" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <a href="/employees/<?= (int) $employee['id'] ?>/edit" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
    </div>
</div>

<?php if (!$departmentActive): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        El departamento de este empleado está inactivo.
        <?php if (!$isActive): ?>
            Reactiva el departamento antes de reactivar al empleado.
        <?php endif; ?>
        <a href="/departments/<?= (int) $employee['department_id'] ?>/edit" class="alert-link">Revisar departamento</a>
    </div>
<?php endif; ?>

<?php if ($employee['position_name'] !== null && !$positionActive): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        El cargo asignado está inactivo. El empleado conserva el historial; puedes asignarle otro cargo al editarlo.
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><i class="bi bi-briefcase text-primary"></i> Información laboral</h2>
                <?php if ($isActive): ?>
                    <form method="post" action="/employees/<?= (int) $employee['id'] ?>/status"
                          data-confirm="¿Desactivar a <?= e($fullName) ?>? Conservará todo su historial.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="inactive">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-person-dash"></i> Desactivar
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post" action="/employees/<?= (int) $employee['id'] ?>/status"
                          data-confirm="¿Reactivar a <?= e($fullName) ?>?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-sm btn-outline-success"
                                <?= $canReactivate ? '' : 'disabled title="Reactiva el departamento primero"' ?>>
                            <i class="bi bi-person-check"></i> Reactivar
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-body-secondary fw-normal">Documento</dt>
                    <dd class="col-sm-8"><?= e((string) $employee['document']) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Departamento</dt>
                    <dd class="col-sm-8"><?= e((string) $employee['department_name']) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Cargo</dt>
                    <dd class="col-sm-8"><?= e((string) ($employee['position_name'] ?? 'Sin cargo')) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Fecha de ingreso</dt>
                    <dd class="col-sm-8"><?= e(format_date($employee['hire_date'] !== null ? (string) $employee['hire_date'] : null)) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Estado</dt>
                    <dd class="col-sm-8">
                        <span class="badge text-bg-<?= e($statusClass) ?>"><?= e(status_label((string) $employee['status'])) ?></span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-telephone text-primary"></i> Contacto y registro</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-body-secondary fw-normal">Correo</dt>
                    <dd class="col-sm-8">
                        <?php if ($employee['email'] !== null): ?>
                            <a href="mailto:<?= e((string) $employee['email']) ?>"><?= e((string) $employee['email']) ?></a>
                        <?php else: ?>
                            <span class="text-body-secondary">No informado</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Teléfono</dt>
                    <dd class="col-sm-8">
                        <?php if ($employee['phone'] !== null): ?>
                            <a href="tel:<?= e((string) $employee['phone']) ?>"><?= e((string) $employee['phone']) ?></a>
                        <?php else: ?>
                            <span class="text-body-secondary">No informado</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Creado</dt>
                    <dd class="col-sm-8"><?= e(format_date($employee['created_at'] !== null ? (string) $employee['created_at'] : null, 'd/m/Y H:i')) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Actualizado</dt>
                    <dd class="col-sm-8"><?= e(format_date($employee['updated_at'] !== null ? (string) $employee['updated_at'] : null, 'd/m/Y H:i')) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5"><i class="bi bi-clock-history text-primary"></i> Historial</h2>
                <p class="text-body-secondary mb-0">
                    La asistencia, las tardanzas y las solicitudes de permiso se mostrarán en el módulo de
                    Asistencia y permisos. La desactivación no elimina ningún registro histórico.
                </p>
            </div>
        </div>
    </div>
</div>
