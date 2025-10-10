<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\TravelPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Activity>
     */
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'travel_plan_id' => TravelPlan::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['sightseeing', 'food', 'activity', 'transport']),
            'date' => fake()->date(),
            'start_time' => fake()->time(),
            'end_time' => fake()->time(),
            'location' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'cost' => fake()->randomFloat(2, 0, 500),
            'currency' => 'PLN',
            'order' => fake()->numberBetween(1, 20),
        ];
    }
}
