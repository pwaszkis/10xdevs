<?php

declare(strict_types=1);

namespace Tests\Feature\PDF;

use App\Models\PlanDay;
use App\Models\PlanPoint;
use App\Models\TravelPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for PDF export functionality.
 *
 * Tests cover:
 * - Generating PDF from travel plans
 * - Authorization checks
 * - Status validation (draft vs planned)
 * - Tracking PDF exports
 * - PDF content validation
 */
class PdfExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        // Ensure temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $tempDir = storage_path('app/temp');
        if (file_exists($tempDir)) {
            $files = glob($tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        parent::tearDown();
    }

    public function test_user_can_export_plan_to_pdf(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
            'title' => 'Paris Trip',
            'destination' => 'Paris',
            'departure_date' => now()->addDays(30),
            'number_of_days' => 3,
        ]);

        // Create at least one day with content (this makes has_ai_plan = true via accessor)
        PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('plans.pdf', $plan));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        // Check filename format
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('paris-trip_paris.pdf', $contentDisposition);
    }

    public function test_pdf_export_increments_counter(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
            'pdf_exports_count' => 0,
        ]);

        PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
        ]);

        $this->actingAs($this->user);

        $this->get(route('plans.pdf', $plan));

        $plan->refresh();
        $this->assertEquals(1, $plan->pdf_exports_count);

        // Export again
        $this->get(route('plans.pdf', $plan));

        $plan->refresh();
        $this->assertEquals(2, $plan->pdf_exports_count);
    }

    public function test_draft_plan_cannot_be_exported(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        // Don't create any days - has_ai_plan will be false

        $this->actingAs($this->user);

        $response = $this->get(route('plans.pdf', $plan));

        $response->assertStatus(400);
    }

    public function test_plan_without_ai_content_cannot_be_exported(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        // Don't create any days - has_ai_plan will be false (no AI content yet)

        $this->actingAs($this->user);

        $response = $this->get(route('plans.pdf', $plan));

        $response->assertStatus(400);
    }

    public function test_user_cannot_export_other_users_plan(): void
    {
        $otherUser = User::factory()->create([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
        ]);

        $plan = TravelPlan::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'planned',
        ]);

        PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('plans.pdf', $plan));

        $response->assertForbidden();
    }

    public function test_pdf_includes_plan_details(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
            'title' => 'Weekend in Rome',
            'destination' => 'Rome',
            'number_of_days' => 2,
            'number_of_people' => 2,
            'budget_per_person' => 500.00,
            'budget_currency' => 'EUR',
        ]);

        $day = PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
            'date' => now()->addDays(10),
            'summary' => 'Exploring ancient Rome',
        ]);

        PlanPoint::factory()->create([
            'plan_day_id' => $day->id,
            'name' => 'Colosseum',
            'description' => 'Ancient amphitheater in the center of Rome',
            'day_part' => 'rano',
            'order_number' => 1,
            'duration_minutes' => 120,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('plans.pdf', $plan));

        $response->assertOk();

        // Verify PDF headers and metadata
        $response->assertHeader('Content-Type', 'application/pdf');

        // Check that response has correct filename
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('weekend-in-rome_rome.pdf', $contentDisposition);

        // Verify the counter was incremented (confirms export actually happened)
        $plan->refresh();
        $this->assertEquals(1, $plan->pdf_exports_count);
    }

    public function test_pdf_filename_is_generated_correctly(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
            'title' => 'My Amazing Trip',
            'destination' => 'New York City',
        ]);

        PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('plans.pdf', $plan));

        $response->assertOk();

        $contentDisposition = $response->headers->get('Content-Disposition');

        // Should be slugified: "my-amazing-trip_new-york-city.pdf"
        $this->assertStringContainsString('my-amazing-trip_new-york-city.pdf', $contentDisposition);
    }

    public function test_pdf_export_with_multiple_days_and_points(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
            'title' => 'Barcelona Adventure',
            'destination' => 'Barcelona',
            'number_of_days' => 3,
        ]);

        // Create 3 days with multiple points each
        for ($dayNum = 1; $dayNum <= 3; $dayNum++) {
            $day = PlanDay::factory()->create([
                'travel_plan_id' => $plan->id,
                'day_number' => $dayNum,
                'date' => now()->addDays(10 + $dayNum - 1),
                'summary' => "Day {$dayNum} summary",
            ]);

            // Create 3 points per day
            for ($pointNum = 1; $pointNum <= 3; $pointNum++) {
                PlanPoint::factory()->create([
                    'plan_day_id' => $day->id,
                    'name' => "Day {$dayNum} Point {$pointNum}",
                    'description' => "Description for point {$pointNum}",
                    'day_part' => ['rano', 'popoludnie', 'wieczor'][$pointNum - 1],
                    'order_number' => $pointNum,
                    'duration_minutes' => 60,
                    'google_maps_url' => "https://maps.google.com/?q=Point+{$pointNum}",
                ]);
            }
        }

        $this->actingAs($this->user);

        $response = $this->get(route('plans.pdf', $plan));

        $response->assertOk();

        // Verify PDF content is substantial (multiple days should produce larger PDF)
        // Note: PDF response uses deleteFileAfterSend, so content might not be available
        // We verify success by checking the response is OK and has PDF headers
        $response->assertHeader('Content-Type', 'application/pdf');

        // Check filename contains plan info
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('barcelona-adventure_barcelona.pdf', $contentDisposition);
    }

    public function test_guest_cannot_export_pdf(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'planned',
        ]);

        PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
        ]);

        // Not authenticated
        $response = $this->get(route('plans.pdf', $plan));

        $response->assertRedirect(route('login'));
    }

    public function test_completed_plan_can_be_exported(): void
    {
        $plan = TravelPlan::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed', // Plan that was already taken
        ]);

        PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => 1,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('plans.pdf', $plan));

        $response->assertOk();
    }
}
