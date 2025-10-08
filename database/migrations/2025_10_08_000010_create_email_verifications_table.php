<?php

/**
 * Migration: Create Email Verifications Table
 *
 * Purpose: Store email verification tokens with expiry tracking
 *
 * Tables Affected: email_verifications
 *
 * Features:
 * - Temporary tokens for email verification (24-hour expiry)
 * - Hashed token storage (SHA256/bcrypt)
 * - Verification timestamp tracking
 * - Support for resending verification emails
 *
 * Workflow:
 * 1. User registers → token generated and hashed → email sent with plain token
 * 2. User clicks link → plain token hashed → matched against DB
 * 3. If match and not expired → users.email_verified_at updated, record marked verified
 * 4. If expired → show error, allow resend
 *
 * Security Notes:
 * - Tokens are hashed before storage (plain token only in email URL)
 * - 24-hour expiry reduces attack window
 * - Foreign key CASCADE delete when user deleted
 * - Rate limiting on resend requests (1 per 5 minutes via Redis)
 *
 * Related Tables: users (N:1)
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
        Schema::create('email_verifications', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to users
            $table->unsignedBigInteger('user_id')->comment('User requesting email verification');

            // Verification token (hashed with SHA256 or bcrypt)
            $table->string('token', 64)->unique()->comment('Hashed verification token (plain token sent via email)');

            // Expiry tracking (created_at + 24 hours)
            $table->timestamp('expires_at')->comment('Token expiration timestamp (24 hours from creation)');

            // Verification status
            $table->timestamp('verified_at')->nullable()->comment('When token was used to verify email (null if not yet verified)');

            // Timestamps
            $table->timestamps();

            // Foreign key constraint
            // ON DELETE CASCADE: when user deleted, verification tokens deleted
            $table->foreign('user_id', 'fk_email_verifications_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Indexes
            $table->index('token', 'idx_email_verifications_token'); // Quick token lookup
            $table->index('user_id', 'idx_email_verifications_user'); // User's verification history
        });

        // Add table comment
        DB::statement("ALTER TABLE email_verifications COMMENT 'Email verification tokens with 24h expiry (N:1 with users)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
    }
};
