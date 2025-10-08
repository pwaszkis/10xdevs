<?php

/**
 * Migration: Create Feedback Table
 *
 * Purpose: Collect user feedback on generated travel plans
 *
 * Tables Affected: feedback
 *
 * Features:
 * - Optional feedback per plan (1:1 relationship)
 * - Binary satisfaction indicator (yes/no)
 * - Structured issue categories via JSON array
 * - Free-form comment field for "other" issues
 *
 * Issue Categories (JSON array values):
 * - za_malo_szczegolow: "Not enough details"
 * - nie_pasuje_do_preferencji: "Doesn't match my preferences"
 * - slaba_kolejnosc: "Poor sightseeing order"
 * - inne: "Other" (requires other_comment to be filled)
 *
 * Analytics Usage:
 * - Plan satisfaction rate: % of satisfied=true
 * - Issue breakdown for product improvements
 * - Trend analysis over time
 *
 * Security Notes:
 * - Foreign key CASCADE delete when plan or user deleted
 * - Unique constraint ensures one feedback per user per plan
 *
 * Related Tables: travel_plans (1:1), users (N:1)
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
        Schema::create('feedback', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('travel_plan_id')->comment('Travel plan being rated');
            $table->unsignedBigInteger('user_id')->comment('User providing feedback');

            // Satisfaction indicator
            $table->boolean('satisfied')->comment('User satisfaction: true (plan meets expectations), false (doesn\'t meet expectations)');

            // Issue categories (JSON array, null if satisfied=true)
            $table->json('issues')->nullable()->comment('JSON array of issue categories: ["za_malo_szczegolow", "nie_pasuje_do_preferencji", "slaba_kolejnosc", "inne"]');

            // Free-form comment (required when "inne" in issues array)
            $table->text('other_comment')->nullable()->comment('Additional comment when "inne" selected in issues (optional otherwise)');

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            // ON DELETE CASCADE: when plan deleted, feedback deleted
            $table->foreign('travel_plan_id', 'fk_feedback_travel_plan_id')
                ->references('id')
                ->on('travel_plans')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // ON DELETE CASCADE: when user deleted, all feedback deleted (GDPR)
            $table->foreign('user_id', 'fk_feedback_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Unique constraint: one feedback per user per plan
            // Note: After regeneration, user could provide new feedback (replace old)
            $table->unique(['travel_plan_id', 'user_id'], 'uk_feedback_plan_user');
        });

        // Add table comment
        DB::statement("ALTER TABLE feedback COMMENT 'User feedback on generated travel plans (1:1 with travel_plans)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
