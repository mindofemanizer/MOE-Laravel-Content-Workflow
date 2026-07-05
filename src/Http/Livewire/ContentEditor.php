<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use MOE\ContentWorkflow\Contracts\Publishable;

class ContentEditor extends Component
{
    use WithFileUploads;

    public Publishable $content;
    public string $field = 'content';
    public string $editorContent = '';
    public ?string $label = null;
    public ?string $placeholder = null;
    public bool $showToolbar = true;

    protected $listeners = [
        'editorImageUpload' => 'handleImageUpload',
    ];

    public function mount(Publishable $content, string $field = 'content', ?string $label = null): void
    {
        $this->content = $content;
        $this->field = $field;
        $this->label = $label ?? ucfirst($field);
        $this->editorContent = $content->getAttribute($field) ?? '';
    }

    public function updatedEditorContent(): void
    {
        $this->content->setAttribute($this->field, $this->editorContent);
    }

    public function render(): View
    {
        return view('moe-content::livewire.content-editor');
    }
}
