<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Services;

use Illuminate\Support\Collection;
use MOE\ContentWorkflow\Contracts\Publishable;

class StateMachineService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('content-workflow');
    }

    public function transition(Publishable $content, string $toStatus, ?string $reason = null): bool
    {
        $fromStatus = $content->getContentStatus();

        if (!$this->isTransitionAllowed($fromStatus, $toStatus)) {
            throw new \InvalidArgumentException(
                "Transition from '{$fromStatus}' to '{$toStatus}' is not allowed."
            );
        }

        if (!$content->canPerformAction("transition:{$toStatus}")) {
            throw new \RuntimeException("User not authorized to perform this transition.");
        }

        $content->setContentStatus($toStatus);

        if ($toStatus === 'published' && $fromStatus !== 'published') {
            $content->setPublishedAt(now());
        } elseif ($fromStatus === 'published' && $toStatus !== 'published') {
            // unpublish handled via observer
        }

        return true;
    }

    public function canTransition(Publishable $content, string $toStatus): bool
    {
        try {
            return $this->isTransitionAllowed($content->getContentStatus(), $toStatus)
                && $content->canPerformAction("transition:{$toStatus}");
        } catch (\Throwable) {
            return false;
        }
    }

    public function getAvailableTransitions(Publishable $content): Collection
    {
        $fromStatus = $content->getContentStatus();
        $allowed = $this->config['transitions'][$fromStatus] ?? [];

        return collect($allowed)->filter(function ($toStatus) use ($content) {
            return $content->canPerformAction("transition:{$toStatus}");
        })->values();
    }

    public function getStatusInfo(string $status): ?array
    {
        return $this->config['statuses'][$status] ?? null;
    }

    public function getAllStatuses(): Collection
    {
        return collect($this->config['statuses']);
    }

    public function renderStatusBadge(Publishable $content): string
    {
        $status = $content->getContentStatus();
        $info = $this->getStatusInfo($status);

        $label = $info['label'] ?? ucfirst($status);
        $color = $info['color'] ?? 'gray';

        $colorMap = [
            'gray' => 'bg-gray-100 text-gray-800',
            'yellow' => 'bg-yellow-100 text-yellow-800',
            'green' => 'bg-green-100 text-green-800',
            'red' => 'bg-red-100 text-red-800',
            'blue' => 'bg-blue-100 text-blue-800',
            'indigo' => 'bg-indigo-100 text-indigo-800',
            'purple' => 'bg-purple-100 text-purple-800',
            'pink' => 'bg-pink-100 text-pink-800',
        ];

        $class = $colorMap[$color] ?? $colorMap['gray'];

        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}\">"
            . e($label) . '</span>';
    }

    public function isTransitionAllowed(string $from, string $to): bool
    {
        $transitions = $this->config['transitions'] ?? [];

        if (!isset($transitions[$from])) {
            return false;
        }

        return in_array($to, $transitions[$from], true);
    }

    public function isValidStatus(string $status): bool
    {
        return isset($this->config['statuses'][$status]);
    }
}
