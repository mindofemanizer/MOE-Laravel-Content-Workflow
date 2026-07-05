<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentSchedule extends Model
{
    protected $table = 'content_schedules';

    protected $fillable = [
        'ulid',
        'content_type',
        'content_id',
        'action',
        'action_payload',
        'scheduled_at',
        'executed_at',
        'cancelled_at',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'action_payload' => 'array',
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->pending()->where('scheduled_at', '<=', now());
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDue(): bool
    {
        return $this->isPending() && $this->scheduled_at <= now();
    }

    public function markExecuted(): void
    {
        $this->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    public function markCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
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
