<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word(),
            'name' => $this->faker->company() . ' School',
            'registration_number' => 'S' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'region_id' => Region::factory(),
            'school_type' => $this->faker->randomElement(['PRIMARY', 'SECONDARY', 'BOTH']),
            'education_level' => $this->faker->randomElement(['PRIMARY', 'SECONDARY']),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'principal_name' => $this->faker->name(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the school should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
