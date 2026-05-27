<?php

namespace App\Http\Controllers\MarkEntry;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Services\MarkEntry\PsleCandidateRegistrationService;
use App\Support\PsleUserScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PsleCandidateRegistrationController extends Controller
{
    public function __construct(private PsleCandidateRegistrationService $service)
    {
    }

    public function regions(Request $request)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        return $this->ok($this->service->regions($request->user(), $request->integer('exam_year_id')));
    }

    public function councils(Request $request)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'region_id' => 'required|integer|exists:regions,id',
            'exam_year_id' => 'nullable|integer|exists:exam_years,id',
        ]);

        if ($validator->fails()) {
            return $this->fail('Please select a valid region.', $validator->errors()->toArray(), 422);
        }

        return $this->ok($this->service->councils($request->user(), $request->integer('region_id'), $request->integer('exam_year_id')));
    }

    public function schools(Request $request)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'exam_year_id' => 'nullable|integer|exists:exam_years,id',
            'region_id' => 'nullable|integer|exists:regions,id',
            'council_id' => 'nullable|integer|exists:district_councils,id',
            'selected_id' => 'nullable|integer|exists:schools,id',
            'q' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->fail('Please check the selected filters.', $validator->errors()->toArray(), 422);
        }

        return $this->ok($this->service->schools($request->user(), $request->all()));
    }

    public function list(Request $request)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'exam_year_id' => 'nullable|integer|exists:exam_years,id',
            'region_id' => 'nullable|integer|exists:regions,id',
            'council_id' => 'nullable|integer|exists:district_councils,id',
            'school_id' => 'nullable|integer|exists:schools,id',
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        if ($validator->fails()) {
            return $this->fail('Please check the selected filters.', $validator->errors()->toArray(), 422);
        }

        $candidates = $this->service->candidates($request->user(), $request->all());
        $summary = $this->service->summary($request->user(), $request->all());

        return $this->ok([
            'items' => collect($candidates->items())->map(fn (Candidate $candidate) => $this->candidateResource($candidate))->values(),
            'pagination' => [
                'current_page' => $candidates->currentPage(),
                'last_page' => $candidates->lastPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
            ],
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return $this->fail('Candidate registration could not be saved.', $validator->errors()->toArray(), 422);
        }

        $result = $this->service->createOrUpdateCandidate($request->user(), $validator->validated());

        return $this->ok([
            'candidate' => $this->candidateResource($result['candidate']->load('school.council')),
            'mode' => $result['mode'],
        ], 'Candidate registered successfully.');
    }

    public function update(Request $request, Candidate $candidate)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return $this->fail('Candidate registration could not be updated.', $validator->errors()->toArray(), 422);
        }

        $result = $this->service->createOrUpdateCandidate($request->user(), $validator->validated(), $candidate, true);

        return $this->ok([
            'candidate' => $this->candidateResource($result['candidate']->load('school.council')),
            'mode' => $result['mode'],
        ], 'Candidate updated successfully.');
    }

    public function destroy(Request $request, Candidate $candidate)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'exam_year_id' => 'required|integer|exists:exam_years,id',
        ]);

        if ($validator->fails()) {
            return $this->fail('Please select a valid exam year.', $validator->errors()->toArray(), 422);
        }

        $this->service->deleteCandidate($request->user(), $candidate, $request->integer('exam_year_id'));

        return $this->ok([], 'Candidate record deleted successfully.');
    }

    public function template(Request $request)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'exam_year_id' => 'nullable|integer|exists:exam_years,id',
            'school_id' => 'nullable|integer|exists:schools,id',
        ]);

        if ($validator->fails()) {
            return $this->fail('Please select a valid Exam Year or Primary School before downloading the template.', $validator->errors()->toArray(), 422);
        }

        return $this->service->templateResponse($request->user(), $request->integer('exam_year_id'), $request->integer('school_id'));
    }

    public function preview(Request $request)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        try {
            $validator = Validator::make($request->all(), [
                'exam_year_id' => 'nullable|integer|exists:exam_years,id',
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
                'school_id' => 'nullable|integer|exists:schools,id',
                'file' => 'required_without:csv_file|file|mimes:csv,txt|max:51200',
                'csv_file' => 'required_without:file|file|mimes:csv,txt|max:51200',
                'on_exists_mode' => 'nullable|in:skip,replace,stop',
            ]);

            if ($validator->fails()) {
                return $this->fail('The upload could not be previewed.', $validator->errors()->toArray(), 422);
            }

            $preview = $this->service->previewBulk($request->user(), $request->file('file') ?: $request->file('csv_file'), $validator->validated());

            return $this->ok($preview, sprintf(
                'Upload preview completed. %d valid rows and %d invalid rows found.',
                $preview['summary']['valid_rows'],
                $preview['summary']['invalid_rows']
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PSLE Import Preview Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->fail('An unexpected error occurred during preview: ' . $e->getMessage(), [], 500);
        }
    }

    public function import(Request $request)
    {
        if ($response = $this->ensureAllowedUser($request)) {
            return $response;
        }

        try {
            $validator = Validator::make($request->all(), [
                'exam_year_id' => 'nullable|integer|exists:exam_years,id',
                'exam_year' => 'nullable|string|regex:/^\d{4}$/',
                'school_id' => 'nullable|integer|exists:schools,id',
                'file' => 'required_without:csv_file|file|mimes:csv,txt|max:51200',
                'csv_file' => 'required_without:file|file|mimes:csv,txt|max:51200',
                'on_exists_mode' => 'nullable|in:skip,replace,stop',
            ]);

            if ($validator->fails()) {
                return $this->fail('The import could not start.', $validator->errors()->toArray(), 422);
            }

            $result = $this->service->importBulk($request->user(), $request->file('file') ?: $request->file('csv_file'), $validator->validated());

            if (($result['success'] ?? true) === false) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Import could not be completed.',
                    'summary' => $result['summary'] ?? [],
                    'errors' => [],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'PSLE pupil import completed successfully.',
                'summary' => $result['summary'] ?? [],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PSLE Import Commit Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->fail('An unexpected error occurred during import: ' . $e->getMessage(), [], 500);
        }
    }

    private function rules(): array
    {
        return [
            'exam_year_id' => 'required|integer|exists:exam_years,id',
            'school_id' => 'required|integer|exists:schools,id',
            'candidate_id' => 'required|string|max:50',
            'prem_no' => 'required|string|size:11',
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
        ];
    }

    private function ensureAllowedUser(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Please sign in to continue.', [], 401);
        }

        $signals = collect([
            $user?->role?->code,
            $user?->role?->name,
            $user?->portal_role,
        ])->filter()->map(fn ($value) => strtolower(str_replace(['-', ' '], '_', trim((string) $value))));

        $isMarkEntryOfficer = $signals->contains(fn ($value) => str_contains($value, 'mark') && str_contains($value, 'officer'));

        if (PsleUserScope::hasGlobalAccess($user) || $isMarkEntryOfficer) {
            return null;
        }

        return $this->fail('You are not authorized to access PSLE candidate registration.', [], 403);
    }

    private function candidateResource(Candidate $candidate): array
    {
        return [
            'id' => $candidate->id,
            'candidate_id' => $candidate->candidate_id,
            'prem_no' => $candidate->prem_no,
            'full_name' => $candidate->full_name,
            'gender' => $candidate->gender,
            'school_id' => $candidate->school_id,
            'school_code' => $candidate->school?->code,
            'school_name' => $candidate->school?->name,
            'council_id' => $candidate->school?->council_id,
            'council_name' => $candidate->school?->council?->name,
            'region_id' => $candidate->school?->region_id ?: $candidate->school?->council?->region_id,
            'status' => $candidate->status ?: 'registered',
            'can_edit' => true,
            'can_delete' => PsleUserScope::hasGlobalAccess(request()->user()) || request()->user()?->portal_role === 'mark_entry_officer',
        ];
    }

    private function ok($data = [], string $message = 'OK')
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ]);
    }

    private function fail(string $message, array $errors = [], int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
