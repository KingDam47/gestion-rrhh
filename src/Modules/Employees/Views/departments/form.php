<?php

declare(strict_types=1);

/** @var array<string, mixed> $department */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var string $formAction */

$isActive = (int) $department['is_active'] === 1;
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h1 class="h3 mb-0"><?= e((string) $department['name']) ?></h1>
            <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>">
                <?= e(status_label($isActive ? 'active' : 'inactive')) ?>
            </span>
        </div>
        <p class="text-body-secondary mb-0">Edita el nombre o la descripción del departamento.</p>
    </div>
    <a href="/departments" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="post" action="<?= e($formAction) ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               id="name" name="name" maxlength="100"
                               value="<?= e(old('name', (string) $department['name'])) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  id="description" name="description" rows="5"
                                  maxlength="1000"><?= e(old('description', (string) ($department['description'] ?? ''))) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        <a href="/departments" class="btn btn-outline-secondary">Cancelar</a>
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
                    <dt class="col-7 text-body-secondary fw-normal">Cargos asociados</dt>
                    <dd class="col-5 text-end"><?= (int) $department['position_count'] ?></dd>

                    <dt class="col-7 text-body-secondary fw-normal">Empleados totales</dt>
                    <dd class="col-5 text-end"><?= (int) $department['employee_count'] ?></dd>

                    <dt class="col-7 text-body-secondary fw-normal">Empleados activos</dt>
                    <dd class="col-5 text-end"><?= (int) $department['active_employee_count'] ?></dd>
                </dl>

                <hr>

                <p class="small text-body-secondary mb-0">
                    El estado se cambia desde el listado de departamentos. Un departamento inactivo conserva
                    todos sus empleados y cargos, pero no se puede asignar a nuevos registros.
                </p>
            </div>
        </div>
    </div>
</div>
