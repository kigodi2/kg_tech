<?php

namespace App\Http\Controllers\MarkEntry\Questions;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkEntry\Questions\LoadQuestionMarkEntryRequest;
use App\Http\Requests\MarkEntry\Questions\SaveQuestionMarkEntryRequest;
use App\Services\MarkEntry\Questions\QuestionMarkEntryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuestionMarkEntryController extends Controller
{
    public function __construct(private QuestionMarkEntryService $service)
    {
    }

    public function show(Request $request, string $examCode)
    {
        $candidateNo = trim((string) ($request->query('candidate_no') ?: session()->getOldInput('candidate_no', '')));
        $subjectId = $request->query('subject_id') ?: session()->getOldInput('subject_id');

        try {
            $data = $this->service->pageData(
                $examCode,
                $request->user(),
                $candidateNo !== '' ? $candidateNo : null,
                $subjectId ? (int) $subjectId : null
            );
        } catch (ValidationException $exception) {
            $data = $this->service->pageData($examCode, $request->user());

            return view('mark-entry.questions.page', $data + [
                'examCode' => strtoupper($examCode),
                'pageErrors' => collect($exception->errors())->flatten()->all(),
                'candidateNo' => $candidateNo,
                'selectedSubjectId' => $subjectId ? (int) $subjectId : null,
            ]);
        }

        return view('mark-entry.questions.page', $data + [
            'examCode' => strtoupper($examCode),
            'pageErrors' => [],
        ]);
    }

    public function load(LoadQuestionMarkEntryRequest $request, string $examCode)
    {
        $routeBase = 'mark-entry.' . strtolower($examCode) . '.questions.show';

        return redirect()->route($routeBase, [
            'candidate_no' => $request->validated('candidate_no'),
            'subject_id' => $request->validated('subject_id'),
        ]);
    }

    public function store(SaveQuestionMarkEntryRequest $request, string $examCode)
    {
        $entry = $this->service->save($examCode, $request->user(), $request->validated());
        $action = $request->validated('entry_action');
        $routeBase = 'mark-entry.' . strtolower($examCode) . '.questions.show';

        if ($action === 'next') {
            return redirect()
                ->route($routeBase, [
                    'subject_id' => $entry->subject_id,
                ])
                ->with('focus_candidate_no', true)
                ->with('success', 'Draft saved. Continue with the next candidate.');
        }

        return redirect()
            ->route($routeBase, [
                'candidate_no' => $entry->candidate_no,
                'subject_id' => $entry->subject_id,
            ])
            ->with('success', $action === 'submit'
                ? 'Question marks submitted successfully.'
                : 'Draft saved successfully.');
    }
}
