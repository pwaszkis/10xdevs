<?php

/**
 * Migration: Create Plan Points Table
 *
 * Purpose: Individual sightseeing points/activities within each day
 *
 * Tables Affected: plan_points
 *
 * Features:
 * - Multiple points per day organized by time of day
 * - AI-generated descriptions and justifications
 * - Google Maps integration via URLs
 * - Optional coordinates for future map features
 * - Estimated duration for time planning
 *
 * Day Parts (time of day organization):
 * - rano: morning activities
 * - poludnie: midday/lunch activities
 * - popoludnie: afternoon activities
 * - wieczor: evening activities
 *
 * Security Notes:
 * - Foreign key CASCADE delete when parent day deleted
 * - Unique constraint on (day, order_number) ensures no duplicate ordering
 *
 * Related Tables: plan_days (N:1)
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
        Schema::create('plan_points', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to plan_days
            $table->unsignedBigInteger('plan_day_id')->comment('Parent plan day ID');

            // Ordering and timing
            $table->tinyInteger('order_number')->comment('Sequential order within the day (1, 2, 3, ...)');
            $table->enum('day_part', ['rano', 'poludnie', 'popoludnie', 'wieczor'])
                ->comment('Time of day: rano (morning), poludnie (midday), popoludnie (afternoon), wieczor (evening)');

            // Point information
            $table->string('name', 255)->comment('Attraction/location name (e.g., "Colosseum")');
            $table->text('description')->comment('AI-generated description (2-3 sentences about the attraction)');
            $table->text('justification')->nullable()->comment('AI explanation why this fits user preferences (optional)');
            $table->smallInteger('duration_minutes')->nullable()->comment('Estimated visit duration in minutes (optional)');

            // Location integration
            $table->string('google_maps_url', 500)->nullable()->comment('Google Maps link for navigation (format: https://www.google.com/maps/search/?api=1&query=...)');
            $table->decimal('location_lat', 10, 8)->nullable()->comment('Attraction latitude (optional, for future map features)');
            $table->decimal('location_lng', 11, 8)->nullable()->comment('Attraction longitude (optional, for future map features)');

            // Timestamps
            $table->timestamps();

            // Foreign key constraint
            // ON DELETE CASCADE: when day deleted, all points are deleted
            $table->foreign('plan_day_id', 'fk_plan_points_plan_day_id')
                ->references('id')
                ->on('plan_days')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Unique constraint: one record per (day, order_number)
            // Prevents duplicate ordering within a day
            $table->unique(['plan_day_id', 'order_number'], 'uk_plan_points_day_order');

            // Composite index for efficient queries
            $table->index(['plan_day_id', 'order_number'], 'idx_plan_points_day_order');
        });

        // Add table comment
        DB::statement("ALTER TABLE plan_points COMMENT 'Sightseeing points/activities within plan days (1:N with plan_days)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_points');
    }
};
