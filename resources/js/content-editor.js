import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';

window.MoeContentEditor = {
    create(element, options = {}) {
        return new Editor({
            element,
            extensions: [
                StarterKit,
                Image,
                Link.configure({
                    openOnClick: false,
                }),
                Placeholder.configure({
                    placeholder: options.placeholder || 'Start writing...',
                }),
            ],
            content: options.content || '',
            onUpdate({ editor }) {
                const hiddenInput = options.hiddenInput;
                if (hiddenInput) {
                    hiddenInput.value = editor.getHTML();
                }
                if (options.onUpdate) {
                    options.onUpdate(editor.getHTML());
                }
            },
        });
    },
};
