<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Tests\Unit;

use MOE\ContentWorkflow\Models\ContentAudit;
use MOE\ContentWorkflow\Tests\TestCase;

class AuditServiceTest extends TestCase
{
    public function test_creates_audit(): void
    {
        $audit = ContentAudit::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'created',
            'snapshot' => ['title' => 'Test'],
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('content_audits', [
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'created',
        ]);
    }

    public function test_generates_ulid_on_create(): void
    {
        $audit = ContentAudit::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'updated',
            'created_at' => now(),
        ]);

        $this->assertNotNull($audit->ulid);
    }

    public function test_sets_created_at_automatically(): void
    {
        $audit = ContentAudit::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'deleted',
        ]);

        $this->assertNotNull($audit->created_at);
    }

    public function test_scope_by_action(): void
    {
        ContentAudit::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'created',
            'created_at' => now(),
        ]);

        ContentAudit::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'updated',
            'created_at' => now(),
        ]);

        $result = ContentAudit::byAction('created')->get();

        $this->assertCount(1, $result);
    }

    public function test_scope_recent(): void
    {
        ContentAudit::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'created',
            'created_at' => now(),
        ]);

        ContentAudit::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'old',
            'created_at' => now()->subDays(60),
        ]);

        $result = ContentAudit::recent(30)->get();

        $this->assertCount(1, $result);
    }

    public function test_scope_for_content(): void
    {
        ContentAudit::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'created',
            'created_at' => now(),
        ]);

        ContentAudit::create([
            'content_type' => 'page',
            'content_id' => 1,
            'action' => 'created',
            'created_at' => now(),
        ]);

        $result = ContentAudit::forContent('post', '1')->get();

        $this->assertCount(1, $result);
    }
}
