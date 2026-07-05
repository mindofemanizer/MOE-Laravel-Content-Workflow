<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_audits', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            
            // Polymorphic relation to content
            $table->morphs('content');
            
            // Action details
            $table->string('action'); // created, updated, status_changed, published, deleted, restored, etc.
            $table->string('field')->nullable(); // which field was changed (for updates)
            
            // Values
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('snapshot')->nullable(); // full snapshot of content at this point
            
            // Context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            
            // Metadata
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');
            
            // Indexes
            // Note: morphs() already creates an index on (content_type, content_id)
            $table->index(['content_type', 'content_id', 'action']);
            $table->index('action');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_audits');
    }
};
