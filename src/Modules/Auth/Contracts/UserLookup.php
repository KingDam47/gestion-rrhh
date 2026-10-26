<?php

declare(strict_types=1);

namespace App\Modules\Auth\Contracts;

/**
 * Contrato público de solo lectura para otros módulos.
 *
 * Attendance lo usa para mostrar el nombre de quien revisó una solicitud
 * sin acceder a la tabla users ni al repositorio de Auth.
 */
interface UserLookup
{
    /**
     * @param list<int> $userIds
     * @return array<int, string> Nombres indexados por ID.
     */
    public function names(array $userIds): array;
}
