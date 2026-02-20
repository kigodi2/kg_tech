<?php

namespace Database\Factories;

use App\Models\MarkImportBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarkImportBatchFactory extends Factory
{
    protected $model = MarkImportBatch::class;

    public function definition(): array
    {
        return [
            'batch_code' => 'BATCH-' . strtoupper($this->faker->unique()->bothify('??###')),
            'exam_year' => 2025,
            'school_id' => 1,
            'subject_id' => 1,
            'exam_type_id' => 1,
            'status' => 'draft',
            'lifecycle_state' => 'draft',
            'total_records' => 10,
            'valid_records' => 10,
            'error_records' => 0,
            'imported_by' => null,
            'imported_at' => now(),
        ];
    }
}
