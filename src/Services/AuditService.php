<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Models\ContentAudit;

class AuditService
{
    public function log(Publishable $content, string $action, ?array $payload = null): bool
    {
        $field = null;
        $oldValue = null;
        $newValue = null;

        if ($payload) {
            $field = $payload['field'] ?? null;
            $oldValue = $payload['old_value'] ?? null;
            $newValue = $payload['new_value'] ?? null;
        }

        $request = request();

        $audit = new ContentAudit([
            'content_type' => $content->getMorphClass(),
            'content_id' => $content->getKey(),
            'action' => $action,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'snapshot' => $content->getContentData(),
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
            'url' => $request ? $request->fullUrl() : null,
            'method' => $request ? $request->method() : null,
            'user_id' => auth()->check() ? auth()->user()->getAuthIdentifier() : null,
        ]);

        return $audit->save();
    }

    public function logFieldChange(Publishable $content, string $field, mixed $oldValue, mixed $newValue): bool
    {
        return $this->log($content, 'updated', [
            'field' => $field,
            'old_value' => is_string($oldValue) ? $oldValue : json_encode($oldValue),
            'new_value' => is_string($newValue) ? $newValue : json_encode($newValue),
        ]);
    }

    public function logStatusChange(Publishable $content, string $from, string $to, ?string $reason = null): bool
    {
        $payload = [
            'field' => 'status',
            'old_value' => $from,
            'new_value' => $to,
        ];

        if ($reason) {
            $payload['reason'] = $reason;
        }

        return $this->log($content, 'status_changed', $payload);
    }

    public function getTrail(Publishable $content): Collection
    {
        return $content->contentAudits()
            ->orderByDesc('created_at')
            ->get();
    }

    public function getTrailForAction(Publishable $content, string $action): Collection
    {
        return $content->contentAudits()
            ->where('action', $action)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getRecent(int $limit = 50): Collection
    {
        return ContentAudit::orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getByUser(int $userId, int $limit = 50): Collection
    {
        return ContentAudit::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function cleanup(): int
    {
        $retention = config('content-workflow.audit.log_retention_days', 365);

        if ($retention <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($retention);

        return ContentAudit::where('created_at', '<', $cutoff)->delete();
    }
}
