<?php

namespace Database\Factories;

use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelPlan>
 */
class TravelPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TravelPlan>
     */
    protected $model = TravelPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'destination' => fake()->city(),
            'destination_lat' => fake()->latitude(),
            'destination_lng' => fake()->longitude(),
            'departure_date' => fake()->date(),
            'number_of_days' => fake()->numberBetween(3, 14),
            'number_of_people' => fake()->numberBetween(1, 6),
            'budget_per_person' => fake()->randomFloat(2, 500, 5000),
            'budget_currency' => 'PLN',
            'user_notes' => fake()->paragraph(),
            'status' => 'draft',
            'has_ai_plan' => false,
        ];
    }
}
