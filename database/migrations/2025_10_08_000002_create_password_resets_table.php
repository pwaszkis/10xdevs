<?php

/**
 * Migration: Create Password Resets Table
 *
 * Purpose: Laravel Breeze password reset token storage
 *
 * Tables Affected: password_resets
 *
 * Features:
 * - Stores temporary password reset tokens
 * - Tokens expire after configured time (default 60 minutes)
 * - Laravel automatically cleans up expired tokens
 *
 * Security Notes:
 * - Tokens are hashed before storage
 * - Single-use tokens (deleted after password reset)
 * - Rate limiting applied to reset requests
 *
 * Related Tables: users
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
        Schema::create('password_resets', function (Blueprint $table) {
            // Email address (not a foreign key, allows reset attempts for non-existent emails)
            $table->string('email', 255)->comment('Email address requesting password reset');

            // Reset token (hashed)
            $table->string('token', 255)->comment('Hashed password reset token sent via email');

            // Timestamp (only created_at, no updated_at)
            $table->timestamp('created_at')->nullable()->comment('Token creation timestamp for expiry calculation');

            // Index on email for quick lookup
            $table->index('email', 'idx_password_resets_email');
        });

        // Add table comment
        DB::statement("ALTER TABLE password_resets COMMENT 'Temporary password reset tokens (Laravel Breeze standard)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
