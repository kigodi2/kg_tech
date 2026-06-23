<?php

namespace Tests\Feature;

use App\Models\ExamSubmission;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSubmissionAdminReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_submission_is_pending_for_admin_review(): void
    {
        $examType = ExamType::create(['name' => 'ACSEE', 'code' => 'ACSEE']);
        $examYear = ExamYear::create(['year_label' => '2026', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MAT', 'exam_type_id' => $examType->id]);

        $user = User::create([
            'name' => 'Teacher',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'password_reset_required' => false,
        ]); 

        $submission = ExamSubmission::create([
            'user_id' => $user->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'subject_id' => $subject->id,
            'exam_paper_path' => 'path.pdf',
            'original_filename' => 'test.pdf',
            'status' => 'pending',
            'validation_results' => ['is_valid' => false],
            'submitted_at' => now(),
        ]);

        $this->assertEquals('pending', $submission->status);

        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('code', 'admin')->first()->id ?? Role::create(['name' => 'Admin', 'code' => 'admin'])->id,
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('exam-submissions.approve', $submission))
            ->assertRedirect();

        $submission->refresh();
        $this->assertEquals('approved', $submission->status);

        $this->actingAs($admin)
            ->post(route('exam-submissions.reject', $submission), ['rejection_reason' => 'Not compliant'])
            ->assertRedirect();

        $submission->refresh();
        $this->assertEquals('rejected', $submission->status);
        $this->assertEquals('Not compliant', $submission->rejection_reason);
    }
}
