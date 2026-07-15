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

    /**
     * @param Publishable $content
     * @return void
     */
    public function mount(Publishable $content): void
    {
        $this->content = $content;
        $this->refreshAudits();
    }

    /**
     * @param string|null $action
     * @return void
     */
    public function filterBy(?string $action): void
    {
        $this->filter = $action;
        $this->refreshAudits();
    }

    /**
     * @return void
     */
    public function refreshAudits(): void
    {
        $query = $this->content->contentAudits()->orderByDesc('created_at');

        if ($this->filter) {
            $query->where('action', $this->filter);
        }

        $this->audits = $query->take($this->perPage)->get()->toArray();
    }

    /**
     * @return void
     */
    public function loadMore(): void
    {
        $this->perPage += 20;
        $this->refreshAudits();
    }

    /**
     * @return View
     */
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
