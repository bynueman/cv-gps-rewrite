# GPS FOOD — Smart Bilingual CMS V3: laporan penerimaan lokal

Dokumen ini merekam fase implementasi lokal sebelum commit. Pemilik kemudian mengotorisasi persiapan rilis Git/ZIP; prosedur manual ada di [template runbook rilis](GPSFOOD_SMART_BILINGUAL_V3_RELEASE_RUNBOOK.template.md). Tidak ada akses produksi otomatis.

Status fase implementasi: **GO untuk penerimaan lokal oleh pemilik**. QA visual interaktif masih diperlukan. Ini bukan persetujuan rilis produksi.

Baseline Git: `1cbfa8b39cdda83d9bd7b3df8b191dbd998a74de`. Working tree bersih sebelum pekerjaan V3. Semua perubahan V3 dibiarkan lokal, belum di-stage/commit/push. Tidak ada akses NyanHosting, perubahan cPanel, produksi, database live, atau file environment.

## Akar masalah dan hasil

Implementasi lama menyaring baris ID/EN secara terpisah lalu memasangkannya menurut indeks. Slot EN kosong dapat bergeser; tampilan detail menggambar bullet tanpa memeriksa teks. Nama EN juga tidak mempunyai versi sumber. Concern FormRequest memanggil provider sebelum validasi, sedangkan TranslationService menganggap HTTP 200 cukup dan mengembalikan sumber saat gagal.

Sekarang validasi mendahului penyimpanan. Sumber ID dan metadata revisi disimpan dalam transaksi yang sama dengan job antrean database. Job memuat record yang diizinkan melalui ID, memeriksa hash sumber/target dan revisi sebelum menulis English. Tidak ada API saat membuka form, mengetik, mengganti bahasa, atau menampilkan halaman publik.

Kasus persis enam catatan ID dan dua EN direproduksi melalui fixture tes. Enam slot tetap berisi konten; empat EN yang hilang terisi, dua EN lama dan urutan/sumber ID dipertahankan. English lama “Original” yang kehilangan “NO MSG” tidak dianggap mutakhir; sumber lengkap ditampilkan sebagai fallback sambil menunggu peninjauan.

## Inventaris konten yang benar-benar ada

| Modul | Cakupan V3 |
|---|---|
| Produk Kuicip / Putri Teko | Nama, tagline, personality, serving, description, highlights, notes, judul SEO, meta description, alt gambar. SEO/alt opsional boleh memakai sumber nama/ringkasan tanpa mengubah sumber ID. |
| Artikel | Title, excerpt, body HTML, tags. Title/excerpt juga menjadi SEO; tidak menambah kolom SEO artikel yang sebelumnya tidak ada. Alt/title gambar di dalam body ikut pemrosesan DOM. |
| Brand | Tagline dan story perusahaan di database; didukung repair. Tidak ada form CRUD brand di proyek ini dan tidak ditambahkan. |
| Pengaturan | Hanya `site_title`, `meta_description_default`, `operating_hours`. English tersimpan, dibagikan sebagai metadata/jam publik. Kontak, alamat resmi, nomor, URL dan konfigurasi bisnis dipertahankan. |
| Mitra | Field nyata berupa nama resmi, kategori, logo. Tidak ada deskripsi bilingual untuk diterjemahkan. Kategori memakai kamus ID/EN yang sudah ada. |
| Sertifikasi | Nama sertifikat resmi, lembaga, nomor, tanggal, URL/dokumen dipertahankan persis. Tidak ada field deskripsi tambahan yang dibuat. |
| Halaman statis | Kamus ID/EN yang sudah ada tetap dipakai pada beranda, merek, katalog, mitra, berita, ekspor, kontak dan teaser cerita. Konsumen konten dinamis beralih ke accessor bersama. Tidak ada redesign. |
| Ruang Cerita / data privat | Tidak termasuk allowlist. Cerita, laporan, token, moderasi, pesan privat, kredensial, TMC/Finance dan dokumen hukum tidak masuk workflow/provider. |

## Alur CMS satu kali Simpan

