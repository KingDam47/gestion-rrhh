<?php

declare(strict_types=1);

namespace App\Modules\Attendance\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Attendance\Services\MonthlyReportService;

final class ReportController
{
    public function __construct(
        private readonly MonthlyReportService $report,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        $report = $this->report->report([
            'month' => (string) $request->query('month', ''),
            'department_id' => (string) $request->query('department_id', ''),
        ]);

        return Response::html($this->view->render('attendance::report', [
            'title' => 'Reporte mensual de asistencia',
            'report' => $report,
        ]));
    }
}
