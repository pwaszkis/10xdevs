<?php

/**
 * Migration: Create AI Generations Table
 *
 * Purpose: Track all AI generation attempts with cost metrics and status
 *
 * Tables Affected: ai_generations
 *
 * Features:
 * - Complete history of all generation attempts (including regenerations)
 * - Cost tracking (tokens + USD cost) for budget monitoring
 * - Status workflow for async processing via Laravel Queue
 * - Error logging for failed generations
 * - Each regeneration creates new record (no update of existing)
 *
 * Status Workflow:
 * - pending: Job queued, waiting to start
 * - processing: AI request in progress
 * - completed: Successfully generated plan
 * - failed: Error occurred (see error_message)
 *
 * Limit Tracking:
 * - Only 'completed' status counts toward user's monthly limit (10/month)
 * - Failed generations don't consume limit (rollback)
 *
 * Security Notes:
 * - Foreign key CASCADE delete when plan or user deleted
 * - No storage of actual AI prompts/responses (GDPR/privacy)
 *
 * Related Tables: travel_plans (N:1), users (N:1)
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
        Schema::create('ai_generations', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('travel_plan_id')->comment('Target travel plan ID');
            $table->unsignedBigInteger('user_id')->comment('User who requested generation (for limit tracking)');

            // Generation status (async processing via queue)
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->comment('Generation status: pending (queued), processing (in progress), completed (success), failed (error)');

            // AI model and usage metrics
            $table->string('model_used', 50)->nullable()->comment('AI model name (e.g., "gpt-4o-mini")');
            $table->integer('tokens_used')->nullable()->comment('Total tokens consumed (prompt + completion)');
            $table->decimal('cost_usd', 10, 4)->nullable()->comment('Generation cost in USD (4 decimal precision, e.g., $0.0234)');

            // Error handling
            $table->text('error_message')->nullable()->comment('Error details if status=failed (for debugging and user feedback)');

            // Timing metrics
            $table->timestamp('started_at')->nullable()->comment('When processing started (status changed to processing)');
            $table->timestamp('completed_at')->nullable()->comment('When completed (success or failure)');

            // Timestamps
            $table->timestamps(); // created_at (queue time), updated_at

            // Foreign key constraints
            // ON DELETE CASCADE: when plan deleted, all generation history deleted
            $table->foreign('travel_plan_id', 'fk_ai_generations_travel_plan_id')
                ->references('id')
                ->on('travel_plans')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // ON DELETE CASCADE: when user deleted, all generation history deleted (GDPR)
            $table->foreign('user_id', 'fk_ai_generations_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Indexes for efficient queries
            // Composite index for limit counting: "how many completed generations this month for user X?"
            $table->index(['user_id', 'created_at'], 'idx_ai_generations_user_created');

            // Index for plan history: "all generation attempts for plan X"
            $table->index('travel_plan_id', 'idx_ai_generations_plan');
        });

        // Add table comment
        DB::statement("ALTER TABLE ai_generations COMMENT 'AI generation attempts with cost tracking and status (1:N with travel_plans)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
