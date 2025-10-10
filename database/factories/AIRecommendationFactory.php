<?php

namespace Database\Factories;

use App\Models\AIRecommendation;
use App\Models\TravelPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIRecommendation>
 */
class AIRecommendationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AIRecommendation>
     */
    protected $model = AIRecommendation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'travel_plan_id' => TravelPlan::factory(),
            'type' => fake()->randomElement(['activity', 'restaurant', 'accommodation']),
            'content' => fake()->paragraph(),
            'is_accepted' => fake()->boolean(),
            'metadata' => [],
        ];
    }
}
