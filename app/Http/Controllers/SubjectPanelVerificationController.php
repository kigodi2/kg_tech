<?php

namespace App\Http\Controllers;

use App\Models\DistrictCouncil;
use App\Models\ExamYear;
use App\Models\GovernanceAuditLog;
use App\Models\MarkVerification;
use App\Models\RawMark;
use App\Models\Region;
use App\Models\School;
use App\Models\SubjectPanelAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubjectPanelVerificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $this->authorizeSubjectPanelLeader($user);

        $assignment = SubjectPanelAssignment::getActiveForUser($user->id);

        if (!$assignment) {
            return view('subject-panel.verification.index', [
                'noAssignment' => true,
                'user' => $user,
            ]);
        }

        $assignedSubjectId = (int) $assignment->subject_id;
        $assignedSubject = $assignment->subject;
        $assignedRegionId = $assignment->region_id;

        $activeYear = ExamYear::where('is_active', true)->first();
        $examYears = ExamYear::orderBy('year_label', 'desc')->get();
        $selectedYearId = $assignment->exam_year_id
            ?: $request->query('exam_year_id', $activeYear?->id);

        $selectedRegionId = $assignedRegionId ?: $request->query('region_id');
        $selectedCouncilId = $request->query('council_id');
        $selectedSchoolId = $request->query('school_id');
        $selectedStatus = $request->query('status');
        $searchQuery = $request->query('search');

        $regions = $assignedRegionId
            ? Region::where('id', $assignedRegionId)->get()
            : Region::whereHas('schools.markImportBatches', function ($query) use ($selectedYearId, $assignedSubjectId) {
                $this->applyReviewableBatchScope($query, $selectedYearId, $assignedSubjectId);
            })->orderBy('name')->get();

        $councils = DistrictCouncil::whereHas('schools.markImportBatches', function ($query) use ($selectedYearId, $assignedSubjectId) {
                $this->applyReviewableBatchScope($query, $selectedYearId, $assignedSubjectId);
            })
            ->whereHas('schools', function ($query) use ($selectedRegionId) {
                if ($selectedRegionId) {
                    $query->where('region_id', $selectedRegionId);
                }
            })
            ->orderBy('name')
            ->get();

        if ($selectedCouncilId && !$councils->contains('id', (int) $selectedCouncilId)) {
            $selectedCouncilId = null;
        }

        $schoolsQuery = School::whereHas('markImportBatches', function ($query) use ($selectedYearId, $assignedSubjectId) {
                $this->applyReviewableBatchScope($query, $selectedYearId, $assignedSubjectId);
            })
            ->whereIn('school_type', ['PRIMARY', 'BOTH'])
            ->orderBy('name');

        if ($selectedRegionId) {
            $schoolsQuery->where('region_id', $selectedRegionId);
        }

        if ($selectedCouncilId) {
            $schoolsQuery->where('council_id', $selectedCouncilId);
        }

        $schools = $schoolsQuery->get();

        if ($selectedSchoolId && !$schools->contains('id', (int) $selectedSchoolId)) {
            $selectedSchoolId = null;
        }

        $marksQuery = RawMark::with([
                'candidate.school.council',
                'candidate.school.region',
                'batch.school',
                'batch.importedByUser',
                'batch.createdByUser',
                'verification',
            ])
            ->where('subject_id', $assignedSubjectId)
            ->whereHas('batch', function ($query) use ($selectedYearId, $assignedSubjectId, $selectedRegionId, $selectedCouncilId, $selectedSchoolId) {
                $this->applyReviewableBatchScope($query, $selectedYearId, $assignedSubjectId, $selectedRegionId, $selectedCouncilId, $selectedSchoolId);
            });

        if ($selectedStatus) {
            if ($selectedStatus === MarkVerification::STATUS_PENDING) {
                $marksQuery->where(function ($query) {
                    $query->whereDoesntHave('verification')
                        ->orWhereHas('verification', fn($q) => $q->where('status', MarkVerification::STATUS_PENDING));
                });
            } else {
                $marksQuery->whereHas('verification', fn($q) => $q->where('status', $selectedStatus));
            }
        }

        if ($searchQuery) {
            $marksQuery->where(function ($query) use ($searchQuery) {
                $query->where('candidate_index_number', 'like', "%{$searchQuery}%")
                    ->orWhere('full_name', 'like', "%{$searchQuery}%");
            });
        }

        $marks = $marksQuery->orderBy('candidate_index_number')->paginate(25)->withQueryString();

        $statsBaseQuery = fn() => RawMark::where('subject_id', $assignedSubjectId)
            ->whereHas('batch', function ($query) use ($selectedYearId, $assignedSubjectId, $selectedRegionId, $selectedCouncilId, $selectedSchoolId) {
                $this->applyReviewableBatchScope($query, $selectedYearId, $assignedSubjectId, $selectedRegionId, $selectedCouncilId, $selectedSchoolId);
            });

        $totalMarks = $statsBaseQuery()->count();
        $verifiedCount = $statsBaseQuery()->whereHas('verification', fn($q) => $q->where('status', MarkVerification::STATUS_VERIFIED))->count();
        $returnedCount = $statsBaseQuery()->whereHas('verification', fn($q) => $q->where('status', MarkVerification::STATUS_RETURNED))->count();
        $correctedCount = $statsBaseQuery()->whereHas('verification', fn($q) => $q->where('status', MarkVerification::STATUS_CORRECTED))->count();
        $pendingCount = $statsBaseQuery()->where(function ($query) {
            $query->whereDoesntHave('verification')
                ->orWhereHas('verification', fn($q) => $q->where('status', MarkVerification::STATUS_PENDING));
        })->count();

        $schoolsCount = School::whereHas('markImportBatches', function ($query) use ($selectedYearId, $assignedSubjectId) {
                $this->applyReviewableBatchScope($query, $selectedYearId, $assignedSubjectId);
            })
            ->when($selectedRegionId, fn($query) => $query->where('region_id', $selectedRegionId))
            ->when($selectedCouncilId, fn($query) => $query->where('council_id', $selectedCouncilId))
            ->count();

        return view('subject-panel.verification.index', [
            'noAssignment' => false,
            'user' => $user,
            'assignment' => $assignment,
            'assignedSubject' => $assignedSubject,
            'examYears' => $examYears,
            'selectedYearId' => $selectedYearId,
            'regions' => $regions,
            'selectedRegionId' => $selectedRegionId,
            'councils' => $councils,
            'selectedCouncilId' => $selectedCouncilId,
            'schools' => $schools,
            'selectedSchoolId' => $selectedSchoolId,
            'selectedStatus' => $selectedStatus,
            'searchQuery' => $searchQuery,
            'marks' => $marks,
            'stats' => [
                'total' => $totalMarks,
                'verified' => $verifiedCount,
                'returned' => $returnedCount,
                'corrected' => $correctedCount,
                'pending' => $pendingCount,
                'schools' => $schoolsCount,
                'candidates' => $totalMarks,
            ],
            'statuses' => MarkVerification::STATUSES,
            'returnReasons' => MarkVerification::RETURN_REASONS,
        ]);
    }

    public function show(RawMark $rawMark)
    {
        $user = Auth::user();
        $this->authorizeSubjectPanelLeader($user);
        $this->authorizeSubjectAccess($user, $rawMark);

        $rawMark->load([
            'candidate.school.council',
            'candidate.school.region',
            'batch.school',
            'batch.importedByUser',
            'batch.createdByUser',
            'subject',
            'verification.verifiedBy',
            'verification.returnedTo',
        ]);

        GovernanceAuditLog::log('panel_mark_viewed', userId: $user->id, data: [
            'raw_mark_id' => $rawMark->id,
            'candidate_index' => $rawMark->candidate_index_number,
            'subject_id' => $rawMark->subject_id,
            'school_id' => $rawMark->batch?->school_id,
            'exam_year_id' => $rawMark->batch?->exam_year_id,
            'ip_address' => request()->ip(),
        ]);

        return view('subject-panel.verification.show', [
            'user' => $user,
            'rawMark' => $rawMark,
            'returnReasons' => MarkVerification::RETURN_REASONS,
        ]);
    }

    public function verify(Request $request, RawMark $rawMark)
    {
        $user = Auth::user();
        $this->authorizeSubjectPanelLeader($user);
        $this->authorizeSubjectAccess($user, $rawMark);

        $existing = $rawMark->verification;
        if ($existing && $existing->isVerified()) {
            return back()->with('info', 'This record has already been verified.');
        }

        if ($existing && $existing->isReturned()) {
            return back()->with('error', 'Cannot verify a record that has been returned for correction. Wait for the Mark Entry Officer to correct and resubmit.');
        }

        DB::transaction(function () use ($rawMark, $user) {
            $verification = MarkVerification::findOrCreateForRawMark($rawMark);
            $oldStatus = $verification->status;

            $verification->update([
                'status' => MarkVerification::STATUS_VERIFIED,
                'verified_by' => $user->id,
                'verified_at' => now(),
                'return_reason' => null,
            ]);

            GovernanceAuditLog::log('panel_mark_verified', userId: $user->id, data: [
                'raw_mark_id' => $rawMark->id,
                'candidate_index' => $rawMark->candidate_index_number,
                'subject_id' => $rawMark->subject_id,
                'school_id' => $rawMark->batch?->school_id,
                'exam_year_id' => $rawMark->batch?->exam_year_id,
                'old_status' => $oldStatus,
                'new_status' => MarkVerification::STATUS_VERIFIED,
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', 'Mark verified successfully.');
    }

    public function returnForCorrection(Request $request, RawMark $rawMark)
    {
        $user = Auth::user();
        $this->authorizeSubjectPanelLeader($user);
        $this->authorizeSubjectAccess($user, $rawMark);

        $request->validate([
            'return_reason' => 'required|string|max:500',
            'paper_code' => 'nullable|in:paper_1,paper_2,paper_3,practical,project',
        ]);

        $existing = $rawMark->verification;
        if ($existing && $existing->isVerified()) {
            return back()->with('error', 'Cannot return a record that has already been verified.');
        }

        $returnedToUserId = $rawMark->batch?->created_by ?: $rawMark->batch?->imported_by;

        DB::transaction(function () use ($rawMark, $user, $request, $returnedToUserId) {
            $verification = MarkVerification::findOrCreateForRawMark($rawMark);
            $oldStatus = $verification->status;

            $verification->update([
                'status' => MarkVerification::STATUS_RETURNED,
                'return_reason' => $request->input('return_reason'),
                'returned_to_user_id' => $returnedToUserId,
                'returned_at' => now(),
                'correction_round' => ($verification->correction_round ?? 0) + 1,
                'verified_at' => null,
                'verified_by' => $user->id,
            ]);

            GovernanceAuditLog::log('panel_mark_returned', userId: $user->id, data: [
                'raw_mark_id' => $rawMark->id,
                'candidate_index' => $rawMark->candidate_index_number,
                'subject_id' => $rawMark->subject_id,
                'school_id' => $rawMark->batch?->school_id,
                'exam_year_id' => $rawMark->batch?->exam_year_id,
                'old_status' => $oldStatus,
                'new_status' => MarkVerification::STATUS_RETURNED,
                'return_reason' => $request->input('return_reason'),
                'paper_code' => $request->input('paper_code'),
                'returned_to_user_id' => $returnedToUserId,
                'correction_round' => $verification->correction_round,
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', 'Mark returned to the Mark Entry Officer for correction.');
    }

    private function authorizeSubjectPanelLeader($user): void
    {
        if (!$user) {
            abort(403, 'Authentication required.');
        }

        if ($user->portal_role !== 'subject_panel_leader' && !$user->isAdmin()) {
            abort(403, 'Access denied. This portal is for Subject Panel Leaders only.');
        }
    }

    private function authorizeSubjectAccess($user, RawMark $rawMark): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $assignment = SubjectPanelAssignment::getActiveForUser($user->id);

        if (!$assignment || (int) $rawMark->subject_id !== (int) $assignment->subject_id) {
            abort(403, 'You are not assigned to verify this subject.');
        }

        $rawMark->loadMissing('batch');

        if ($assignment->exam_year_id && (int) $rawMark->batch?->exam_year_id !== (int) $assignment->exam_year_id) {
            abort(403, 'You are not assigned to verify this exam year.');
        }

        if ($assignment->region_id && (int) $rawMark->batch?->region_id !== (int) $assignment->region_id) {
            abort(403, 'You are not assigned to verify marks in this region.');
        }
    }

    private function applyReviewableBatchScope($query, $examYearId, int $subjectId, $regionId = null, $councilId = null, $schoolId = null): void
    {
        $query->where('subject_id', $subjectId)
            ->whereHas('examType', fn($examTypeQuery) => $examTypeQuery->where('code', 'PSLE'))
            ->whereIn('status', ['submitted', 'approved', 'locked', 'processed']);

        if ($examYearId) {
            $query->where('exam_year_id', $examYearId);
        }

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        if ($councilId) {
            $query->whereHas('school', fn($schoolQuery) => $schoolQuery->where('council_id', $councilId));
        }

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
    }
}
