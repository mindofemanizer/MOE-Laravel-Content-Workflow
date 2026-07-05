<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Services;

use Illuminate\Support\Collection;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Models\ContentVersion;

class VersioningService
{
    public function create(Publishable $content, ?string $label = null): bool
    {
        $maxVersions = config('content-workflow.versioning.max_versions', 50);
        $lastVersion = $content->contentVersions()->max('version_number') ?? 0;

        if ($lastVersion >= $maxVersions) {
            return false;
        }

        $this->markAllAsNotCurrent($content);

        $version = new ContentVersion([
            'content_type' => $content->getMorphClass(),
            'content_id' => $content->getKey(),
            'version_number' => $lastVersion + 1,
            'version_label' => $label,
            'data' => $content->getContentData(),
            'is_current' => true,
            'created_by' => auth()->check() ? auth()->user()->getAuthIdentifier() : null,
        ]);

        $result = $version->save();

        $this->cleanup($content);

        return $result;
    }

    public function restore(Publishable $content, int $versionNumber): bool
    {
        $version = $content->contentVersions()
            ->where('version_number', $versionNumber)
            ->first();

        if (!$version) {
            throw new \InvalidArgumentException("Version #{$versionNumber} not found.");
        }

        $this->create($content, "Restored from version #{$versionNumber}");

        $restored = $content->restoreFromData($version->data);

        $version->update([
            'restored_at' => now(),
            'restored_by' => auth()->check() ? auth()->user()->getAuthIdentifier() : null,
        ]);

        return $restored;
    }

    public function getVersions(Publishable $content): Collection
    {
        return $content->contentVersions()
            ->orderByDesc('version_number')
            ->get();
    }

    public function getCurrentVersion(Publishable $content): ?ContentVersion
    {
        return $content->contentVersions()
            ->where('is_current', true)
            ->first();
    }

    public function getVersion(Publishable $content, int $versionNumber): ?ContentVersion
    {
        return $content->contentVersions()
            ->where('version_number', $versionNumber)
            ->first();
    }

    public function diff(Publishable $content, int $fromVersion, int $toVersion): array
    {
        $from = $this->getVersion($content, $fromVersion);
        $to = $this->getVersion($content, $toVersion);

        if (!$from || !$to) {
            throw new \InvalidArgumentException('One or both versions not found.');
        }

        $fromData = $from->data ?? [];
        $toData = $to->data ?? [];

        $changes = [];

        foreach ($toData as $key => $value) {
            if (!array_key_exists($key, $fromData)) {
                $changes[$key] = ['old' => null, 'new' => $value];
            } elseif ($fromData[$key] !== $value) {
                $changes[$key] = ['old' => $fromData[$key], 'new' => $value];
            }
        }

        foreach ($fromData as $key => $value) {
            if (!array_key_exists($key, $toData)) {
                $changes[$key] = ['old' => $value, 'new' => null];
            }
        }

        return $changes;
    }

    protected function markAllAsNotCurrent(Publishable $content): void
    {
        $content->contentVersions()
            ->where('is_current', true)
            ->update(['is_current' => false]);
    }

    protected function cleanup(Publishable $content): void
    {
        if (!config('content-workflow.versioning.cleanup_old', true)) {
            return;
        }

        $maxVersions = config('content-workflow.versioning.max_versions', 50);

        $excess = $content->contentVersions()
            ->orderByDesc('version_number')
            ->skip($maxVersions)
            ->take(100)
            ->pluck('id');

        if ($excess->isNotEmpty()) {
            ContentVersion::whereIn('id', $excess)->delete();
        }
    }
}
