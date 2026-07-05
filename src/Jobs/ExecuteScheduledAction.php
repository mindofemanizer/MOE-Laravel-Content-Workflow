<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MOE\ContentWorkflow\Models\ContentSchedule;
use MOE\ContentWorkflow\Services\ScheduleService;

class ExecuteScheduledAction implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ContentSchedule $schedule,
    ) {
        $this->onConnection(config('content-workflow.queue.connection', config('queue.default', 'sync')));
        $this->onQueue(config('content-workflow.queue.queue', 'content-workflow'));
    }

    public function handle(ScheduleService $scheduleService): void
    {
        $scheduleService->executeSchedule($this->schedule);
    }

    public function failed(\Throwable $e): void
    {
        $this->schedule->markFailed($e->getMessage());
    }
}
