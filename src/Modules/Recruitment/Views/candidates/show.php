<?php

declare(strict_types=1);

/** @var array<string, mixed> $candidate */
/** @var list<array<string, mixed>> $applications */

$activeTab = 'candidates';
require dirname(__DIR__) . '/partials/tabs.php';

$fullName = trim((string) $candidate['first_name'] . ' ' . (string) $candidate['last_name']);
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
        <h1 class="h3 mb-1"><?= e($fullName) ?></h1>
        <p class="text-body-secondary mb-0">
            <i class="bi bi-envelope"></i> <?= e((string) $candidate['email']) ?>
            <?php if ($candidate['phone'] !== null): ?>
                · <i class="bi bi-telephone"></i> <?= e((string) $candidate['phone']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/candidates" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <a href="/candidates/<?= (int) $candidate['id'] ?>/edit" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-person text-primary"></i> Datos del candidato</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-body-secondary fw-normal">Nombres</dt>
                    <dd class="col-sm-8"><?= e((string) $candidate['first_name']) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Apellidos</dt>
                    <dd class="col-sm-8"><?= e((string) $candidate['last_name']) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Correo</dt>
                    <dd class="col-sm-8">
                        <a href="mailto:<?= e((string) $candidate['email']) ?>"><?= e((string) $candidate['email']) ?></a>
                    </dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Teléfono</dt>
                    <dd class="col-sm-8"><?= e((string) ($candidate['phone'] ?? 'No informado')) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Registrado</dt>
                    <dd class="col-sm-8"><?= e(format_date($candidate['created_at'] !== null ? (string) $candidate['created_at'] : null, 'd/m/Y H:i')) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Actualizado</dt>
                    <dd class="col-sm-8"><?= e(format_date($candidate['updated_at'] !== null ? (string) $candidate['updated_at'] : null, 'd/m/Y H:i')) ?></dd>
                </dl>

                <?php if ($candidate['notes'] !== null): ?>
                    <hr>
                    <h3 class="h6 text-body-secondary">Observaciones</h3>
                    <p class="mb-0" style="white-space: pre-line;"><?= e((string) $candidate['notes']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5"><i class="bi bi-diagram-3 text-primary"></i> Postulaciones</h2>
                <p class="display-6 fw-semibold mb-1"><?= count($applications) ?></p>
                <p class="text-body-secondary">
                    El candidato puede postularse a más de una vacante. Solo se permite una
                    postulación por vacante.
                </p>
                <a href="/applications/create?candidate_id=<?= (int) $candidate['id'] ?>"
                   class="btn btn-outline-primary w-100">
                    <i class="bi bi-plus-lg"></i> Nueva postulación
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0"><i class="bi bi-list-check text-primary"></i> Historial de postulaciones</h2>
    </div>

    <?php if ($applications === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-diagram-3 display-5 text-body-tertiary"></i>
            <p class="text-body-secondary mt-3 mb-3">Este candidato aún no tiene postulaciones.</p>
            <a href="/applications/create?candidate_id=<?= (int) $candidate['id'] ?>"
               class="btn btn-primary">Registrar postulación</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Vacante</th>
                    <th scope="col">Departamento</th>
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
                                <?= e((string) $application['vacancy_title']) ?>
                            </a>
                        </td>
                        <td><?= e((string) $application['vacancy_department_name']) ?></td>
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
