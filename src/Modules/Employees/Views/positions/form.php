<?php

declare(strict_types=1);

/** @var array<string, mixed> $position */
/** @var list<array<string, mixed>> $departments */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var string $formAction */

$isActive = (int) $position['is_active'] === 1;
$departmentActive = (int) $position['department_active'] === 1;
$canChangeDepartment = (int) $position['employee_count'] === 0;
$selectedDepartment = $canChangeDepartment
    ? (int) old('department_id', (string) $position['department_id'])
    : (int) $position['department_id'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h1 class="h3 mb-0"><?= e((string) $position['name']) ?></h1>
            <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>">
                <?= e(status_label($isActive ? 'active' : 'inactive')) ?>
            </span>
        </div>
        <p class="text-body-secondary mb-0">
            <?= e((string) $position['department_name']) ?>
            <?php if (!$departmentActive): ?>
                · <span class="text-warning">Departamento inactivo</span>
            <?php endif; ?>
        </p>
    </div>
    <a href="/positions" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<?php if (!$canChangeDepartment): ?>
    <div class="alert alert-info" role="note">
        <i class="bi bi-info-circle"></i>
        Este cargo tiene empleados asignados. Para cambiar de departamento, crea un cargo nuevo y reasigna
        a los empleados desde sus fichas.
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="post" action="<?= e($formAction) ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="department_id" class="form-label">Departamento <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['department_id']) ? 'is-invalid' : '' ?>"
                                id="department_id" name="department_id" required
                                <?= $canChangeDepartment ? '' : 'disabled' ?>>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= (int) $department['id'] ?>"
                                    <?= $selectedDepartment === (int) $department['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $department['name']) ?><?= (int) $department['is_active'] === 1 ? '' : ' (inactivo)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$canChangeDepartment): ?>
                            <input type="hidden" name="department_id" value="<?= (int) $position['department_id'] ?>">
                            <div class="form-text">El departamento está bloqueado porque el cargo tiene empleados.</div>
                        <?php endif; ?>
                        <?php if (isset($errors['department_id'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['department_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del cargo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               id="name" name="name" maxlength="120"
                               value="<?= e(old('name', (string) $position['name'])) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  id="description" name="description" rows="5"
                                  maxlength="1000"><?= e(old('description', (string) ($position['description'] ?? ''))) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        <a href="/positions" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-info-circle text-primary"></i> Resumen</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-7 text-body-secondary fw-normal">Empleados totales</dt>
                    <dd class="col-5 text-end"><?= (int) $position['employee_count'] ?></dd>

                    <dt class="col-7 text-body-secondary fw-normal">Empleados activos</dt>
                    <dd class="col-5 text-end"><?= (int) $position['active_employee_count'] ?></dd>

                    <dt class="col-7 text-body-secondary fw-normal">Departamento</dt>
                    <dd class="col-5 text-end"><?= e((string) $position['department_name']) ?></dd>
                </dl>

                <hr>

                <p class="small text-body-secondary mb-0">
                    El estado se cambia desde el listado de cargos. Desactivar un cargo no modifica la ficha
                    de los empleados que lo tienen asignado.
                </p>
            </div>
        </div>
    </div>
</div>
