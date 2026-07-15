<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentAudit extends Model
{
    protected $table = 'content_audits';

    public $timestamps = false;

    protected $fillable = [
        'ulid',
        'content_type',
        'content_id',
        'action',
        'field',
        'old_value',
        'new_value',
        'snapshot',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByAction(Builder $query, string $action): void
    {
        $query->where('action', $action);
    }

    public function scopeForContent(Builder $query, string $type, string $id): void
    {
        $query->where('content_type', $type)->where('content_id', $id);
    }

    public function scopeRecent(Builder $query, int $days = 30): void
    {
        $query->where('created_at', '>=', now()->subDays($days));
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->ulid) {
                $model->ulid = (string) \Illuminate\Support\Str::ulid();
            }
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
    }
}
