<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use MOE\ContentWorkflow\Contracts\Publishable;
use MOE\ContentWorkflow\Facades\MoeContent;
use MOE\ContentWorkflow\Models\ContentSchedule;

class ContentScheduler extends Component
{
    public Publishable $content;

    #[Validate('required|in:publish,unpublish,archive')]
    public string $action = 'publish';

    #[Validate('required|date|after:now')]
    public ?string $scheduledAt = null;

    public ?string $scheduledTime = null;
    public array $pendingSchedules = [];

    /**
     * @param Publishable $content
     * @return void
     */
    public function mount(Publishable $content): void
    {
        $this->content = $content;
        $this->scheduledAt = now()->addDay()->format('Y-m-d');
        $this->scheduledTime = '09:00';
        $this->refreshSchedules();
    }

    /**
     * @return void
     */
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

    /**
     * @param int $scheduleId
     * @return void
     */
    public function cancelSchedule(int $scheduleId): void
    {
        ContentSchedule::findOrFail($scheduleId)->markCancelled();
        $this->refreshSchedules();
    }

    /**
     * @return void
     */
    public function refreshSchedules(): void
    {
        $this->pendingSchedules = $this->content->contentSchedules()
            ->where('status', 'pending')
            ->orderBy('scheduled_at')
            ->get()
            ->toArray();
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('moe-content::livewire.content-scheduler');
    }
}
