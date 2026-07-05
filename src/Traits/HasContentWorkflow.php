<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use MOE\ContentWorkflow\Facades\MoeContent;
use MOE\ContentWorkflow\Models\ContentAudit;
use MOE\ContentWorkflow\Models\ContentSchedule;
use MOE\ContentWorkflow\Models\ContentVersion;

trait HasContentWorkflow
{
    protected ?string $contentStatusField = null;
    protected ?string $publishedAtField = 'published_at';
    protected ?string $unpublishedAtField = 'unpublished_at';
    protected ?string $authorField = 'user_id';
    protected ?string $editorField = 'updated_by';

    protected static function bootHasContentWorkflow(): void
    {
        static::created(function ($model) {
            $model->logContentAudit('created');
            $model->createInitialVersion();
        });

        static::updating(function ($model) {
            $model->handleStatusChange();
        });

        static::updated(function ($model) {
            $model->logContentAudit('updated');
            if ($model->shouldCreateVersion()) {
                $model->createNewVersion();
            }
        });

        static::deleted(function ($model) {
            $model->logContentAudit('deleted');
            $model->cancelScheduledActions();
        });

        static::restored(function ($model) {
            $model->logContentAudit('restored');
        });
    }

    // ========================================================================
    // Interface Implementation
    // ========================================================================

    public function getContentStatus(): string
    {
        $field = $this->contentStatusField ?? config('content-workflow.status_field', 'status');
        return $this->getAttribute($field) ?? 'draft';
    }

    public function setContentStatus(string $status): bool
    {
        $field = $this->contentStatusField ?? config('content-workflow.status_field', 'status');
        return $this->update([$field => $status]);
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        if (!$this->publishedAtField) {
            return null;
        }
        return $this->getAttribute($this->publishedAtField);
    }

    public function setPublishedAt(?\DateTimeInterface $date): bool
    {
        if (!$this->publishedAtField) {
            return false;
        }
        return $this->update([$this->publishedAtField => $date]);
    }

    public function getUnpublishedAt(): ?\DateTimeInterface
    {
        if (!$this->unpublishedAtField) {
            return null;
        }
        return $this->getAttribute($this->unpublishedAtField);
    }

    public function setUnpublishedAt(?\DateTimeInterface $date): bool
    {
        if (!$this->unpublishedAtField) {
            return false;
        }
        return $this->update([$this->unpublishedAtField => $date]);
    }

    public function isPublished(): bool
    {
        $status = $this->getContentStatus();
        $publishedAt = $this->getPublishedAt();

        if ($status !== 'published') {
            return false;
        }

        if ($publishedAt && $publishedAt > now()) {
            return false; // Scheduled for future
        }

        $unpublishedAt = $this->getUnpublishedAt();
        if ($unpublishedAt && $unpublishedAt <= now()) {
            return false; // Already unpublished
        }

        return true;
    }

    public function isScheduled(): bool
    {
        return $this->contentSchedules()
            ->where('status', 'pending')
            ->where('scheduled_at', '>', now())
            ->exists();
    }

    public function isEditable(): bool
    {
        $status = $this->getContentStatus();
        return in_array($status, ['draft', 'pending_review', 'archived'], true);
    }

    public function getContentData(): array
    {
        $data = $this->getAttributes();

        foreach ($this->getContentDataExcludedFields() as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    protected function getContentDataExcludedFields(): array
    {
        return array_merge(
            $this->hidden ?? [],
            config('content-workflow.sensitive_fields', [
                'password',
                'remember_token',
                'two_factor_secret',
                'two_factor_recovery_codes',
            ])
        );
    }

    public function restoreFromData(array $data): bool
    {
        unset($data['id'], $data['created_at'], $data['updated_at']);

        $allowedFields = array_keys($this->getContentData());
        $data = array_intersect_key($data, array_flip($allowedFields));

        return $this->update($data);
    }

    public function getContentTitle(): string
    {
        $titleFields = ['title', 'name', 'subject', 'headline'];

        foreach ($titleFields as $field) {
            if ($this->getAttribute($field)) {
                return $this->getAttribute($field);
            }
        }

        return "{$this->getContentType()} #{$this->getKey()}";
    }

    public function getContentType(): string
    {
        return class_basename($this);
    }

    public function contentSchedules(): MorphMany
    {
        return $this->morphMany(ContentSchedule::class, 'content');
    }

    public function contentVersions(): MorphMany
    {
        return $this->morphMany(ContentVersion::class, 'content');
    }

    public function contentAudits(): MorphMany
    {
        return $this->morphMany(ContentAudit::class, 'content');
    }

    public function getContentAuthor(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        if (!$this->authorField) {
            return null;
        }

        $authorId = $this->getAttribute($this->authorField);

        if (!$authorId) {
            return null;
        }

        $userModel = config('content-workflow.user_model', config('auth.providers.users.model', 'App\\Models\\User'));

        return $userModel::find($authorId);
    }

    public function getContentEditor(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        if (!$this->editorField) {
            return null;
        }

        $editorId = $this->getAttribute($this->editorField);

        if (!$editorId) {
            return null;
        }

        $userModel = config('content-workflow.user_model', config('auth.providers.users.model', 'App\\Models\\User'));

        return $userModel::find($editorId);
    }

    public function onStatusChanged(string $from, string $to, ?string $reason = null): void
    {
        // Override in model for custom logic
        event(new \MOE\ContentWorkflow\Events\ContentStatusChanged($this, $from, $to, $reason));
    }

    public function onPublished(): void
    {
        // Override in model for custom logic
        event(new \MOE\ContentWorkflow\Events\ContentPublished($this));
    }

    public function onUnpublished(): void
    {
        // Override in model for custom logic
        event(new \MOE\ContentWorkflow\Events\ContentUnpublished($this));
    }

    public function canPerformAction(string $action, ?\Illuminate\Contracts\Auth\Authenticatable $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Override in model for custom permission logic
        return true;
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    protected function handleStatusChange(): void
    {
        $field = $this->contentStatusField ?? config('content-workflow.status_field', 'status');

        if (!$this->isDirty($field)) {
            return;
        }

        $from = $this->getOriginal($field) ?? 'draft';
        $to = $this->getAttribute($field);

        if ($to === 'published' && $from !== 'published') {
            $this->setPublishedAt(now());
            $this->onPublished();
        } elseif ($from === 'published' && $to !== 'published') {
            $this->onUnpublished();
        }

        $this->onStatusChanged($from, $to);
    }

    protected function logContentAudit(string $action): void
    {
        if (!config('content-workflow.audit.enabled', true)) {
            return;
        }

        MoeContent::logAudit($this, $action);
    }

    protected function createInitialVersion(): void
    {
        if (!config('content-workflow.versioning.enabled', true)) {
            return;
        }

        MoeContent::createVersion($this, 'Initial version');
    }

    protected function shouldCreateVersion(): bool
    {
        if (!config('content-workflow.versioning.enabled', true)) {
            return false;
        }

        // Only create version if content actually changed
        $versionedFields = config('content-workflow.versioning.fields', []);

        if (empty($versionedFields)) {
            return $this->wasChanged();
        }

        foreach ($versionedFields as $field) {
            if ($this->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    protected function createNewVersion(): void
    {
        MoeContent::createVersion($this);
    }

    protected function cancelScheduledActions(): void
    {
        MoeContent::cancelSchedule($this);
    }
}
