import { useState } from "react";
import { router } from "@inertiajs/react";
import { Trash2 } from "lucide-react";
import { ConfirmDialog } from "@/Components/admin/ConfirmDialog";
import { useToast } from "@/Components/admin/Toast";

/**
 * Generic destructive-delete button for admin list rows — routes every
 * module's delete action through the same styled confirm dialog (naming
 * the item) and toast feedback, instead of window.confirm().
 */
export function DeleteButton({
  itemLabel,
  routeName,
  routeParams,
  label = "Hapus",
  className = "inline-flex items-center gap-1.5 text-sm font-semibold text-red-700 hover:underline",
  onSuccess,
}: {
  /** Human-readable label used in the confirm copy and toast, e.g. `artikel "Panen Raya Singkong"`. */
  itemLabel: string;
  routeName: string;
  routeParams?: string | number | (string | number)[];
  label?: string;
  className?: string;
  onSuccess?: () => void;
}) {
  const [open, setOpen] = useState(false);
  const [pending, setPending] = useState(false);
  const toast = useToast();

  function handleConfirm() {
    setPending(true);
    router.delete(route(routeName, routeParams), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(`${itemLabel} berhasil dihapus.`);
        onSuccess?.();
      },
      onError: () => toast.error(`Gagal menghapus ${itemLabel}.`),
      onFinish: () => {
        setPending(false);
        setOpen(false);
      },
    });
  }

  return (
    <>
      <button type="button" onClick={() => setOpen(true)} title={label} className={className}>
        <Trash2 className="h-4 w-4" />
        {label}
      </button>
      <ConfirmDialog
        open={open}
        title="Hapus item ini?"
        description={`${itemLabel} akan dihapus secara permanen. Tindakan ini tidak bisa dibatalkan.`}
        pending={pending}
        onConfirm={handleConfirm}
        onCancel={() => setOpen(false)}
      />
    </>
  );
}
