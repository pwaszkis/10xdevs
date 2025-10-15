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
     * Counter to track day numbers per travel plan
     *
     * @var array<int, int>
     */
    private static array $dayCounters = [];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $travelPlanId = TravelPlan::factory();

        return [
            'travel_plan_id' => $travelPlanId,
            'day_number' => function (array $attributes) {
                $planId = $attributes['travel_plan_id'];
                if (! isset(self::$dayCounters[$planId])) {
                    self::$dayCounters[$planId] = 1;
                } else {
                    self::$dayCounters[$planId]++;
                }

                return self::$dayCounters[$planId];
            },
            'date' => fake()->date(),
            'summary' => fake()->sentence(),
        ];
    }
}
