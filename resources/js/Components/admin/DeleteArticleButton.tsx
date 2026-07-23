import { useState } from "react";
import { router } from "@inertiajs/react";
import { TrashIcon } from "@/Components/admin/icons";

export function DeleteArticleButton({ id, title }: { id: number; title: string }) {
  const [pending, setPending] = useState(false);

  function handleDelete() {
    if (!window.confirm(`Hapus artikel "${title}"? Tindakan ini tidak bisa dibatalkan.`)) return;
    setPending(true);
    router.delete(route("admin.articles.destroy", id), {
      preserveScroll: true,
      onFinish: () => setPending(false),
    });
  }

  return (
    <button
      type="button"
      onClick={handleDelete}
      disabled={pending}
      title="Hapus"
      className="inline-flex items-center gap-1.5 text-sm font-semibold text-red-700 hover:underline disabled:opacity-60"
    >
      <TrashIcon className="h-4 w-4" />
      {pending ? "Menghapus…" : "Hapus"}
    </button>
  );
}
