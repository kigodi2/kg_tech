<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\Region;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectMarks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CseeMarkEntryController extends Controller
{
    public function index()
    {
        return view('mark-entry.csee');
    }

    public function bootstrap()
    {
        $examType = $this->resolveCseeExamType();

        $years = ExamYear::query()
            ->whereHas('candidateExamRegistrations', function (Builder $query) use ($examType) {
                $query->where('exam_type_id', $examType->id);
            })
            ->orderByDesc('year_label')
            ->get(['id', 'year_label', 'is_active'])
            ->map(fn (ExamYear $year) => [
                'id' => (int) $year->id,
                'year_label' => (string) $year->year_label,
                'is_active' => (bool) $year->is_active,
            ])
            ->values();

        if ($years->isEmpty()) {
            $years = ExamYear::query()
                ->orderByDesc('year_label')
                ->get(['id', 'year_label', 'is_active'])
                ->map(fn (ExamYear $year) => [
                    'id' => (int) $year->id,
                    'year_label' => (string) $year->year_label,
                    'is_active' => (bool) $year->is_active,
                ])
                ->values();
        }

        $activeYear = $years->firstWhere('is_active', true) ?? $years->first();

        return response()->json([
            'data' => [
                'exam_years' => $years,
                'active_year' => $activeYear,
                'regions' => Region::query()
                    ->orderBy('name')
                    ->get(['id', 'code', 'name'])
                    ->map(fn (Region $region) => [
                        'id' => (int) $region->id,
                        'code' => (string) $region->code,
                        'name' => (string) $region->name,
                    ])
                    ->values(),
            ],
        ]);
    }

    public function districts(Request $request)
    {
        $validated = $request->validate([
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
        ]);

        $districts = District::query()
            ->when(
                !empty($validated['region_id']),
                fn (Builder $query) => $query->where('region_id', (int) $validated['region_id'])
            )
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'region_id']);

        return response()->json([
            'data' => $districts->map(fn (District $district) => [
                'id' => (int) $district->id,
                'code' => (string) $district->code,
                'name' => (string) $district->name,
                'region_id' => (int) $district->region_id,
            ])->values(),
        ]);
    }

    public function schools(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $examType = $this->resolveCseeExamType();
        $examYear = $this->resolveExamYear($filters['exam_year'] ?? null);

        $schools = School::query()
            ->select('schools.id', 'schools.code', 'schools.name', 'schools.district_id', 'schools.region_id')
            ->whereHas('candidates.examRegistrations', function (Builder $query) use ($examType, $examYear) {
                $query->where('exam_type_id', $examType->id)
                    ->where('exam_year_id', $examYear->id);
            })
            ->when(
                !empty($filters['region_id']),
                fn (Builder $query) => $query->where('schools.region_id', (int) $filters['region_id'])
            )
            ->when(
                !empty($filters['district_id']),
                fn (Builder $query) => $query->where('schools.district_id', (int) $filters['district_id'])
            )
            ->orderBy('schools.code')
            ->get();

        return response()->json([
            'data' => $schools->map(fn (School $school) => [
                'id' => (int) $school->id,
                'code' => (string) $school->code,
                'name' => (string) $school->name,
                'district_id' => (int) $school->district_id,
                'region_id' => (int) ($school->region_id ?? 0),
            ])->values(),
        ]);
    }

    public function subjects(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $examType = $this->resolveCseeExamType();
        $examYear = $this->resolveExamYear($filters['exam_year'] ?? null);

        $subjects = Subject::query()
            ->select('subjects.id', 'subjects.code', 'subjects.name')
            ->where('subjects.exam_type_id', $examType->id)
            ->whereHas('selections', function (Builder $query) use ($filters, $examType, $examYear) {
                $query->where('exam_type_id', $examType->id)
                    ->where('exam_year_id', $examYear->id)
                    ->whereHas('candidate', function (Builder $candidateQuery) use ($filters) {
                        $candidateQuery
                            ->when(
                                !empty($filters['school_id']),
                                fn (Builder $query) => $query->where('school_id', (int) $filters['school_id'])
                            )
                            ->when(
                                !empty($filters['district_id']),
                                fn (Builder $query) => $query->whereHas('school', fn (Builder $schoolQuery) => $schoolQuery->where('district_id', (int) $filters['district_id']))
                            )
                            ->when(
                                !empty($filters['region_id']),
                                fn (Builder $query) => $query->whereHas('school', fn (Builder $schoolQuery) => $schoolQuery->where('region_id', (int) $filters['region_id']))
                            );
                    });
            })
            ->orderBy('subjects.code')
            ->get();

        return response()->json([
            'data' => $subjects->map(fn (Subject $subject) => [
                'id' => (int) $subject->id,
                'code' => (string) $subject->code,
                'name' => (string) $subject->name,
            ])->values(),
        ]);
    }

    public function dashboard(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $examType = $this->resolveCseeExamType();
        $examYear = $this->resolveExamYear($filters['exam_year'] ?? null);
        $perPage = max(10, min((int) ($filters['per_page'] ?? 100), 100));

        $candidateQuery = Candidate::query()
            ->whereHas('examRegistrations', function (Builder $query) use ($examType, $examYear) {
                $query->where('exam_type_id', $examType->id)
                    ->where('exam_year_id', $examYear->id);
            })
            ->with([
                'school:id,code,name,district_id,region_id',
                'school.district:id,name',
                'school.region:id,name',
                'subjectSelections' => function ($query) use ($examType, $examYear) {
                    $query->where('exam_type_id', $examType->id)
                        ->where('exam_year_id', $examYear->id)
                        ->with('subject:id,code,name')
                        ->orderBy('subject_id');
                },
                'marks' => function ($query) use ($examType, $examYear) {
                    $query->where('exam_type_id', $examType->id)
                        ->where('year', (int) $examYear->year_label)
                        ->select('id', 'candidate_id', 'subject_id', 'exam_type_id', 'year', 'marks_obtained', 'grade');
                },
            ]);

        $this->applyCandidateFilters($candidateQuery, $filters);

        $paginator = $candidateQuery
            ->orderBy('candidate_id')
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));

        $candidateCollection = $paginator->getCollection();

        $candidateIds = $candidateCollection->pluck('id')->map(fn ($id) => (int) $id)->all();
        $registeredSelectionsCount = CandidateSubjectSelection::query()
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->when(
                !empty($candidateIds),
                fn (Builder $query) => $query->whereIn('candidate_id', $candidateIds),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->count();
        $enteredMarksCount = SubjectMarks::query()
            ->where('exam_type_id', $examType->id)
            ->where('year', (int) $examYear->year_label)
            ->when(
                !empty($candidateIds),
                fn (Builder $query) => $query->whereIn('candidate_id', $candidateIds),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->count();

        $batchQuery = MarkImportBatch::query()
            ->with(['school:id,code,name', 'district:id,name', 'subject:id,code,name'])
            ->where('exam_type_id', $examType->id)
            ->where('exam_year', (int) $examYear->year_label);

        if (!empty($filters['region_id'])) {
            $batchQuery->where('region_id', (int) $filters['region_id']);
        }
        if (!empty($filters['district_id'])) {
            $batchQuery->where('district_id', (int) $filters['district_id']);
        }
        if (!empty($filters['school_id'])) {
            $batchQuery->where('school_id', (int) $filters['school_id']);
        }
        if (!empty($filters['subject_id'])) {
            $batchQuery->where('subject_id', (int) $filters['subject_id']);
        }

        $recentBatches = $batchQuery
            ->latest('imported_at')
            ->limit(12)
            ->get()
            ->map(fn (MarkImportBatch $batch) => [
                'id' => (int) $batch->id,
                'batch_code' => (string) $batch->batch_code,
                'status' => (string) $batch->status,
                'status_label' => (string) (MarkImportBatch::STATUSES[$batch->status] ?? ucfirst((string) $batch->status)),
                'school' => trim(((string) ($batch->school?->code ?? '')) . ' - ' . ((string) ($batch->school?->name ?? '-')), ' -'),
                'subject' => trim(((string) ($batch->subject?->code ?? '')) . ' - ' . ((string) ($batch->subject?->name ?? '-')), ' -'),
                'district' => (string) ($batch->district?->name ?? '-'),
                'rows' => (int) ($batch->total_records ?? 0),
                'valid_records' => (int) ($batch->valid_records ?? 0),
                'error_records' => (int) ($batch->error_records ?? 0),
                'imported_at' => optional($batch->imported_at)->format('Y-m-d H:i'),
            ])
            ->values();

        $summaryCandidateQuery = Candidate::query()->whereHas('examRegistrations', function (Builder $query) use ($examType, $examYear) {
            $query->where('exam_type_id', $examType->id)
                ->where('exam_year_id', $examYear->id);
        });
        $this->applyCandidateFilters($summaryCandidateQuery, $filters);
        $candidateTotal = (clone $summaryCandidateQuery)->count();
        $schoolTotal = (clone $summaryCandidateQuery)->distinct('school_id')->count('school_id');

        $selectionSummaryQuery = CandidateSubjectSelection::query()
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->whereHas('candidate', function (Builder $query) use ($filters) {
                $this->applyCandidateFilters($query, $filters);
            });

        $subjectTotal = (clone $selectionSummaryQuery)->distinct('subject_id')->count('subject_id');
        $registeredTotal = (clone $selectionSummaryQuery)->count();

        $marksSummaryQuery = SubjectMarks::query()
            ->where('exam_type_id', $examType->id)
            ->where('year', (int) $examYear->year_label)
            ->whereHas('candidate', function (Builder $query) use ($filters) {
                $this->applyCandidateFilters($query, $filters);
            });
        $enteredTotal = (clone $marksSummaryQuery)->count();

        return response()->json([
            'data' => [
                'exam_year' => [
                    'id' => (int) $examYear->id,
                    'year_label' => (string) $examYear->year_label,
                ],
                'summary' => [
                    'candidate_count' => $candidateTotal,
                    'school_count' => $schoolTotal,
                    'subject_count' => $subjectTotal,
                    'registered_subject_rows' => $registeredTotal,
                    'entered_subject_rows' => $enteredTotal,
                    'batch_count' => $batchQuery->count(),
                ],
                'scope_progress' => [
                    'registered_subject_rows' => $registeredSelectionsCount,
                    'entered_subject_rows' => $enteredMarksCount,
                ],
                'candidates' => $candidateCollection->map(function (Candidate $candidate) {
                    $subjectSelections = $candidate->subjectSelections ?? collect();
                    $enteredSubjectIds = collect($candidate->marks ?? [])
                        ->pluck('subject_id')
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();

                    return [
                        'id' => (int) $candidate->id,
                        'candidate_id' => (string) $candidate->candidate_id,
                        'full_name' => (string) $candidate->full_name,
                        'gender' => (string) $candidate->gender,
                        'school' => [
                            'id' => (int) ($candidate->school?->id ?? 0),
                            'code' => (string) ($candidate->school?->code ?? ''),
                            'name' => (string) ($candidate->school?->name ?? '-'),
                            'district' => (string) ($candidate->school?->district?->name ?? '-'),
                            'region' => (string) ($candidate->school?->region?->name ?? '-'),
                        ],
                        'registered_subjects' => $subjectSelections->map(function (CandidateSubjectSelection $selection) use ($enteredSubjectIds) {
                            $subjectId = (int) $selection->subject_id;

                            return [
                                'subject_id' => $subjectId,
                                'code' => (string) ($selection->subject?->code ?? ''),
                                'name' => (string) ($selection->subject?->name ?? ''),
                                'entered' => $enteredSubjectIds->contains($subjectId),
                            ];
                        })->values(),
                        'registered_count' => $subjectSelections->count(),
                        'entered_count' => $enteredSubjectIds->count(),
                    ];
                })->values(),
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
                'recent_batches' => $recentBatches,
            ],
        ]);
    }

    private function resolveCseeExamType(): ExamType
    {
        return ExamType::query()->where('code', 'CSEE')->firstOrFail();
    }

    private function resolveExamYear(?string $examYearLabel): ExamYear
    {
        if ($examYearLabel) {
            $year = ExamYear::query()->where('year_label', $examYearLabel)->first();
            if ($year) {
                return $year;
            }
        }

        return ExamYear::query()
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'exam_year' => ['nullable', 'string'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'search' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
    }

    private function applyCandidateFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['region_id'])) {
            $query->whereHas('school', fn (Builder $schoolQuery) => $schoolQuery->where('region_id', (int) $filters['region_id']));
        }

        if (!empty($filters['district_id'])) {
            $query->whereHas('school', fn (Builder $schoolQuery) => $schoolQuery->where('district_id', (int) $filters['district_id']));
        }

        if (!empty($filters['school_id'])) {
            $query->where('school_id', (int) $filters['school_id']);
        }

        if (!empty($filters['subject_id'])) {
            $subjectId = (int) $filters['subject_id'];
            $query->whereHas('subjectSelections', fn (Builder $selectionQuery) => $selectionQuery->where('subject_id', $subjectId));
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery
                    ->where('candidate_id', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhereHas('school', function (Builder $schoolQuery) use ($search) {
                        $schoolQuery
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }
    }
}
