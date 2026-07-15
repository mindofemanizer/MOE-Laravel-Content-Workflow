<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Models;

use Illuminate\Database\Eloquent\Builder;
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
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    /**
     * @param Builder $query
     * @return void
     */
    public function scopeDue(Builder $query): void
    {
        $query->pending()->where('scheduled_at', '<=', now());
    }

    /**
     * @param Builder $query
     * @param string $action
     * @return void
     */
    public function scopeByAction(Builder $query, string $action): void
    {
        $query->where('action', $action);
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * @return bool
     */
    public function isDue(): bool
    {
        return $this->isPending() && $this->scheduled_at !== null && $this->scheduled_at <= now();
    }

    /**
     * @return void
     */
    public function markExecuted(): void
    {
        $this->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);
    }

    /**
     * @param string $error
     * @return void
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * @return void
     */
    public function markCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
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
