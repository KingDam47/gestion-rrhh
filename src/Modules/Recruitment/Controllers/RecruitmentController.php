<?php

declare(strict_types=1);

namespace App\Modules\Recruitment\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Recruitment\Services\ApplicationService;
use App\Modules\Recruitment\Services\CandidateService;
use App\Modules\Recruitment\Services\VacancyService;

final class RecruitmentController
{
    public function __construct(
        private readonly VacancyService $vacancies,
        private readonly CandidateService $candidates,
        private readonly ApplicationService $applications,
        private readonly View $view
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('recruitment::overview', [
            'title' => 'Reclutamiento y selección',
            'vacancyStats' => $this->vacancies->stats(),
            'candidateStats' => $this->candidates->stats(),
            'applicationStats' => $this->applications->stats(),
            'openVacancies' => $this->vacancies->recentOpen(),
            'selectedApplications' => $this->applications->recentSelected(),
        ]));
    }
}
