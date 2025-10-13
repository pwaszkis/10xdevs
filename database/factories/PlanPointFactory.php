<?php

namespace Database\Factories;

use App\Models\PlanDay;
use App\Models\PlanPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanPoint>
 */
class PlanPointFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PlanPoint>
     */
    protected $model = PlanPoint::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_day_id' => PlanDay::factory(),
            'order_number' => fake()->numberBetween(1, 10),
            'day_part' => fake()->randomElement(['rano', 'poludnie', 'popoludnie', 'wieczor']),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'justification' => fake()->sentence(),
            'duration_minutes' => fake()->numberBetween(30, 240),
            'google_maps_url' => fake()->url(),
            'location_lat' => fake()->latitude(),
            'location_lng' => fake()->longitude(),
        ];
    }
}
