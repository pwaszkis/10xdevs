<?php

/**
 * Migration: Create Jobs Table
 *
 * Purpose: Laravel queue system for asynchronous processing
 *
 * Tables Affected: jobs
 *
 * Features:
 * - Database driver for Laravel queue (alternative to Redis)
 * - Async AI generation processing
 * - Job retry mechanism with attempt tracking
 * - Priority queue support via queue name
 *
 * Usage in VibeTravels:
 * - AI plan generation (long-running, 10-30 seconds)
 * - Email sending (via Mailgun API)
 * - Future: PDF generation, scheduled tasks
 *
 * Queue Names:
 * - default: Standard priority jobs
 * - high: High priority (e.g., user waiting for AI result)
 * - low: Background tasks (e.g., analytics aggregation)
 *
 * Configuration Note:
 * - Tech stack mentions "Queue System + Redis"
 * - This table supports database driver as backup/alternative
 * - Recommended: Use Redis driver for production (faster)
 * - Keep this table for development/testing simplicity
 *
 * Related Tables: failed_jobs (failed job storage)
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
        Schema::create('jobs', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Queue name for priority handling
            $table->string('queue', 255)->comment('Queue name (default, high, low) for priority processing');

            // Serialized job payload
            $table->longText('payload')->comment('Serialized job data (class, method, parameters)');

            // Retry tracking
            $table->unsignedTinyInteger('attempts')->comment('Number of processing attempts (for retry logic)');

            // Timing (Unix timestamps for Laravel queue compatibility)
            $table->unsignedInteger('reserved_at')->nullable()->comment('When job was picked up by worker (Unix timestamp)');
            $table->unsignedInteger('available_at')->comment('When job becomes available for processing (Unix timestamp, for delayed jobs)');
            $table->unsignedInteger('created_at')->comment('When job was queued (Unix timestamp)');

            // Index on queue for worker efficiency
            $table->index('queue', 'idx_jobs_queue');
        });

        // Add table comment
        DB::statement("ALTER TABLE jobs COMMENT 'Laravel queue jobs for async processing (database driver)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
