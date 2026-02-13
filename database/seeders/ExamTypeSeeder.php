<?php

namespace Database\Seeders;

use App\Models\ExamType;
use Illuminate\Database\Seeder;

class ExamTypeSeeder extends Seeder
{
    public function run(): void
    {
        $examTypes = [
            ['code' => 'PSLE', 'name' => 'Primary School Leaving Examination', 'education_level' => 'PRIMARY'],
            ['code' => 'ACSEE', 'name' => 'Advanced Certificate of Secondary Education', 'education_level' => 'SECONDARY'],
            ['code' => 'CSEE', 'name' => 'Certificate of Secondary Education Examination', 'education_level' => 'SECONDARY'],
            ['code' => 'FORM_II', 'name' => 'Form II Exams', 'education_level' => 'SECONDARY'],
            ['code' => 'FORM_IV', 'name' => 'Form IV Exams', 'education_level' => 'SECONDARY'],
        ];

        foreach ($examTypes as $examType) {
            ExamType::create($examType);
        }
    }
}
