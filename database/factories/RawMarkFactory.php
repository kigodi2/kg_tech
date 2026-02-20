<?php

namespace Database\Factories;

use App\Models\RawMark;
use Illuminate\Database\Eloquent\Factories\Factory;

class RawMarkFactory extends Factory
{
    protected $model = RawMark::class;

    public function definition(): array
    {
        return [
            'mark_import_batch_id' => 1,
            'row_number' => $this->faker->unique()->numberBetween(1, 9999),
            'candidate_index_number' => 'S0001/' . $this->faker->unique()->numerify('####'),
            'full_name' => $this->faker->name(),
            'paper_1_marks' => $this->faker->randomFloat(1, 20, 100),
            'paper_2_marks' => $this->faker->randomFloat(1, 20, 100),
            'paper_3_marks' => null,
            'has_errors' => false,
            'raw_data' => json_encode(['source' => 'factory']),
        ];
    }
}
