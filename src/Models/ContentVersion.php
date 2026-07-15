<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @return MorphTo
     */
    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param Builder $query
     * @return void
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('is_current', true);
    }

    /**
     * @param Builder $query
     * @param string $type
     * @param string $id
     * @return void
     */
    public function scopeForContent(Builder $query, string $type, string $id): void
    {
        $query->where('content_type', $type)->where('content_id', $id);
    }

    /**
     * @return void
     */
    public function markAsCurrent(): void
    {
        $this->update(['is_current' => true]);
    }

    /**
     * @return void
     */
    public function markAsNotCurrent(): void
    {
        $this->update(['is_current' => false]);
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->ulid) {
                $model->ulid = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}
