<?php

/**
 * Migration: Create User Events Table
 *
 * Purpose: Track user behavior and actions for analytics
 *
 * Tables Affected: user_events
 *
 * Features:
 * - Event-based tracking for all significant user actions
 * - Flexible event_data JSON for action-specific details
 * - Support for anonymous events (nullable user_id)
 * - No IP/user agent tracking in MVP (privacy-first)
 *
 * Event Types:
 * - login: User logged in
 * - logout: User logged out
 * - onboarding_completed: User finished onboarding flow
 * - plan_created: New travel plan created
 * - plan_saved_as_draft: Plan saved without AI generation
 * - ai_generated: AI plan generation completed
 * - ai_regenerated: Plan regenerated with AI
 * - pdf_exported: Plan exported to PDF
 * - feedback_submitted: User submitted plan feedback
 *
 * Event Data Examples (JSON):
 * - login: {"provider": "email|google"}
 * - plan_created: {"travel_plan_id": 123, "destination": "Rome"}
 * - ai_generated: {"travel_plan_id": 123, "ai_generation_id": 456, "tokens_used": 1500}
 * - pdf_exported: {"travel_plan_id": 123, "file_size_bytes": 245678}
 *
 * Analytics Usage:
 * - Onboarding completion rate
 * - Plans per user (engagement)
 * - AI generation patterns
 * - Monthly active users (MAU)
 * - 30-day retention
 *
 * Security Notes:
 * - Foreign key CASCADE delete when user deleted (GDPR)
 * - No PII in event_data (only IDs and metrics)
 * - No IP address or user agent tracking in MVP
 *
 * Related Tables: users (N:1, nullable)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_events', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to users (nullable for anonymous events)
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who triggered event (null for anonymous/pre-login events)');

            // Event type
            $table->enum('event_type', [
                'login',
                'logout',
                'onboarding_started',
                'onboarding_step_completed',
                'onboarding_completed',
                'plan_created',
                'plan_saved_as_draft',
                'ai_generated',
                'ai_regenerated',
                'pdf_exported',
                'feedback_submitted',
            ])->comment('Event type/category for analytics segmentation');

            // Event-specific data (JSON)
            $table->json('event_data')->nullable()->comment('JSON object with event-specific details (e.g., travel_plan_id, tokens_used)');

            // Note: IP address and user_agent omitted in MVP for privacy
            // Future: Uncomment below if needed for fraud detection
            // $table->string('ip_address', 45)->nullable()->comment('User IP address (IPv6 support)');
            // $table->text('user_agent')->nullable()->comment('Browser user agent string');

            // Timestamp (only created_at, events are immutable)
            $table->timestamp('created_at')->useCurrent()->comment('Event timestamp');

            // Foreign key constraint (nullable)
            $table->foreign('user_id', 'fk_user_events_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Indexes for analytics queries
            // Pattern: "all events for user X in time range Y"
            $table->index(['user_id', 'created_at'], 'idx_user_events_user_created');

            // Pattern: "all events of type X in time range Y"
            $table->index(['event_type', 'created_at'], 'idx_user_events_type_created');
        });

        // Add table comment
        DB::statement("ALTER TABLE user_events COMMENT 'User behavior tracking for analytics (immutable event log)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_events');
    }
};
