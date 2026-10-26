<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array<string, string> $errors */
/** @var string|null $generalError */

$isActive = (int) $user['is_active'] === 1;
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Restablecer contraseña</h1>
        <p class="text-body-secondary mb-0">
            Define una contraseña nueva para la cuenta. La contraseña anterior dejará de funcionar.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/users/<?= (int) $user['id'] ?>/edit" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <a href="/users" class="btn btn-outline-secondary">
            <i class="bi bi-person-badge"></i> Usuarios
        </a>
    </div>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-key text-primary"></i> Nueva contraseña</h2>
            </div>
            <div class="card-body">
                <form method="post" action="/users/<?= (int) $user['id'] ?>/password" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Contraseña <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   id="password" name="password" minlength="10" maxlength="255"
                                   autocomplete="new-password" required autofocus>
                            <button class="btn btn-outline-secondary" type="button"
                                    data-toggle-password="#password" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Mínimo 10 caracteres.</div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">
                            Confirmar contraseña <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                                   id="password_confirmation" name="password_confirmation"
                                   minlength="10" maxlength="255" autocomplete="new-password" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    data-toggle-password="#password_confirmation" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password_confirmation'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['password_confirmation']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        <a href="/users/<?= (int) $user['id'] ?>/edit" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-shield-check"></i> Guardar contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0"><i class="bi bi-person-badge text-primary"></i> Cuenta</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-body-secondary fw-normal">Nombre</dt>
                    <dd class="col-7 text-end"><?= e((string) $user['name']) ?></dd>

                    <dt class="col-5 text-body-secondary fw-normal">Correo</dt>
                    <dd class="col-7 text-end text-break"><?= e((string) $user['email']) ?></dd>

                    <dt class="col-5 text-body-secondary fw-normal">Rol</dt>
                    <dd class="col-7 text-end"><?= e(status_label((string) $user['role'])) ?></dd>

                    <dt class="col-5 text-body-secondary fw-normal">Estado</dt>
                    <dd class="col-7 text-end">
                        <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>">
                            <?= e(status_label($isActive ? 'active' : 'inactive')) ?>
                        </span>
                    </dd>

                    <dt class="col-5 text-body-secondary fw-normal">Último acceso</dt>
                    <dd class="col-7 text-end">
                        <?= e(format_date(
                            $user['last_login_at'] !== null ? (string) $user['last_login_at'] : null,
                            'd/m/Y H:i'
                        )) ?>
                    </dd>
                </dl>

                <hr>

                <p class="small text-body-secondary mb-0">
                    La contraseña se guarda con hash. Nadie, ni siquiera un administrador, puede consultarla.
                </p>
            </div>
        </div>
    </div>
</div>
