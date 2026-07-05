<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use MOE\ContentWorkflow\Models\ContentSchedule;
use MOE\ContentWorkflow\Models\ContentVersion;
use MOE\ContentWorkflow\Models\ContentAudit;

interface Publishable
{
    /**
     * Get the current status of the content
     */
    public function getContentStatus(): string;

    /**
     * Set the status of the content
     */
    public function setContentStatus(string $status): bool;

    /**
     * Get the publish date
     */
    public function getPublishedAt(): ?\DateTimeInterface;

    /**
     * Set the publish date
     */
    public function setPublishedAt(?\DateTimeInterface $date): bool;

    /**
     * Get the unpublish date (for scheduled unpublishing)
     */
    public function getUnpublishedAt(): ?\DateTimeInterface;

    /**
     * Set the unpublish date
     */
    public function setUnpublishedAt(?\DateTimeInterface $date): bool;

    /**
     * Check if content is published
     */
    public function isPublished(): bool;

    /**
     * Check if content is scheduled for publishing
     */
    public function isScheduled(): bool;

    /**
     * Check if content can be edited
     */
    public function isEditable(): bool;

    /**
     * Get the content data as array for versioning
     */
    public function getContentData(): array;

    /**
     * Restore content from array (for version restore)
     */
    public function restoreFromData(array $data): bool;

    /**
     * Get the content title/label for display
     */
    public function getContentTitle(): string;

    /**
     * Get the content type identifier
     */
    public function getContentType(): string;

    /**
     * Relationship to schedules
     */
    public function contentSchedules(): MorphMany;

    /**
     * Relationship to versions
     */
    public function contentVersions(): MorphMany;

    /**
     * Relationship to audits
     */
    public function contentAudits(): MorphMany;

    /**
     * Get the user who created the content
     */
    public function getContentAuthor(): ?\Illuminate\Contracts\Auth\Authenticatable;

    /**
     * Get the user who last updated the content
     */
    public function getContentEditor(): ?\Illuminate\Contracts\Auth\Authenticatable;

    /**
     * Called when status changes
     */
    public function onStatusChanged(string $from, string $to, ?string $reason = null): void;

    /**
     * Called when content is published
     */
    public function onPublished(): void;

    /**
     * Called when content is unpublished
     */
    public function onUnpublished(): void;

    /**
     * Check if user can perform action
     */
    public function canPerformAction(string $action, ?\Illuminate\Contracts\Auth\Authenticatable $user = null): bool;

    /**
     * Get the morph class name (from Eloquent Model)
     */
    public function getMorphClass(): string;

    /**
     * Get the primary key value (from Eloquent Model)
     */
    public function getKey(): mixed;

    /**
     * Get an attribute from the model
     */
    public function getAttribute(string $key): mixed;

    /**
     * Set an attribute on the model
     */
    public function setAttribute(string $key, mixed $value): mixed;
}
