<?php

namespace Database\Factories;

use App\Models\ExamType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamType>
 */
class ExamTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->randomElement(['PSLE', 'CSEE', 'ACSEE']),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the exam type should be ACSEE.
     */
    public function acsee(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'ACSEE',
            'name' => 'Advanced Certificate of Secondary Education Examination',
        ]);
    }

    /**
     * Indicate that the exam type should be CSEE.
     */
    public function csee(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'CSEE',
            'name' => 'Certificate of Secondary Education Examination',
        ]);
    }

    /**
     * Indicate that the exam type should be PSLE.
     */
    public function psle(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PSLE',
            'name' => 'Primary School Leaving Examination',
        ]);
    }

    /**
     * Indicate that the exam type should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
