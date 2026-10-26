<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Auth\Services\AuditLogService;

final class AuditController
{
    public function __construct(
        private readonly AuditLogService $logs,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->query('q', ''),
            'entity_type' => $request->query('entity_type'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $result = $this->logs->search($filters, (int) ($request->query('page', '1') ?? 1));

        return Response::html($this->view->render('auth::audit.index', [
            'title' => 'Auditoría',
            'pagination' => $result,
            'filters' => $result['filters'],
            'entityTypes' => $this->logs->entityTypes(),
        ]));
    }
}
