<?php

declare(strict_types=1);

/** @var string $activeTab */
$activeTab = $activeTab ?? 'attendance';

$tabs = [
    ['key' => 'attendance', 'label' => 'Registros', 'icon' => 'bi-calendar-check', 'href' => '/attendance'],
    ['key' => 'leaves', 'label' => 'Permisos', 'icon' => 'bi-calendar2-week', 'href' => '/leave-requests'],
    ['key' => 'report', 'label' => 'Reporte mensual', 'icon' => 'bi-file-earmark-bar-graph', 'href' => '/attendance/report'],
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
