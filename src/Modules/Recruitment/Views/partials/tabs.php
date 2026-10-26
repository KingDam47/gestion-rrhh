<?php

declare(strict_types=1);

/** @var string $activeTab */
$activeTab = $activeTab ?? 'overview';

$tabs = [
    ['key' => 'overview', 'label' => 'Resumen', 'icon' => 'bi-grid', 'href' => '/recruitment'],
    ['key' => 'vacancies', 'label' => 'Vacantes', 'icon' => 'bi-briefcase', 'href' => '/vacancies'],
    ['key' => 'candidates', 'label' => 'Candidatos', 'icon' => 'bi-person-lines-fill', 'href' => '/candidates'],
    ['key' => 'applications', 'label' => 'Postulaciones', 'icon' => 'bi-diagram-3', 'href' => '/applications'],
];
?>
<ul class="nav nav-pills gap-2 mb-4 flex-wrap">
    <?php foreach ($tabs as $tab): ?>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === $tab['key'] ? 'active' : '' ?>"
               href="<?= e($tab['href']) ?>"
               <?= $activeTab === $tab['key'] ? 'aria-current="page"' : '' ?>>
                <i class="bi <?= e($tab['icon']) ?>"></i>
                <?= e($tab['label']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
