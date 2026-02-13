<?php

namespace App\Http\View\Composers;

use App\Services\Results\NectaGradingService;
use App\Services\Results\AverageMarksService;
use Illuminate\View\View;

/**
 * ResultsComposer
 * 
 * Shares grading and marking services with result-related views.
 * This is better than instantiating services in the view.
 */
class ResultsComposer
{
    private NectaGradingService $gradingService;
    private AverageMarksService $averageMarksService;

    public function __construct()
    {
        $this->gradingService = app(NectaGradingService::class);
        $this->averageMarksService = new AverageMarksService($this->gradingService);
    }

    /**
     * Bind data to the view
     */
    public function compose(View $view): void
    {
        $view->with([
            'gradingService' => $this->gradingService,
            'averageMarksService' => $this->averageMarksService,
        ]);
    }
}
