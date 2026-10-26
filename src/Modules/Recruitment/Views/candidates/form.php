<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $candidate */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var string $formAction */
/** @var bool $isEdit */

$cancelUrl = $isEdit ? '/candidates/' . (int) $candidate['id'] : '/candidates';
$name = $isEdit
    ? trim((string) $candidate['first_name'] . ' ' . (string) $candidate['last_name'])
    : '';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $isEdit ? 'Editar candidato' : 'Nuevo candidato' ?></h1>
        <p class="text-body-secondary mb-0">
            <?= $isEdit
                ? 'Actualiza los datos de contacto y las observaciones del candidato.'
                : 'Registra los datos básicos para iniciar el seguimiento de la postulación.' ?>
        </p>
    </div>
    <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<?php if ($isEdit): ?>
    <div class="alert alert-light border" role="note">
        <i class="bi bi-info-circle text-primary"></i>
        El correo debe ser único. Los cambios no alteran las postulaciones ya registradas.
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
                            <label for="first_name" class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                                   id="first_name" name="first_name" maxlength="80"
                                   value="<?= e(old('first_name', (string) ($candidate['first_name'] ?? ''))) ?>"
                                   autocomplete="given-name" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['first_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="last_name" class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                   id="last_name" name="last_name" maxlength="80"
                                   value="<?= e(old('last_name', (string) ($candidate['last_name'] ?? ''))) ?>"
                                   autocomplete="family-name" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['last_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   id="email" name="email" maxlength="190"
                                   value="<?= e(old('email', (string) ($candidate['email'] ?? ''))) ?>"
                                   autocomplete="email" required>
                            <div class="form-text">Se guarda en minúsculas y no puede repetirse.</div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                   id="phone" name="phone" maxlength="30"
                                   value="<?= e(old('phone', (string) ($candidate['phone'] ?? ''))) ?>"
                                   autocomplete="tel">
                            <div class="form-text">Opcional. Ejemplo: +1 555 0101.</div>
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
                    <h2 class="h5 mb-0"><i class="bi bi-journal-text text-primary"></i> Observaciones</h2>
                </div>
                <div class="card-body">
                    <label for="notes" class="form-label">Notas internas</label>
                    <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>"
                              id="notes" name="notes" rows="8" maxlength="1000"
                              placeholder="Experiencia, disponibilidad u otros datos relevantes."><?= e(old('notes', (string) ($candidate['notes'] ?? ''))) ?></textarea>
                    <div class="form-text">No guardes currículos, fotos ni documentos de identidad.</div>
                    <?php if (isset($errors['notes'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['notes']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2"></i> <?= $isEdit ? 'Guardar cambios' : 'Registrar candidato' ?>
        </button>
    </div>
</form>
