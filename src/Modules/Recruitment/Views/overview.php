<?php

declare(strict_types=1);

/** @var array{total: int, open: int, closed: int} $vacancyStats */
/** @var array{total: int} $candidateStats */
/** @var array{total: int, received: int, screening: int, interview: int, selected: int, rejected: int, pending_hire: int} $applicationStats */
/** @var list<array<string, mixed>> $openVacancies */
/** @var list<array<string, mixed>> $selectedApplications */

$activeTab = 'overview';
require __DIR__ . '/partials/tabs.php';

$inProcess = (int) $applicationStats['received']
    + (int) $applicationStats['screening']
    + (int) $applicationStats['interview'];
$statusClasses = [
    'received' => 'secondary',
    'screening' => 'info',
    'interview' => 'primary',
    'selected' => 'success',
    'rejected' => 'danger',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Reclutamiento y selección</h1>
        <p class="text-body-secondary mb-0">Vacantes, candidatos y seguimiento de los procesos de selección.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/vacancies/create" class="btn btn-outline-secondary">
            <i class="bi bi-briefcase"></i> Nueva vacante
        </a>
        <a href="/applications/create" class="btn btn-primary">
            <i class="bi bi-diagram-3"></i> Nueva postulación
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Vacantes abiertas</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $vacancyStats['open'] ?></p>
                <p class="small text-body-secondary mb-0">de <?= (int) $vacancyStats['total'] ?> registradas</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Candidatos</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $candidateStats['total'] ?></p>
                <p class="small text-body-secondary mb-0">registrados en el proceso</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Postulaciones en curso</p>
                <p class="display-6 fw-semibold text-primary mb-0"><?= $inProcess ?></p>
                <p class="small text-body-secondary mb-0">recibidas, en evaluación o entrevista</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Pendientes de alta</p>
                <p class="display-6 fw-semibold text-warning mb-0"><?= (int) $applicationStats['pending_hire'] ?></p>
                <p class="small text-body-secondary mb-0">seleccionados sin empleado asociado</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="h5 mb-0"><i class="bi bi-briefcase text-primary"></i> Vacantes abiertas</h2>
                <a href="/vacancies" class="btn btn-sm btn-outline-secondary">Ver todas</a>
            </div>
            <?php if ($openVacancies === []): ?>
                <div class="card-body py-4 text-center">
                    <p class="text-body-secondary mb-3">No hay vacantes abiertas en este momento.</p>
                    <a href="/vacancies/create" class="btn btn-sm btn-primary">Crear vacante</a>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($openVacancies as $vacancy): ?>
                        <a href="/vacancies/<?= (int) $vacancy['id'] ?>"
                           class="list-group-item list-group-item-action py-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold"><?= e((string) $vacancy['title']) ?></div>
                                    <div class="small text-body-secondary">
                                        <i class="bi bi-diagram-3"></i> <?= e((string) $vacancy['department_name']) ?>
                                        <?php if ($vacancy['position_name'] !== null): ?>
                                            · <?= e((string) $vacancy['position_name']) ?>
                                        <?php endif; ?>
                                        · Apertura <?= e(format_date((string) $vacancy['opening_date'])) ?>
                                    </div>
                                </div>
                                <span class="badge text-bg-light">
                                    <?= (int) $vacancy['application_count'] ?> postulación(es)
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="h5 mb-0"><i class="bi bi-person-check text-primary"></i> Seleccionados</h2>
                <a href="/applications?status=selected" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <?php if ($selectedApplications === []): ?>
                <div class="card-body py-4 text-center">
                    <p class="text-body-secondary mb-0">Aún no hay candidatos seleccionados.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($selectedApplications as $application): ?>
                        <div class="list-group-item py-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <a href="/applications/<?= (int) $application['id'] ?>"
                                       class="fw-semibold text-decoration-none">
                                        <?= e((string) $application['candidate_name_natural']) ?>
                                    </a>
                                    <div class="small text-body-secondary">
                                        <?= e((string) $application['vacancy_title']) ?>
                                        · <?= e(format_date((string) $application['applied_at'])) ?>
                                    </div>
                                </div>
                                <?php if ($application['employee_id'] === null): ?>
                                    <a href="/applications/<?= (int) $application['id'] ?>/hire"
                                       class="btn btn-sm btn-success">
                                        <i class="bi bi-person-plus"></i> Dar de alta
                                    </a>
                                <?php else: ?>
                                    <a href="/employees/<?= (int) $application['employee_id'] ?>"
                                       class="btn btn-sm btn-outline-secondary">Ver empleado</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="/candidates/create" class="btn btn-outline-secondary">
        <i class="bi bi-person-plus"></i> Registrar candidato
    </a>
</div>
