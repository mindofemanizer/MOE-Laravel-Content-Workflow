<?php

declare(strict_types=1);

namespace MOE\ContentWorkflow\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
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

    public function mount(Publishable $content, string $field = 'content', ?string $label = null): void
    {
        $this->content = $content;
        $this->field = $field;
        $this->label = $label ?? ucfirst($field);
        $this->editorContent = $content->getAttribute($field) ?? '';
    }

    #[On('editorImageUpload')]
    public function handleImageUpload(): void
    {
        // Handle image upload from editor
    }

    public function updatedEditorContent(): void
    {
        $this->editorContent = $this->sanitizeHtml($this->editorContent);
        $this->content->setAttribute($this->field, $this->editorContent);
        $this->content->save();
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<[^>]*on\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace('/<[^>]*on\w+\s*=\s*\'[^\']*\'/i', '', $html);
        $html = preg_replace('/<[^>]*on\w+\s*=\s*\S+/i', '', $html);
        $html = str_ireplace('javascript:', '', $html);

        $allowedTags = config('content-workflow.editor.allowed_tags',
            '<p><br><strong><em><u><s><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><pre><code><hr><a><img><table><thead><tbody><tr><th><td><span><div>'
        );

        return strip_tags($html, $allowedTags);
    }

    public function render(): View
    {
        return view('moe-content::livewire.content-editor');
    }
}
