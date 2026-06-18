<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\GovernanceAuditLog;
use App\Models\Region;
use App\Models\Subject;
use App\Models\SubjectPanelAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class SubjectPanelAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $assignments = SubjectPanelAssignment::with(['user', 'subject', 'examYear', 'region', 'createdBy'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $psleSubjects = Subject::whereHas('examType', fn($q) => $q->where('code', 'PSLE'))
            ->orderBy('name')->get();

        $panelLeaders = User::where('portal_role', 'subject_panel_leader')
            ->orderBy('name')->get();

        $examYears = ExamYear::orderBy('year_label', 'desc')->get();
        $regions   = Region::orderBy('name')->get();

        return view('admin.subject-panel-assignments.index', compact(
            'assignments', 'psleSubjects', 'panelLeaders', 'examYears', 'regions'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'subject_id'   => 'required|exists:subjects,id',
            'exam_year_id' => 'nullable|exists:exam_years,id',
            'region_id'    => 'nullable|exists:regions,id',
            'is_active'    => 'boolean',
        ]);

        // Verify the user has panel leader role
        $panelUser = User::findOrFail($validated['user_id']);
        if ($panelUser->portal_role !== 'subject_panel_leader' && !$panelUser->isAdmin()) {
            return back()->withErrors(['user_id' => 'The selected user is not a Subject Panel Leader.'])->withInput();
        }

        // Verify the user is active
        if ($panelUser->status !== 'active') {
            return back()->withErrors(['user_id' => 'The selected user is not active.'])->withInput();
        }

        // Verify the subject is a PSLE subject only
        $psleExamType = ExamType::where('code', 'PSLE')->first();
        $psleExamTypeId = $psleExamType?->id;
        $subject = Subject::findOrFail($validated['subject_id']);
        if ((int)$subject->exam_type_id !== (int)$psleExamTypeId) {
            return back()->withErrors(['subject_id' => 'The selected subject is not a PSLE subject.'])->withInput();
        }

        // Prevent duplicate active assignments
        $duplicateExists = SubjectPanelAssignment::where([
            'user_id' => $validated['user_id'],
            'subject_id' => $validated['subject_id'],
            'exam_year_id' => $validated['exam_year_id'] ?? null,
            'region_id' => $validated['region_id'] ?? null,
            'is_active' => true,
        ])->exists();

        if ($duplicateExists) {
            return back()->withErrors(['user_id' => 'This panel leader already has an active assignment for this subject, year, and region scope.'])->withInput();
        }

        // General unique check to avoid DB unique constraint crash
        $uniqueViolation = SubjectPanelAssignment::where([
            'user_id' => $validated['user_id'],
            'subject_id' => $validated['subject_id'],
            'exam_year_id' => $validated['exam_year_id'] ?? null,
        ])->exists();

        if ($uniqueViolation) {
            return back()->withErrors(['user_id' => 'This panel leader already has an assignment for this subject and year.'])->withInput();
        }

        $assignment = SubjectPanelAssignment::create([
            ...$validated,
            'exam_type_id' => $psleExamTypeId,
            'is_active'  => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        GovernanceAuditLog::log(
            'panel_assignment_created',
            userId: null,
            adminId: auth()->id(),
            data: [
                'assignment_id' => $assignment->id,
                'subject_id'    => $assignment->subject_id,
                'exam_year_id'  => $assignment->exam_year_id,
                'region_id'     => $assignment->region_id,
                'for_user_id'   => $panelUser->id,
            ]
        );

        return redirect('/mark-entry/psle?view=subject-panel-assignments')->with('success', 'Subject Panel Assignment created successfully.');
    }

    public function destroy(SubjectPanelAssignment $subjectPanelAssignment)
    {
        $subjectPanelAssignment->delete();

        GovernanceAuditLog::log(
            'panel_assignment_deleted',
            adminId: auth()->id(),
            data: [
                'assignment_id' => $subjectPanelAssignment->id,
                'user_id'       => $subjectPanelAssignment->user_id,
                'subject_id'    => $subjectPanelAssignment->subject_id,
            ]
        );

        return redirect('/mark-entry/psle?view=subject-panel-assignments')->with('success', 'Assignment removed.');
    }

    public function toggleActive(SubjectPanelAssignment $subjectPanelAssignment)
    {
        $subjectPanelAssignment->update(['is_active' => !$subjectPanelAssignment->is_active]);
        $status = $subjectPanelAssignment->is_active ? 'activated' : 'deactivated';
        return redirect('/mark-entry/psle?view=subject-panel-assignments')->with('success', "Assignment {$status}.");
    }
}
