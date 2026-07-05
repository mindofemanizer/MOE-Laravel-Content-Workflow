<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_versions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            
            // Polymorphic relation to content
            $table->morphs('content');
            
            // Version info
            $table->integer('version_number');
            $table->string('version_label')->nullable(); // e.g., "Before major edit", "v2.1"
            
            // Snapshot of content data
            $table->json('data');
            $table->json('metadata')->nullable(); // additional context
            
            // Change tracking
            $table->json('changed_fields')->nullable(); // which fields changed
            $table->text('change_summary')->nullable();
            
            // Status
            $table->boolean('is_current')->default(false);
            $table->timestamp('restored_at')->nullable();
            
            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes
            // Note: morphs() already creates an index on (content_type, content_id)
            // unique already covers (content_type, content_id, version_number)
            $table->index('is_current');
            $table->unique(['content_type', 'content_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_versions');
    }
};
