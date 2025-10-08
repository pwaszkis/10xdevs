<?php

/**
 * Migration: Create Email Logs Table
 *
 * Purpose: Track all outbound emails for debugging and rate limiting
 *
 * Tables Affected: email_logs
 *
 * Features:
 * - Complete audit trail of all emails sent
 * - Status tracking (queued → sent → delivered/failed)
 * - Support for anonymous emails (nullable user_id)
 * - Metadata storage for email template variables
 * - Optional Mailgun webhook integration (MVP: status stops at 'sent')
 *
 * Email Types:
 * - verification: Email verification link
 * - welcome: Post-onboarding welcome email
 * - limit_warning: 8/10 generations used
 * - limit_reached: 10/10 generations used
 * - trip_reminder: 3 days before departure (optional in MVP)
 *
 * Rate Limiting Support:
 * - Query: "SELECT COUNT(*) WHERE user_id=X AND email_type='verification' AND sent_at > NOW() - INTERVAL 5 MINUTE"
 * - Prevents spam (max 1 verification email per 5 minutes)
 *
 * Security Notes:
 * - Foreign key CASCADE delete when user deleted (GDPR)
 * - No email body storage (templates in Blade files)
 * - Metadata is JSON for flexibility
 *
 * Related Tables: users (N:1, nullable)
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
        Schema::create('email_logs', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to users (nullable for anonymous emails)
            $table->unsignedBigInteger('user_id')->nullable()->comment('Recipient user ID (null for anonymous/waitlist emails)');

            // Recipient email address (stored separately for anonymous support)
            $table->string('email', 255)->comment('Recipient email address');

            // Email type (determines which template used)
            $table->enum('email_type', ['verification', 'welcome', 'limit_warning', 'limit_reached', 'trip_reminder'])
                ->comment('Email type/template: verification, welcome, limit_warning, limit_reached, trip_reminder');

            // Email status tracking
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'bounced'])
                ->default('queued')
                ->comment('Email status: queued (pending), sent (passed to Mailgun), delivered (Mailgun webhook), failed/bounced (errors)');

            // Template variables (JSON storage)
            $table->json('metadata')->nullable()->comment('JSON object with template variables (e.g., {"plan_title": "...", "destination": "..."})');

            // Timestamps
            $table->timestamp('sent_at')->nullable()->comment('When email was sent to Mailgun (status changed to sent)');
            $table->timestamp('delivered_at')->nullable()->comment('When Mailgun confirmed delivery (via webhook, optional in MVP)');

            // Error tracking
            $table->text('error_message')->nullable()->comment('Error details if status=failed or bounced');

            // Standard timestamps
            $table->timestamps(); // created_at (queued time), updated_at

            // Foreign key constraint (nullable, so no ON DELETE CASCADE needed)
            $table->foreign('user_id', 'fk_email_logs_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('restrict');

            // Composite index for rate limiting queries
            // Query pattern: "recent verification emails for user X"
            $table->index(['user_id', 'email_type', 'sent_at'], 'idx_email_logs_user_type_sent');
        });

        // Add table comment
        DB::statement("ALTER TABLE email_logs COMMENT 'Outbound email tracking for audit and rate limiting (N:1 with users)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