1. Isi **Konten Indonesia**, tambahkan item melalui editor daftar yang mempunyai key tetap.
2. Klik **Simpan** sekali. Validasi dan penyimpanan ID selesai sebelum pemrosesan terjemahan.
3. Buka halaman edit untuk melihat status: menunggu, sedang diterjemahkan, siap, gagal, manual, stale, atau perlu ditinjau.
4. Worker mengisi English tersimpan. **Pratinjau English tersimpan** memuat hasil ke form tanpa Save kedua.
5. Disclosure English menyediakan override manual. Retry biasa melindungi manual; tindakan penggantian manual meminta konfirmasi dan menyimpan salinan sebelumnya di metadata server.
6. Bila sumber berubah, hasil generated yang terkait diproses ulang. Manual tetap tersimpan dan ditandai stale bila perlu.
7. Form mengirim daftar English yang benar-benar diedit. Form yang sudah terbuka sebelum worker selesai tidak menimpa hasil worker dengan English lama.

Daftar memakai `{key,id,en}` dalam JSON yang sama; tidak perlu tabel baru untuk setiap item. JSON lama `{id,en}` tetap terbaca. Tambah/hapus/pindah mempertahankan pasangan berdasarkan key. Perubahan ambigu dari klien textarea lama ditolak untuk mencegah hilangnya English manual.

Sumber tetap tersedia jika provider gagal. Accessor publik memilih English yang tersedia dan mutakhir, lalu fallback sumber dengan label bahasa **[ID]** / **[EN]** pada teks/list. Baris yang kedua bahasanya kosong tidak menggambar bullet. Alt, pencarian dan metadata memakai accessor teks yang sama; label visual tidak dimasukkan ke atribut tersebut.

## Provider, batas, dan keakuratan

MyMemory yang sudah digunakan proyek tetap dipakai, tanpa SDK/subscription/secret baru.

- HTTP 200 harus disertai `responseStatus=200` dan translatedText yang valid. Respons kuota, kosong, malformed, serta teks sumber identik yang tidak termasuk glossary bukan keberhasilan.
- HTTPS, connect timeout 2 detik, timeout request 5 detik, paling banyak satu retry timeout/5xx; maksimal 4 percobaan HTTP per job termasuk retry.
- Segment UTF-8 maksimal 500 byte; cache 30 hari berdasarkan provider, versi glossary, pasangan bahasa dan hash sumber.
- Batas aplikasi 12 request/menit dan maksimal 4.000 karakter/hari secara bersama melalui cache persisten/lock. Batas harian dapat diturunkan, tidak dinaikkan di atas 4.000 oleh konfigurasi ini.
- Batas lokal per menit menunda kelanjutan 65 detik; budget per job menunda 5 detik. Hasil segment sebelumnya di-cache agar HTML panjang dapat dilanjutkan. Kegagalan provider/kuota harian berhenti dengan status gagal, memerlukan retry eksplisit ketika layanan tersedia.
- DOMDocument/DOMXPath menerjemahkan text node dan alt/title, mempertahankan tag, list, penekanan, URL, gambar; sanitasi dilakukan sebelum dan sesudah.
- Glossary melindungi GPS FOOD, CV Gama Putra Santosa, Kuicip, Putri Teko, Cassa Lite dan istilah/varian yang dikonfigurasi. Spasi tepi dipertahankan.
- Angka harus sama. Penambahan klaim NO MSG, gluten-free, halal, organic, pengawet atau penyembuhan yang dikenali ditolak. Klaim sensitif yang sudah ada ditandai perlu review; ini bukan verifikasi ilmiah/sertifikasi.
- NO MSG hanya dikecualikan dari review tambahan untuk sumber Kuicip Original/Truffle yang sudah memuat klaim tersebut. Tidak disalin ke varian lain.

