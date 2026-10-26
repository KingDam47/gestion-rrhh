<?php

declare(strict_types=1);

use App\Core\Application;

/**
 * Front controller: único punto de entrada de la aplicación.
 *
 * Con el servidor integrado de PHP se sirven los archivos existentes
 * (CSS, JS, imágenes) y el resto pasa por la aplicación.
 */
if (PHP_SAPI === 'cli-server') {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    if ($path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}

/** @var Application $application */
$application = require dirname(__DIR__) . '/src/bootstrap.php';
$application->run();
