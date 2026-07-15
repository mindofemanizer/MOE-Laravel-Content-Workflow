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
    protected ?string $publishedAtField = null;
    protected ?string $unpublishedAtField = null;
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

    /**
     * @return string
     */
    public function getContentStatus(): string
    {
        $field = $this->contentStatusField ?? config('content-workflow.status_field', 'status');

        return $this->getAttribute($field) ?? 'draft';
    }

    /**
     * @param string $status
     * @return bool
     */
    public function setContentStatus(string $status): bool
    {
        $field = $this->contentStatusField ?? config('content-workflow.status_field', 'status');

        return $this->update([$field => $status]);
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPublishedAt(): ?\DateTimeInterface
    {
        if (!$this->publishedAtField) {

            return null;
        }

        return $this->getAttribute($this->publishedAtField);
    }

    /**
     * @param \DateTimeInterface|null $date
     * @return bool
     */
    public function setPublishedAt(?\DateTimeInterface $date): bool
    {
        if (!$this->publishedAtField) {

            return false;
        }

        return $this->update([$this->publishedAtField => $date]);
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getUnpublishedAt(): ?\DateTimeInterface
    {
        if (!$this->unpublishedAtField) {

            return null;
        }

        return $this->getAttribute($this->unpublishedAtField);
    }

    /**
     * @param \DateTimeInterface|null $date
     * @return bool
     */
    public function setUnpublishedAt(?\DateTimeInterface $date): bool
    {
        if (!$this->unpublishedAtField) {

            return false;
        }

        return $this->update([$this->unpublishedAtField => $date]);
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        $status = $this->getContentStatus();
        $publishedAt = $this->getPublishedAt();

        if ($status !== 'published') {

            return false;
        }

        if ($publishedAt && $publishedAt > now()) {

            return false;
        }

        $unpublishedAt = $this->getUnpublishedAt();
        if ($unpublishedAt && $unpublishedAt <= now()) {

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->contentSchedules()
            ->where('status', 'pending')
            ->where('scheduled_at', '>', now())
            ->exists();
    }

    /**
     * @return bool
     */
    public function isEditable(): bool
    {
        $status = $this->getContentStatus();

        return in_array($status, ['draft', 'pending_review', 'archived'], true);
    }

    /**
     * @return array
     */
    public function getContentData(): array
    {
        $data = $this->getAttributes();

        foreach ($this->getContentDataExcludedFields() as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * @return array
     */
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

    /**
     * @param array $data
     * @return bool
     */
    public function restoreFromData(array $data): bool
    {
        unset($data['id'], $data['created_at'], $data['updated_at']);

        $allowedFields = array_keys($this->getContentData());
        $data = array_intersect_key($data, array_flip($allowedFields));

        return $this->update($data);
    }

    /**
     * @return string
     */
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

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return class_basename($this);
    }

    /**
     * @return MorphMany
     */
    public function contentSchedules(): MorphMany
    {
        return $this->morphMany(ContentSchedule::class, 'content');
    }

    /**
     * @return MorphMany
     */
    public function contentVersions(): MorphMany
    {
        return $this->morphMany(ContentVersion::class, 'content');
    }

    /**
     * @return MorphMany
     */
    public function contentAudits(): MorphMany
    {
        return $this->morphMany(ContentAudit::class, 'content');
    }

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
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

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
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

    /**
     * @param string $from
     * @param string $to
     * @param string|null $reason
     * @return void
     */
    public function onStatusChanged(string $from, string $to, ?string $reason = null): void
    {
        event(new \MOE\ContentWorkflow\Events\ContentStatusChanged($this, $from, $to, $reason));
    }

    /**
     * @return void
     */
    public function onPublished(): void
    {
        event(new \MOE\ContentWorkflow\Events\ContentPublished($this));
    }

    /**
     * @return void
     */
    public function onUnpublished(): void
    {
        event(new \MOE\ContentWorkflow\Events\ContentUnpublished($this));
    }

    /**
     * @param string $action
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @return bool
     */
    public function canPerformAction(string $action, ?\Illuminate\Contracts\Auth\Authenticatable $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {

            return false;
        }

        return true;
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * @return void
     */
    protected function handleStatusChange(): void
    {
        $field = $this->contentStatusField ?? config('content-workflow.status_field', 'status');

        if (!$this->isDirty($field)) {

            return;
        }

        $from = $this->getOriginal($field) ?? 'draft';
        $to = $this->getAttribute($field);

        if ($to === 'published' && $from !== 'published') {
            if ($this->publishedAtField) {
                $this->{$this->publishedAtField} = now();
            }
            $this->onPublished();
        } elseif ($from === 'published' && $to !== 'published') {
            $this->onUnpublished();
        }

        $this->onStatusChanged($from, $to);
    }

    /**
     * @param string $action
     * @return void
     */
    protected function logContentAudit(string $action): void
    {
        if (!config('content-workflow.audit.enabled', true)) {

            return;
        }

        MoeContent::logAudit($this, $action);
    }

    /**
     * @return void
     */
    protected function createInitialVersion(): void
    {
        if (!config('content-workflow.versioning.enabled', true)) {

            return;
        }

        MoeContent::createVersion($this, 'Initial version');
    }

    /**
     * @return bool
     */
    protected function shouldCreateVersion(): bool
    {
        if (!config('content-workflow.versioning.enabled', true)) {

            return false;
        }

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

    /**
     * @return void
     */
    protected function createNewVersion(): void
    {
        MoeContent::createVersion($this);
    }

    /**
     * @return void
     */
    protected function cancelScheduledActions(): void
    {
        MoeContent::cancelSchedule($this);
    }
}
