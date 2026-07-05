<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Facades\MoeContent;

class ContentAuditLog extends Component
{
    public Publishable $content;
    public array $audits = [];
    public ?string $filter = null;
    public int $perPage = 20;

    public function mount(Publishable $content): void
    {
        $this->content = $content;
        $this->refreshAudits();
    }

    public function filterBy(?string $action): void
    {
        $this->filter = $action;
        $this->refreshAudits();
    }

    public function refreshAudits(): void
    {
        $query = $this->content->contentAudits()->orderByDesc('created_at');

        if ($this->filter) {
            $query->where('action', $this->filter);
        }

        $this->audits = $query->take($this->perPage)->get()->toArray();
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
        $this->refreshAudits();
    }

    public function render(): View
    {
        $actions = $this->content->contentAudits()
            ->select('action')
            ->distinct()
            ->pluck('action')
            ->toArray();

        return view('moe-content::livewire.content-audit-log', [
            'availableFilters' => $actions,
        ]);
    }
}
