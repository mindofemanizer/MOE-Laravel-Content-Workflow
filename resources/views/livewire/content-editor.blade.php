<div wire:ignore x-data="{
    editor: null,
    content: @js($editorContent ?? ''),
    init() {
        if (window.MoeContentEditor) {
            const hiddenInput = this.$refs.hiddenInput;
            this.editor = window.MoeContentEditor.create(this.$refs.editor, {
                content: this.content,
                placeholder: @js($placeholder ?? 'Start writing...'),
                hiddenInput,
                onUpdate(html) {
                    @this.set('editorContent', html);
                },
            });
        } else {
            this.loadCdnEditor();
        }
    },
    loadCdnEditor() {
        const editor = this.$refs.editor;
        if (this.content) editor.innerHTML = this.content;
        editor.contentEditable = true;
    },
    updateContent(e) {
        this.content = e.target.innerHTML;
        @this.set('editorContent', this.content);
    },
    destroy() {
        if (this.editor) this.editor.destroy();
    },
}">
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif

    <div class="border border-gray-300 rounded-lg overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-300 px-3 py-2 flex flex-wrap gap-1" x-show="showToolbar">
            <template x-if="editor">
                <div class="flex flex-wrap gap-1">
                    <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ 'bg-gray-200': editor.isActive('bold') }" class="p-1 rounded hover:bg-gray-200" title="Bold"><strong>B</strong></button>
                    <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ 'bg-gray-200': editor.isActive('italic') }" class="p-1 rounded hover:bg-gray-200" title="Italic"><em>I</em></button>
                    <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="{ 'bg-gray-200': editor.isActive('heading', { level: 3 }) }" class="p-1 rounded hover:bg-gray-200" title="Heading">H</button>
                    <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'bg-gray-200': editor.isActive('bulletList') }" class="p-1 rounded hover:bg-gray-200" title="Bullet list">&bull;</button>
                    <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'bg-gray-200': editor.isActive('orderedList') }" class="p-1 rounded hover:bg-gray-200" title="Ordered list">1.</button>
                    <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="{ 'bg-gray-200': editor.isActive('blockquote') }" class="p-1 rounded hover:bg-gray-200" title="Quote">"</button>
                    <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="p-1 rounded hover:bg-gray-200" title="Horizontal rule">---</button>
                    <button type="button" @click="editor.chain().focus().unsetAllMarks().clearNodes().run()" class="p-1 rounded hover:bg-gray-200" title="Clear formatting">&times;</button>
                    <button type="button" @click="editor.chain().focus().undo().run()" class="p-1 rounded hover:bg-gray-200" title="Undo">&larr;</button>
                    <button type="button" @click="editor.chain().focus().redo().run()" class="p-1 rounded hover:bg-gray-200" title="Redo">&rarr;</button>
                </div>
            </template>
            <template x-if="!editor">
                <div class="flex flex-wrap gap-1">
                    <button type="button" @click="document.execCommand('bold')" class="p-1 rounded hover:bg-gray-200"><strong>B</strong></button>
                    <button type="button" @click="document.execCommand('italic')" class="p-1 rounded hover:bg-gray-200"><em>I</em></button>
                    <button type="button" @click="document.execCommand('insertUnorderedList')" class="p-1 rounded hover:bg-gray-200">&bull;</button>
                    <button type="button" @click="document.execCommand('insertOrderedList')" class="p-1 rounded hover:bg-gray-200">1.</button>
                </div>
            </template>
        </div>

        <div
            x-ref="editor"
            x-show="!editor"
            @input="updateContent"
            class="min-h-[300px] p-4 prose prose-sm max-w-none focus:outline-none"
        ></div>

        <div
            x-ref="editor"
            x-show="editor"
            class="min-h-[300px] p-4 prose prose-sm max-w-none focus:outline-none"
        ></div>

        <input type="hidden" x-ref="hiddenInput" name="{{ $field }}" :value="content" />
    </div>
</div>
