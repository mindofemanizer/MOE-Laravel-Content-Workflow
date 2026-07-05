<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentVersion extends Model
{
    protected $table = 'content_versions';

    protected $fillable = [
        'ulid',
        'content_type',
        'content_id',
        'version_number',
        'version_label',
        'data',
        'metadata',
        'changed_fields',
        'change_summary',
        'is_current',
        'restored_at',
        'created_by',
        'restored_by',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'changed_fields' => 'array',
        'is_current' => 'boolean',
        'restored_at' => 'datetime',
    ];

    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeForContent($query, string $type, string $id)
    {
        return $query->where('content_type', $type)->where('content_id', $id);
    }

    public function markAsCurrent(): void
    {
        $this->update(['is_current' => true]);
    }

    public function markAsNotCurrent(): void
    {
        $this->update(['is_current' => false]);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->ulid) {
                $model->ulid = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}
