<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_schedules', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            
            // Polymorphic relation to content
            $table->morphs('content');
            
            // Schedule details
            $table->string('action'); // publish, unpublish, status_change
            $table->json('action_payload')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Status
            $table->string('status')->default('pending'); // pending, executed, failed, cancelled
            $table->text('error_message')->nullable();
            
            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes
            // Note: morphs() already creates an index on (content_type, content_id)
            $table->index(['scheduled_at', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_schedules');
    }
};
