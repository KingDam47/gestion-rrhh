<?php

declare(strict_types=1);

/** @var array<string, mixed> $vacancy */
/** @var list<array<string, mixed>> $applications */

$activeTab = 'vacancies';
require dirname(__DIR__) . '/partials/tabs.php';

$isOpen = (string) $vacancy['status'] === 'open';
$statusClass = $isOpen ? 'success' : 'secondary';
$statusClasses = [
    'received' => 'secondary',
    'screening' => 'info',
    'interview' => 'primary',
    'selected' => 'success',
    'rejected' => 'danger',
];
$applicationCount = count($applications);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h1 class="h3 mb-0"><?= e((string) $vacancy['title']) ?></h1>
            <span class="badge text-bg-<?= e($statusClass) ?>"><?= e(status_label((string) $vacancy['status'])) ?></span>
        </div>
        <p class="text-body-secondary mb-0">
            <?= e((string) $vacancy['department_name']) ?>
            <?php if ($vacancy['position_name'] !== null): ?>
                · <?= e((string) $vacancy['position_name']) ?>
            <?php endif; ?>
            · Apertura <?= e(format_date((string) $vacancy['opening_date'])) ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/vacancies" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <?php if ($isOpen): ?>
            <a href="/applications/create?vacancy_id=<?= (int) $vacancy['id'] ?>" class="btn btn-outline-primary">
                <i class="bi bi-person-plus"></i> Postular candidato
            </a>
            <a href="/vacancies/<?= (int) $vacancy['id'] ?>/edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <form method="post" action="/vacancies/<?= (int) $vacancy['id'] ?>/close"
                  data-confirm="¿Cerrar esta vacante? No se podrá reabrir ni editar, y dejará de aceptar postulaciones.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-lock"></i> Cerrar vacante
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isOpen): ?>
    <div class="alert alert-secondary" role="alert">
        <i class="bi bi-lock"></i>
        Esta vacante está cerrada y no admite nuevas postulaciones. Su información y sus
        postulaciones se conservan para consulta.
    </div>
<?php endif; ?>

<?php if ((int) $vacancy['department_active'] !== 1): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        El departamento de esta vacante está inactivo.
        <a href="/departments/<?= (int) $vacancy['department_id'] ?>/edit" class="alert-link">Revisar departamento</a>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-briefcase text-primary"></i> Información de la vacante</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-body-secondary fw-normal">Departamento</dt>
                    <dd class="col-sm-8"><?= e((string) $vacancy['department_name']) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Cargo</dt>
                    <dd class="col-sm-8"><?= e((string) ($vacancy['position_name'] ?? 'Sin cargo')) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Apertura</dt>
                    <dd class="col-sm-8"><?= e(format_date((string) $vacancy['opening_date'])) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Cierre</dt>
                    <dd class="col-sm-8"><?= e(format_date($vacancy['closing_date'] !== null ? (string) $vacancy['closing_date'] : null)) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Postulaciones</dt>
                    <dd class="col-sm-8"><?= $applicationCount ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Actualizada</dt>
                    <dd class="col-sm-8"><?= e(format_date($vacancy['updated_at'] !== null ? (string) $vacancy['updated_at'] : null, 'd/m/Y H:i')) ?></dd>
                </dl>

                <?php if ($vacancy['description'] !== null): ?>
                    <hr>
                    <h3 class="h6 text-body-secondary">Descripción</h3>
                    <p class="mb-0" style="white-space: pre-line;"><?= e((string) $vacancy['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5"><i class="bi bi-diagram-3 text-primary"></i> Seguimiento</h2>
                <p class="text-body-secondary">
                    Cada postulación avanza por las etapas de evaluación. Al seleccionar un candidato
                    podrás iniciar el alta guiada como empleado.
                </p>
                <a href="/applications?vacancy_id=<?= (int) $vacancy['id'] ?>"
                   class="btn btn-outline-primary w-100">
                    <i class="bi bi-list-check"></i> Ver postulaciones de esta vacante
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h5 mb-0"><i class="bi bi-people text-primary"></i> Postulaciones</h2>
        <?php if ($isOpen): ?>
            <a href="/applications/create?vacancy_id=<?= (int) $vacancy['id'] ?>"
               class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Nueva postulación
            </a>
        <?php endif; ?>
    </div>

    <?php if ($applications === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-person-lines-fill display-5 text-body-tertiary"></i>
            <p class="text-body-secondary mt-3 mb-3">Todavía no hay candidatos postulados a esta vacante.</p>
            <?php if ($isOpen): ?>
                <a href="/applications/create?vacancy_id=<?= (int) $vacancy['id'] ?>" class="btn btn-primary">
                    Registrar postulación
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Candidato</th>
                    <th scope="col">Postulación</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Empleado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $application): ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none"
                               href="/applications/<?= (int) $application['id'] ?>">
                                <?= e((string) $application['candidate_name_natural']) ?>
                            </a>
                            <div class="small text-body-secondary"><?= e((string) $application['candidate_email']) ?></div>
                        </td>
                        <td><?= e(format_date((string) $application['applied_at'])) ?></td>
                        <td>
                            <span class="badge text-bg-<?= e($statusClasses[(string) $application['status']] ?? 'secondary') ?>">
                                <?= e(status_label((string) $application['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($application['employee_id'] !== null): ?>
                                <a href="/employees/<?= (int) $application['employee_id'] ?>">
                                    <?= e((string) ($application['employee_name'] ?? 'Ver empleado')) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-body-secondary">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/applications/<?= (int) $application['id'] ?>"
                               class="btn btn-sm btn-outline-secondary">Ver seguimiento</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
