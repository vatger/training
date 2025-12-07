import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import { useCallback, useEffect } from 'react';
import { Bold, Italic, List, ListOrdered, Heading1, Heading2, Heading3, Image, Strikethrough } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Image as ImageExtension } from '@tiptap/extension-image';

interface WYSIWYGEditorProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    minHeight?: string;
}

const MenuButton = ({
    onClick,
    isActive = false,
    children,
    title,
}: {
    onClick: () => void;
    isActive?: boolean;
    children: React.ReactNode;
    title: string;
}) => (
    <button
        type="button"
        onClick={onClick}
        title={title}
        className={cn('rounded p-2 transition-colors hover:bg-muted', isActive && 'bg-muted text-primary')}
    >
        {children}
    </button>
);

// Isolated toolbar component to prevent re-renders
const EditorToolbar = ({ editor, addImage }: { editor: any; addImage: () => void }) => {
    if (!editor) return null;

    return (
        <div className="flex flex-wrap gap-1 border-b bg-muted/30 p-2">
            <MenuButton onClick={() => editor.chain().focus().toggleBold().run()} isActive={editor.isActive('bold')} title="Bold">
                <Bold className="h-4 w-4" />
            </MenuButton>
            <MenuButton onClick={() => editor.chain().focus().toggleItalic().run()} isActive={editor.isActive('italic')} title="Italic">
                <Italic className="h-4 w-4" />
            </MenuButton>
            <MenuButton onClick={() => editor.chain().focus().toggleStrike().run()} isActive={editor.isActive('strike')} title="Strikethrough">
                <Strikethrough className="h-4 w-4" />
            </MenuButton>

            <div className="mx-1 w-px bg-border" />

            <MenuButton
                onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
                isActive={editor.isActive('heading', { level: 1 })}
                title="Heading 1"
            >
                <Heading1 className="h-4 w-4" />
            </MenuButton>
            <MenuButton
                onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
                isActive={editor.isActive('heading', { level: 2 })}
                title="Heading 2"
            >
                <Heading2 className="h-4 w-4" />
            </MenuButton>
            <MenuButton
                onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
                isActive={editor.isActive('heading', { level: 3 })}
                title="Heading 3"
            >
                <Heading3 className="h-4 w-4" />
            </MenuButton>

            <div className="mx-1 w-px bg-border" />

            <MenuButton onClick={() => editor.chain().focus().toggleBulletList().run()} isActive={editor.isActive('bulletList')} title="Bullet List">
                <List className="h-4 w-4" />
            </MenuButton>
            <MenuButton
                onClick={() => editor.chain().focus().toggleOrderedList().run()}
                isActive={editor.isActive('orderedList')}
                title="Numbered List"
            >
                <ListOrdered className="h-4 w-4" />
            </MenuButton>

            <div className="mx-1 w-px bg-border" />

            <MenuButton onClick={addImage} title="Add Image">
                <Image className="h-4 w-4" />
            </MenuButton>
        </div>
    );
};

export function WYSIWYGEditor({ value, onChange, placeholder = 'Start writing...', minHeight = '150px' }: WYSIWYGEditorProps) {
    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3],
                },
            }),
            Placeholder.configure({
                placeholder,
            }),
            ImageExtension,
        ],
        content: value,
        editorProps: {
            attributes: {
                class: 'prose prose-sm max-w-none focus:outline-none',
            },
        },
        onUpdate: ({ editor }) => {
            const html = editor.getHTML();
            onChange(html);
        },
        // Critical performance optimization - don't re-render on every transaction
        immediatelyRender: false,
        shouldRerenderOnTransaction: false,
    });

    // Only update content when value prop changes externally (not from typing)
    useEffect(() => {
        if (editor && value !== editor.getHTML()) {
            const { from, to } = editor.state.selection;
            editor.commands.setContent(value, false);
            // Restore cursor position
            editor.commands.setTextSelection({ from, to });
        }
    }, [value, editor]);

    const addImage = useCallback(() => {
        const url = window.prompt('URL');

        if (url) {
            editor?.chain().focus().setImage({ src: url }).run();
        }
    }, [editor]);

    if (!editor) {
        return null;
    }

    return (
        <div className="overflow-hidden rounded-lg border">
            <EditorToolbar editor={editor} addImage={addImage} />

            {/* Editor Content */}
            <div className="min-h-[var(--min-height)] bg-white p-4 dark:bg-gray-950" style={{ '--min-height': minHeight } as React.CSSProperties}>
                <EditorContent editor={editor} />
            </div>

            <style
                dangerouslySetInnerHTML={{
                    __html: `
                .ProseMirror {
                    min-height: ${minHeight};
                }

                .ProseMirror p.is-editor-empty:first-child::before {
                    color: #adb5bd;
                    content: attr(data-placeholder);
                    float: left;
                    height: 0;
                    pointer-events: none;
                }

                .ProseMirror:focus {
                    outline: none;
                }

                .ProseMirror h1 {
                    font-size: 2em;
                    font-weight: 700;
                    margin-top: 0.67em;
                    margin-bottom: 0.67em;
                    line-height: 1.2;
                }

                .ProseMirror h2 {
                    font-size: 1.5em;
                    font-weight: 600;
                    margin-top: 0.83em;
                    margin-bottom: 0.83em;
                    line-height: 1.3;
                }

                .ProseMirror h3 {
                    font-size: 1.17em;
                    font-weight: 600;
                    margin-top: 1em;
                    margin-bottom: 1em;
                    line-height: 1.4;
                }

                .ProseMirror p {
                    margin-top: 0.5em;
                    margin-bottom: 0.5em;
                }

                .ProseMirror ul,
                .ProseMirror ol {
                    padding-left: 1.5em;
                    margin-top: 0.5em;
                    margin-bottom: 0.5em;
                }

                .ProseMirror ul {
                    list-style-type: disc;
                }

                .ProseMirror ol {
                    list-style-type: decimal;
                }

                .ProseMirror li {
                    margin-top: 0.25em;
                    margin-bottom: 0.25em;
                }

                .ProseMirror blockquote {
                    border-left: 3px solid #e5e7eb;
                    padding-left: 1em;
                    color: #6b7280;
                    font-style: italic;
                    margin: 1em 0;
                }

                .ProseMirror code {
                    background-color: #f3f4f6;
                    padding: 0.125em 0.25em;
                    border-radius: 0.25em;
                    font-size: 0.875em;
                    font-family: monospace;
                }

                .ProseMirror pre {
                    background-color: #1f2937;
                    color: #f9fafb;
                    padding: 1em;
                    border-radius: 0.5em;
                    overflow-x: auto;
                    margin: 1em 0;
                }

                .ProseMirror pre code {
                    background-color: transparent;
                    color: inherit;
                    padding: 0;
                    font-size: 0.875em;
                }

                .ProseMirror hr {
                    border: none;
                    border-top: 2px solid #e5e7eb;
                    margin: 2em 0;
                }

                .ProseMirror strong {
                    font-weight: 600;
                }

                .ProseMirror em {
                    font-style: italic;
                }
            `,
                }}
            />
        </div>
    );
}