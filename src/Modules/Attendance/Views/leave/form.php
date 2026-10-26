<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $employees */
/** @var array<string, string> $errors */
/** @var string|null $generalError */

$selectedEmployee = (int) old('employee_id', '');
$selectedType = old('leave_type', 'vacation');
$canSubmit = $employees !== [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Nueva solicitud de permiso</h1>
        <p class="text-body-secondary mb-0">Registra una solicitud en nombre de un empleado activo.</p>
    </div>
    <a href="/leave-requests" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<?php if (!$canSubmit): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-info-circle"></i> No hay empleados activos disponibles.
        <a href="/employees" class="alert-link">Activa o registra un empleado</a> antes de crear solicitudes.
    </div>
<?php endif; ?>

<form method="post" action="/leave-requests" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-person text-primary"></i> Empleado y tipo</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Empleado <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['employee_id']) ? 'is-invalid' : '' ?>"
                                id="employee_id" name="employee_id" required>
                            <option value="">Selecciona un empleado</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= (int) $employee['id'] ?>"
                                    <?= $selectedEmployee === (int) $employee['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $employee['last_name'] . ', ' . (string) $employee['first_name']) ?>
                                    · <?= e((string) $employee['document']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Solo se muestran empleados activos.</div>
                        <?php if (isset($errors['employee_id'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['employee_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="leave_type" class="form-label">Tipo de permiso <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['leave_type']) ? 'is-invalid' : '' ?>"
                                id="leave_type" name="leave_type" required>
                            <option value="vacation" <?= $selectedType === 'vacation' ? 'selected' : '' ?>>Vacaciones</option>
                            <option value="personal" <?= $selectedType === 'personal' ? 'selected' : '' ?>>Permiso personal</option>
                            <option value="sick" <?= $selectedType === 'sick' ? 'selected' : '' ?>>Incapacidad</option>
                            <option value="other" <?= $selectedType === 'other' ? 'selected' : '' ?>>Otro</option>
                        </select>
                        <?php if (isset($errors['leave_type'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['leave_type']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="alert alert-light border mb-0" role="note">
                        <i class="bi bi-info-circle text-primary"></i>
                        La solicitud se crea en estado <strong>pendiente</strong>. La revisión se realiza desde
                        la ficha de la solicitud.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-calendar-range text-primary"></i> Período y motivo</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="start_date" class="form-label">Fecha inicial <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>"
                                   id="start_date" name="start_date"
                                   value="<?= e(old('start_date')) ?>" required>
                            <?php if (isset($errors['start_date'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['start_date']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="end_date" class="form-label">Fecha final <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= isset($errors['end_date']) ? 'is-invalid' : '' ?>"
                                   id="end_date" name="end_date"
                                   value="<?= e(old('end_date')) ?>" required>
                            <?php if (isset($errors['end_date'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['end_date']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="reason" class="form-label">Motivo <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors['reason']) ? 'is-invalid' : '' ?>"
                                  id="reason" name="reason" rows="4" maxlength="500"
                                  required placeholder="Describe brevemente el motivo de la solicitud"><?= e(old('reason')) ?></textarea>
                        <div class="form-text">Máximo 500 caracteres.</div>
                        <?php if (isset($errors['reason'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['reason']) ?></div>
                        <?php endif; ?>
                    </div>

                    <p class="form-text mt-3 mb-0">
                        No se permiten períodos superpuestos con solicitudes pendientes o aprobadas del mismo empleado.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a href="/leave-requests" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" <?= $canSubmit ? '' : 'disabled' ?>>
            <i class="bi bi-check2"></i> Crear solicitud
        </button>
    </div>
</form>
