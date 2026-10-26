<?php

declare(strict_types=1);

/** @var int $status */
/** @var string $message */
/** @var string $title */
?>
<div class="py-5 text-center">
    <p class="display-1 fw-bold text-body-tertiary mb-0"><?= e((string) $status) ?></p>
    <h1 class="h3 mt-2"><?= e($title) ?></h1>
    <p class="text-body-secondary"><?= e($message) ?></p>
    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
        <a href="/dashboard" class="btn btn-primary"><i class="bi bi-house"></i> Ir al inicio</a>
        <a href="/login" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-in-right"></i> Iniciar sesión</a>
    </div>
</div>
