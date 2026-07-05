<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Facades\MoeContent;

class ContentScheduler extends Component
{
    public Publishable $content;
    public string $action = 'publish';
    public ?string $scheduledAt = null;
    public ?string $scheduledTime = null;
    public array $pendingSchedules = [];

    protected $rules = [
        'action' => 'required|in:publish,unpublish,archive',
        'scheduledAt' => 'required|date|after:now',
    ];

    public function mount(Publishable $content): void
    {
        $this->content = $content;
        $this->scheduledAt = now()->addDay()->format('Y-m-d');
        $this->scheduledTime = '09:00';
        $this->refreshSchedules();
    }

    public function schedule(): void
    {
        $this->validate();

        $dateTime = new \DateTime("{$this->scheduledAt} {$this->scheduledTime}");

        try {
            MoeContent::schedule($this->content, $dateTime, $this->action);
            $this->dispatch('content-scheduled', action: $this->action);
            $this->refreshSchedules();
        } catch (\Throwable $e) {
            $this->addError('schedule', $e->getMessage());
        }
    }

    public function cancelSchedule(int $scheduleId): void
    {
        MoeContent::cancelSchedule($this->content);
        $this->refreshSchedules();
    }

    public function refreshSchedules(): void
    {
        $this->pendingSchedules = $this->content->contentSchedules()
            ->where('status', 'pending')
            ->orderBy('scheduled_at')
            ->get()
            ->toArray();
    }

    public function render(): View
    {
        return view('moe-content::livewire.content-scheduler');
    }
}
