<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $vacancy */
/** @var list<array<string, mixed>> $departments */
/** @var list<array<string, mixed>> $positions */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var string $formAction */
/** @var bool $isEdit */

$selectedDepartment = (int) old('department_id', (string) ($vacancy['department_id'] ?? ''));
$selectedPosition = (int) old('position_id', (string) ($vacancy['position_id'] ?? ''));
$hasActiveDepartment = array_filter(
    $departments,
    static fn (array $department): bool => (int) $department['is_active'] === 1
) !== [];
$canSubmit = $isEdit || $hasActiveDepartment;
$cancelUrl = $isEdit ? '/vacancies/' . (int) $vacancy['id'] : '/vacancies';
$openingDate = old('opening_date', (string) ($vacancy['opening_date'] ?? date('Y-m-d')));
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $isEdit ? 'Editar vacante' : 'Nueva vacante' ?></h1>
        <p class="text-body-secondary mb-0">
            <?= $isEdit
                ? 'Actualiza la información de la búsqueda mientras esté abierta.'
                : 'Registra la búsqueda y el departamento al que pertenece.' ?>
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

<?php if (!$hasActiveDepartment): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-info-circle"></i> No hay departamentos activos disponibles.
        <a href="/departments" class="alert-link">Crea o reactiva un departamento</a> antes de registrar vacantes.
    </div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-briefcase text-primary"></i> Datos de la vacante</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               id="title" name="title" maxlength="150"
                               value="<?= e(old('title', (string) ($vacancy['title'] ?? ''))) ?>"
                               placeholder="Ejemplo: Analista de recursos humanos" required>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['title']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  id="description" name="description" rows="7" maxlength="2000"
                                  placeholder="Funciones, requisitos y condiciones del puesto."><?= e(old('description', (string) ($vacancy['description'] ?? ''))) ?></textarea>
                        <div class="form-text">Opcional. No incluyas datos personales de candidatos.</div>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-calendar3 text-primary"></i> Clasificación y fechas</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Departamento <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['department_id']) ? 'is-invalid' : '' ?>"
                                id="department_id" name="department_id" data-employee-department required>
                            <option value="">Selecciona un departamento</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= (int) $department['id'] ?>"
                                    <?= $selectedDepartment === (int) $department['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $department['name']) ?><?= (int) $department['is_active'] === 1 ? '' : ' (inactivo)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['department_id'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['department_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="position_id" class="form-label">Cargo</label>
                        <select class="form-select <?= isset($errors['position_id']) ? 'is-invalid' : '' ?>"
                                id="position_id" name="position_id" data-employee-position>
                            <option value="">Sin cargo</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?= (int) $position['id'] ?>"
                                        data-department="<?= (int) $position['department_id'] ?>"
                                    <?= $selectedPosition === (int) $position['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $position['name']) ?>
                                    · <?= e((string) $position['department_name']) ?><?= (int) $position['is_active'] === 1 ? '' : ' (inactivo)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Solo se muestran los cargos del departamento seleccionado.</div>
                        <?php if (isset($errors['position_id'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['position_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="opening_date" class="form-label">Fecha de apertura <span class="text-danger">*</span></label>
                        <input type="date" class="form-control <?= isset($errors['opening_date']) ? 'is-invalid' : '' ?>"
                               id="opening_date" name="opening_date"
                               value="<?= e($openingDate) ?>" required>
                        <?php if (isset($errors['opening_date'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['opening_date']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <label for="closing_date" class="form-label">Fecha de cierre</label>
                        <input type="date" class="form-control <?= isset($errors['closing_date']) ? 'is-invalid' : '' ?>"
                               id="closing_date" name="closing_date"
                               value="<?= e(old('closing_date', (string) ($vacancy['closing_date'] ?? ''))) ?>">
                        <div class="form-text">Opcional. No puede ser anterior a la apertura. El cierre manual fija la fecha real.</div>
                        <?php if (isset($errors['closing_date'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['closing_date']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" <?= $canSubmit ? '' : 'disabled' ?>>
            <i class="bi bi-check2"></i> <?= $isEdit ? 'Guardar cambios' : 'Crear vacante' ?>
        </button>
    </div>
</form>
