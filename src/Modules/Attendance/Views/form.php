<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $record */
/** @var list<array<string, mixed>> $employees */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var string $formAction */
/** @var bool $isEdit */

$selectedEmployee = (int) old('employee_id', (string) ($record['employee_id'] ?? ''));
$selectedStatus = old('status', (string) ($record['status'] ?? 'present'));
$canSubmit = $employees !== [];
$cancelUrl = '/attendance';
$today = date('Y-m-d');
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $isEdit ? 'Editar asistencia' : 'Nuevo registro de asistencia' ?></h1>
        <p class="text-body-secondary mb-0">
            <?= $isEdit
                ? 'Corrige las horas, el estado o la observación del registro.'
                : 'Registra la entrada y la salida de un empleado para una fecha.' ?>
        </p>
    </div>
    <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">
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
        <a href="/employees" class="alert-link">Activa o registra un empleado</a> antes de cargar asistencia.
    </div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-person text-primary"></i> Empleado y fecha</h2>
                </div>
                <div class="card-body">
                    <?php if ($isEdit): ?>
                        <div class="mb-3">
                            <p class="form-label">Empleado</p>
                            <div class="form-control bg-body-tertiary" role="note">
                                <?= e((string) ($record['employee_name'] ?? 'Empleado no disponible')) ?>
                                <span class="text-body-secondary">
                                    · <?= e((string) ($record['employee_document'] ?? '')) ?>
                                </span>
                            </div>
                            <div class="form-text">El empleado no cambia al corregir un registro existente.</div>
                        </div>
                    <?php else: ?>
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
                    <?php endif; ?>

                    <div>
                        <label for="work_date" class="form-label">Fecha de trabajo <span class="text-danger">*</span></label>
                        <input type="date" class="form-control <?= isset($errors['work_date']) ? 'is-invalid' : '' ?>"
                               id="work_date" name="work_date" max="<?= e($today) ?>"
                               value="<?= e(old('work_date', (string) ($record['work_date'] ?? $today))) ?>"
                               required>
                        <div class="form-text">No se admiten fechas futuras. Solo un registro por empleado y día.</div>
                        <?php if (isset($errors['work_date'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['work_date']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-clock text-primary"></i> Horario y estado</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>"
                                id="status" name="status" data-attendance-status required>
                            <option value="present" <?= $selectedStatus === 'present' ? 'selected' : '' ?>>Presente</option>
                            <option value="late" <?= $selectedStatus === 'late' ? 'selected' : '' ?>>Tardanza</option>
                            <option value="absent" <?= $selectedStatus === 'absent' ? 'selected' : '' ?>>Ausencia</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['status']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3" data-attendance-times>
                        <div class="col-12 col-sm-6">
                            <label for="check_in" class="form-label">Hora de entrada</label>
                            <input type="time" class="form-control <?= isset($errors['check_in']) ? 'is-invalid' : '' ?>"
                                   id="check_in" name="check_in"
                                   value="<?= e(old('check_in', ($record['check_in'] ?? null) !== null ? substr((string) $record['check_in'], 0, 5) : '')) ?>">
                            <?php if (isset($errors['check_in'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['check_in']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="check_out" class="form-label">Hora de salida</label>
                            <input type="time" class="form-control <?= isset($errors['check_out']) ? 'is-invalid' : '' ?>"
                                   id="check_out" name="check_out"
                                   value="<?= e(old('check_out', ($record['check_out'] ?? null) !== null ? substr((string) $record['check_out'], 0, 5) : '')) ?>">
                            <div class="form-text">Puede quedar vacía si aún no ha salido.</div>
                            <?php if (isset($errors['check_out'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['check_out']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 mb-3" role="note">
                        <i class="bi bi-info-circle text-primary"></i>
                        Presente y tardanza requieren hora de entrada. La ausencia no lleva horas y necesita una
                        observación con el motivo.
                    </div>

                    <div>
                        <label for="notes" class="form-label">Observación</label>
                        <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>"
                                  id="notes" name="notes" rows="3" maxlength="500"
                                  placeholder="Detalle de la tardanza, ausencia o corrección"><?= e(old('notes', (string) ($record['notes'] ?? ''))) ?></textarea>
                        <div class="form-text">Opcional, salvo en ausencias. Máximo 500 caracteres.</div>
                        <?php if (isset($errors['notes'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['notes']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" <?= $canSubmit ? '' : 'disabled' ?>>
            <i class="bi bi-check2"></i> <?= $isEdit ? 'Guardar cambios' : 'Registrar asistencia' ?>
        </button>
    </div>
</form>
