<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Console;

use Illuminate\Console\Command;
use MOE\ContentWorkflow\Services\ScheduleService;

class PublishWorkflowCommand extends Command
{
    protected $signature = 'moe:publish-workflow
        {--force : Execute without confirmation}';

    protected $description = 'Process all due scheduled content actions';

    public function handle(ScheduleService $schedule): int
    {
        if (!$this->option('force') && !$this->confirm('Process all due scheduled content actions?', true)) {
            $this->info('Command cancelled.');
            return self::SUCCESS;
        }

        $executed = $schedule->executePending();

        if ($executed > 0) {
            $this->info("Executed {$executed} scheduled content action(s).");
        } else {
            $this->info('No pending scheduled actions to execute.');
        }

        return self::SUCCESS;
    }
}