Dokumentasi provider: [spesifikasi MyMemory](https://mymemory.translated.net/doc/spec.php) dan [usage limits](https://mymemory.translated.net/doc/usagelimits.php). MyMemory mendokumentasikan batas anonymous 5.000 karakter/hari; aplikasi memakai 4.000 untuk ruang cadangan. Tidak ada batch endpoint yang diasumsikan.

**Integrasi nyata telah diverifikasi**, terbatas pada frasa publik tetap `Halo dunia` → `Hello world`. Script probe menjalankan workflow dan job yang sama, membaca kembali `short_en` dari database SQLite `:memory:`, dengan status `ready` dan exit 0. Bukan mock, bukan data perusahaan/UGC, bukan akses produksi. Ini membuktikan satu sampel provider + persistensi; bukan jaminan mutu semua terjemahan atau worker hosting yang belum dikonfigurasi.

## Repair dan hasil dry-run lokal

Perintah tersedia: `translations:repair`. Default adalah **DRY-RUN**, tanpa penulisan database/cache/provider/queue.

Scope: `--type=products|articles|brands|settings` (boleh berulang), `--id=<id>` untuk satu tipe, `--batch=1..100`, `--after=<id>`. Cursor berlaku pada tiap tipe; lanjutkan per tipe agar tidak melewatkan ID yang lebih kecil pada tipe lain.

Laporan mengidentifikasi missing EN, slot/length mismatch, SEO yang kosong, stale/failed/stalled, duplicate/malformed, source hilang dan legacy yang versinya tidak diketahui. Record malformed/duplicate dilewati untuk review. English manual/legacy yang tidak dapat dibuktikan mutakhir tidak ditimpa.

Dry-run aktual pada preview, batch 10 per tipe:

| Hasil | Nilai |
|---|---|
| Temuan | 126 (jumlah isu field/list, bukan 126 record) |
| Record layak generate | 10 |
| Cursor produk | `--after=10` |
| Cursor brand | `--after=2` |
| Artikel / pengaturan | Tidak ada record pada scope preview tersebut |
| Database website | SHA-256 sebelum/sesudah identik |
| Database stories | SHA-256 sebelum/sesudah identik |
| APPLY preview / produksi | Tidak dijalankan |

Hasil preview memakai data lokal yang sudah ada; bukan inventaris database produksi. Laporan lengkap lokal: `storage/app/private/cms-v3-repair-dry-run.txt`.

APPLY hanya setelah preview dan persetujuan backup. Dibutuhkan `--apply --backup-confirmed --review=<token dry-run yang masih sesuai>`. APPLY hanya mengantrekan field yang layak, tidak memanggil provider langsung. Tes in-memory membuktikan apply setelah preview, perlindungan manual, lalu apply ulang tanpa job tambahan. Tidak ada APPLY pada data preview milik pemilik atau data produksi dalam pekerjaan ini.

## Preview dan penerimaan pemilik

Preview saat ini: [http://127.0.0.1:8766/](http://127.0.0.1:8766/).

Gunakan PHP lokal yang sama dari root repo. Jangan gunakan `composer setup`, reset/seed ulang atau artisan biasa yang membaca environment proyek untuk penerimaan ini.

```powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe -S 127.0.0.1:8766 -t public scripts/local-preview-router.php
```

Jalankan server hanya jika port belum aktif. Schema V3 sudah dipersiapkan pada database website preview yang dimiliki harness, melalui migrasi targeted berikut; database stories diperiksa tidak berubah, tanpa seed/reset:

```powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe scripts/local-preview.php cms:prepare
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe scripts/local-preview.php translations:repair --dry-run --type=products --batch=10
```

Login dengan akun preview yang sudah ada. Edit produk, tambahkan satu highlight ID, Simpan sekali. Worker preview sengaja memblokir HTTP eksternal secara default. Untuk mengirim **hanya konten perusahaan publik yang disetujui**, jalankan secara eksplisit:

```powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe scripts/local-preview.php translations:work --live-provider
```

Worker ini berhenti ketika kosong, maksimal 45 detik / 5 job per pemanggilan. Kelanjutan yang masih delayed dapat memerlukan pemanggilan berikutnya setelah waktunya tiba; tidak perlu Save ulang. Ulangi pemeriksaan status/pratinjau, lalu buka produk publik dan pilih English. Lakukan juga pada draft artikel untuk memeriksa nested HTML. Perubahan sumber yang memiliki manual EN harus tampil stale, sampai ditinjau/diganti secara eksplisit.

Probe publik tetap dapat diulang tanpa memakai record preview:

```powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe scripts/translation-probe.php --live-provider
```

## Migrasi dan cron untuk fase rilis terpisah

**Instruksi berikut belum dijalankan di hosting.** Sesudah persetujuan rilis terpisah: backup database website beserta file, verifikasi PHP/extension, konfigurasi cache persisten, tabel jobs dan failed_jobs yang sudah disediakan proyek, serta queue database yang menunjuk database website yang sama. Workflow menolak antrean yang mengarah database lain.

Migrasi V3 hanya menambahkan `content_translations` dan nullable `settings.value_en`. Tidak mengganti ID/source dan tidak mengubah schema stories.

```bash
php artisan migrate --database=mysql --path=database/migrations/2026_10_26_110000_create_content_translations_table.php --force
```

`mysql` di atas adalah nama koneksi website yang harus diverifikasi operator. Jangan menjalankan migrasi stories atau migrasi lain sekadar untuk V3. Siapkan maintenance singkat/urutan rilis agar kode baru tidak melayani request sebelum tabel V3 tersedia. Untuk rollback kode, pertahankan migrasi additive dan backup; jangan menghapus hasil English melalui rollback schema tanpa keputusan pemilik.

Cron shared hosting tidak mengandalkan daemon. Berdasarkan app path yang didokumentasikan proyek, contoh entry cPanel setiap menit:

```cron
* * * * * cd /home/gpsfoodi/gsp-laravel && flock -n storage/framework/translations-worker.lock php artisan queue:work database --queue=translations --stop-when-empty --max-time=50 --timeout=40 --tries=1 --memory=128 >> storage/logs/translations-worker.log 2>&1
```

Operator harus memverifikasi path akun, `command -v php`, `php -v`, `command -v flock`, extension DOM/mbstring/PDO serta dukungan timeout worker di lingkungan Cron sebelum memasang entry. Ketersediaan `flock`/PCNTL di hosting belum diverifikasi; bila tidak tersedia, gunakan penjadwalan ber-lock yang didukung hosting sebelum rilis. Jangan memasang entry tanpa lock yang sesuai. Cache harus persisten dan dipakai bersama oleh semua worker, bukan array.

Tidak ada npm di produksi. Bundle dibuat lokal. Cron scheduler yang sudah ada tidak diganti. Retry provider gagal/kuota dilakukan melalui panel atau repair scoped; tidak ada loop retry outage tanpa batas. Pantau status gagal dan usage provider. Cron ini belum diaktifkan, sehingga terjemahan otomatis hosting belum dapat dinyatakan operasional.

## Locale dan SEO

ID/EN toggle tetap memakai kamus yang ada. Bahasa tersimpan di `gpsfood.lang` pada localStorage, bertahan pada navigasi Inertia; client memperbarui `document.documentElement.lang`. Produk/artikel memakai English tersimpan untuk title/description client. Layout juga memakai site-title/default-description tersimpan, dengan fallback aman.

Initial HTML, JSON-LD dan canonical tetap dibangun server melalui Seo, berbahasa Indonesia. Server tidak membaca localStorage. Tanpa SSR/URL locale, crawler awal tetap memperoleh versi ID; belum ada halaman EN terindeks terpisah atau hreflang baru. OG/schema awal juga tidak berubah mengikuti localStorage. Ini batas indexing internasional yang tetap berlaku. Sitemap/canonical dan noindex cerita dipertahankan.

## Validasi aktual

| Pemeriksaan | Hasil |
|---|---|
| Laravel: CMS, provider mock, CRUD, permission/CSRF, SEO, mitra/sertifikasi, isolasi, Ruang Cerita | 125 tes, 1.328 assertion, exit 0 |
| Suite V3 khusus | 24 tes di dalam hitungan Laravel di atas |
| Frontend bilingual + commerce + story cards + soft launch | 25 lulus |
| TypeScript `--noEmit` | Lulus |
| Pint pada PHP yang diubah | Lulus |
| Syntax PHP | 39 file lulus; file terakhir yang disentuh juga diperiksa |
| `npm run build` lokal | Lulus |
| `git diff --check` | Lulus |
| HTTP loopback | 20 pemeriksaan lulus; kedua hash database tidak berubah |
| Provider nyata + persistensi | `Halo dunia` → `Hello world`, ready, exit 0 |
| Browser visual | **VISUAL BROWSER QA NOT VERIFIED** — daftar browser/app sesi kosong |
| MySQL/PHP 8.2 hosting | Belum dijalankan; tes memakai SQLite dan PHP lokal 8.4 |

125 warning PHPUnit semuanya berasal dari `.env` yang tidak ada di direktori sementara `gpsfood-tests-*`, sesuai isolasi environment; tidak ada assertion gagal. Frontend SSR harness mencatat warning useLayoutEffect yang sudah ada pada animasi produk. Build mencatat dua referensi font lokal tidak ditemukan saat bundling; referensi/font tidak diubah oleh V3 dan tampilan/font runtime belum diverifikasi visual.

Bukti lokal yang diabaikan Git: `storage/app/private/cms-v3-final-php-tests.log`, `cms-v3-http-smoke.json`, dan `cms-v3-repair-dry-run.txt`. Build berada di `public/build` sesuai pola proyek dan tidak di-stage.

## Peninjauan manusia yang masih diperlukan

English legacy yang sumber/revisinya tidak diketahui, nama/terminologi merek yang sengaja identik, klaim produk, mutu marketing, artikel panjang, dan mismatch “Original” / “Original (NO MSG)” tetap perlu review. Missing slots dapat diisi melalui repair setelah preview+backup disetujui. Preview yang ada sengaja tidak di-apply otomatis. Semua sumber Indonesia dipertahankan.

GO untuk mencoba alur lokal; belum GO produksi. Gate tersisa adalah penerimaan visual/pemilik, review klaim/legacy, uji migrasi pada MySQL terisolasi dan verifikasi cron/cache/PHP hosting dalam fase rilis yang diotorisasi terpisah.

## Berkas berubah dan baru

Daftar di bawah dibuat dari status Git akhir pekerjaan V3. Concern lama yang menerjemahkan sebelum validasi dihapus. Tidak ada dependency/package baru, perubahan .env, atau kode platform cerita yang diubah.

| Status | Berkas |
|---|---|
| Baru | [app/Console/Commands/RepairTranslations.php](../app/Console/Commands/RepairTranslations.php) |
| Baru | [app/Exceptions/TranslationFailure.php](../app/Exceptions/TranslationFailure.php) |
| Diubah | [app/Http/Controllers/Admin/ArticleController.php](../app/Http/Controllers/Admin/ArticleController.php) |
| Diubah | [app/Http/Controllers/Admin/ProductController.php](../app/Http/Controllers/Admin/ProductController.php) |
| Diubah | [app/Http/Controllers/Admin/SettingController.php](../app/Http/Controllers/Admin/SettingController.php) |
| Diubah | [app/Http/Controllers/Admin/TranslationController.php](../app/Http/Controllers/Admin/TranslationController.php) |
| Diubah | [app/Http/Controllers/NewsController.php](../app/Http/Controllers/NewsController.php) |
| Diubah | [app/Http/Controllers/ProductController.php](../app/Http/Controllers/ProductController.php) |
| Diubah | [app/Http/Middleware/HandleInertiaRequests.php](../app/Http/Middleware/HandleInertiaRequests.php) |
| Diubah | [app/Http/Requests/Admin/Concerns/ArticleValidationRules.php](../app/Http/Requests/Admin/Concerns/ArticleValidationRules.php) |
| Dihapus | [app/Http/Requests/Admin/Concerns/AutoTranslatesBilingualFields.php](../app/Http/Requests/Admin/Concerns/AutoTranslatesBilingualFields.php) |
| Diubah | [app/Http/Requests/Admin/StoreProductRequest.php](../app/Http/Requests/Admin/StoreProductRequest.php) |
| Diubah | [app/Http/Requests/Admin/UpdateProductRequest.php](../app/Http/Requests/Admin/UpdateProductRequest.php) |
| Diubah | [app/Http/Requests/Admin/UpdateSettingsRequest.php](../app/Http/Requests/Admin/UpdateSettingsRequest.php) |
| Diubah | [app/Http/Resources/ArticleResource.php](../app/Http/Resources/ArticleResource.php) |
| Diubah | [app/Http/Resources/BrandResource.php](../app/Http/Resources/BrandResource.php) |
| Diubah | [app/Http/Resources/ProductResource.php](../app/Http/Resources/ProductResource.php) |
| Baru | [app/Jobs/TranslateCompanyContent.php](../app/Jobs/TranslateCompanyContent.php) |
| Diubah | [app/Models/Article.php](../app/Models/Article.php) |
| Diubah | [app/Models/Brand.php](../app/Models/Brand.php) |
| Baru | [app/Models/Concerns/HasContentTranslation.php](../app/Models/Concerns/HasContentTranslation.php) |
| Baru | [app/Models/ContentTranslation.php](../app/Models/ContentTranslation.php) |
| Diubah | [app/Models/Product.php](../app/Models/Product.php) |
| Diubah | [app/Models/Setting.php](../app/Models/Setting.php) |
| Baru | [app/Services/ContentTranslationWorkflow.php](../app/Services/ContentTranslationWorkflow.php) |
| Diubah | [app/Services/TranslationService.php](../app/Services/TranslationService.php) |
| Baru | [app/Support/BilingualLists.php](../app/Support/BilingualLists.php) |
| Baru | [app/Support/CompanyContent.php](../app/Support/CompanyContent.php) |
| Baru | [app/Support/LocalizedContent.php](../app/Support/LocalizedContent.php) |
| Diubah | [app/Support/Seo.php](../app/Support/Seo.php) |
| Baru | [config/translations.php](../config/translations.php) |
| Baru | [database/migrations/2026_10_26_110000_create_content_translations_table.php](../database/migrations/2026_10_26_110000_create_content_translations_table.php) |
| Baru | [docs/GPSFOOD_SMART_BILINGUAL_CMS_V3.md](../docs/GPSFOOD_SMART_BILINGUAL_CMS_V3.md) |
| Diubah | [resources/js/Components/admin/AutoTranslateButton.tsx](../resources/js/Components/admin/AutoTranslateButton.tsx) |
| Baru | [resources/js/Components/admin/BilingualListEditor.tsx](../resources/js/Components/admin/BilingualListEditor.tsx) |
| Baru | [resources/js/Components/admin/TranslationPanel.tsx](../resources/js/Components/admin/TranslationPanel.tsx) |
| Diubah | [resources/js/Components/ArticleCard.tsx](../resources/js/Components/ArticleCard.tsx) |
| Diubah | [resources/js/Components/Footer.tsx](../resources/js/Components/Footer.tsx) |
| Diubah | [resources/js/Components/ProductCard.tsx](../resources/js/Components/ProductCard.tsx) |
| Diubah | [resources/js/Components/ProductCatalog.tsx](../resources/js/Components/ProductCatalog.tsx) |
| Diubah | [resources/js/Components/ProductPackshot.tsx](../resources/js/Components/ProductPackshot.tsx) |
| Diubah | [resources/js/Components/sections/FeaturedProducts.tsx](../resources/js/Components/sections/FeaturedProducts.tsx) |
| Diubah | [resources/js/Components/sections/NewsPreview.tsx](../resources/js/Components/sections/NewsPreview.tsx) |
| Diubah | [resources/js/Components/sections/ProductFamilies.tsx](../resources/js/Components/sections/ProductFamilies.tsx) |
| Diubah | [resources/js/Layouts/SiteLayout.tsx](../resources/js/Layouts/SiteLayout.tsx) |
| Diubah | [resources/js/lib/i18n.tsx](../resources/js/lib/i18n.tsx) |
| Baru | [resources/js/lib/localized.tsx](../resources/js/lib/localized.tsx) |
| Diubah | [resources/js/Pages/Admin/Articles/Form.tsx](../resources/js/Pages/Admin/Articles/Form.tsx) |
| Diubah | [resources/js/Pages/Admin/Products/Form.tsx](../resources/js/Pages/Admin/Products/Form.tsx) |
| Diubah | [resources/js/Pages/Admin/Settings/Edit.tsx](../resources/js/Pages/Admin/Settings/Edit.tsx) |
| Diubah | [resources/js/Pages/Contact.tsx](../resources/js/Pages/Contact.tsx) |
| Diubah | [resources/js/Pages/News/Show.tsx](../resources/js/Pages/News/Show.tsx) |
| Diubah | [resources/js/Pages/Products/Overview.tsx](../resources/js/Pages/Products/Overview.tsx) |
| Diubah | [resources/js/Pages/Products/ProductDetail.tsx](../resources/js/Pages/Products/ProductDetail.tsx) |
| Diubah | [resources/js/types/index.ts](../resources/js/types/index.ts) |
| Diubah | [routes/web.php](../routes/web.php) |
| Diubah | [scripts/local-preview.php](../scripts/local-preview.php) |
| Baru | [scripts/translation-probe.php](../scripts/translation-probe.php) |
| Diubah | [tests/Feature/Admin/ArticleCrudTest.php](../tests/Feature/Admin/ArticleCrudTest.php) |
| Diubah | [tests/Feature/Admin/ProductCrudTest.php](../tests/Feature/Admin/ProductCrudTest.php) |
| Baru | [tests/Feature/Admin/SmartBilingualCmsTest.php](../tests/Feature/Admin/SmartBilingualCmsTest.php) |
| Diubah | [tests/Feature/Admin/TranslationTest.php](../tests/Feature/Admin/TranslationTest.php) |
| Baru | [tests/frontend/bilingual-cms.test.mjs](../tests/frontend/bilingual-cms.test.mjs) |
| Diubah | [tests/Support/LocalPreview.php](../tests/Support/LocalPreview.php) |

Status Git akhir: 45 modified, 1 deleted (concern lama), 18 untracked/new; 0 staged. HEAD tetap `1cbfa8b39cdda83d9bd7b3df8b191dbd998a74de`. Tidak ada commit, push, deploy, atau perubahan environment/produksi.
