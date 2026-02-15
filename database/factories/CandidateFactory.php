<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Candidate>
 */
class CandidateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'combination' => $this->faker->randomElement(['PCM', 'PCB', 'HGE', 'CBE', null]),
            'is_active' => true,
            'exam_type' => $this->faker->randomElement(['PSLE', 'CSEE', 'ACSEE', null]),
            'status' => $this->faker->randomElement(['registered', 'pending']),
        ];
    }

    /**
     * Indicate that the candidate is a school candidate with an index number.
     */
    public function school(): static
    {
        return $this->state(fn (array $attributes) => [
            'candidate_id' => 'S' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT) . '-' . str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Indicate that the candidate is a private candidate with an index number.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'candidate_id' => 'P' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT) . '-' . str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Indicate that the candidate should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
