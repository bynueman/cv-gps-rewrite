import { useEffect } from "react";
import { useEditor, EditorContent } from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import Link from "@tiptap/extension-link";
import TiptapImage from "@tiptap/extension-image";

const toolbarBtn =
  "rounded-lg px-2.5 py-1.5 text-sm font-semibold text-espresso-700 hover:bg-cream-200 aria-pressed:bg-gold-500/20 aria-pressed:text-gold-700";

export function RichTextEditor({
  value,
  onChange,
  onUploadImage,
}: {
  value: string;
  onChange: (html: string) => void;
  onUploadImage: (file: File) => Promise<string | null>;
}) {
  const editor = useEditor({
    extensions: [
      StarterKit.configure({ heading: { levels: [2, 3] } }),
      Link.configure({ openOnClick: false, autolink: true }),
      TiptapImage,
    ],
    content: value,
    immediatelyRender: false,
    editorProps: {
      attributes: {
        class: "prose-article min-h-[220px] focus:outline-none",
      },
    },
    onUpdate: ({ editor }) => onChange(editor.getHTML()),
  });

  // Keep the editor in sync when the ID/EN tab switches to a different
  // language's value (external prop change, not a user keystroke).
  useEffect(() => {
    if (!editor) return;
    if (editor.getHTML() !== value) {
      editor.commands.setContent(value, { emitUpdate: false });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value, editor]);

  if (!editor) return null;

  function handleImageButton() {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/jpeg,image/png,image/webp";
    input.onchange = async () => {
      const file = input.files?.[0];
      if (!file) return;
      const url = await onUploadImage(file);
      if (url) editor?.chain().focus().setImage({ src: url }).run();
    };
    input.click();
  }

  function setLink() {
    const url = window.prompt("URL tautan:");
    if (!url) return;
    editor?.chain().focus().extendMarkRange("link").setLink({ href: url }).run();
  }

  return (
    <div className="rounded-xl border border-espresso-900/15 bg-cream-50">
      <div className="flex flex-wrap gap-1 border-b border-espresso-900/10 p-2">
        <button type="button" aria-pressed={editor.isActive("bold")} onClick={() => editor.chain().focus().toggleBold().run()} className={toolbarBtn}>
          B
        </button>
        <button type="button" aria-pressed={editor.isActive("italic")} onClick={() => editor.chain().focus().toggleItalic().run()} className={`${toolbarBtn} italic`}>
          I
        </button>
        <button type="button" aria-pressed={editor.isActive("strike")} onClick={() => editor.chain().focus().toggleStrike().run()} className={`${toolbarBtn} line-through`}>
          S
        </button>
        <span className="mx-1 my-1 w-px bg-espresso-900/10" />
        <button type="button" aria-pressed={editor.isActive("heading", { level: 2 })} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} className={toolbarBtn}>
          H2
        </button>
        <button type="button" aria-pressed={editor.isActive("heading", { level: 3 })} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()} className={toolbarBtn}>
          H3
        </button>
        <span className="mx-1 my-1 w-px bg-espresso-900/10" />
        <button type="button" aria-pressed={editor.isActive("bulletList")} onClick={() => editor.chain().focus().toggleBulletList().run()} className={toolbarBtn}>
          • List
        </button>
        <button type="button" aria-pressed={editor.isActive("orderedList")} onClick={() => editor.chain().focus().toggleOrderedList().run()} className={toolbarBtn}>
          1. List
        </button>
        <button type="button" aria-pressed={editor.isActive("blockquote")} onClick={() => editor.chain().focus().toggleBlockquote().run()} className={toolbarBtn}>
          Kutipan
        </button>
        <span className="mx-1 my-1 w-px bg-espresso-900/10" />
        <button type="button" aria-pressed={editor.isActive("link")} onClick={setLink} className={toolbarBtn}>
          Tautan
        </button>
        <button type="button" onClick={handleImageButton} className={toolbarBtn}>
          Gambar
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().undo().run()}
          className={toolbarBtn}
        >
          ↶
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().redo().run()}
          className={toolbarBtn}
        >
          ↷
        </button>
      </div>
      <EditorContent editor={editor} className="px-4 py-3" />
    </div>
  );
}
