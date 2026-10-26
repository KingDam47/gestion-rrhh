<?php

declare(strict_types=1);

/** @var array<string, mixed> $leave */
/** @var array<string, string> $errors */
/** @var string|null $generalError */

$status = (string) $leave['status'];
$statusClasses = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
$statusClass = $statusClasses[$status] ?? 'secondary';
$isPending = $status === 'pending';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h1 class="h3 mb-0">Solicitud de permiso #<?= (int) $leave['id'] ?></h1>
            <span class="badge text-bg-<?= e($statusClass) ?>"><?= e(status_label($status)) ?></span>
        </div>
        <p class="text-body-secondary mb-0">
            <?= e((string) $leave['employee_name']) ?>
            <?php if ($leave['employee_document'] !== null): ?>
                · <?= e((string) $leave['employee_document']) ?>
            <?php endif; ?>
            <?php if ($leave['department_name'] !== null): ?>
                · <?= e((string) $leave['department_name']) ?>
            <?php endif; ?>
        </p>
    </div>
    <a href="/leave-requests" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<?php if ((string) ($leave['employee_status'] ?? '') !== 'active'): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        El empleado está inactivo. La solicitud conserva su historial y puede revisarse si sigue pendiente.
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-calendar2-week text-primary"></i> Datos de la solicitud</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-body-secondary fw-normal">Empleado</dt>
                    <dd class="col-sm-8"><?= e((string) $leave['employee_name']) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Documento</dt>
                    <dd class="col-sm-8"><?= e((string) ($leave['employee_document'] ?? '—')) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Departamento</dt>
                    <dd class="col-sm-8"><?= e((string) ($leave['department_name'] ?? '—')) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Tipo</dt>
                    <dd class="col-sm-8"><?= e(status_label((string) $leave['leave_type'])) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Período</dt>
                    <dd class="col-sm-8">
                        <?= e(format_date((string) $leave['start_date'])) ?>
                        – <?= e(format_date((string) $leave['end_date'])) ?>
                        <span class="text-body-secondary">(<?= (int) $leave['days'] ?> días)</span>
                    </dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Estado</dt>
                    <dd class="col-sm-8">
                        <span class="badge text-bg-<?= e($statusClass) ?>"><?= e(status_label($status)) ?></span>
                    </dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Motivo</dt>
                    <dd class="col-sm-8"><?= nl2br(e((string) $leave['reason'])) ?></dd>

                    <dt class="col-sm-4 text-body-secondary fw-normal">Creada</dt>
                    <dd class="col-sm-8">
                        <?= e(format_date($leave['created_at'] !== null ? (string) $leave['created_at'] : null, 'd/m/Y H:i')) ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-clipboard-check text-primary"></i> Revisión</h2>
            </div>
            <div class="card-body">
                <?php if ($isPending): ?>
                    <p class="text-body-secondary">
                        Esta solicitud está pendiente. Registra una decisión; no podrá modificarse después.
                    </p>
                    <form method="post" action="/leave-requests/<?= (int) $leave['id'] ?>/review" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="status" class="form-label">Decisión <span class="text-danger">*</span></label>
                            <select class="form-select"
                                    id="status" name="status" required>
                                <option value="approved" <?= old('status') === 'approved' ? 'selected' : '' ?>>Aprobar</option>
                                <option value="rejected" <?= old('status') === 'rejected' ? 'selected' : '' ?>>Rechazar</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="review_notes" class="form-label">Observación de revisión</label>
                            <textarea class="form-control <?= isset($errors['review_notes']) ? 'is-invalid' : '' ?>"
                                      id="review_notes" name="review_notes" rows="3" maxlength="500"
                                      placeholder="Opcional. Puede explicar la decisión."><?= e(old('review_notes')) ?></textarea>
                            <div class="form-text">Máximo 500 caracteres.</div>
                            <?php if (isset($errors['review_notes'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['review_notes']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"
                                data-confirm="¿Registrar la revisión? Esta acción no se puede deshacer.">
                            <i class="bi bi-check2-circle"></i> Registrar revisión
                        </button>
                    </form>
                <?php else: ?>
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-body-secondary fw-normal">Resultado</dt>
                        <dd class="col-sm-7">
                            <span class="badge text-bg-<?= e($statusClass) ?>"><?= e(status_label($status)) ?></span>
                        </dd>

                        <dt class="col-sm-5 text-body-secondary fw-normal">Revisado por</dt>
                        <dd class="col-sm-7"><?= e((string) ($leave['reviewer_name'] ?? '—')) ?></dd>

                        <dt class="col-sm-5 text-body-secondary fw-normal">Fecha de revisión</dt>
                        <dd class="col-sm-7">
                            <?= e(format_date($leave['reviewed_at'] !== null ? (string) $leave['reviewed_at'] : null, 'd/m/Y H:i')) ?>
                        </dd>

                        <dt class="col-sm-5 text-body-secondary fw-normal">Observación</dt>
                        <dd class="col-sm-7">
                            <?= $leave['review_notes'] !== null
                                ? nl2br(e((string) $leave['review_notes']))
                                : '<span class="text-body-secondary">Sin observación</span>' ?>
                        </dd>
                    </dl>
                    <p class="form-text mt-3 mb-0">Las solicitudes resueltas no se reabren en el MVP.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
