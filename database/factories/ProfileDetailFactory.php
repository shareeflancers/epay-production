<?php

namespace Database\Factories;

use App\Models\Consumer;
use App\Models\FeeFundStructure;
use App\Models\ProfileDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factory<\App\Models\ProfileDetail>
 */
class ProfileDetailFactory extends Factory
{
    protected $model = ProfileDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_type' => fake()->randomElement(['student', 'institution', 'region', 'directorate', 'inductee']),
            'consumer_id' => Consumer::factory(),
            'name' => fake()->name(),
            'father_or_guardian_name' => fake()->name('male'),
            'region_name' => fake()->randomElement(['North', 'South', 'East', 'West', 'Central']),
            'institution_name' => fake()->company() . ' School',
            'institution_level' => fake()->randomElement(['Primary', 'Middle', 'High', 'Higher Secondary', 'College']),
            'class' => fake()->randomElement(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']),
            'section' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'fee_fund_structure_id' => FeeFundStructure::factory(),
            'is_active' => fake()->boolean(90),
        ];
    }

    /**
     * Indicate that the profile is for a student.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_type' => 'student',
        ]);
    }

    /**
     * Indicate that the profile is for an institution.
     */
    public function institution(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_type' => 'institution',
        ]);
    }

    /**
     * Indicate that the profile is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
