<?php

declare(strict_types=1);

/** @var string $activeTab */
$activeTab = $activeTab ?? 'employees';

$tabs = [
    ['key' => 'employees', 'label' => 'Empleados', 'icon' => 'bi-people', 'href' => '/employees'],
    ['key' => 'departments', 'label' => 'Departamentos', 'icon' => 'bi-diagram-3', 'href' => '/departments'],
    ['key' => 'positions', 'label' => 'Cargos', 'icon' => 'bi-briefcase', 'href' => '/positions'],
];
?>
<ul class="nav nav-pills gap-2 mb-4">
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
