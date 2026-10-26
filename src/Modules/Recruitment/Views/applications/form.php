<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $application */
/** @var list<array<string, mixed>> $candidates */
/** @var list<array<string, mixed>> $vacancies */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var string $formAction */
/** @var bool $isEdit */
/** @var array{candidate_id?: string, vacancy_id?: string} $defaults */

$selectedCandidate = (int) old('candidate_id', (string) ($defaults['candidate_id'] ?? ''));
$selectedVacancy = (int) old('vacancy_id', (string) ($defaults['vacancy_id'] ?? ''));
$canSubmit = $candidates !== [] && $vacancies !== [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Nueva postulación</h1>
        <p class="text-body-secondary mb-0">Asocia un candidato registrado con una vacante abierta.</p>
    </div>
    <a href="/applications" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<?php if ($candidates === []): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-info-circle"></i> No hay candidatos registrados.
        <a href="/candidates/create" class="alert-link">Registra un candidato</a> antes de crear una postulación.
    </div>
<?php endif; ?>

<?php if ($vacancies === []): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-info-circle"></i> No hay vacantes abiertas.
        <a href="/vacancies/create" class="alert-link">Crea una vacante</a> antes de registrar postulaciones.
    </div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-diagram-3 text-primary"></i> Datos de la postulación</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="candidate_id" class="form-label">Candidato <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['candidate_id']) ? 'is-invalid' : '' ?>"
                                id="candidate_id" name="candidate_id" required>
                            <option value="">Selecciona un candidato</option>
                            <?php foreach ($candidates as $candidate): ?>
                                <option value="<?= (int) $candidate['id'] ?>"
                                    <?= $selectedCandidate === (int) $candidate['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $candidate['last_name'] . ', ' . (string) $candidate['first_name']) ?>
                                    · <?= e((string) $candidate['email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            ¿No está registrado? <a href="/candidates/create">Crea el candidato primero</a>.
                        </div>
                        <?php if (isset($errors['candidate_id'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['candidate_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="vacancy_id" class="form-label">Vacante <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['vacancy_id']) ? 'is-invalid' : '' ?>"
                                id="vacancy_id" name="vacancy_id" required>
                            <option value="">Selecciona una vacante abierta</option>
                            <?php foreach ($vacancies as $vacancy): ?>
                                <option value="<?= (int) $vacancy['id'] ?>"
                                    <?= $selectedVacancy === (int) $vacancy['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $vacancy['title']) ?>
                                    · <?= e((string) $vacancy['department_name']) ?>
                                    · Apertura <?= e(format_date((string) $vacancy['opening_date'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Solo se muestran vacantes abiertas. No se permite duplicar candidato y vacante.</div>
                        <?php if (isset($errors['vacancy_id'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['vacancy_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <label for="notes" class="form-label">Observaciones</label>
                        <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>"
                                  id="notes" name="notes" rows="4" maxlength="1000"
                                  placeholder="Comentarios iniciales del proceso."><?= e(old('notes', (string) ($application['notes'] ?? ''))) ?></textarea>
                        <?php if (isset($errors['notes'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['notes']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5"><i class="bi bi-info-circle text-primary"></i> Cómo funciona</h2>
                    <ol class="small text-body-secondary mb-0 ps-3">
                        <li class="mb-2">La postulación nace en estado <strong>Recibida</strong>.</li>
                        <li class="mb-2">RR. HH. la pasa a <strong>En evaluación</strong> y luego a <strong>Entrevista</strong>.</li>
                        <li class="mb-2">Desde la entrevista puede marcarse como <strong>Seleccionada</strong> o rechazarse.</li>
                        <li>El rechazo es posible en cualquier etapa previa a la selección.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a href="/applications" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" <?= $canSubmit ? '' : 'disabled' ?>>
            <i class="bi bi-check2"></i> Registrar postulación
        </button>
    </div>
</form>
