<?php

declare(strict_types=1);

/** @var array<string, mixed> $pagination */
/** @var array{q: string, entity_type: string|null, date_from: string|null, date_to: string|null} $filters */
/** @var list<string> $entityTypes */

$baseQuery = array_filter([
    'q' => $filters['q'],
    'entity_type' => $filters['entity_type'],
    'date_from' => $filters['date_from'],
    'date_to' => $filters['date_to'],
], static fn (mixed $value): bool => $value !== null && $value !== '');

$pageUrl = static fn (int $page): string => '/audit?' . http_build_query($baseQuery + ['page' => $page]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Auditoría</h1>
        <p class="text-body-secondary mb-0">
            Historial de las acciones administrativas registradas por el sistema.
            Solo el administrador puede consultarlo.
        </p>
    </div>
    <span class="badge text-bg-light border fs-6">
        <?= (int) $pagination['total'] ?> registros
    </span>
</div>

<div class="alert alert-light border" role="note">
    <i class="bi bi-shield-lock text-primary"></i>
    La auditoría guarda la acción, la entidad afectada, el usuario responsable y metadatos
    limitados a estado o rol. No almacena nombres, correos, contraseñas ni notas.
</div>

<form method="get" action="/audit" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="q" class="form-label">Buscar</label>
                <input type="search" class="form-control" id="q" name="q"
                       value="<?= e($filters['q']) ?>"
                       placeholder="Acción, entidad o usuario">
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <label for="entity_type" class="form-label">Entidad</label>
                <select class="form-select" id="entity_type" name="entity_type">
                    <option value="">Todas</option>
                    <?php foreach ($entityTypes as $entityType): ?>
                        <option value="<?= e($entityType) ?>"
                            <?= $filters['entity_type'] === $entityType ? 'selected' : '' ?>>
                            <?= e(audit_entity_label($entityType)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="date_from" class="form-label">Desde</label>
                <input type="date" class="form-control" id="date_from" name="date_from"
                       value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label for="date_to" class="form-label">Hasta</label>
                <input type="date" class="form-control" id="date_to" name="date_to"
                       value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
            </div>
            <div class="col-12 col-lg-2 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/audit" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <?php if ($pagination['items'] === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-clipboard-x display-5 text-body-tertiary"></i>
            <h2 class="h5 mt-3">No hay registros que coincidan</h2>
            <p class="text-body-secondary mb-4">Prueba con otros filtros o consulta el historial completo.</p>
            <a href="/audit" class="btn btn-outline-secondary">Limpiar filtros</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Acción</th>
                    <th scope="col">Entidad</th>
                    <th scope="col">Detalle</th>
                    <th scope="col">IP</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $log): ?>
                    <tr>
                        <td class="text-nowrap">
                            <?= e(format_date(
                                $log['created_at'] !== null ? (string) $log['created_at'] : null,
                                'd/m/Y H:i'
                            )) ?>
                        </td>
                        <td>
                            <?php if (($log['user_name'] ?? null) !== null): ?>
                                <span class="fw-semibold"><?= e((string) $log['user_name']) ?></span>
                            <?php else: ?>
                                <span class="text-body-secondary">Sistema o cuenta eliminada</span>
                            <?php endif; ?>
                            <div class="small text-body-secondary">ID <?= (int) $log['user_id'] ?></div>
                        </td>
                        <td>
                            <span class="badge text-bg-light border">
                                <?= e(audit_action_label((string) $log['action'])) ?>
                            </span>
                            <div class="small text-body-secondary font-monospace"><?= e((string) $log['action']) ?></div>
                        </td>
                        <td>
                            <?= e(audit_entity_label((string) $log['entity_type'])) ?>
                            <div class="small text-body-secondary">
                                <?= $log['entity_id'] !== null ? 'ID ' . (int) $log['entity_id'] : 'Sin ID' ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($log['metadata_items'] === []): ?>
                                <span class="text-body-secondary">—</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($log['metadata_items'] as $item): ?>
                                        <span class="badge text-bg-light border fw-normal">
                                            <?= e($item['key']) ?>:
                                            <span class="font-monospace"><?= e($item['value']) ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap small text-body-secondary">
                            <?= e((string) ($log['ip_address'] ?? '—')) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-body-secondary">
                Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?>
                de <?= (int) $pagination['total'] ?> registros
            </span>

            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav aria-label="Paginación de auditoría">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= (int) $pagination['page'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="<?= e($pageUrl(max(1, (int) $pagination['page'] - 1))) ?>"
                               <?= (int) $pagination['page'] <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                Anterior
                            </a>
                        </li>
                        <?php for ($page = 1; $page <= (int) $pagination['pages']; $page++): ?>
                            <li class="page-item <?= $page === (int) $pagination['page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e($pageUrl($page)) ?>"
                                   <?= $page === (int) $pagination['page'] ? 'aria-current="page"' : '' ?>>
                                    <?= $page ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= (int) $pagination['page'] >= (int) $pagination['pages'] ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="<?= e($pageUrl(min((int) $pagination['pages'], (int) $pagination['page'] + 1))) ?>"
                               <?= (int) $pagination['page'] >= (int) $pagination['pages'] ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                Siguiente
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
