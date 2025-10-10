<?php

namespace Database\Factories;

use App\Models\Export;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Export>
 */
class ExportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Export>
     */
    protected $model = Export::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'travel_plan_id' => TravelPlan::factory(),
            'type' => 'travel_plan',
            'format' => fake()->randomElement(['pdf', 'json', 'csv']),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
        ];
    }
}
