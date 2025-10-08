<?php

/**
 * Migration: Create Failed Jobs Table
 *
 * Purpose: Store failed queue jobs for debugging and retry
 *
 * Tables Affected: failed_jobs
 *
 * Features:
 * - Automatic storage of permanently failed jobs
 * - Complete error stack trace for debugging
 * - UUID for unique identification
 * - Manual retry capability via artisan command
 *
 * Usage in VibeTravels:
 * - Failed AI generation jobs (timeout, API errors)
 * - Failed email sending jobs (Mailgun errors)
 * - Analysis of failure patterns for stability improvements
 *
 * Retry Process:
 * - Jobs fail after max attempts (default: 3)
 * - Moved from 'jobs' table to 'failed_jobs' table
 * - Admin can inspect error and retry via: php artisan queue:retry {uuid}
 *
 * Monitoring:
 * - Alert if failed_jobs count > threshold
 * - Daily review of failure exceptions
 * - Pattern analysis for systemic issues
 *
 * Related Tables: jobs (source of failed jobs)
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
        Schema::create('failed_jobs', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Unique identifier for retry commands
            $table->string('uuid', 255)->unique()->comment('Unique job identifier for retry via artisan command');

            // Connection and queue information
            $table->text('connection')->comment('Queue connection name (e.g., database, redis)');
            $table->text('queue')->comment('Queue name where job failed (default, high, low)');

            // Job details
            $table->longText('payload')->comment('Serialized job data (class, method, parameters) for retry');

            // Exception details (for debugging)
            $table->longText('exception')->comment('Full exception stack trace for debugging');

            // Failure timestamp
            $table->timestamp('failed_at')->useCurrent()->comment('When job permanently failed (after max retry attempts)');

            // Index on UUID for quick retry lookup
            $table->index('uuid', 'idx_failed_jobs_uuid');
        });

        // Add table comment
        DB::statement("ALTER TABLE failed_jobs COMMENT 'Permanently failed queue jobs for debugging and retry'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
