<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Tests\Unit;

use MOE\ContentWorkflow\Services\StateMachineService;
use MOE\ContentWorkflow\Tests\TestCase;

class StatusBadgeTest extends TestCase
{
    protected StateMachineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StateMachineService();
    }

    public function test_get_all_statuses_returns_configured_statuses(): void
    {
        $statuses = $this->service->getAllStatuses();

        $this->assertCount(4, $statuses);
    }

    public function test_status_info_has_required_keys(): void
    {
        foreach (['draft', 'pending_review', 'published', 'archived'] as $status) {
            $info = $this->service->getStatusInfo($status);

            $this->assertNotNull($info, "Status '{$status}' should have info");
            $this->assertArrayHasKey('label', $info);
            $this->assertArrayHasKey('color', $info);
            $this->assertArrayHasKey('icon', $info);
        }
    }

    public function test_get_available_transitions_returns_valid_transitions(): void
    {
        $transitions = $this->service->getAvailableTransitions(
            new class implements \MOE\ContentWorkflow\Contracts\Publishable {
                public function getContentStatus(): string { return 'draft'; }
                public function canPerformAction(string $action, $user = null): bool { return true; }
                public function setContentStatus(string $status): bool { return true; }
                public function getPublishedAt(): ?\DateTimeInterface { return null; }
                public function setPublishedAt(?\DateTimeInterface $date): bool { return true; }
                public function getUnpublishedAt(): ?\DateTimeInterface { return null; }
                public function setUnpublishedAt(?\DateTimeInterface $date): bool { return true; }
                public function isPublished(): bool { return false; }
                public function isScheduled(): bool { return false; }
                public function isEditable(): bool { return true; }
                public function getContentData(): array { return []; }
                public function restoreFromData(array $data): bool { return true; }
                public function getContentTitle(): string { return 'Test'; }
                public function getContentType(): string { return 'test'; }
                public function contentSchedules(): \Illuminate\Database\Eloquent\Relations\MorphMany { throw new \RuntimeException('not implemented'); }
                public function contentVersions(): \Illuminate\Database\Eloquent\Relations\MorphMany { throw new \RuntimeException('not implemented'); }
                public function contentAudits(): \Illuminate\Database\Eloquent\Relations\MorphMany { throw new \RuntimeException('not implemented'); }
                public function getContentAuthor(): ?\Illuminate\Contracts\Auth\Authenticatable { return null; }
                public function getContentEditor(): ?\Illuminate\Contracts\Auth\Authenticatable { return null; }
                public function onStatusChanged(string $from, string $to, ?string $reason = null): void {}
                public function onPublished(): void {}
                public function onUnpublished(): void {}
                public function getMorphClass(): string { return 'test'; }
                public function getKey(): mixed { return 1; }
                public function getAttribute($key): mixed { return null; }
                public function setAttribute($key, $value): mixed { return $this; }
            }
        );

        $this->assertCount(3, $transitions);
        $this->assertContains('pending_review', $transitions);
        $this->assertContains('published', $transitions);
        $this->assertContains('archived', $transitions);
    }
}
