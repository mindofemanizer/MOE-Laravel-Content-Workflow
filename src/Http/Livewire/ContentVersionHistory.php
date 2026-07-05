<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Facades\MoeContent;

class ContentVersionHistory extends Component
{
    public Publishable $content;
    public array $versions = [];
    public ?int $comparingFrom = null;
    public ?int $comparingTo = null;
    public ?array $diff = null;
    public bool $showDiff = false;

    public function mount(Publishable $content): void
    {
        $this->content = $content;
        $this->refreshVersions();
    }

    public function restore(int $versionNumber): void
    {
        try {
            MoeContent::restoreVersion($this->content, $versionNumber);
            $this->dispatch('content-restored', version: $versionNumber);
            $this->refreshVersions();
        } catch (\Throwable $e) {
            $this->addError('restore', $e->getMessage());
        }
    }

    public function compare(int $from, int $to): void
    {
        $this->comparingFrom = $from;
        $this->comparingTo = $to;
        $this->diff = app(\MOE\ContentWorkflow\Services\VersioningService::class)
            ->diff($this->content, $from, $to);
        $this->showDiff = true;
    }

    public function hideDiff(): void
    {
        $this->showDiff = false;
        $this->diff = null;
        $this->comparingFrom = null;
        $this->comparingTo = null;
    }

    public function refreshVersions(): void
    {
        $this->versions = MoeContent::getVersions($this->content)->toArray();
    }

    public function render(): View
    {
        return view('moe-content::livewire.content-version-history');
    }
}
