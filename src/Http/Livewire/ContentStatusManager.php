<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Facades\MoeContent;

class ContentStatusManager extends Component
{
    public Publishable $content;
    public array $availableTransitions = [];
    public ?string $reason = null;
    public bool $showReason = false;

    /**
     * @param Publishable $content
     * @return void
     */
    public function mount(Publishable $content): void
    {
        $this->content = $content;
        $this->refreshTransitions();
    }

    /**
     * @param string $toStatus
     * @return void
     */
    public function changeStatus(string $toStatus): void
    {
        try {
            MoeContent::transition($this->content, $toStatus, $this->reason);
            $this->dispatch('content-status-changed', status: $toStatus);
            $this->refreshTransitions();
            $this->showReason = false;
            $this->reason = null;
        } catch (\Throwable $e) {
            $this->addError('transition', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function refreshTransitions(): void
    {
        $this->availableTransitions = MoeContent::getAvailableTransitions($this->content)->toArray();
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('moe-content::livewire.content-status-manager', [
            'currentStatus' => MoeContent::renderStatus($this->content),
            'statusInfo' => app(\MOE\ContentWorkflow\Services\StateMachineService::class)
                ->getStatusInfo($this->content->getContentStatus()),
        ]);
    }
}
