<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamYear;

class SeedExamYears extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:exam-years';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed exam years for testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if exam years already exist
        if (ExamYear::count() > 0) {
            $this->info('Exam years already exist: ' . ExamYear::count());
            return 0;
        }

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

        $this->info('✅ Exam years created successfully!');
        $this->info(ExamYear::pluck('year_label')->join(', '));

        return 0;
    }
}
