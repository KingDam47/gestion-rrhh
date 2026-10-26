<?php

declare(strict_types=1);

/** @var array<string, mixed> $application */
/** @var list<array<string, mixed>> $departments */
/** @var list<array<string, mixed>> $positions */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var string $formAction */

$activeTab = 'applications';
require dirname(__DIR__) . '/partials/tabs.php';

$departmentIds = array_map(
    static fn (array $department): int => (int) $department['id'],
    $departments
);
$suggestedDepartment = in_array((int) $application['vacancy_department_id'], $departmentIds, true)
    ? (int) $application['vacancy_department_id']
    : 0;

$suggestedPosition = 0;
foreach ($positions as $position) {
    if (
        (int) $position['id'] === (int) $application['vacancy_position_id']
        && (int) $position['department_id'] === $suggestedDepartment
    ) {
        $suggestedPosition = (int) $position['id'];
        break;
    }
}

$selectedDepartment = (int) old('department_id', (string) $suggestedDepartment);
$selectedPosition = (int) old('position_id', (string) $suggestedPosition);
$canSubmit = $departments !== [];
$cancelUrl = '/applications/' . (int) $application['id'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Alta guiada de empleado</h1>
        <p class="text-body-secondary mb-0">
            Confirma los datos de <?= e((string) $application['candidate_name_natural']) ?>
            para registrarlo como empleado.
        </p>
    </div>
    <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver a la postulación
    </a>
</div>

<div class="alert alert-light border" role="note">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <span class="text-body-secondary small text-uppercase d-block">Candidato</span>
            <strong><?= e((string) $application['candidate_name_natural']) ?></strong>
            <div class="small text-body-secondary"><?= e((string) $application['candidate_email']) ?></div>
        </div>
        <div class="col-12 col-md-6">
            <span class="text-body-secondary small text-uppercase d-block">Vacante</span>
            <strong><?= e((string) $application['vacancy_title']) ?></strong>
            <div class="small text-body-secondary">
                <?= e((string) $application['vacancy_department_name']) ?>
                <?php if ($application['vacancy_position_name'] !== null): ?>
                    · <?= e((string) $application['vacancy_position_name']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <hr class="my-3">
    <p class="small mb-0">
        <i class="bi bi-info-circle text-primary"></i>
        El alta crea un empleado <strong>activo</strong> y vincula la postulación. Revisa el documento
        y el correo: deben ser únicos entre los empleados. No se copian currículos ni documentos.
    </p>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<?php if (!$canSubmit): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-info-circle"></i> No hay departamentos activos disponibles.
        <a href="/departments" class="alert-link">Crea o reactiva un departamento</a> antes de dar de alta al empleado.
    </div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-person text-primary"></i> Datos personales</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="document" class="form-label">Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['document']) ? 'is-invalid' : '' ?>"
                                   id="document" name="document" maxlength="30"
                                   value="<?= e(old('document')) ?>" autocomplete="off" required>
                            <div class="form-text">Obligatorio y único. Se guarda sin espacios y en mayúsculas.</div>
                            <?php if (isset($errors['document'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['document']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="hire_date" class="form-label">Fecha de ingreso <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= isset($errors['hire_date']) ? 'is-invalid' : '' ?>"
                                   id="hire_date" name="hire_date"
                                   value="<?= e(old('hire_date', date('Y-m-d'))) ?>" required>
                            <?php if (isset($errors['hire_date'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['hire_date']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="first_name" class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                                   id="first_name" name="first_name" maxlength="80"
                                   value="<?= e(old('first_name', (string) $application['candidate_first_name'])) ?>"
                                   autocomplete="given-name" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['first_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="last_name" class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                   id="last_name" name="last_name" maxlength="80"
                                   value="<?= e(old('last_name', (string) $application['candidate_last_name'])) ?>"
                                   autocomplete="family-name" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['last_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   id="email" name="email" maxlength="190"
                                   value="<?= e(old('email', (string) ($application['candidate_email'] ?? ''))) ?>"
                                   autocomplete="email">
                            <div class="form-text">Opcional. Si se informa, no puede repetirse entre empleados.</div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                   id="phone" name="phone" maxlength="30"
                                   value="<?= e(old('phone', (string) ($application['candidate_phone'] ?? ''))) ?>"
                                   autocomplete="tel">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['phone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-briefcase text-primary"></i> Datos laborales</h2>
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
                                    <?= e((string) $department['name']) ?>
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
                                    · <?= e((string) $position['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Solo se muestran los cargos del departamento seleccionado.</div>
                        <?php if (isset($errors['position_id'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['position_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="alert alert-light border mb-0" role="note">
                        <i class="bi bi-link-45deg text-primary"></i>
                        La postulación quedará vinculada al empleado creado.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-success" <?= $canSubmit ? '' : 'disabled' ?>>
            <i class="bi bi-person-check"></i> Confirmar alta de empleado
        </button>
    </div>
</form>
