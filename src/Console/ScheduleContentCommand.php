<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Console;

use Illuminate\Console\Command;
use MOE\ContentWorkflow\Services\ScheduleService;

class ScheduleContentCommand extends Command
{
    protected $signature = 'moe:schedule-content
        {action : Action to perform (publish, unpublish, archive)}
        {ids* : Content IDs to schedule}
        {--type= : Content type (morph class)}
        {--at=now+1hour : When to execute the action}
        {--dry-run : Show what would be done without executing}';

    protected $description = 'Schedule content actions for one or more content items';

    /**
     * @return int
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $ids = $this->argument('ids');
        $type = $this->option('type');
        $at = $this->option('at');
        $dryRun = $this->option('dry-run');

        if (!in_array($action, ['publish', 'unpublish', 'archive'], true)) {
            $this->error("Invalid action '{$action}'. Allowed: publish, unpublish, archive.");

            return self::FAILURE;
        }

        try {
            $scheduledAt = strtotime($at) ? new \DateTime($at) : throw new \InvalidArgumentException();
        } catch (\Throwable) {
            $this->error("Invalid time format '{$at}'. Use relative like 'now+1hour' or absolute like '2026-07-06 14:00'.");

            return self::FAILURE;
        }

        $this->info("Scheduling {$action} for " . count($ids) . " content(s) at {$scheduledAt->format('Y-m-d H:i:s')}.");

        if ($type) {
            $this->line("Content type: {$type}");
        }

        if ($dryRun) {
            $this->warn('Dry-run mode — no changes made.');

            return self::SUCCESS;
        }

        $scheduleService = app(ScheduleService::class);

        if (!$type) {
            $this->error('The --type option is required to resolve content models.');

            return self::FAILURE;
        }

        $errors = 0;

        foreach ($ids as $id) {
            $content = $type::find($id);

            if (!$content) {
                $this->error("Content with ID {$id} not found.");
                $errors++;
                continue;
            }

            $scheduleService->create($content, $scheduledAt, $action);
            $this->line("Scheduled {$action} for {$type} #{$id}.");
        }

        if ($errors > 0) {

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
