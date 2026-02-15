<?php

namespace Database\Factories;

use App\Models\ExamYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamYear>
 */
class ExamYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = $this->faker->year();
        
        return [
            'year_label' => (string) $year,
            'is_active' => false,
            'is_locked' => false,
        ];
    }

    /**
     * Indicate that the exam year should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the exam year is for 2026.
     */
    public function year2026(): static
    {
        return $this->state(fn (array $attributes) => [
            'year_label' => '2026',
            'is_active' => true,
        ]);
    }
}
