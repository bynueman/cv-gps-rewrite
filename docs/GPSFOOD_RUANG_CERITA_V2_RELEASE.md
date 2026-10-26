# GPS FOOD — Ruang Cerita V2: promosi dan rilis manual

Dokumen rilis ini menggantikan instruksi untuk menyembunyikan seluruh promosi dan instruksi aktivasi Stage A pada dokumen historis. Tidak menyatakan deployment sudah dilakukan oleh agen.

## Status dan cakupan

Menurut pemilik, platform cerita produksi sudah terbuka pada gpsfood.id/kuicip/cerita, database cerita terpisah dan anti-spam local sudah operasional. Baseline produksi terakhir yang dilaporkan: b1f2878cb5433615daca3f4e34204f8bf30e4353. Operator harus memeriksa HEAD aktual sebelum deployment.

UI/UX publik dan moderasi yang disetujui sudah masuk commit b4fb5b3ebb338a690a20f98bff4032bb6f1556a3. Rilis berikutnya mempertahankannya melalui riwayat Git, tanpa membangun ulang form, token, modal, anti-spam, atau admin search.

Perubahan tambahan:
- Menu utama Ruang Cerita / Story Space setelah Produk Kami, ke /kuicip/cerita. Satu daftar link dipakai desktop dan mobile; active state dan penutupan menu saat navigasi tetap memakai mekanisme yang ada.
- Desktop navigation mulai xl (1280px); 1024px memakai menu mobile. Gap desktop 16px dan 24px pada 2xl, tanpa mengecilkan ukuran teks. Mobile menu dapat di-scroll pada layar pendek.
- Satu StoryInvitation setelah ProductFamilies dan sebelum ExportPreview. Cream/espresso/gold, judul Setiap Rasa Punya Cerita., CTA Mulai Bercerita, ornamen SVG percakapan dekoratif yang disederhanakan menjadi tanpa ilustrasi pada mobile.
- Copy Indonesia/Inggris ada di dictionary i18n yang sudah digunakan. Tidak ada foto, kemasan baru, testimoni, angka komunitas, dependency, atau animasi baru.

Corporate hero, showcase Kuicip/Putri Teko, katalog, footer, splash, CMS dan link Shopee/WhatsApp tidak diubah. Header bersama memang menampilkan menu baru pada semua halaman publik. Menu/teaser menuju landing; keterbukaan submission tetap ditentukan backend, bukan keberadaan link promosi.

## Konfigurasi, data, dan SEO

Pertahankan tanpa mengedit .env:
- STORIES_ENABLED=true
- STORIES_POLICIES_APPROVED=true
- STORIES_ANTISPAM_PROVIDER=local
- Policy version dan retensi yang sudah disetujui, kuota/cooldown, credential, APP_KEY, STORIES_ABUSE_KEY, koneksi DB, session/cache.

Tidak ada schema/dependency/route change atau migration baru. Jangan melakukan initialization, seed, reset, migration ulang, perubahan record cerita, atau rollback database.

Public discovery lewat navbar/homepage tidak mengubah indexing. Route cerita tetap middleware noindex dengan X-Robots-Tag: noindex, nofollow, meta robots yang ada tetap dipertahankan, canonical /kuicip/cerita tetap valid. Tidak menambahkan sitemap entry atau schema UGC/review/rating.

## Validasi dan artefak

Gunakan TypeScript noEmit, kedua file frontend story tests, serta Laravel StoryPlatformTest, StoryAntispamTest, dan StorySoftLaunchTest. Test isolation memakai DB in-memory dan storage sementara; warning file .env yang tidak ada pada direktori sementara tidak berasal dari .env produksi.

Browser automation belum tersedia saat persiapan ini: VISUAL BROWSER QA NOT VERIFIED. Pemilik perlu memeriksa 1440, 1366, 1024, 768, 390 dan 360px; navbar overflow, menu mobile, CTA, form/verifikasi, modal, dan console.

Artefak final dibuat ulang setelah push normal dari source bersih:
- storage/app/private/releases/gpsfood-ruang-cerita-v2-<short-sha>.zip
- JSON provenance berisi SHA commit/remote, manifest hash, daftar file commit, inventory dan hash setiap file build.
- File .sha256 dan runbook manual dengan SHA/ZIP/hash literal.

ZIP hanya berisi build/manifest.json dan build/assets/*, memakai slash POSIX dan permission Unix. Jangan commit/upload SQLite, runtime, .env, log, backup, vendor, node_modules atau artefak ZIP ke Git.

## Operator NyanHosting

Agen tidak mengakses cPanel/produksi. Operator menggunakan runbook final pada storage/app/private/releases yang dibuat untuk commit rilis, bukan ZIP/runbook historis.

Mulai dari cd ~/gsp-laravel dan verifikasi path /home/gpsfoodi/gsp-laravel, HEAD aktual, main/remote/SHA, ancestor fast-forward, modifikasi server dan konflik custom .htaccess maupun .gitignore di storage/bootstrap. Konfirmasi backup privat kedua DB, .env, build, uploads, source dan kapasitas disk. Upload ZIP ke /home/gpsfoodi/, verifikasi checksum/entry/CRC, maintenance, pull --ff-only, cocokkan HEAD, ekstrak ke public dan cocokkan manifest/asset.

Laravel yang terkunci di repository mendukung optimize:clear --except=cache. Runbook menggunakan opsi ini agar bootstrap/config/route/view dibersihkan sambil mempertahankan application cache untuk challenge/attempt/rate-limit/lock anti-spam; kemudian config:cache, route:cache, view:cache. Jangan menjalankan cache:clear terpisah.

Periksa flags/provider, policy version dan retensi identik sebelum/sesudah; DB cerita tetap terpisah dan dapat diakses. Gunakan bypass maintenance untuk smoke check tanpa kiriman/mutasi destruktif. Keluar maintenance hanya setelah lolos, lalu periksa homepage/menu/teaser, cerita, admin, katalog/CTA, font/logo.

Rollback harus memakai SHA aktual sebelum rilis dan build backup yang cocok, tanpa rewrite history, reset hard, clean, restore DB otomatis, atau penghapusan media. Simpan data baru yang sah. Semua detail command dan hash literal tersedia pada runbook artefak final.
