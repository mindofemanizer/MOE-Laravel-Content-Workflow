<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Tests\Unit;

use MOE\ContentWorkflow\Models\ContentVersion;
use MOE\ContentWorkflow\Tests\TestCase;

class VersioningServiceTest extends TestCase
{
    public function test_creates_version(): void
    {
        $version = ContentVersion::create([
            'content_type' => 'post',
            'content_id' => 1,
            'version_number' => 1,
            'version_label' => 'Initial version',
            'data' => ['title' => 'Hello', 'content' => 'World'],
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('content_versions', [
            'content_type' => 'post',
            'content_id' => 1,
            'version_number' => 1,
            'is_current' => true,
        ]);

        $this->assertEquals('Hello', $version->data['title']);
    }

    public function test_generates_ulid_on_create(): void
    {
        $version = ContentVersion::create([
            'content_type' => 'post',
            'content_id' => 1,
            'version_number' => 1,
            'data' => ['title' => 'Test'],
            'is_current' => false,
        ]);

        $this->assertNotNull($version->ulid);
        $this->assertTrue(strlen($version->ulid) === 26);
    }

    public function test_scope_current(): void
    {
        ContentVersion::create([
            'content_type' => 'post',
            'content_id' => 1,
            'version_number' => 1,
            'data' => ['title' => 'v1'],
            'is_current' => false,
        ]);

        $current = ContentVersion::create([
            'content_type' => 'post',
            'content_id' => 1,
            'version_number' => 2,
            'data' => ['title' => 'v2'],
            'is_current' => true,
        ]);

        $result = ContentVersion::current()->get();

        $this->assertCount(1, $result);
        $this->assertEquals($current->id, $result->first()->id);
    }

    public function test_marks_version_as_current(): void
    {
        $version = ContentVersion::create([
            'content_type' => 'post',
            'content_id' => 1,
            'version_number' => 1,
            'data' => ['title' => 'Test'],
            'is_current' => false,
        ]);

        $version->markAsCurrent();

        $this->assertTrue($version->fresh()->is_current);
    }

    public function test_scope_for_content(): void
    {
        ContentVersion::create([
            'content_type' => 'post',
            'content_id' => 1,
            'version_number' => 1,
            'data' => ['title' => 'Post 1'],
            'is_current' => false,
        ]);

        ContentVersion::create([
            'content_type' => 'page',
            'content_id' => 1,
            'version_number' => 1,
            'data' => ['title' => 'Page 1'],
            'is_current' => false,
        ]);

        $result = ContentVersion::forContent('post', '1')->get();

        $this->assertCount(1, $result);
    }
}
