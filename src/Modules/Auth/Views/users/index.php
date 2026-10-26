<?php

declare(strict_types=1);

/** @var array<string, mixed> $pagination */
/** @var array{q: string, role: string|null, status: string|null} $filters */
/** @var array{total: int, active: int, inactive: int, admins: int, active_admins: int, hr: int} $stats */
/** @var array<string, string> $errors */
/** @var string|null $generalError */
/** @var array<string, mixed> $currentUser */

$baseQuery = array_filter([
    'q' => $filters['q'],
    'role' => $filters['role'],
    'status' => $filters['status'],
], static fn (mixed $value): bool => $value !== null && $value !== '');

$pageUrl = static fn (int $page): string => '/users?' . http_build_query($baseQuery + ['page' => $page]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Usuarios</h1>
        <p class="text-body-secondary mb-0">
            Cuentas de acceso al sistema. Solo el administrador puede crear, editar, desactivar o restablecer contraseñas.
        </p>
    </div>
    <a href="/users/create" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Nuevo usuario
    </a>
</div>

<?php if ($generalError !== null): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= e($generalError) ?>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Total</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $stats['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Activos</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $stats['active'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Administradores</p>
                <p class="display-6 fw-semibold text-primary mb-0"><?= (int) $stats['admins'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Personal RR. HH.</p>
                <p class="display-6 fw-semibold text-info-emphasis mb-0"><?= (int) $stats['hr'] ?></p>
            </div>
        </div>
    </div>
</div>

<form method="get" action="/users" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="q" class="form-label">Buscar</label>
                <input type="search" class="form-control" id="q" name="q"
                       value="<?= e($filters['q']) ?>"
                       placeholder="Nombre o correo electrónico">
            </div>
            <div class="col-6 col-lg-2">
                <label for="role" class="form-label">Rol</label>
                <select class="form-select" id="role" name="role">
                    <option value="">Todos</option>
                    <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    <option value="hr" <?= $filters['role'] === 'hr' ? 'selected' : '' ?>>Personal RR. HH.</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/users" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <?php if ($pagination['items'] === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-person-badge display-5 text-body-tertiary"></i>
            <h2 class="h5 mt-3">No hay usuarios que coincidan</h2>
            <p class="text-body-secondary mb-4">Prueba con otros filtros o crea una cuenta nueva.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="/users" class="btn btn-outline-secondary">Limpiar filtros</a>
                <a href="/users/create" class="btn btn-primary">Crear usuario</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Usuario</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Último acceso</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $user): ?>
                    <?php
                    $isActive = (int) $user['is_active'] === 1;
                    $role = (string) $user['role'];
                    $isSelf = (int) $user['id'] === (int) $currentUser['id'];
                    $isLastAdmin = $role === 'admin' && $isActive && (int) $stats['active_admins'] === 1;
                    $canToggle = !$isSelf && !$isLastAdmin;
                    $roleClass = $role === 'admin' ? 'primary' : 'info';
                    ?>
                    <tr>
                        <td>
                            <span class="fw-semibold"><?= e((string) $user['name']) ?></span>
                            <?php if ($isSelf): ?>
                                <span class="badge text-bg-light border ms-1">Tu cuenta</span>
                            <?php endif; ?>
                            <div class="small text-body-secondary"><?= e((string) $user['email']) ?></div>
                        </td>
                        <td>
                            <span class="badge text-bg-<?= e($roleClass) ?>"><?= e(status_label($role)) ?></span>
                            <?php if ($isLastAdmin): ?>
                                <span class="badge text-bg-warning ms-1">Último admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>">
                                <?= e(status_label($isActive ? 'active' : 'inactive')) ?>
                            </span>
                        </td>
                        <td>
                            <?= e(format_date(
                                $user['last_login_at'] !== null ? (string) $user['last_login_at'] : null,
                                'd/m/Y H:i'
                            )) ?>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                <a href="/users/<?= (int) $user['id'] ?>/edit"
                                   class="btn btn-sm btn-outline-secondary" title="Editar" aria-label="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="/users/<?= (int) $user['id'] ?>/password"
                                   class="btn btn-sm btn-outline-secondary" title="Restablecer contraseña"
                                   aria-label="Restablecer contraseña">
                                    <i class="bi bi-key"></i>
                                </a>
                                <?php if ($canToggle): ?>
                                    <form method="post" action="/users/<?= (int) $user['id'] ?>/status"
                                          data-confirm="<?= $isActive
                                              ? '¿Desactivar esta cuenta? La sesión activa se cerrará en la siguiente petición.'
                                              : '¿Reactivar esta cuenta?' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-<?= $isActive ? 'danger' : 'success' ?>"
                                                title="<?= $isActive ? 'Desactivar' : 'Reactivar' ?>">
                                            <i class="bi <?= $isActive ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                            title="<?= $isSelf
                                                ? 'No puedes desactivar tu propia cuenta.'
                                                : 'No puedes desactivar al último administrador activo.' ?>">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-body-secondary">
                Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?>
                de <?= (int) $pagination['total'] ?> usuarios
            </span>
            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav aria-label="Paginación de usuarios">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= (int) $pagination['page'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e($pageUrl((int) $pagination['page'] - 1)) ?>">Anterior</a>
                        </li>
                        <?php for ($page = 1; $page <= (int) $pagination['pages']; $page++): ?>
                            <li class="page-item <?= $page === (int) $pagination['page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e($pageUrl($page)) ?>"><?= $page ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= (int) $pagination['page'] >= (int) $pagination['pages'] ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e($pageUrl((int) $pagination['page'] + 1)) ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
