<?php

declare(strict_types=1);

/** @var array<string, mixed> $currentUser */
/** @var array<string, mixed> $summary */

$attendanceToday = $summary['attendance']['today'];
$attendanceMonth = $summary['attendance']['month'];
$applications = $summary['applications'];
$pendingHire = (int) $applications['pending_hire'];
$monthQuery = rawurlencode((string) $summary['month']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Hola, <?= e((string) $currentUser['name']) ?></h1>
        <p class="text-body-secondary mb-0">
            Resumen de la operación al <?= e(format_date((string) $summary['today'])) ?>.
        </p>
    </div>
    <span class="badge text-bg-primary fs-6"><?= e(status_label((string) $currentUser['role'])) ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <p class="text-body-secondary small text-uppercase mb-1">
                        <a href="/employees?status=active" class="stretched-link text-reset text-decoration-none">
                            Empleados activos
                        </a>
                    </p>
                    <span class="text-primary fs-5"><i class="bi bi-people"></i></span>
                </div>
                <p class="display-6 fw-semibold mb-1"><?= (int) $summary['employees']['active'] ?></p>
                <p class="small text-body-secondary mb-0">
                    Inactivos: <?= (int) $summary['employees']['inactive'] ?> ·
                    Total: <?= (int) $summary['employees']['total'] ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <p class="text-body-secondary small text-uppercase mb-1">
                        <a href="/attendance/report?month=<?= $monthQuery ?>"
                           class="stretched-link text-reset text-decoration-none">
                            Asistencia de <?= e((string) $summary['month_label']) ?>
                        </a>
                    </p>
                    <span class="text-success fs-5"><i class="bi bi-calendar-check"></i></span>
                </div>
                <p class="display-6 fw-semibold mb-1"><?= (int) $attendanceMonth['total'] ?></p>
                <p class="small text-body-secondary mb-0">
                    Hoy: <?= (int) $attendanceToday['total'] ?> ·
                    Tardanzas: <?= (int) $attendanceMonth['late'] ?> ·
                    Ausencias: <?= (int) $attendanceMonth['absent'] ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <p class="text-body-secondary small text-uppercase mb-1">
                        <a href="/leave-requests?status=pending"
                           class="stretched-link text-reset text-decoration-none">
                            Permisos pendientes
                        </a>
                    </p>
                    <span class="text-warning-emphasis fs-5"><i class="bi bi-calendar2-week"></i></span>
                </div>
                <p class="display-6 fw-semibold mb-1"><?= (int) $summary['leaves']['pending'] ?></p>
                <p class="small text-body-secondary mb-0">
                    Aprobados: <?= (int) $summary['leaves']['approved'] ?> ·
                    Rechazados: <?= (int) $summary['leaves']['rejected'] ?> ·
                    Total: <?= (int) $summary['leaves']['total'] ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <p class="text-body-secondary small text-uppercase mb-1">
                        <a href="/vacancies?status=open"
                           class="stretched-link text-reset text-decoration-none">
                            Vacantes abiertas
                        </a>
                    </p>
                    <span class="text-primary fs-5"><i class="bi bi-briefcase"></i></span>
                </div>
                <p class="display-6 fw-semibold mb-1"><?= (int) $summary['vacancies']['open'] ?></p>
                <p class="small text-body-secondary mb-0">
                    Cerradas: <?= (int) $summary['vacancies']['closed'] ?> ·
                    Total: <?= (int) $summary['vacancies']['total'] ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="h5 mb-0"><i class="bi bi-diagram-3 text-primary"></i> Reclutamiento</h2>
                <a href="/applications" class="btn btn-sm btn-outline-secondary">Ver postulaciones</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <p class="text-body-secondary small text-uppercase mb-1">En curso</p>
                        <p class="h2 fw-semibold text-primary mb-0"><?= (int) $summary['in_process'] ?></p>
                        <p class="small text-body-secondary mb-0">postulaciones activas</p>
                    </div>
                    <div class="col-6 col-md-3">
                        <p class="text-body-secondary small text-uppercase mb-1">Seleccionadas</p>
                        <p class="h2 fw-semibold text-success mb-0"><?= (int) $applications['selected'] ?></p>
                        <p class="small text-body-secondary mb-0">listas para dar de alta</p>
                    </div>
                    <div class="col-6 col-md-3">
                        <p class="text-body-secondary small text-uppercase mb-1">Pendientes de alta</p>
                        <p class="h2 fw-semibold text-warning-emphasis mb-0"><?= $pendingHire ?></p>
                        <p class="small text-body-secondary mb-0">sin empleado asociado</p>
                    </div>
                    <div class="col-6 col-md-3">
                        <p class="text-body-secondary small text-uppercase mb-1">Candidatos</p>
                        <p class="h2 fw-semibold mb-0"><?= (int) $summary['candidates']['total'] ?></p>
                        <p class="small text-body-secondary mb-0">registrados</p>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex flex-wrap gap-2">
                    <a href="/applications?status=received" class="badge rounded-pill text-bg-secondary text-decoration-none">
                        Recibidas: <?= (int) $applications['received'] ?>
                    </a>
                    <a href="/applications?status=screening" class="badge rounded-pill text-bg-info text-decoration-none">
                        En evaluación: <?= (int) $applications['screening'] ?>
                    </a>
                    <a href="/applications?status=interview" class="badge rounded-pill text-bg-primary text-decoration-none">
                        Entrevista: <?= (int) $applications['interview'] ?>
                    </a>
                    <a href="/applications?status=selected" class="badge rounded-pill text-bg-success text-decoration-none">
                        Seleccionadas: <?= (int) $applications['selected'] ?>
                    </a>
                    <a href="/applications?status=rejected" class="badge rounded-pill text-bg-danger text-decoration-none">
                        Rechazadas: <?= (int) $applications['rejected'] ?>
                    </a>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="/vacancies" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-briefcase"></i> Vacantes
                    </a>
                    <a href="/candidates" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-person-lines-fill"></i> Candidatos
                    </a>
                    <a href="/applications?status=selected" class="btn btn-sm btn-success">
                        <i class="bi bi-person-check"></i> Ver seleccionadas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-lightning-charge text-primary"></i> Accesos rápidos</h2>
            </div>
            <div class="list-group list-group-flush">
                <a href="/employees/create"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-plus text-primary"></i> Registrar empleado</span>
                    <i class="bi bi-chevron-right small text-body-secondary"></i>
                </a>
                <a href="/attendance/create"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-plus text-success"></i> Registrar asistencia</span>
                    <i class="bi bi-chevron-right small text-body-secondary"></i>
                </a>
                <a href="/leave-requests/create"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar2-plus text-warning-emphasis"></i> Crear solicitud de permiso</span>
                    <i class="bi bi-chevron-right small text-body-secondary"></i>
                </a>
                <a href="/leave-requests?status=pending"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clipboard-check text-warning-emphasis"></i> Revisar permisos pendientes</span>
                    <span class="badge rounded-pill text-bg-warning"><?= (int) $summary['leaves']['pending'] ?></span>
                </a>
                <a href="/vacancies/create"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-briefcase text-primary"></i> Crear vacante</span>
                    <i class="bi bi-chevron-right small text-body-secondary"></i>
                </a>
                <a href="/applications?status=selected"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-check text-success"></i> Ver candidatos seleccionados</span>
                    <span class="badge rounded-pill text-bg-success"><?= (int) $applications['selected'] ?></span>
                </a>
            </div>
            <div class="card-footer bg-white small text-body-secondary">
                Los tres módulos están disponibles desde la navegación superior.
            </div>
        </div>
    </div>
</div>
