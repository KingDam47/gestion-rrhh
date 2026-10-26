<?php

declare(strict_types=1);

/** @var list<array{type: string, message: string}> $flash */
?>
<?php if (($flash ?? []) !== []): ?>
    <div class="app-flash" aria-live="polite">
        <?php foreach ($flash as $message): ?>
            <div class="alert alert-<?= e($message['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= e($message['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
