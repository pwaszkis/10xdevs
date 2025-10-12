<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Http\Requests\SubmitFeedbackRequest;
use App\Models\TravelPlan;
use App\Models\TravelPlanFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelPlanFeedback>
 */
class TravelPlanFeedbackFactory extends Factory
{
    protected $model = TravelPlanFeedback::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $satisfied = $this->faker->boolean(70); // 70% positive feedback

        return [
            'travel_plan_id' => TravelPlan::factory(),
            'satisfied' => $satisfied,
            'issues' => $satisfied ? null : $this->generateIssues(),
        ];
    }

    /**
     * Indicate that the feedback is positive.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'satisfied' => true,
            'issues' => null,
        ]);
    }

    /**
     * Indicate that the feedback is negative.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'satisfied' => false,
            'issues' => $this->generateIssues(),
        ]);
    }

    /**
     * Indicate that the feedback has specific issue.
     */
    public function withIssue(string $issue): static
    {
        return $this->state(fn (array $attributes) => [
            'satisfied' => false,
            'issues' => [$issue],
        ]);
    }

    /**
     * Indicate that the feedback has multiple issues.
     *
     * @param  list<string>  $issues
     */
    public function withIssues(array $issues): static
    {
        return $this->state(fn (array $attributes) => [
            'satisfied' => false,
            'issues' => $issues,
        ]);
    }

    /**
     * Generate random issues for negative feedback.
     *
     * @return list<string>
     */
    private function generateIssues(): array
    {
        $availableIssues = [
            SubmitFeedbackRequest::ISSUE_NOT_ENOUGH_DETAILS,
            SubmitFeedbackRequest::ISSUE_NOT_MATCHING_PREFERENCES,
            SubmitFeedbackRequest::ISSUE_POOR_ITINERARY_ORDER,
            SubmitFeedbackRequest::ISSUE_OTHER,
        ];

        $count = $this->faker->numberBetween(1, 3);

        return $this->faker->randomElements($availableIssues, $count);
    }
}
