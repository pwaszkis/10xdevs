<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\TravelPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Feedback>
     */
    protected $model = Feedback::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'travel_plan_id' => TravelPlan::factory(),
            'satisfied' => fake()->boolean(),
            'issues' => fake()->boolean() ? fake()->randomElements(['za_malo_szczegolow', 'nie_pasuje_do_preferencji', 'slaba_kolejnosc'], fake()->numberBetween(1, 3)) : null,
            'other_comment' => fake()->boolean() ? fake()->paragraph() : null,
        ];
    }
}
