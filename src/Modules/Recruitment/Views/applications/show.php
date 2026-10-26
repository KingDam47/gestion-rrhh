<?php

declare(strict_types=1);

/** @var array<string, mixed> $application */
/** @var list<string> $transitions */
/** @var bool $isFinal */

$activeTab = 'applications';
require dirname(__DIR__) . '/partials/tabs.php';

$statusClasses = [
    'received' => 'secondary',
    'screening' => 'info',
    'interview' => 'primary',
    'selected' => 'success',
    'rejected' => 'danger',
];
$currentStatus = (string) $application['status'];
$statusClass = $statusClasses[$currentStatus] ?? 'secondary';
$isSelected = $currentStatus === 'selected';
$isRejected = $currentStatus === 'rejected';
$hasEmployee = $application['employee_id'] !== null;

$steps = [
    'received' => ['label' => 'Recibida', 'icon' => 'bi-inbox'],
    'screening' => ['label' => 'En evaluación', 'icon' => 'bi-search'],
    'interview' => ['label' => 'Entrevista', 'icon' => 'bi-chat-dots'],
    'selected' => ['label' => 'Seleccionada', 'icon' => 'bi-person-check'],
];
$currentIndex = array_search($currentStatus, array_keys($steps), true);

$actionLabels = [
    'screening' => 'Pasar a evaluación',
    'interview' => 'Pasar a entrevista',
    'selected' => 'Marcar como seleccionada',
    'rejected' => 'Rechazar',
];
$actionClasses = [
    'screening' => 'btn-primary',
    'interview' => 'btn-primary',
    'selected' => 'btn-success',
    'rejected' => 'btn-outline-danger',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h1 class="h3 mb-0"><?= e((string) $application['candidate_name_natural']) ?></h1>
            <span class="badge text-bg-<?= e($statusClass) ?>"><?= e(status_label($currentStatus)) ?></span>
        </div>
        <p class="text-body-secondary mb-0">
            <?= e((string) $application['vacancy_title']) ?>
            · <?= e((string) $application['vacancy_department_name']) ?>
            · Postulación del <?= e(format_date((string) $application['applied_at'])) ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/applications" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <?php if ($hasEmployee): ?>
            <a href="/employees/<?= (int) $application['employee_id'] ?>" class="btn btn-outline-success">
                <i class="bi bi-person-badge"></i> Ver empleado
            </a>
        <?php elseif ($isSelected): ?>
            <a href="/applications/<?= (int) $application['id'] ?>/hire" class="btn btn-success">
                <i class="bi bi-person-plus"></i> Dar de alta como empleado
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isSelected && !$hasEmployee): ?>
    <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2" role="alert">
        <div>
            <i class="bi bi-check-circle"></i>
            Candidato seleccionado. El alta de empleado no es automática: RR. HH. debe confirmar los datos.
        </div>
        <a href="/applications/<?= (int) $application['id'] ?>/hire" class="btn btn-sm btn-success">
            Iniciar alta guiada
        </a>
    </div>
<?php endif; ?>

