<?php

namespace Database\Seeders;

use App\Models\ExamYear;
use Illuminate\Database\Seeder;

class ExamYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create exam years
        ExamYear::create([
            'year_label' => '2025',
            'is_active' => true,
            'is_locked' => false,
        ]);

        ExamYear::create([
            'year_label' => '2024',
            'is_active' => false,
            'is_locked' => false,
        ]);

        ExamYear::create([
            'year_label' => '2023',
            'is_active' => false,
            'is_locked' => false,
        ]);
    }
}
