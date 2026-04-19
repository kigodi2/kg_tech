<?php

namespace App\Http\Controllers\ExamDevelopment;

use App\Http\Controllers\Controller;
use App\Models\ExamDevelopment\ExamProject;
use App\Models\ExamDevelopment\ExamProjectPaper;
use App\Services\ExamDevelopment\ExportService;

class ExportController extends Controller
{
    public function __construct(
        protected ExportService $exportService
    ) {
    }

    public function show(ExamProject $project)
    {
        $project->load(['papers.sections.slots.assignedQuestion']);

        return view('exam-development.exports.show', ['project' => $project]);
    }

    public function download(ExamProjectPaper $paper, string $variant)
    {
        return match ($variant) {
            'candidate' => $this->exportService->candidatePaper($paper),
            'marking-scheme' => $this->exportService->confidentialMarkingScheme($paper),
            'moderator' => $this->exportService->moderatorVersion($paper),
            'archive' => $this->exportService->archiveVersion($paper),
            default => abort(404),
        };
    }
}
