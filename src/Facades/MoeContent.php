<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use MOE\ContentWorkflow\ContentWorkflowManager;
use MOE\ContentWorkflow\Contracts\Publishable;

/**
 * @method static bool transition(Publishable $content, string $toStatus, ?string $reason = null)
 * @method static bool canTransition(Publishable $content, string $toStatus)
 * @method static Collection getAvailableTransitions(Publishable $content)
 * @method static bool schedule(Publishable $content, \DateTimeInterface $scheduledAt, ?string $action = 'publish')
 * @method static bool cancelSchedule(Publishable $content, ?string $action = null)
 * @method static bool createVersion(Publishable $content, ?string $label = null)
 * @method static bool restoreVersion(Publishable $content, int $versionNumber)
 * @method static Collection getVersions(Publishable $content)
 * @method static bool logAudit(Publishable $content, string $action, ?array $payload = null)
 * @method static Collection getAuditTrail(Publishable $content)
 * @method static string renderStatus(Publishable $content)
 * @method static bool can(string $action, ?Publishable $content = null)
 */
class MoeContent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ContentWorkflowManager::class;
    }
}
