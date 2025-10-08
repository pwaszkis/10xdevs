<?php

/**
 * Migration: Create Plan Days Table
 *
 * Purpose: Individual days within a travel plan itinerary
 *
 * Tables Affected: plan_days
 *
 * Features:
 * - One record per day of trip (1:N relationship with travel_plans)
 * - Sequential day numbering (1, 2, 3, ...)
 * - Stored date for easy display (calculated as departure_date + day_number - 1)
 * - Optional AI-generated summary per day
 *
 * Relationships:
 * - Parent: travel_plans (N:1)
 * - Children: plan_points (1:N) - sightseeing points within the day
 *
 * Security Notes:
 * - Foreign key CASCADE delete when parent plan deleted
 * - Unique constraint ensures no duplicate day numbers per plan
 *
 * Related Tables: travel_plans (N:1), plan_points (1:N)
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
        Schema::create('plan_days', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to travel_plans
            $table->unsignedBigInteger('travel_plan_id')->comment('Parent travel plan ID');

            // Day information
            $table->tinyInteger('day_number')->comment('Sequential day number (1, 2, 3, ..., up to 30)');
            $table->date('date')->comment('Actual calendar date for this day (departure_date + day_number - 1)');

            // Optional AI-generated summary
            $table->text('summary')->nullable()->comment('Optional AI-generated day summary (e.g., "Day devoted to Old Town exploration")');

            // Timestamps
            $table->timestamps();

            // Foreign key constraint
            // ON DELETE CASCADE: when plan deleted, all days are deleted
            $table->foreign('travel_plan_id', 'fk_plan_days_travel_plan_id')
                ->references('id')
                ->on('travel_plans')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Unique constraint: one record per (plan, day_number)
            $table->unique(['travel_plan_id', 'day_number'], 'uk_plan_days_plan_day');

            // Composite index for efficient queries
            $table->index(['travel_plan_id', 'day_number'], 'idx_plan_days_plan_day');
        });

        // Add table comment
        DB::statement("ALTER TABLE plan_days COMMENT 'Individual days within travel plan itinerary (1:N with travel_plans)'");
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: Dropping this table will CASCADE delete all plan_points (sightseeing locations).
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_days');
    }
};
