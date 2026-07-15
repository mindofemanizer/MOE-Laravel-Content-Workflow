<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Services;

use Illuminate\Support\Collection;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Models\ContentSchedule;

class ScheduleService
{
    /**
     * @param Publishable $content
     * @param \DateTimeInterface $scheduledAt
     * @param string $action
     * @param array|null $payload
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function create(Publishable $content, \DateTimeInterface $scheduledAt, string $action = 'publish', ?array $payload = null): bool
    {
        if (!$this->isActionValid($action)) {
            throw new \InvalidArgumentException("Invalid schedule action: '{$action}'.");
        }

        if ($scheduledAt <= now()) {
            throw new \InvalidArgumentException('Scheduled time must be in the future.');
        }

        $schedule = new ContentSchedule([
            'content_type' => $content->getMorphClass(),
            'content_id' => $content->getKey(),
            'action' => $action,
            'action_payload' => $payload,
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
            'created_by' => auth()->check() ? auth()->user()->getAuthIdentifier() : null,
        ]);

        return $schedule->save();
    }

    /**
     * @param Publishable $content
     * @param string|null $action
     * @return bool
     */
    public function cancel(Publishable $content, ?string $action = null): bool
    {
        $query = ContentSchedule::forContent($content->getMorphClass(), (string) $content->getKey())
            ->pending();

        if ($action) {
            $query->byAction($action);
        }

        $cancelled = 0;

        foreach ($query->cursor() as $schedule) {
            $schedule->markCancelled();
            $cancelled++;
        }

        return $cancelled > 0;
    }

    /**
     * @return int
     */
    public function executePending(): int
    {
        $executed = 0;

        ContentSchedule::due()->chunk(50, function ($schedules) use (&$executed) {
            foreach ($schedules as $schedule) {
                try {
                    $this->executeSchedule($schedule);
                    $executed++;
                } catch (\Throwable $e) {
                    $schedule->markFailed($e->getMessage());
                }
            }
        });

        return $executed;
    }

    /**
     * @param ContentSchedule $schedule
     * @return void
     * @throws \InvalidArgumentException
     */
    public function executeSchedule(ContentSchedule $schedule): void
    {
        $content = $schedule->content;

        if (!$content) {
            $schedule->markFailed('Content not found.');

            return;
        }

        if (!$content instanceof Publishable) {
            $schedule->markFailed('Content is not publishable.');

            return;
        }

        switch ($schedule->action) {
            case 'publish':
                $content->setContentStatus('published');
                break;

            case 'unpublish':
                $content->setContentStatus('draft');
                break;

            case 'archive':
                $content->setContentStatus('archived');
                break;

            case 'status_change':
                $targetStatus = $schedule->action_payload['status'] ?? null;
                if ($targetStatus) {
                    $content->setContentStatus($targetStatus);
                }
                break;

            default:
                throw new \InvalidArgumentException("Unknown schedule action: '{$schedule->action}'.");
        }

        $schedule->markExecuted();
    }

    /**
     * @param Publishable $content
     * @return Collection
     */
    public function getPendingSchedules(Publishable $content): Collection
    {
        return $content->contentSchedules()
            ->where('status', 'pending')
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface|null $to
     * @return Collection
     */
    public function getUpcoming(\DateTimeInterface $from, ?\DateTimeInterface $to = null): Collection
    {
        $query = ContentSchedule::pending()
            ->where('scheduled_at', '>=', $from);

        if ($to) {
            $query->where('scheduled_at', '<=', $to);
        }

        return $query->orderBy('scheduled_at')->get();
    }

    /**
     * @param string $action
     * @return bool
     */
    protected function isActionValid(string $action): bool
    {
        return in_array($action, ['publish', 'unpublish', 'archive', 'status_change'], true);
    }
}
