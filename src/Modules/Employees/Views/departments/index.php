<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $departments */
/** @var array<string, string> $errors */
/** @var string|null $generalError */

$activeTab = 'departments';
require __DIR__ . '/../partials/tabs.php';

$activeDepartments = count(array_filter(
    $departments,
    static fn (array $department): bool => (int) $department['is_active'] === 1
));
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Departamentos</h1>
        <p class="text-body-secondary mb-0">
            Organiza las áreas de la empresa. Los departamentos con historial no se eliminan: se desactivan.
        </p>
    </div>
    <span class="badge text-bg-light border fs-6">
        <?= $activeDepartments ?> activos de <?= count($departments) ?>
    </span>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-plus-circle text-primary"></i> Nuevo departamento</h2>
            </div>
            <div class="card-body">
                <form method="post" action="/departments" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               id="name" name="name" maxlength="100" value="<?= e(old('name')) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  id="description" name="description" rows="3" maxlength="1000"><?= e(old('description')) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2"></i> Crear departamento
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <?php if ($departments === []): ?>
                <div class="card-body py-5 text-center">
                    <i class="bi bi-diagram-3 display-5 text-body-tertiary"></i>
                    <h2 class="h5 mt-3">Todavía no hay departamentos</h2>
                    <p class="text-body-secondary mb-0">Crea el primero con el formulario de la izquierda.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th scope="col">Departamento</th>
                            <th scope="col" class="text-center">Cargos</th>
                            <th scope="col" class="text-center">Empleados</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($departments as $department): ?>
                            <?php
                            $isActive = (int) $department['is_active'] === 1;
                            $statusClass = $isActive ? 'success' : 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?= e((string) $department['name']) ?></span>
                                    <?php if ($department['description'] !== null && $department['description'] !== ''): ?>
                                        <div class="small text-body-secondary">
                                            <?= e((string) $department['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="/positions?department_id=<?= (int) $department['id'] ?>"
                                       class="text-decoration-none">
                                        <?= (int) $department['position_count'] ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold"><?= (int) $department['active_employee_count'] ?></span>
                                    <span class="text-body-secondary small">/ <?= (int) $department['employee_count'] ?></span>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= e($statusClass) ?>">
                                        <?= e(status_label($isActive ? 'active' : 'inactive')) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                        <a href="/departments/<?= (int) $department['id'] ?>/edit"
                                           class="btn btn-sm btn-outline-secondary" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post"
                                              action="/departments/<?= (int) $department['id'] ?>/status"
                                              data-confirm="<?= $isActive
                                                  ? '¿Desactivar este departamento? Sus empleados y cargos conservarán el historial.'
                                                  : '¿Reactivar este departamento?' ?>">
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
