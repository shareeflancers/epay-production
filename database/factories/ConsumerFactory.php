<?php

namespace Database\Factories;

use App\Models\Consumer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factory<\App\Models\Consumer>
 */
class ConsumerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Consumer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consumer_type' => fake()->randomElement(['student', 'institution', 'region', 'directorate', 'inductee']),
            'identification_number' => fake()->unique()->numerify('##########'),
            'consumer_number' => fake()->unique()->numerify('############'),
            'institution_id' => fake()->numerify('###'),
            'region_id' => fake()->numerify('###'),
            'is_active' => fake()->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the consumer is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the consumer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific consumer type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'consumer_type' => $type,
        ]);
    }
}
