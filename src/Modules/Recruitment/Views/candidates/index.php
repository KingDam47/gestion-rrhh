<?php

declare(strict_types=1);

/** @var array<string, mixed> $pagination */
/** @var array{q: string} $filters */
/** @var array{total: int, with_applications: int, selected: int} $summary */

$activeTab = 'candidates';
require dirname(__DIR__) . '/partials/tabs.php';

$pageUrl = static fn (int $page): string => '/candidates?' . http_build_query(
    array_filter(['q' => $filters['q']], static fn (string $value): bool => $value !== '') + ['page' => $page]
);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Candidatos</h1>
        <p class="text-body-secondary mb-0">Registro de las personas que participan en los procesos de selección.</p>
    </div>
    <a href="/candidates/create" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Nuevo candidato
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Total</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $summary['total'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Con postulaciones</p>
                <p class="display-6 fw-semibold text-primary mb-0"><?= (int) $summary['with_applications'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-body-secondary small text-uppercase mb-1">Seleccionados</p>
                <p class="display-6 fw-semibold text-success mb-0"><?= (int) $summary['selected'] ?></p>
            </div>
        </div>
    </div>
</div>

<form method="get" action="/candidates" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-6">
                <label for="q" class="form-label">Buscar</label>
                <input type="search" class="form-control" id="q" name="q"
                       value="<?= e($filters['q']) ?>"
                       placeholder="Nombre, apellidos o correo electrónico">
            </div>
            <div class="col-12 col-lg-6 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/candidates" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <?php if ($pagination['items'] === []): ?>
        <div class="card-body py-5 text-center">
            <i class="bi bi-person-lines-fill display-5 text-body-tertiary"></i>
            <h2 class="h5 mt-3">No hay candidatos que coincidan</h2>
            <p class="text-body-secondary mb-4">Prueba con otra búsqueda o registra un candidato nuevo.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="/candidates" class="btn btn-outline-secondary">Limpiar filtros</a>
                <a href="/candidates/create" class="btn btn-primary">Registrar candidato</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th scope="col">Candidato</th>
                    <th scope="col">Contacto</th>
                    <th scope="col">Postulaciones</th>
                    <th scope="col">Seleccionado</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pagination['items'] as $candidate): ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-decoration-none"
                               href="/candidates/<?= (int) $candidate['id'] ?>">
                                <?= e((string) $candidate['last_name'] . ', ' . (string) $candidate['first_name']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="mailto:<?= e((string) $candidate['email']) ?>"><?= e((string) $candidate['email']) ?></a>
                            <?php if ($candidate['phone'] !== null): ?>
                                <div class="small text-body-secondary"><?= e((string) $candidate['phone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $candidate['application_count'] ?></td>
                        <td>
                            <?php if ((int) $candidate['selected_count'] > 0): ?>
                                <span class="badge text-bg-success">Sí</span>
                            <?php else: ?>
                                <span class="badge text-bg-light">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Acciones del candidato">
                                <a href="/candidates/<?= (int) $candidate['id'] ?>" class="btn btn-outline-secondary"
                                   title="Ver ficha" aria-label="Ver ficha">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="/candidates/<?= (int) $candidate['id'] ?>/edit"
                                   class="btn btn-outline-secondary" title="Editar" aria-label="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="small text-body-secondary">
                Mostrando <?= (int) $pagination['from'] ?>–<?= (int) $pagination['to'] ?>
                de <?= (int) $pagination['total'] ?> candidatos
            </span>

            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav aria-label="Paginación de candidatos">
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
