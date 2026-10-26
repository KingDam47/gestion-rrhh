<?php

declare(strict_types=1);

/** @var string $appName */
/** @var array<string, mixed>|null $currentUser */
/** @var list<array<string, mixed>> $navigation */
/** @var string $currentPath */
/** @var string $title */
/** @var string $content */

$role = $currentUser['role'] ?? null;
$items = array_values(array_filter(
    $navigation,
    static fn (array $item): bool => ($item['enabled'] ?? false) === true
        && $role !== null
        && in_array($role, $item['roles'] ?? [], true)
));

$isActive = static function (array $item) use ($currentPath): bool {
    $paths = $item['match'] ?? [$item['path']];

    foreach ($paths as $path) {
        $path = (string) $path;

        if ($path === '/' ? $currentPath === '/' : ($currentPath === $path || str_starts_with($currentPath, $path . '/'))) {
            return true;
        }
    }

    return false;
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e($appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/dashboard">
            <span class="brand-mark brand-mark--sm"><i class="bi bi-people-fill"></i></span>
            <span><?= e($appName) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation"
                aria-controls="mainNavigation" aria-expanded="false" aria-label="Mostrar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($items as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive($item) ? 'active' : '' ?>"
                           href="<?= e((string) $item['path']) ?>">
                            <i class="bi <?= e((string) ($item['icon'] ?? '')) ?>"></i>
                            <?= e((string) $item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($currentUser !== null): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <?= e((string) $currentUser['name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><span class="dropdown-item-text small text-body-secondary"><?= e((string) $currentUser['email']) ?></span></li>
                        <li><span class="dropdown-item-text small"><?= e(status_label((string) $currentUser['role'])) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/account/password">
                                <i class="bi bi-key"></i> Mi cuenta
                            </a>
                        </li>
                        <li>
                            <form method="post" action="/logout" class="px-3 py-1">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="flex-grow-1 py-4">
    <div class="container">
        <?php require __DIR__ . '/../partials/flash.php'; ?>
        <?= $content ?>
    </div>
</main>

<footer class="app-footer py-3 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between gap-2 small text-body-secondary">
        <span><?= e($appName) ?> · Proyecto académico con datos ficticios</span>
        <span>PHP · MySQL · Bootstrap 5</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
