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

            $uploadedFile = $request->file('file') ?: $request->file('csv_file');
            \Illuminate\Support\Facades\Log::info('PSLE pupil import validation started', [
                'user_id' => auth()->id(),
                'file_name' => $uploadedFile?->getClientOriginalName(),
                'mime' => $uploadedFile?->getClientMimeType(),
                'size' => $uploadedFile?->getSize(),
                'exam_year_id' => $request->input('exam_year_id'),
                'exam_year' => $request->input('exam_year'),
            ]);

            $preview = $this->service->previewBulk($request->user(), $uploadedFile, $validator->validated());

            return $this->ok($preview, sprintf(
                'Upload preview completed. %d valid rows and %d invalid rows found.',
                $preview['summary']['valid_rows'],
                $preview['summary']['invalid_rows']
            ));
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return $e->getResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file failed validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PSLE pupil import validation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to validate PSLE pupil file. Please check the Laravel log.',
                'debug' => config('app.debug') ? [
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
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

            $uploadedFile = $request->file('file') ?: $request->file('csv_file');
            \Illuminate\Support\Facades\Log::info('PSLE pupil import commit started', [
                'user_id' => auth()->id(),
                'file_name' => $uploadedFile?->getClientOriginalName(),
                'mime' => $uploadedFile?->getClientMimeType(),
                'size' => $uploadedFile?->getSize(),
                'exam_year_id' => $request->input('exam_year_id'),
                'exam_year' => $request->input('exam_year'),
            ]);

            $result = $this->service->importBulk($request->user(), $uploadedFile, $validator->validated());

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
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return $e->getResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file failed validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PSLE pupil import commit failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to commit PSLE pupil import. Please check the Laravel log.',
                'debug' => config('app.debug') ? [
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
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
            if ($request->expectsJson() || $request->ajax()) {
                return $this->fail('Unauthenticated or session expired.', [], 401);
            }
            return redirect('/login');
        }

        $email = strtolower(trim((string) $user->email));

        // 1. Direct admin / owner bypass (including agreykigodi@gmail.com)
        $isAdmin = ($email === 'agreykigodi@gmail.com' || (bool) $user->is_admin || (method_exists($user, 'isAdmin') && $user->isAdmin()));

        // 2. Extract all role/group indicators (signals) from the user object dynamically
        $signals = collect([
            $user->portal_role,
        ])->filter()->map(fn ($value) => strtolower(str_replace(['-', ' '], '_', trim((string) $value))));

        // Handle role relation if it's an object, string, or collection
        if (isset($user->role)) {
            if (is_object($user->role)) {
                if (isset($user->role->code)) {
                    $signals->push(strtolower(str_replace(['-', ' '], '_', trim((string) $user->role->code))));
                }
                if (isset($user->role->name)) {
                    $signals->push(strtolower(str_replace(['-', ' '], '_', trim((string) $user->role->name))));
                }
            } elseif (is_string($user->role)) {
                $signals->push(strtolower(str_replace(['-', ' '], '_', trim($user->role))));
            }
        }

        // Handle Spatie Roles getRoleNames() if exists
        if (method_exists($user, 'getRoleNames')) {
            try {
                foreach ($user->getRoleNames() as $rName) {
                    $signals->push(strtolower(str_replace(['-', ' '], '_', trim((string) $rName))));
                }
            } catch (\Throwable $e) {
                // Ignore Spatie failures if not fully loaded/set up
            }
        }

        // Handle Spatie Roles relation or custom roles collection if exists
        if (isset($user->roles) && (is_array($user->roles) || $user->roles instanceof \Illuminate\Support\Collection || $user->roles instanceof \Countable)) {
            foreach ($user->roles as $roleObj) {
                if (is_object($roleObj)) {
                    if (isset($roleObj->name)) {
                        $signals->push(strtolower(str_replace(['-', ' '], '_', trim((string) $roleObj->name))));
                    }
                    if (isset($roleObj->code)) {
                        $signals->push(strtolower(str_replace(['-', ' '], '_', trim((string) $roleObj->code))));
                    }
                } elseif (is_string($roleObj)) {
                    $signals->push(strtolower(str_replace(['-', ' '], '_', trim($roleObj))));
                }
            }
        }

        // 3. Define allowed role codes
        $allowedRoles = [
            'admin', 'administrator', 'super_admin', 'system_admin',
            'mock_dao', 'mock_rao', 'mock_secretariat', 'reo', 'rao', 'deo', 'dao',
            'mark_officer', 'mark_entry_officer', 'meo',
            'marking_centre_verifier', 'centre_verifier', 'mcv',
            'regional_mark_entry_officer', 'district_mark_entry_officer'
        ];

        $isAuthorized = $isAdmin
            || PsleUserScope::hasGlobalAccess($user)
            || $signals->contains(fn ($value) => in_array($value, $allowedRoles, true))
            || $signals->contains(fn ($value) => str_contains($value, 'mark') && str_contains($value, 'officer'));

        // Log the authorization check outcome for diagnostics in production
        \Illuminate\Support\Facades\Log::warning('PSLE import validation authorization check', [
            'user_id' => $user->id,
            'email' => $email,
            'is_admin' => $isAdmin,
            'signals' => $signals->toArray(),
            'is_authorized' => $isAuthorized,
            'url' => $request->path(),
            'expects_json' => $request->expectsJson() || $request->ajax() || $request->is('api/*'),
        ]);

        if ($isAuthorized) {
            return null;
        }

        // 4. Force JSON response if requested or if it's an AJAX/API call
        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Your account is not allowed to access PSLE candidate registration.',
                'errors' => [],
            ], 403);
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
