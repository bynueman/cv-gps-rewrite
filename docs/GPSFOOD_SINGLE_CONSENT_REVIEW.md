# Ruang Cerita Kuicip — satu persetujuan (5 November 2026)

Perubahan lokal pada `main`, melanjutkan retirement token dalam commit
`0c6a82664e8b13127fd799d12e232b301766ddc9`. Tidak ada akses/deployment produksi,
commit/push, perubahan `.env`, migration, atau pembaruan massal data.

## Perilaku dan bukti consent

- Sebelumnya: checkbox kebijakan wajib, checkbox publikasi wajib untuk Publik,
  dan checkbox opsional media sosial. Sekarang: tepat satu checkbox kebijakan
  wajib, dengan tautan Panduan Komunitas dan Pemberitahuan Privasi. Tautan
  membuka panel kebijakan dan menuju judul terkait.
- Teks ID: “Saya telah membaca dan menyetujui Panduan Komunitas dan
  Pemberitahuan Privasi Ruang Cerita Kuicip.”
- Teks EN: “I have read and agree to the Kuicip Story Space Community
  Guidelines and Privacy Notice.”
- Radio Publik secara eksplisit memberi izin publikasi hanya setelah persetujuan
  admin. Payload mengirim `publication_consent` berdasarkan pilihan tersebut;
  server tetap mensyaratkan nilai accepted untuk Publik. Checkbox kebijakan
  tetap required/accepted untuk kedua pilihan.
- Kiriman baru selalu pending. Feed tetap memerlukan visibility public, status
  approved, publication_consent true dan published_at. Moderasi tidak mengubah
  visibility atau consent; approval Privat tidak menetapkan published_at.
- `policy_version`, waktu `consented_at`, publication_consent, visibility dan
  request hash tetap menjadi bukti keputusan kiriman. Versi aktif wajib cocok.
- Form tidak meminta/mengirim izin media sosial. Server selalu menyimpan
  `social_consent=false` pada kiriman baru, termasuk payload yang memaksakan true.
  Penggunaan eksternal memerlukan izin terpisah yang sah. Rekaman izin lama
  tidak diubah; retry menggunakan consent historis untuk mencocokkan hash asli,
  tanpa membuat consent baru atau mengubah record.
- CAPTCHA, honeypot, pembatasan kiriman, duplicate protection dan story code
  tetap memakai alur yang ada. Pembaruan CAPTCHA hanya mengubah state verifikasi;
  body/visibility/checkbox tidak direset oleh tombol pembaruan soal.

## Token yang sudah dipensiunkan

Implementasi retirement yang ada dipertahankan: response baru/retry hanya
`code` dan `accepted`, hash baru null, hash lama disembunyikan, receipt tidak
merender token bahkan bila input receipt mengandung properti legacy. POST
removal mengembalikan 410 tanpa query/mutasi cerita. Redaksi superadmin tetap
memerlukan review, konfirmasi kode dan referensi permintaan, serta menjaga
consent, hash lama dan riwayat keputusan.

## Langkah konfigurasi produksi MANUAL setelah review pemilik

Versi yang disiapkan: `kuicip-v4-single-consent-2026-11-05`.

1. Pemilik meninjau wording ID/EN dan perilaku Publik/Privat ini.
2. Saat deployment terpisah yang diotorisasi, operator mengubah hanya nilai
   berikut di `.env` produksi: `STORIES_POLICY_VERSION=kuicip-v4-single-consent-2026-11-05`.
   Jangan menimpa konfigurasi provider, key, database, retensi atau flag lainnya.
   Untuk menerima kiriman, `STORIES_ENABLED` dan `STORIES_POLICIES_APPROVED`
   tetap harus true berdasarkan persetujuan operasional pemilik.
3. Jalankan `php artisan optimize:clear` lalu `php artisan config:cache`,
   `php artisan route:cache`, dan `php artisan view:cache` dalam deployment tersebut.
   Sertakan hasil build lokal `public/build`; jangan menjalankan npm di produksi.
4. Jangan mengubah policy_version/consent/hash pada record historis. Tidak perlu
   migration. Kode baru menutup penerimaan kiriman produksi bila versi konfigurasi
   masih lama; moderasi historis tetap memakai aturan yang ada.

Panduan rilis v3 tetap menjadi catatan historis, bukan versi kebijakan untuk
form baru ini. Tidak ada bukti versi aset produksi yang diambil dalam tugas ini:
screenshot pemilik yang menggambarkan tiga checkbox menunjukkan UI sebelum
perubahan ini, tetapi tidak membuktikan release mana yang sudah terpasang.

## Validasi

Hasil pengujian lokal:

- `php artisan test --filter="Story|ReleaseReadiness"`: exit 0, 65 tes,
  922 assertion, tanpa kegagalan. Terdapat 65 warning pembacaan `.env` yang
  memang tidak ada pada direktori sementara tes; bukan `.env` produksi.
  Eksekusi memerlukan akses di luar sandbox karena `is_readable` PHP pada
  bootstrap gagal di sandbox Windows.
- `node --test tests/frontend/*.test.mjs`: 34/34 lulus. Render server pada
  fixture produk yang sudah ada mengeluarkan warning React useLayoutEffect.
- `node node_modules/typescript/bin/tsc --noEmit`: lulus.
- `php vendor/bin/pint --test`: lulus.
- `npm run build`: lulus; Vite memperingatkan dua referensi font WOFF2 yang
  baru akan di-resolve saat runtime. Ketersediaan font tersebut tidak diverifikasi.
- `git diff --check`: lulus.

Tes backend mencakup
required consent, approval Publik/Privat, default/forged social consent, retry
historis, retirement token, redaksi, policy gate dan antispam. Tes frontend
merender form ID/EN dan memastikan satu checkbox wajib, dua link kebijakan,
radio visibility serta receipt tanpa token.

Browser host lokal tidak tersedia pada sesi ini. Uji visual 320/360/390 px,
pengukuran scrollWidth, klik tautan kebijakan, dan interaksi expired-CAPTCHA
masih perlu review browser lokal oleh pemilik. Pemeriksaan kode memastikan
target label minimum 44 px, checkbox 20 px, wrapping teks dan grid mobile satu
kolom; ini bukan pengganti hasil uji visual. Tidak ada klaim bahwa produksi
atau CAPTCHA eksternal sudah diuji.
