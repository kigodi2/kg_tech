<?php

namespace Tests\Feature;

use App\Models\ExamType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTypeLevelFilterStyleTest extends TestCase
{
    use RefreshDatabase;

    protected function signIn(): User
    {
        $user = User::create([
            'name' => 'Exam Type Tester',
            'email' => 'exam-type-tester@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_exam_types_index_modal_uses_searchable_level_field(): void
    {
        $this->signIn();

        $response = $this->get('/exam-types');

        $response->assertOk();
        $response->assertSee('list="exam-type-level-options"', false);
        $response->assertSee('placeholder="Search level"', false);
        $response->assertSee('<datalist id="exam-type-level-options">', false);
        $response->assertSee('<option value="Primary"></option>', false);
        $response->assertSee('<option value="Secondary"></option>', false);
        $response->assertSee('<option value="Advanced Secondary"></option>', false);
    }

    public function test_exam_type_edit_page_uses_searchable_education_level_field(): void
    {
        $this->signIn();

        $examType = ExamType::create([
            'name' => 'Primary School Leaving Education',
            'code' => 'PSLE',
            'education_level' => 'PRIMARY',
        ]);

        $response = $this->get("/exam-types/{$examType->id}/edit");

        $response->assertOk();
        $response->assertSee('list="education_level_options"', false);
        $response->assertSee('placeholder="Search education level"', false);
        $response->assertSee('value="PRIMARY"', false);
    }
}
