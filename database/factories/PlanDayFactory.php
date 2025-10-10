<?php

namespace Database\Factories;

use App\Models\PlanDay;
use App\Models\TravelPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanDay>
 */
class PlanDayFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PlanDay>
     */
    protected $model = PlanDay::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'travel_plan_id' => TravelPlan::factory(),
            'day_number' => fake()->numberBetween(1, 14),
            'date' => fake()->date(),
            'summary' => fake()->sentence(),
        ];
    }
}
