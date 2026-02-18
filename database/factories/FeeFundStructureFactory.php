<?php

namespace Database\Factories;

use App\Models\FeeFundCategory;
use App\Models\FeeFundStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factory<\App\Models\FeeFundStructure>
 */
class FeeFundStructureFactory extends Factory
{
    protected $model = FeeFundStructure::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $admissionFee = fake()->randomFloat(2, 500, 5000);
        $slc = fake()->randomFloat(2, 100, 500);
        $tutionFee = fake()->randomFloat(2, 1000, 10000);
        $idf = fake()->randomFloat(2, 50, 500);
        $examFee = fake()->randomFloat(2, 200, 1000);
        $itFee = fake()->randomFloat(2, 100, 500);
        $csf = fake()->randomFloat(2, 50, 300);
        $rdf = fake()->randomFloat(2, 50, 300);
        $cdf = fake()->randomFloat(2, 50, 300);
        $securityFund = fake()->randomFloat(2, 500, 2000);
        $bsFund = fake()->randomFloat(2, 50, 200);
        $prepFund = fake()->randomFloat(2, 50, 200);
        $donationFund = fake()->randomFloat(2, 0, 500);

        $total = $admissionFee + $slc + $tutionFee + $idf + $examFee + $itFee +
                 $csf + $rdf + $cdf + $securityFund + $bsFund + $prepFund + $donationFund;

        return [
            'region' => fake()->randomElement(['North', 'South', 'East', 'West', 'Central']),
            'institution_level' => fake()->randomElement(['Primary', 'Middle', 'High', 'Higher Secondary', 'College']),
            'fee_fund_category_id' => FeeFundCategory::factory(),
            'admission_fee' => $admissionFee,
            'slc' => $slc,
            'tution_fee' => $tutionFee,
            'idf' => $idf,
            'exam_fee' => $examFee,
            'it_fee' => $itFee,
            'csf' => $csf,
            'rdf' => $rdf,
            'cdf' => $cdf,
            'security_fund' => $securityFund,
            'bs_fund' => $bsFund,
            'prep_fund' => $prepFund,
            'donation_fund' => $donationFund,
            'total' => $total,
            'is_active' => fake()->boolean(90),
        ];
    }

    /**
     * Indicate that the structure is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Set for a specific region.
     */
    public function forRegion(string $region): static
    {
        return $this->state(fn (array $attributes) => [
            'region' => $region,
        ]);
    }

    /**
     * Set for a specific institution level.
     */
    public function forLevel(string $level): static
    {
        return $this->state(fn (array $attributes) => [
            'institution_level' => $level,
        ]);
    }
}
