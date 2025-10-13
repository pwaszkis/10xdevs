<?php

namespace Database\Factories;

use App\Models\AIGeneration;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIGeneration>
 */
class AIGenerationFactory extends Factory
{
    protected $model = AIGeneration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'travel_plan_id' => TravelPlan::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'model_used' => 'gpt-4o-mini',
            'tokens_used' => fake()->numberBetween(500, 5000),
            'cost_usd' => fake()->randomFloat(4, 0.01, 0.50),
            'error_message' => null,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ];
    }

    /**
     * Indicate that the generation is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'tokens_used' => fake()->numberBetween(1000, 4000),
            'cost_usd' => fake()->randomFloat(4, 0.05, 0.30),
        ]);
    }

    /**
     * Indicate that the generation failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'API Error: Rate limit exceeded',
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the generation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'tokens_used' => null,
            'cost_usd' => null,
        ]);
    }
}
