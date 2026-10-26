<?php

declare(strict_types=1);

/**
 * Autoload PSR-4 mínimo.
 *
 * El runtime no depende de Composer: en InfinityFree no hay SSH ni Composer CLI.
 * Composer solo se usa en desarrollo para PHPUnit.
 */
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\' => __DIR__ . '/',
        'Tests\\' => dirname(__DIR__) . '/tests/',
    ];

    foreach ($prefixes as $prefix => $baseDirectory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDirectory . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require $file;
        }

        return;
    }
});