<?php if ($isSelected && $hasEmployee): ?>
    <div class="alert alert-light border" role="note">
        <i class="bi bi-person-check text-success"></i>
        Esta postulación ya tiene un empleado asociado:
        <a href="/employees/<?= (int) $application['employee_id'] ?>" class="alert-link">
            <?= e((string) ($application['employee_name'] ?? 'Ver ficha del empleado')) ?>
        </a>
        <?php if ($application['employee_document'] !== null): ?>
            · <?= e((string) $application['employee_document']) ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-diagram-3 text-primary"></i> Estado del proceso</h2>
            </div>
            <div class="card-body">
                <?php if ($isRejected): ?>
                    <div class="alert alert-danger mb-0" role="alert">
                        <i class="bi bi-x-circle"></i>
                        La postulación fue rechazada. El proceso está cerrado y no se puede reabrir.
                        <?php if ($application['updated_at'] !== null): ?>
                            <div class="small mt-1">
                                Última actualización: <?= e(format_date((string) $application['updated_at'], 'd/m/Y H:i')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <ol class="list-unstyled mb-0">
                        <?php foreach ($steps as $key => $step): ?>
                            <?php
                            $index = array_search($key, array_keys($steps), true);
                            $isDone = $currentIndex !== false && $index < $currentIndex;
                            $isCurrent = $key === $currentStatus;
                            $circleClass = $isDone ? 'success' : ($isCurrent ? 'primary' : 'light');
                            $textClass = $isCurrent ? 'fw-semibold' : ($isDone ? '' : 'text-body-secondary');
                            ?>
                            <li class="d-flex gap-3 <?= $key === 'selected' ? '' : 'mb-3' ?>">
                                <span class="badge rounded-circle text-bg-<?= e($circleClass) ?> d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                      style="width: 2.25rem; height: 2.25rem;">
                                    <i class="bi <?= $isDone ? 'bi-check-lg' : e($step['icon']) ?>"></i>
                                </span>
                                <div class="pt-1">
                                    <div class="<?= e($textClass) ?>">
                                        <?= e($step['label']) ?>
                                        <?php if ($isCurrent): ?>
                                            <span class="badge text-bg-primary ms-1">Actual</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isCurrent && $application['updated_at'] !== null): ?>
                                        <div class="small text-body-secondary">
                                            Actualizado el <?= e(format_date((string) $application['updated_at'], 'd/m/Y H:i')) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-arrow-repeat text-primary"></i> Actualizar estado</h2>
            </div>
            <div class="card-body">
                <?php if ($isFinal): ?>
                    <div class="alert alert-secondary mb-0" role="alert">
                        <i class="bi bi-lock"></i>
                        <?= $isSelected ? 'La postulación está seleccionada' : 'La postulación está rechazada' ?>
                        y es un estado final. No admite más cambios.
                    </div>
                <?php else: ?>
                    <form method="post" action="/applications/<?= (int) $application['id'] ?>/status"
                          data-confirm="¿Confirmas el cambio de estado? Los estados Seleccionada y Rechazada son finales.">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="1000"
                                      placeholder="Comentarios de la evaluación o motivo del rechazo."><?= e((string) ($application['notes'] ?? '')) ?></textarea>
                            <div class="form-text">Se guardan junto con el nuevo estado.</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($transitions as $nextStatus): ?>
                                <button type="submit" name="status" value="<?= e($nextStatus) ?>"
                                        class="btn <?= e($actionClasses[$nextStatus] ?? 'btn-primary') ?>">
                                    <?= e($actionLabels[$nextStatus] ?? status_label($nextStatus)) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-person text-primary"></i> Candidato</h2>
            </div>
            <div class="card-body">
                <p class="fw-semibold mb-1">
                    <a class="text-decoration-none" href="/candidates/<?= (int) $application['candidate_id'] ?>">
                        <?= e((string) $application['candidate_name_natural']) ?>
                    </a>
                </p>
                <p class="small mb-1">
                    <i class="bi bi-envelope"></i>
                    <a href="mailto:<?= e((string) $application['candidate_email']) ?>">
                        <?= e((string) $application['candidate_email']) ?>
                    </a>
                </p>
                <?php if ($application['candidate_phone'] !== null): ?>
                    <p class="small mb-0">
                        <i class="bi bi-telephone"></i> <?= e((string) $application['candidate_phone']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-briefcase text-primary"></i> Vacante</h2>
            </div>
            <div class="card-body">
                <p class="fw-semibold mb-1">
                    <a class="text-decoration-none" href="/vacancies/<?= (int) $application['vacancy_id'] ?>">
                        <?= e((string) $application['vacancy_title']) ?>
                    </a>
                </p>
                <dl class="row small mb-0">
                    <dt class="col-5 text-body-secondary fw-normal">Departamento</dt>
                    <dd class="col-7"><?= e((string) $application['vacancy_department_name']) ?></dd>

                    <dt class="col-5 text-body-secondary fw-normal">Cargo</dt>
                    <dd class="col-7"><?= e((string) ($application['vacancy_position_name'] ?? 'Sin cargo')) ?></dd>

                    <dt class="col-5 text-body-secondary fw-normal">Vacante</dt>
                    <dd class="col-7">
                        <span class="badge text-bg-<?= (string) $application['vacancy_status'] === 'open' ? 'success' : 'secondary' ?>">
                            <?= e(status_label((string) $application['vacancy_status'])) ?>
                        </span>
                    </dd>

                    <dt class="col-5 text-body-secondary fw-normal">Apertura</dt>
                    <dd class="col-7"><?= e(format_date((string) $application['vacancy_opening_date'])) ?></dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-journal-text text-primary"></i> Observaciones</h2>
            </div>
            <div class="card-body">
                <?php if ($application['notes'] !== null): ?>
                    <p class="mb-0" style="white-space: pre-line;"><?= e((string) $application['notes']) ?></p>
                <?php else: ?>
                    <p class="text-body-secondary mb-0">Sin observaciones registradas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
