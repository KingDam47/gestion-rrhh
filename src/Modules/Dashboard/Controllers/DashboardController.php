<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Dashboard\Services\DashboardSummaryService;

final class DashboardController
{
    public function __construct(
        private readonly DashboardSummaryService $summary,
        private readonly View $view
    ) {
    }

    public function home(Request $request): Response
    {
        return Response::redirect(Auth::check() ? '/dashboard' : '/login');
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('dashboard::index', [
            'title' => 'Panel de inicio',
            'summary' => $this->summary->summary(),
        ]));
    }
}
