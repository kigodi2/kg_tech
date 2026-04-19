<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamProjectPaper;
use App\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportService
{
    public function candidatePaper(ExamProjectPaper $paper)
    {
        return $this->download($paper, 'exam-development.exports.candidate-paper', 'candidate-paper');
    }

    public function confidentialMarkingScheme(ExamProjectPaper $paper)
    {
        return $this->download($paper, 'exam-development.exports.confidential-marking-scheme', 'confidential-marking-scheme');
    }

    public function moderatorVersion(ExamProjectPaper $paper)
    {
        return $this->download($paper, 'exam-development.exports.moderator-paper', 'moderator-paper');
    }

    public function archiveVersion(ExamProjectPaper $paper)
    {
        return $this->download($paper, 'exam-development.exports.archive-paper', 'archive-paper');
    }

    protected function download(ExamProjectPaper $paper, string $view, string $action)
    {
        $paper->loadMissing([
            'project.subject',
            'project.examType',
            'sections.slots.assignedQuestion.options',
            'sections.slots.assignedQuestion.attachments',
            'sections.slots.assignedQuestion.markingSchemes.items',
        ]);

        ReportExport::log('exam_development_pdf', $action, [
            'paper_id' => $paper->id,
            'exam_year_id' => null,
            'project_id' => $paper->exam_project_id,
        ], 'exam-development');

        $pdf = Pdf::loadView($view, ['paper' => $paper])->setPaper('a4');

        return $pdf->download(sprintf('%s-%s.pdf', $paper->paper_code, $action));
    }
}
