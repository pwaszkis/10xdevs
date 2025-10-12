<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TravelPlan;
use App\Models\TravelPlanFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelPlanFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private TravelPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);
    }

    public function test_user_can_submit_positive_feedback(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/travel-plans/{$this->plan->id}/feedback", [
                'satisfied' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Dziękujemy za feedback!',
            ]);

        $this->assertDatabaseHas('travel_plan_feedback', [
            'travel_plan_id' => $this->plan->id,
            'satisfied' => true,
            'issues' => null,
        ]);
    }

    public function test_user_can_submit_negative_feedback_with_issues(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/travel-plans/{$this->plan->id}/feedback", [
                'satisfied' => false,
                'issues' => [
                    'not_enough_details',
                    'poor_itinerary_order',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $feedback = TravelPlanFeedback::where('travel_plan_id', $this->plan->id)->first();
        $this->assertNotNull($feedback);
        $this->assertFalse($feedback->satisfied);
        $this->assertIsArray($feedback->issues);
        $this->assertContains('not_enough_details', $feedback->issues);
        $this->assertContains('poor_itinerary_order', $feedback->issues);
    }

    public function test_user_can_submit_negative_feedback_with_other_comment(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/travel-plans/{$this->plan->id}/feedback", [
                'satisfied' => false,
                'issues' => ['other'],
                'other_comment' => 'Brak restauracji wegańskich',
            ]);

        $response->assertStatus(201);

        $feedback = TravelPlanFeedback::where('travel_plan_id', $this->plan->id)->first();
        $this->assertNotNull($feedback);
        $this->assertIsArray($feedback->issues);
        $this->assertContains('other', $feedback->issues);
    }

    public function test_negative_feedback_requires_at_least_one_issue(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/travel-plans/{$this->plan->id}/feedback", [
                'satisfied' => false,
                'issues' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('issues');
    }

    public function test_feedback_satisfied_field_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/travel-plans/{$this->plan->id}/feedback", [
                // Missing 'satisfied' field
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('satisfied');
    }

    public function test_user_cannot_submit_feedback_for_other_users_plan(): void
    {
        $otherUser = User::factory()->create();
        $otherPlan = TravelPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/travel-plans/{$otherPlan->id}/feedback", [
                'satisfied' => true,
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_update_existing_feedback(): void
    {
        // Create initial feedback
        TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => true,
            'issues' => null,
        ]);

        // Update to negative feedback
        $response = $this->actingAs($this->user)
            ->postJson("/api/travel-plans/{$this->plan->id}/feedback", [
                'satisfied' => false,
                'issues' => ['not_enough_details'],
            ]);

        $response->assertStatus(201);

        $feedback = TravelPlanFeedback::where('travel_plan_id', $this->plan->id)->first();
        $this->assertFalse($feedback->satisfied);
        $this->assertIsArray($feedback->issues);
        $this->assertContains('not_enough_details', $feedback->issues);

        // Ensure only one feedback record exists
        $this->assertEquals(1, TravelPlanFeedback::where('travel_plan_id', $this->plan->id)->count());
    }

    public function test_user_can_retrieve_feedback_for_plan(): void
    {
        TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => false,
            'issues' => ['not_enough_details', 'poor_itinerary_order'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/travel-plans/{$this->plan->id}/feedback");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'satisfied' => false,
                    'issues' => ['not_enough_details', 'poor_itinerary_order'],
                ],
            ]);
    }

    public function test_retrieving_nonexistent_feedback_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/travel-plans/{$this->plan->id}/feedback");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Brak feedbacku dla tego planu.',
            ]);
    }

    public function test_user_can_delete_feedback(): void
    {
        TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => true,
            'issues' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/travel-plans/{$this->plan->id}/feedback");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Feedback został usunięty.',
            ]);

        $this->assertDatabaseMissing('travel_plan_feedback', [
            'travel_plan_id' => $this->plan->id,
        ]);
    }

    public function test_feedback_model_has_positive_helper_method(): void
    {
        $positiveFeedback = TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => true,
            'issues' => null,
        ]);

        $negativeFeedback = TravelPlanFeedback::create([
            'travel_plan_id' => TravelPlan::factory()->create(['user_id' => $this->user->id])->id,
            'satisfied' => false,
            'issues' => ['not_enough_details'],
        ]);

        $this->assertTrue($positiveFeedback->isPositive());
        $this->assertFalse($negativeFeedback->isPositive());
    }

    public function test_feedback_model_can_check_specific_issue(): void
    {
        $feedback = TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => false,
            'issues' => ['not_enough_details', 'poor_itinerary_order'],
        ]);

        $this->assertTrue($feedback->hasIssue('not_enough_details'));
        $this->assertTrue($feedback->hasIssue('poor_itinerary_order'));
        $this->assertFalse($feedback->hasIssue('not_matching_preferences'));
    }

    public function test_feedback_model_returns_formatted_issues(): void
    {
        $feedback = TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => false,
            'issues' => ['not_enough_details', 'poor_itinerary_order'],
        ]);

        $formatted = $feedback->getFormattedIssues();

        $this->assertIsArray($formatted);
        $this->assertContains('Za mało szczegółów', $formatted);
        $this->assertContains('Słaba kolejność zwiedzania', $formatted);
    }

    public function test_travel_plan_has_feedback_relationship(): void
    {
        TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => true,
            'issues' => null,
        ]);

        $plan = TravelPlan::with('feedback')->find($this->plan->id);

        $this->assertNotNull($plan->feedback);
        $this->assertInstanceOf(TravelPlanFeedback::class, $plan->feedback);
        $this->assertTrue($plan->hasFeedback());
    }

    public function test_feedback_is_deleted_when_plan_is_deleted(): void
    {
        TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => true,
            'issues' => null,
        ]);

        // Use forceDelete() because TravelPlan uses SoftDeletes
        // Cascade delete only works with hard deletes
        $this->plan->forceDelete();

        $this->assertDatabaseMissing('travel_plan_feedback', [
            'travel_plan_id' => $this->plan->id,
        ]);
    }

    public function test_only_one_feedback_per_plan_is_allowed(): void
    {
        TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => true,
            'issues' => null,
        ]);

        // Try to create another feedback for the same plan
        $this->expectException(\Illuminate\Database\QueryException::class);

        TravelPlanFeedback::create([
            'travel_plan_id' => $this->plan->id,
            'satisfied' => false,
            'issues' => ['not_enough_details'],
        ]);
    }

    public function test_guest_cannot_submit_feedback(): void
    {
        $response = $this->postJson("/api/travel-plans/{$this->plan->id}/feedback", [
            'satisfied' => true,
        ]);

        $response->assertStatus(401);
    }
}
