<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'Gestión RR. HH.'),
    'env' => Env::get('APP_ENV', 'local'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => rtrim(Env::get('APP_URL', 'http://localhost'), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'UTC'),
    // Activar solo si la aplicación está detrás de un proxy de confianza que
    // sobrescribe X-Forwarded-For / CF-Connecting-IP. Si no, se usa REMOTE_ADDR.
    'trust_proxy' => Env::bool('TRUST_PROXY', false),
    'session' => [
        'name' => Env::get('SESSION_NAME', 'gestion_rrhh_session'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', '120'),
        'secure' => Env::bool('SESSION_SECURE', false),
        'same_site' => 'Lax',
    ],
];
