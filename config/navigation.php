<?php

declare(strict_types=1);

/**
 * Navegación principal.
 *
 * "enabled" se activa cuando la ruta del módulo existe. Así no se muestran
 * enlaces muertos mientras el proyecto avanza por fases.
 */
return [
    [
        'label' => 'Inicio',
        'path' => '/dashboard',
        'icon' => 'bi-speedometer2',
        'roles' => ['admin', 'hr'],
        'enabled' => true,
    ],
    [
        'label' => 'Empleados',
        'path' => '/employees',
        'icon' => 'bi-people',
        'roles' => ['admin', 'hr'],
        'enabled' => true,
        'match' => ['/employees', '/departments', '/positions'],
    ],
    [
        'label' => 'Asistencia',
        'path' => '/attendance',
        'icon' => 'bi-calendar-check',
        'roles' => ['admin', 'hr'],
        'enabled' => true,
        'match' => ['/attendance', '/leave-requests'],
    ],
    [
        'label' => 'Reclutamiento',
        'path' => '/recruitment',
        'icon' => 'bi-briefcase',
        'roles' => ['admin', 'hr'],
        'enabled' => true,
        'match' => ['/recruitment', '/vacancies', '/candidates', '/applications'],
    ],
    [
        'label' => 'Usuarios',
        'path' => '/users',
        'icon' => 'bi-person-badge',
        'roles' => ['admin'],
        'enabled' => true,
    ],
    [
        'label' => 'Auditoría',
        'path' => '/audit',
        'icon' => 'bi-shield-check',
        'roles' => ['admin'],
        'enabled' => true,
    ],
];
