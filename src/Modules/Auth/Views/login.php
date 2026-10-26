<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var string|null $generalError */
?>
<h2 class="h5 mb-1">Iniciar sesión</h2>
<p class="text-body-secondary small mb-4">Acceso exclusivo para administración y personal de RR. HH.</p>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert"><?= e($generalError) ?></div>
<?php endif; ?>

<form method="post" action="/login" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email"
               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               id="email" name="email" value="<?= e(old('email')) ?>"
               autocomplete="username" required autofocus
               <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback" id="email-error"><?= e($errors['email']) ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Contraseña</label>
        <div class="input-group">
            <input type="password"
                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   id="password" name="password" autocomplete="current-password" required
                   <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>>
            <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password"
                    aria-label="Mostrar contraseña">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback d-block" id="password-error"><?= e($errors['password']) ?></div>
        <?php endif; ?>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
    </div>
</form>
