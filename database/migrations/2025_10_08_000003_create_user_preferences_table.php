<?php

/**
 * Migration: Create User Preferences Table
 *
 * Purpose: Store user travel preferences for AI plan generation
 *
 * Tables Affected: user_preferences
 *
 * Features:
 * - 1:1 relationship with users table
 * - JSON array for interests categories (multi-select)
 * - ENUM fields for practical parameters (single-select)
 * - Created after onboarding completion
 *
 * Interests Categories (JSON array values):
 * - historia_kultura
 * - przyroda_outdoor
 * - gastronomia
 * - nocne_zycie
 * - plaze_relaks
 * - sporty_aktywnosci
 * - sztuka_muzea
 *
 * Security Notes:
 * - Foreign key with CASCADE delete ensures cleanup when user deleted
 * - Preferences are read on every AI generation (cached in Redis)
 *
 * Related Tables: users (1:1), ai_generations (used during generation)
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
        Schema::create('user_preferences', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to users (1:1 relationship)
            $table->unsignedBigInteger('user_id')->unique()->comment('User ID, 1:1 relationship with users table');

            // Interests (JSON array of selected categories)
            $table->json('interests_categories')->nullable()->comment('JSON array of interest categories: ["historia_kultura", "przyroda_outdoor", ...]');

            // Practical parameters (ENUM single-select fields)
            $table->enum('travel_pace', ['spokojne', 'umiarkowane', 'intensywne'])
                ->nullable()
                ->comment('Travel pace preference: spokojne (relaxed), umiarkowane (moderate), intensywne (intensive)');

            $table->enum('budget_level', ['ekonomiczny', 'standardowy', 'premium'])
                ->nullable()
                ->comment('Budget level: ekonomiczny (budget), standardowy (standard), premium (luxury)');

            $table->enum('transport_preference', ['pieszo_publiczny', 'wynajem_auta', 'mix'])
                ->nullable()
                ->comment('Transport preference: pieszo_publiczny (walk+public), wynajem_auta (car rental), mix (mixed)');

            $table->enum('restrictions', ['brak', 'dieta', 'mobilnosc'])
                ->nullable()
                ->comment('Special restrictions: brak (none), dieta (dietary/vegan), mobilnosc (mobility/accessibility)');

            // Timestamps
            $table->timestamps();

            // Foreign key constraint
            // ON DELETE CASCADE: when user deleted, preferences are deleted
            $table->foreign('user_id', 'fk_user_preferences_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Note: unique index on user_id created automatically by ->unique()
        });

        // Add table comment
        DB::statement("ALTER TABLE user_preferences COMMENT 'User travel preferences for AI plan personalization (1:1 with users)'");
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: Dropping this table will remove all user preferences.
     *          Users will need to complete onboarding again.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
