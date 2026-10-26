<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $user */
/** @var bool $isEdit */
/** @var string $formAction */
/** @var array{total: int, active: int, inactive: int, admins: int, active_admins: int, hr: int} $stats */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var array<string, mixed> $currentUser */

$role = old('role', (string) ($user['role'] ?? 'hr'));
$isActive = $isEdit ? (int) $user['is_active'] === 1 : true;
$isSelf = $isEdit && (int) $user['id'] === (int) $currentUser['id'];
$isLastActiveAdmin = $isEdit
    && (string) $user['role'] === 'admin'
    && $isActive
    && (int) $stats['active_admins'] === 1;
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
        <p class="text-body-secondary mb-0">
            <?= $isEdit
                ? 'Actualiza los datos de la cuenta y su rol de acceso.'
                : 'Crea una cuenta de acceso para administración o para el personal de RR. HH.' ?>
        </p>
    </div>
    <a href="/users" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<?php if ($isLastActiveAdmin): ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-shield-exclamation"></i>
        Esta es la única cuenta de administrador activa. No se puede desactivar ni cambiar a RR. HH.
    </div>
<?php endif; ?>

<?php if ($isSelf): ?>
    <div class="alert alert-info" role="alert">
        <i class="bi bi-person-check"></i>
        Estás editando tu propia cuenta. No puedes desactivarla. Si cambias tu rol y queda otro
        administrador activo, tu sesión se cerrará para aplicar los nuevos permisos.
    </div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-person-badge text-primary"></i> Datos de la cuenta</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               id="name" name="name" maxlength="120"
                               value="<?= e(old('name', (string) ($user['name'] ?? ''))) ?>"
                               autocomplete="name" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               id="email" name="email" maxlength="190"
                               value="<?= e(old('email', (string) ($user['email'] ?? ''))) ?>"
                               autocomplete="email" required>
                        <div class="form-text">Se normaliza a minúsculas y no puede repetirse.</div>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>"
                                id="role" name="role" required>
                            <option value="hr" <?= $role === 'hr' ? 'selected' : '' ?>
                                <?= $isLastActiveAdmin ? 'disabled' : '' ?>>
                                Personal de RR. HH. — operación diaria
                            </option>
                            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>
                                Administrador — operación y gestión de usuarios
                            </option>
                        </select>
                        <?php if (isset($errors['role'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['role']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">
                            El administrador puede gestionar cuentas y consultar la auditoría; RR. HH. no.
                        </div>
                    </div>

                    <?php if (!$isEdit): ?>
                        <hr>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                           id="password" name="password" minlength="10" maxlength="255"
                                           autocomplete="new-password" required>
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

                            <div class="col-12 col-md-6">
                                <label for="password_confirmation" class="form-label">
                                    Confirmar contraseña <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                                           id="password_confirmation" name="password_confirmation"
                                           minlength="10" maxlength="255" autocomplete="new-password" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                            data-toggle-password="#password_confirmation"
                                            aria-label="Mostrar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['password_confirmation'])): ?>
                                    <div class="invalid-feedback d-block"><?= e($errors['password_confirmation']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0"><i class="bi bi-shield-lock text-primary"></i> Resumen de la cuenta</h2>
                </div>
                <div class="card-body">
                    <?php if ($isEdit): ?>
                        <dl class="row mb-0">
                            <dt class="col-6 text-body-secondary fw-normal">Estado</dt>
                            <dd class="col-6 text-end">
                                <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>">
                                    <?= e(status_label($isActive ? 'active' : 'inactive')) ?>
                                </span>
                            </dd>

                            <dt class="col-6 text-body-secondary fw-normal">Rol actual</dt>
                            <dd class="col-6 text-end"><?= e(status_label((string) $user['role'])) ?></dd>

                            <dt class="col-6 text-body-secondary fw-normal">Último acceso</dt>
                            <dd class="col-6 text-end">
                                <?= e(format_date(
                                    $user['last_login_at'] !== null ? (string) $user['last_login_at'] : null,
                                    'd/m/Y H:i'
                                )) ?>
                            </dd>

                            <dt class="col-6 text-body-secondary fw-normal">Creada</dt>
                            <dd class="col-6 text-end">
                                <?= e(format_date(
                                    $user['created_at'] !== null ? (string) $user['created_at'] : null
                                )) ?>
                            </dd>
                        </dl>

                        <hr>

                        <p class="small text-body-secondary">
                            La contraseña no se muestra ni se puede consultar. Si la persona olvidó la suya,
                            restablécala desde aquí.
                        </p>

                        <a href="/users/<?= (int) $user['id'] ?>/password" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-key"></i> Restablecer contraseña
                        </a>
                    <?php else: ?>
                        <p class="text-body-secondary small mb-3">
                            La cuenta se crea <strong>activa</strong>. La persona podrá iniciar sesión
                            inmediatamente con el correo y la contraseña que definas.
                        </p>
                        <ul class="small text-body-secondary mb-0">
                            <li>El correo debe ser único.</li>
                            <li>La contraseña debe tener al menos 10 caracteres.</li>
                            <li>La cuenta se puede desactivar después, sin eliminarla.</li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
        <a href="/users" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2"></i> <?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?>
        </button>
    </div>
</form>
