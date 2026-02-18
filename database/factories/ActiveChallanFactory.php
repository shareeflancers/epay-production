<?php

namespace Database\Factories;

use App\Models\ActiveChallan;
use App\Models\Consumer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factory<\App\Models\ActiveChallan>
 */
class ActiveChallanFactory extends Factory
{
    protected $model = ActiveChallan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountBase = fake()->randomFloat(2, 1000, 50000);
        $amountArrears = fake()->randomFloat(2, 0, 5000);
        $amountWithinDueDate = $amountBase + $amountArrears;
        $amountAfterDueDate = $amountWithinDueDate + fake()->randomFloat(2, 100, 1000);

        return [
            'consumer_id' => Consumer::factory(),
            'challan_no' => fake()->unique()->numerify('CHN-####-####-####'),
            'status' => fake()->randomElement(['U', 'P', 'B']),
            'tran_auth_id' => fake()->numerify('######'),
            'bank_mnemonic' => fake()->randomElement(['HBL', 'UBL', 'MCB', 'ABL', 'NBP', 'BAFL']),
            'due_date' => fake()->dateTimeBetween('now', '+3 months'),
            'amount_base' => $amountBase,
            'amount_arrears' => $amountArrears,
            'amount_within_dueDate' => $amountWithinDueDate,
            'amount_after_dueDate' => $amountAfterDueDate,
            'date_paid' => fake()->optional(0.3)->dateTimeBetween('-1 month', 'now'),
            'fee_type' => fake()->randomElement(['fee', 'voucher']),
            'reserved' => fake()->optional(0.2)->text(200),
            'is_active' => fake()->boolean(90),
        ];
    }

    /**
     * Indicate that the challan is unpaid.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'U',
            'date_paid' => null,
        ]);
    }

    /**
     * Indicate that the challan is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'P',
            'date_paid' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the challan is bounced.
     */
    public function bounced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'B',
        ]);
    }

    /**
     * Set fee type.
     */
    public function ofFeeType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_type' => $type,
        ]);
    }
}
