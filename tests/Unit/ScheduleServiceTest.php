<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Tests\Unit;

use Carbon\Carbon;
use MOE\ContentWorkflow\Models\ContentSchedule;
use MOE\ContentWorkflow\Services\ScheduleService;
use MOE\ContentWorkflow\Tests\TestCase;

class ScheduleServiceTest extends TestCase
{
    protected ScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScheduleService();
    }

    public function test_creates_schedule(): void
    {
        $model = new ContentSchedule();
        $model->forceFill([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);
        $model->save();

        $this->assertDatabaseHas('content_schedules', [
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'status' => 'pending',
        ]);
    }

    public function test_schedule_is_pending(): void
    {
        $schedule = new ContentSchedule([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $this->assertTrue($schedule->isPending());
        $this->assertFalse($schedule->isDue());
    }

    public function test_schedule_is_due_when_past(): void
    {
        $schedule = new ContentSchedule([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->subHour(),
            'status' => 'pending',
        ]);

        $this->assertTrue($schedule->isDue());
    }

    public function test_marks_as_executed(): void
    {
        $schedule = ContentSchedule::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->subHour(),
            'status' => 'pending',
        ]);

        $schedule->markExecuted();

        $this->assertEquals('executed', $schedule->fresh()->status);
        $this->assertNotNull($schedule->fresh()->executed_at);
    }

    public function test_marks_as_failed(): void
    {
        $schedule = ContentSchedule::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->subHour(),
            'status' => 'pending',
        ]);

        $schedule->markFailed('Something went wrong');

        $this->assertEquals('failed', $schedule->fresh()->status);
        $this->assertEquals('Something went wrong', $schedule->fresh()->error_message);
    }

    public function test_marks_as_cancelled(): void
    {
        $schedule = ContentSchedule::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $schedule->markCancelled();

        $this->assertEquals('cancelled', $schedule->fresh()->status);
        $this->assertNotNull($schedule->fresh()->cancelled_at);
    }

    public function test_scopes_pending(): void
    {
        $schedule = ContentSchedule::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        ContentSchedule::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->addDay(),
            'status' => 'executed',
        ]);

        $pending = ContentSchedule::pending()->get();

        $this->assertCount(1, $pending);
        $this->assertEquals($schedule->id, $pending->first()->id);
    }

    public function test_scopes_due(): void
    {
        ContentSchedule::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->subHour(),
            'status' => 'pending',
        ]);

        ContentSchedule::create([
            'content_type' => 'post',
            'content_id' => 1,
            'action' => 'publish',
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $due = ContentSchedule::due()->get();

        $this->assertCount(1, $due);
    }
}
