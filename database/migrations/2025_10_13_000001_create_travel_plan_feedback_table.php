<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_plan_feedback', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys
            $table->foreignId('travel_plan_id')
                ->constrained('travel_plans')
                ->cascadeOnDelete();

            // Feedback data
            $table->boolean('satisfied');
            $table->json('issues')->nullable(); // Array of issue types when satisfied = false

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['travel_plan_id', 'created_at']);
            $table->index('satisfied'); // For analytics queries

            // Unique constraint: one feedback per plan
            $table->unique('travel_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_plan_feedback');
    }
};
