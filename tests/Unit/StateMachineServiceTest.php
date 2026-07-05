<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Tests\Unit;

use MOE\ContentWorkflow\Services\StateMachineService;
use MOE\ContentWorkflow\Tests\TestCase;

class StateMachineServiceTest extends TestCase
{
    protected StateMachineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StateMachineService();
    }

    public function test_validates_allowed_transitions(): void
    {
        $this->assertTrue($this->service->isTransitionAllowed('draft', 'published'));
        $this->assertTrue($this->service->isTransitionAllowed('draft', 'pending_review'));
        $this->assertTrue($this->service->isTransitionAllowed('published', 'archived'));
        $this->assertTrue($this->service->isTransitionAllowed('archived', 'draft'));
    }

    public function test_rejects_invalid_transitions(): void
    {
        $this->assertFalse($this->service->isTransitionAllowed('draft', 'draft'));
        $this->assertFalse($this->service->isTransitionAllowed('published', 'pending_review'));
        $this->assertFalse($this->service->isTransitionAllowed('archived', 'published'));
    }

    public function test_rejects_unknown_status(): void
    {
        $this->assertFalse($this->service->isTransitionAllowed('draft', 'unknown'));
        $this->assertFalse($this->service->isTransitionAllowed('unknown', 'draft'));
    }

    public function test_validates_status(): void
    {
        $this->assertTrue($this->service->isValidStatus('draft'));
        $this->assertTrue($this->service->isValidStatus('published'));
        $this->assertFalse($this->service->isValidStatus('deleted'));
    }

    public function test_returns_status_info(): void
    {
        $info = $this->service->getStatusInfo('published');

        $this->assertNotNull($info);
        $this->assertEquals('Published', $info['label']);
        $this->assertEquals('green', $info['color']);
    }

    public function test_returns_all_statuses(): void
    {
        $statuses = $this->service->getAllStatuses();

        $this->assertCount(4, $statuses);
        $this->assertTrue($statuses->has('draft'));
        $this->assertTrue($statuses->has('published'));
    }

    public function test_returns_null_for_invalid_status(): void
    {
        $this->assertNull($this->service->getStatusInfo('nonexistent'));
    }
}
