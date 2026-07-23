import { useRef, useState } from "react";

export type UploadedImage = {
  image: string | null;
  imageThumb: string | null;
  imageOg: string | null;
};

export function ImageUploadField({
  initial,
  onChange,
}: {
  initial: UploadedImage;
  onChange: (value: UploadedImage) => void;
}) {
  const [value, setValue] = useState<UploadedImage>(initial);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  async function handleFile(file: File) {
    setError(null);
    setUploading(true);
    try {
      const form = new FormData();
      form.append("file", file);
      const res = await window.axios.post(route("admin.upload"), form);
      const next: UploadedImage = {
        image: res.data.image,
        imageThumb: res.data.imageThumb,
        imageOg: res.data.imageOg,
      };
      setValue(next);
      onChange(next);
    } catch (err: any) {
      setError(err?.response?.data?.error || "Gagal mengunggah gambar. Periksa koneksi.");
    } finally {
      setUploading(false);
    }
  }

  return (
    <div>
      <div className="flex items-center gap-4">
        <div className="relative h-24 w-32 flex-none overflow-hidden rounded-xl bg-espresso-800">
          {value.imageThumb || value.image ? (
            <img
              src={(value.imageThumb || value.image)!}
              alt=""
              className="absolute inset-0 h-full w-full object-cover"
            />
          ) : (
            <span className="flex h-full items-center justify-center font-display text-sm text-cream-100/30">
              GPS
            </span>
          )}
        </div>
        <div>
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            disabled={uploading}
            className="btn-outline !px-4 !py-2 text-sm disabled:opacity-60"
          >
            {uploading ? "Mengunggah…" : "Unggah gambar"}
          </button>
          <input
            ref={inputRef}
            type="file"
            accept="image/jpeg,image/png,image/webp"
            className="hidden"
            onChange={(e) => {
              const file = e.target.files?.[0];
              if (file) handleFile(file);
              e.target.value = "";
            }}
          />
          <p className="mt-1.5 text-xs text-espresso-500">JPEG, PNG, atau WebP, maks 10MB.</p>
          {error ? <p className="mt-1 text-xs text-red-700">{error}</p> : null}
        </div>
      </div>
    </div>
  );
}
