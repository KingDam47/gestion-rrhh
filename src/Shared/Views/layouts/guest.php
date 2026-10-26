<?php

declare(strict_types=1);

/** @var string $appName */
/** @var string $appEnv */
/** @var string $title */
/** @var string $content */
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
<body class="app-guest">
<main class="min-vh-100 d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                <div class="text-center mb-4">
                    <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
                    <h1 class="h3 mt-3 mb-1"><?= e($appName) ?></h1>
                    <p class="text-body-secondary small mb-0">Sistema de Gestión Humana</p>
                </div>

                <?php require __DIR__ . '/../partials/flash.php'; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <?= $content ?>
                    </div>
                </div>

                <?php if ($appEnv === 'local'): ?>
                    <p class="text-center text-body-secondary small mt-4 mb-0">
                        Demo local: <code>admin@gestion-rrhh.test</code> / <code>password123</code>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
