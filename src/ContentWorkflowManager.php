<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Services\AuditService;
use MOE\ContentWorkflow\Services\ScheduleService;
use MOE\ContentWorkflow\Services\StateMachineService;
use MOE\ContentWorkflow\Services\VersioningService;

class ContentWorkflowManager
{
    protected StateMachineService $stateMachine;
    protected ScheduleService $schedule;
    protected VersioningService $versioning;
    protected AuditService $audit;

    public function __construct(
        StateMachineService $stateMachine,
        ScheduleService $schedule,
        VersioningService $versioning,
        AuditService $audit
    ) {
        $this->stateMachine = $stateMachine;
        $this->schedule = $schedule;
        $this->versioning = $versioning;
        $this->audit = $audit;
    }

    /**
     * Transition content to a new status
     */
    public function transition(Publishable $content, string $toStatus, ?string $reason = null): bool
    {
        return $this->stateMachine->transition($content, $toStatus, $reason);
    }

    /**
     * Check if transition is allowed
     */
    public function canTransition(Publishable $content, string $toStatus): bool
    {
        return $this->stateMachine->canTransition($content, $toStatus);
    }

    /**
     * Get available transitions for content
     */
    public function getAvailableTransitions(Publishable $content): Collection
    {
        return $this->stateMachine->getAvailableTransitions($content);
    }

    /**
     * Schedule content publication
     */
    public function schedule(Publishable $content, \DateTimeInterface $scheduledAt, ?string $action = 'publish'): bool
    {
        return $this->schedule->create($content, $scheduledAt, $action);
    }

    /**
     * Cancel scheduled action
     */
    public function cancelSchedule(Publishable $content, ?string $action = null): bool
    {
        return $this->schedule->cancel($content, $action);
    }

    /**
     * Create new version of content
     */
    public function createVersion(Publishable $content, ?string $label = null): bool
    {
        return $this->versioning->create($content, $label);
    }

    /**
     * Restore content to specific version
     */
    public function restoreVersion(Publishable $content, int $versionNumber): bool
    {
        return $this->versioning->restore($content, $versionNumber);
    }

    /**
     * Get all versions of content
     */
    public function getVersions(Publishable $content): Collection
    {
        return $this->versioning->getVersions($content);
    }

    /**
     * Log audit entry
     */
    public function logAudit(Publishable $content, string $action, ?array $payload = null): bool
    {
        return $this->audit->log($content, $action, $payload);
    }

    /**
     * Get audit trail for content
     */
    public function getAuditTrail(Publishable $content): Collection
    {
        return $this->audit->getTrail($content);
    }

    /**
     * Render status badge HTML
     */
    public function renderStatus(Publishable $content): string
    {
        return $this->stateMachine->renderStatusBadge($content);
    }

    /**
     * Check user permission for action
     */
    public function can(string $action, ?Publishable $content = null): bool
    {
        if (!$content) {
            return false;
        }
        return $content->canPerformAction($action);
    }
}
