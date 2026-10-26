<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $positions */
/** @var list<array<string, mixed>> $departments */
/** @var list<array<string, mixed>> $formDepartments */
/** @var int|null $departmentFilter */
/** @var array<string, string> $errors */
/** @var string|null $generalError */

$activeTab = 'positions';
require __DIR__ . '/../partials/tabs.php';

$selectedDepartment = (int) old('department_id', (string) ($departmentFilter ?? ''));
$canCreate = $formDepartments !== [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Cargos</h1>
        <p class="text-body-secondary mb-0">
            Cada cargo pertenece a un departamento. Un cargo con empleados asignados no cambia de departamento.
        </p>
    </div>
    <span class="badge text-bg-light border fs-6"><?= count($positions) ?> cargos</span>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<?php if (!$canCreate): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-info-circle"></i> No hay departamentos activos.
        <a href="/departments" class="alert-link">Crea o reactiva un departamento</a> antes de registrar cargos.
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-plus-circle text-primary"></i> Nuevo cargo</h2>
            </div>
            <div class="card-body">
                <form method="post" action="/positions" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="department_id" class="form-label">Departamento <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['department_id']) ? 'is-invalid' : '' ?>"
                                id="department_id" name="department_id" required>
                            <option value="">Selecciona un departamento</option>
                            <?php foreach ($formDepartments as $department): ?>
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
                        <label for="name" class="form-label">Nombre del cargo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               id="name" name="name" maxlength="120" value="<?= e(old('name')) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  id="description" name="description" rows="3"
                                  maxlength="1000"><?= e(old('description')) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" <?= $canCreate ? '' : 'disabled' ?>>
                        <i class="bi bi-check2"></i> Crear cargo
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <form method="get" action="/positions" class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-8">
                        <label for="filter_department_id" class="form-label">Filtrar por departamento</label>
                        <select class="form-select" id="filter_department_id" name="department_id">
                            <option value="">Todos los departamentos</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= (int) $department['id'] ?>"
                                    <?= (int) $departmentFilter === (int) $department['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $department['name']) ?><?= (int) $department['is_active'] === 1 ? '' : ' (inactivo)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                        <a href="/positions" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <?php if ($positions === []): ?>
                <div class="card-body py-5 text-center">
                    <i class="bi bi-briefcase display-5 text-body-tertiary"></i>
                    <h2 class="h5 mt-3">No hay cargos para mostrar</h2>
                    <p class="text-body-secondary mb-0">
                        <?= $departmentFilter !== null
                            ? 'El departamento seleccionado no tiene cargos. Prueba con otro filtro.'
                            : 'Crea el primer cargo con el formulario de la izquierda.' ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th scope="col">Cargo</th>
                            <th scope="col">Departamento</th>
                            <th scope="col" class="text-center">Empleados</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($positions as $position): ?>
                            <?php
                            $isActive = (int) $position['is_active'] === 1;
                            $departmentActive = (int) $position['department_active'] === 1;
                            $statusClass = $isActive ? 'success' : 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?= e((string) $position['name']) ?></span>
                                    <?php if ($position['description'] !== null && $position['description'] !== ''): ?>
                                        <div class="small text-body-secondary">
                                            <?= e((string) $position['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e((string) $position['department_name']) ?>
                                    <?php if (!$departmentActive): ?>
                                        <span class="badge text-bg-warning ms-1">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold"><?= (int) $position['active_employee_count'] ?></span>
                                    <span class="text-body-secondary small">/ <?= (int) $position['employee_count'] ?></span>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= e($statusClass) ?>">
                                        <?= e(status_label($isActive ? 'active' : 'inactive')) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                        <a href="/positions/<?= (int) $position['id'] ?>/edit"
                                           class="btn btn-sm btn-outline-secondary" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post"
                                              action="/positions/<?= (int) $position['id'] ?>/status"
                                              data-confirm="<?= $isActive
                                                  ? '¿Desactivar este cargo? Los empleados asociados conservarán su historial.'
                                                  : '¿Reactivar este cargo?' ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-<?= $isActive ? 'danger' : 'success' ?>"
                                                    title="<?= $isActive ? 'Desactivar' : 'Reactivar' ?>">
                                                <i class="bi <?= $isActive ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
