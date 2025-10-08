<?php

/**
 * Migration: Create Travel Plans Table
 *
 * Purpose: Core travel plan storage with user notes and metadata
 *
 * Tables Affected: travel_plans
 *
 * Features:
 * - Unlimited plans per user
 * - Soft delete support (user can recover plans)
 * - Status workflow: draft → planned → completed
 * - Optional budget and coordinates
 * - User notes (transformed by AI into detailed plan)
 *
 * Status Values:
 * - draft: Plan saved without AI generation or incomplete
 * - planned: Plan with generated AI itinerary
 * - completed: Trip finished (auto-changed after departure_date + number_of_days)
 *
 * Security Notes:
 * - Foreign key CASCADE delete when user removed (GDPR compliance)
 * - Soft delete allows user to recover accidentally deleted plans
 * - Row-level security via Laravel Policies (user can only access own plans)
 *
 * Related Tables: users (N:1), plan_days (1:N), ai_generations (1:N),
 *                 feedback (1:1), pdf_exports (1:N)
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
        Schema::create('travel_plans', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to users
            $table->unsignedBigInteger('user_id')->comment('Plan owner user ID');

            // Core plan information
            $table->string('title', 255)->comment('User-defined plan title (e.g., "Summer Vacation in Italy")');
            $table->string('destination', 255)->comment('Destination name (free text, e.g., "Rome, Italy")');

            // Optional coordinates for destination (future map integration)
            $table->decimal('destination_lat', 10, 8)->nullable()->comment('Destination latitude (optional, for future map features)');
            $table->decimal('destination_lng', 11, 8)->nullable()->comment('Destination longitude (optional, for future map features)');

            // Trip dates and participants
            $table->date('departure_date')->comment('Trip departure date (validation in Laravel: >= today for new plans)');
            $table->tinyInteger('number_of_days')->comment('Trip duration in days (1-30, validated by Laravel)');
            $table->tinyInteger('number_of_people')->comment('Number of travelers (1-10, validated by Laravel)');

            // Budget (optional)
            $table->decimal('budget_per_person', 10, 2)->nullable()->comment('Estimated budget per person (optional)');
            $table->string('budget_currency', 3)->default('PLN')->comment('Budget currency code: PLN (default), USD, or EUR');

            // User notes (input for AI generation)
            $table->text('user_notes')->nullable()->comment('User free-form notes and ideas (transformed by AI into detailed plan)');

            // Status tracking
            $table->enum('status', ['draft', 'planned', 'completed'])
                ->default('draft')
                ->comment('Plan status: draft (not generated), planned (AI generated), completed (trip finished)');

            // Timestamps
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft delete

            // Foreign key constraint
            // ON DELETE CASCADE: when user deleted, all their plans are deleted (GDPR)
            $table->foreign('user_id', 'fk_travel_plans_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Indexes for query performance
            $table->index('user_id', 'idx_travel_plans_user_id'); // Filter by user
            $table->index('status', 'idx_travel_plans_status'); // Quick filters (drafts, planned, completed)
            $table->index('created_at', 'idx_travel_plans_created_at'); // Sorting (newest first)
        });

        // Add CHECK constraints (MySQL 8.0.16+)
        DB::statement('ALTER TABLE travel_plans ADD CONSTRAINT chk_travel_plans_number_of_days CHECK (number_of_days BETWEEN 1 AND 30)');
        DB::statement('ALTER TABLE travel_plans ADD CONSTRAINT chk_travel_plans_number_of_people CHECK (number_of_people BETWEEN 1 AND 10)');
        DB::statement("ALTER TABLE travel_plans ADD CONSTRAINT chk_travel_plans_budget_currency CHECK (budget_currency IN ('PLN', 'USD', 'EUR'))");

        // Add table comment
        DB::statement("ALTER TABLE travel_plans COMMENT 'User travel plans with soft delete and status tracking'");
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: Dropping this table will CASCADE delete all related data:
     *          - plan_days (and their plan_points)
     *          - ai_generations
     *          - feedback
     *          - pdf_exports
     */
    public function down(): void
    {
        // Drop CHECK constraints first
        DB::statement('ALTER TABLE travel_plans DROP CONSTRAINT IF EXISTS chk_travel_plans_number_of_days');
        DB::statement('ALTER TABLE travel_plans DROP CONSTRAINT IF EXISTS chk_travel_plans_number_of_people');
        DB::statement('ALTER TABLE travel_plans DROP CONSTRAINT IF EXISTS chk_travel_plans_budget_currency');

        Schema::dropIfExists('travel_plans');
    }
};
