<?php

namespace Database\Factories;

use App\Models\FeeFundCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factory<\App\Models\FeeFundCategory>
 */
class FeeFundCategoryFactory extends Factory
{
    protected $model = FeeFundCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'details' => fake()->randomElement([
                'Regular Fee',
                'Hostel Fee',
                'Transport Fee',
                'Lab Fee',
                'Library Fee',
                'Sports Fee',
                'Examination Fee',
                'Development Fund',
            ]),
            'is_active' => fake()->boolean(90),
        ];
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
