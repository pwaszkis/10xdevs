<?php

/**
 * Migration: Create Users Table
 *
 * Purpose: Core user authentication and profile management
 *
 * Tables Affected: users
 *
 * Features:
 * - Multi-provider authentication (email+password, Google OAuth)
 * - Email verification tracking
 * - Onboarding progress tracking
 * - AI generation limit tracking with monthly reset
 * - GDPR-compliant hard delete (ON DELETE CASCADE for all related data)
 *
 * Security Notes:
 * - Passwords are bcrypt hashed (handled by Laravel Breeze)
 * - Email verification required before full access
 * - Provider + provider_id uniqueness prevents OAuth account duplication
 *
 * Related Tables: user_preferences, travel_plans, ai_generations, feedback,
 *                 pdf_exports, email_verifications, email_logs, user_events
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
        Schema::create('users', function (Blueprint $table) {
            // Primary key
            $table->id(); // int unsigned auto_increment primary key

            // Authentication fields
            $table->string('email', 255)->unique()->comment('User email address, unique across all providers');
            $table->string('password', 255)->nullable()->comment('Bcrypt hashed password, nullable for OAuth users');

            // OAuth provider fields
            $table->enum('provider', ['email', 'google'])
                ->default('email')
                ->comment('Authentication provider: email (password-based) or google (OAuth)');
            $table->string('provider_id', 255)->nullable()->comment('OAuth provider user ID, null for email auth');

            // Email verification
            $table->timestamp('email_verified_at')->nullable()->comment('Timestamp of email verification, null if unverified');

            // Profile fields (filled during onboarding)
            $table->string('nickname', 100)->nullable()->comment('User display name, filled during onboarding');
            $table->string('home_location', 255)->nullable()->comment('User home country/city, filled during onboarding');
            $table->string('timezone', 50)->default('UTC')->nullable()->comment('User timezone for date/time display');

            // Onboarding tracking
            $table->timestamp('onboarding_completed_at')->nullable()->comment('Timestamp when onboarding completed, null if incomplete');
            $table->tinyInteger('onboarding_step')->default(0)->comment('Current onboarding step: 0=not started, 1=basic data, 2=interests, 3=params, 4=completed');

            // AI generation limits (monthly reset)
            $table->integer('ai_generations_count_current_month')->default(0)->comment('Number of AI generations used in current month (max 10)');
            $table->timestamp('ai_generations_reset_at')->nullable()->comment('Next reset date (first day of next month)');

            // Laravel session management
            $table->rememberToken()->comment('Laravel remember me token for persistent sessions');

            // Timestamps
            $table->timestamps(); // created_at, updated_at

            // Indexes
            // Note: unique index on email created automatically by ->unique()
            // Composite index on (provider, provider_id) for OAuth uniqueness
            // Warning: MySQL allows multiple NULL values in unique index, which is acceptable here
            $table->index(['provider', 'provider_id'], 'idx_users_provider_provider_id');
        });

        // Add table comment
        DB::statement("ALTER TABLE users COMMENT 'Core users table with multi-provider auth and onboarding tracking'");
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: Dropping this table will CASCADE delete all user-related data
     *          including travel plans, preferences, feedback, and analytics.
     *          This is intentional for GDPR compliance (hard delete).
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
