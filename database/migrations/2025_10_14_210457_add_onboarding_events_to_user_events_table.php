<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new event types to the enum
        DB::statement("ALTER TABLE user_events MODIFY COLUMN event_type ENUM(
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
            'feedback_submitted'
        ) NOT NULL COMMENT 'Event type/category for analytics segmentation'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the onboarding event types
        DB::statement("ALTER TABLE user_events MODIFY COLUMN event_type ENUM(
            'login',
            'logout',
            'onboarding_completed',
            'plan_created',
            'plan_saved_as_draft',
            'ai_generated',
            'ai_regenerated',
            'pdf_exported',
            'feedback_submitted'
        ) NOT NULL COMMENT 'Event type/category for analytics segmentation'");
    }
};
