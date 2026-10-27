# GPS FOOD V2 — Final local acceptance

**Preview terkini:** server8766 kini memakai profil persisten lokal (bukan GET-only); lihat [panduan local preview](GPSFOOD_LOCAL_PREVIEW.md). Dokumen ini mempertahankan hasil acceptance historis. Flags produksi tetap disabled; persetujuan kebijakan demo bukan persetujuan bisnis.

**Catatan setelah acceptance:** splash awal diubah atas permintaan owner26Oktober. Hasil/inventaris/paket pada laporan ini merekam snapshot sebelum perubahan splash; ZIP lama tidak lagi mewakili frontend terbaru. Validasi terbaru160tests/1085assertions dan QA tambahan ada pada konteks bagian29. Visual tetap NOT RUN.

Tanggal: **26 Oktober 2026 (Asia/Jakarta)**. Keputusan: **CONDITIONAL GO — website V2 dengan Ruang Cerita disabled**. Aktivasi Ruang Cerita: **NO-GO**. Tidak ada deployment/commit/push; rekomendasi bersyarat bukan persetujuan mengaktifkan produksi.

## 1. Lingkup dan bukti

Baseline Git diperiksa sebelum pekerjaan: 53 modified, 41 untracked, index/staging kosong; HEAD f093da9abc39421470e4f0e218a05dd0d1a34027. Semua implementasi V2 dan F01–F06 dipertahankan. Perubahan aplikasi tidak diperlukan pada acceptance ini; perbaikan hanya panduan rilis/type checking dan pengujian baru. Database produksi/lokal bernilai, credential produksi, .env nyata, hosting/VPS, TMC/Finance tidak diakses atau diubah.

TestIsolationTest dijalankan terlebih dahulu: 2 tests/13 assertions PASS. TestCase mengabaikan .env nyata, memaksa SQLite :memory: sebelum provider, public/storage/bootstrap sementara, array cache/session, fake mail, preventStrayRequests dan cleanup canonical test-owned. Baru setelah itu test upload dan suite lengkap dijalankan. HTTP harness memakai bootstrap yang sama, fixture seed repository/sintetis, GET-only, localhost, dan dua database memori hanya ketika mode cerita dinyalakan secara eksplisit. File media asli tidak dihapus.

## 2. Hasil website, CMS, commerce dan cerita

| Area | Bukti aktual | Status / batas |
|---|---|---|
| Homepage, overview, dua katalog, dua detail produk, news, mitra, contact, export/legalitas | Feature tests + HTTP HTML default off | PASS backend/HTTP; visual NOT VERIFIED |
| Produk aktif dan related | Existing CRUD/ProductExperienceTest; related satu brand <=3 dan tidak self | PASS fixture, data produksi tidak dibaca |
| Shopee opsional/invalid URL/CMS persistence | ProductExperienceTest | PASS validasi/mapping dengan URL contoh sintetis; destinasi bisnis aktual belum diverifikasi/dikunjungi |
| WhatsApp encode dan fallback nomor kosong/invalid | Node commerce assertions | PASS helper; CTA mobile/browser NOT VERIFIED |
| Metadata ID/EN, canonical, JSON-LD | ProductExperienceTest + HTTP detail | PASS HTML/props; language switch interaktif NOT VERIFIED |
| CMS listing/create/edit/save/upload/izin | Existing feature tests + ReleaseReadinessTest; HTTP list/create/edit | PASS backend; browser form/upload NOT VERIFIED |
| Simpan metadata tanpa mengganti atribut lain/gambar | ReleaseReadinessTest membandingkan seluruh raw attributes di luar field yang disengaja, serta hash file upload sementara | PASS |
| Flag default off + approval default off | ReleaseReadinessTest dengan koneksi stories dihapus | PASS; website berjalan, mutations/feed/moderasi ditolak, command prune no-op |
| Flag on tetapi approval belum disahkan | POST503 tanpa koneksi stories/HTTP luar | PASS |
| Private/pending/consent/otorisasi/Turnstile | StoryPlatformTest | PASS mock; Turnstile domain produksi NOT RUN |
| Enabled local story UI/feed | HTTP fixture mode on: page enabled, feed6+1, kategori kosong, private excluded | PASS HTTP; rendering/interaksi NOT VERIFIED |
| Asset statis | 79 URL200: 54 aset build, 22 gambar seed, dua font, satu logo | PASS transport; cropping/appearance NOT VERIFIED |
| Route caching | Routes prepareForSerialization/compile/var_export round trip + middleware superadmin | PASS mekanisme kompilasi; command produksi/permission filesystem hosting belum dijalankan |

Browser inventory melalui cua.listBrowsers() mengembalikan [] pada task ini. Tidak memasang dependency/browser tambahan. **Tidak ada browser test, screenshot, console/runtime JS, layout mobile, focus, atau pemeriksaan visual yang dinyatakan lulus.**

## 3. Kompatibilitas database dan urutan rilis

Migration produk: database/migrations/2026_10_25_100000_add_commerce_and_seo_to_products_table.php, koneksi **default website**. Menambah delapan kolom nullable: shopee_url (2048), net_weight (80), seo_title_id/en, meta_description_id/en dan image_alt_id/en (255). Validation membatasi title120/meta160/alt255. Tidak backfill, rename/drop kolom lama atau mengubah slug/ID/record.

| Kombinasi | Keputusan |
|---|---|
| Data/schema lama -> migration up | PASS: snapshot seluruh row/kolom lama tetap sama pada fixture SQLite; semua kolom baru null |
| Operasi kolom lama setelah migration | PASS: insert/update dengan field lama tetap bekerja dan tidak menghapus metadata baru. Code lama kompatibel secara struktural; aplikasi produksi lama tidak diboot pada hosting |
| Code V2 membaca sebelum migration | PASS untuk home, overview, dua katalog/detail dan form edit CMS; field baru fallback null |
| Code V2 menyimpan field baru sebelum migration | **Tidak kompatibel**: regression mengonfirmasi QueryException kolom belum ada; ini prasyarat urutan rilis |
| MySQL/MariaDB | Grammar Laravel menghasilkan delapan ALTER TABLE ADD ... NULL, tanpa membuka PDO. **Bukan eksekusi ALTER pada server MySQL/MariaDB** |
| Migration down | Menghapus delapan field baru beserta isinya; tidak dipakai untuk rollback rutin |

Urutan wajib setelah rilis disetujui: backup/restore tervalidasi -> aktifkan maintenance dengan code lama -> tarik commit rilis yang sama dengan bundle -> pastikan baseline migrations dan SHA sesuai -> migration produk dengan --database=<website-connection> dan --path eksplisit -> upload/extract build, font/logo -> refresh cache -> verifikasi kedua flag false -> baru buka trafik dan health check. Jika gagal, tetap maintenance; jangan otomatis rollback/drop/reseed. MySQL DDL dapat autocommit dan perlu pemeriksaan schema parsial sebelum retry.

Untuk website dengan cerita off, **tidak perlu membuat DB cerita, mengisi Turnstile, atau menjalankan migration stories**. Schema cerita terpisah database/migrations/stories/2026_10_25_110000_create_story_tables.php tidak otomatis dijalankan oleh migrate website biasa. Ikuti [runbook deployment](GPSFOOD_V2_DEPLOYMENT.md#5-urutan-operator-di-cpanel--belum-dijalankan).

Kompatibilitas terhadap database produksi aktual tetap memerlukan operator membandingkan schema/migrate:status. Tidak ada pengambilan data/credential produksi untuk audit ini. Uji ALTER MySQL/MariaDB staging setara hosting masih syarat release acceptance.

## 4. QA browser manual — semua hasil saat ini NOT VERIFIED

### Pratinjau lokal aman yang sudah dibuat

Dari root repository, jalankan di terminal lokal:

~~~powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe -S 127.0.0.1:8766 -t public storage/app/private/acceptance-20261026-1792948629635/router.php
~~~

Harness di lokasi ignored ini khusus mesin/workspace saat acceptance. Memerlukan vendor dev/TestCase; jangan upload atau menjalankannya di hosting. Ia tidak membaca .env nyata, hanya GET, mereset fixture memori setiap request, membuat user superadmin sintetis untuk pratinjau halaman CMS, dan menghapus hanya direktori temporer miliknya. Tidak cocok untuk menilai persistence/form POST karena seluruh mutation sengaja405. Server yang dijalankan agen sudah dihentikan; mulai ulang command di atas untuk manual QA, lalu Ctrl+C setelah selesai. Tidak memakai artisan serve terhadap .env/database lokal yang bernilai.

1. Buka profil browser baru ke http://127.0.0.1:8766/ . Buka DevTools, aktifkan Preserve log dan Disable cache di Network. Jangan memakai credential produksi.
2. Gunakan device toolbar: **375×812**, **768×1024**, **1440×1000** pada zoom100%; lakukan juga zoom200% untuk keterbacaan/focus.
3. Di setiap ukuran, buka daftar URL di bawah, refresh langsung, lakukan interaksi yang relevan, catat Console errors/failed requests. Simpan screenshot dengan nama <width>-<page>-<state>.png hanya setelah benar-benar diambil.

| Halaman | URL fixture | 375 / 768 / 1440 |
|---|---|---|
| Homepage | / | NOT VERIFIED |
| Product overview | /products | NOT VERIFIED |
| Kuicip catalog | /products/kuicip | NOT VERIFIED |
| Putri Teko catalog | /products/putri-teko | NOT VERIFIED |
| Kuicip detail | /products/kuicip/original | NOT VERIFIED |
| Putri Teko detail | /products/putri-teko/beras-kencur | NOT VERIFIED |
| News | /news | NOT VERIFIED |
| Partner directory | /mitra | NOT VERIFIED |
| Contact | /contact | NOT VERIFIED |
| Export | /export | NOT VERIFIED |
| Certifications | /export/legalitas-sertifikasi | NOT VERIFIED |
| Cerita disabled | /kuicip/cerita?qa_stories=off | NOT VERIFIED |
| Cerita enabled sintetis | /kuicip/cerita?qa_stories=on | NOT VERIFIED |
| CMS list/create/edit | /admin/products, /admin/products/new, /admin/products/1/edit | NOT VERIFIED |

### Pemeriksaan di tiap viewport

4. Logo header/footer: gambar utuh, rasio sekitar3:1, tanpa distorsi/crop. Network logo200. Typography: DM Sans body/UI, Archivo Black display; kedua WOFF2 lokal200 tanpa font request eksternal. Periksa heading panjang dan kontras tombol.
5. Pastikan tidak ada horizontal overflow, overlap header/CTA, atau teks keluar kartu. Grid katalog satu kolom375, dua768, tiga1440; gambar menunjukkan packshot utuh dan ruangnya stabil saat loading.
6. Kuicip: cari Original -> satu hasil fixture; cari zzz-tidak-ada -> empty state; Hapus filter -> delapan fixture. Putri Teko: botol -> tiga fixture; toples -> empat fixture; kombinasi group/kemasan yang tidak cocok -> empty state; reset ->14. Angka ini hanya seed repository, bukan klaim katalog produksi.
7. Buka kartu/detail: nama, brand dan gambar sesuai produk yang dipilih; breadcrumb kembali ke brand yang benar; related <=3, brand sama dan bukan produk sendiri. Periksa CTA wrap/stack tanpa overflow375. Fixture default tidak mempunyai Shopee -> tombol tidak tampil; jangan menambahkan URL bisnis rekaan.
8. Periksa href WhatsApp dan decode parameter text: nama/brand sesuai pilihan, karakter spasi/Unicode/ampersand ter-encode, nomor berasal dari konfigurasi. Pemeriksaan tidak memerlukan pengiriman pesan atau pembelian. Untuk CTA Shopee aktif, gunakan tautan produk yang benar dari pemilik pada staging disposable; target rel dan destination harus diverifikasi, bukan memakai URL contoh unit test sebagai tautan bisnis.
9. Desktop: buka dropdown Export dengan keyboard Enter/Space, pilih Legalitas, Escape menutup dan mengembalikan focus. Mobile: buka/tutup hamburger/submenu, semua link terjangkau, navigasi menutup menu. Tab/Shift+Tab harus memperlihatkan focus pada tautan, tombol, field dan summary.
10. Ganti ID/EN di semua halaman, termasuk heading panjang, hasil pencarian dan detail; label tidak kosong, canonical tetap URL yang sama. Periksa metadata melalui View Source untuk initial ID dan Elements setelah perubahan bahasa; tidak ada fake offers/rating.
11. Emulasikan Slow3G lalu navigasi Inertia: loader logo kecil muncul bila request lambat, tidak menutupi atau memblokir halaman, hilang sesudah selesai. Di DevTools Rendering pilih prefers-reduced-motion: reduce; pulse/reveal/scroll tidak memaksa animasi.
12. Cerita off: form/feed tidak tampil; Network tidak mengirim feed atau Turnstile. Endpoint feed404; mutation backend sudah diuji terpisah di PHPUnit. Cerita on: kategori/form/privacy/consent tampil; ubah public -> private dan pastikan consent publikasi/reuse hilang; penghitung karakter bekerja. Feed enam, load-more menambah satu; category bahagia fixture kosong. Private KC-0008 tidak muncul. Fixture literal <img ...> harus menjadi teks, tidak menciptakan elemen/event; Console window.QA_XSS harus undefined. **Langkah ini belum dieksekusi dalam browser.**
13. Site key sengaja kosong pada harness: submit disabled dan pemberitahuan unavailable benar. Jangan memakai preview ini untuk menyatakan CAPTCHA/submission flow lulus. Setelah pemeriksaan, buka ?qa_stories=off agar cookie preview kembali off.
14. CMS read-only: periksa listing, collapsible SEO, field Shopee/berat opsional, bilingual tabs, unsaved-changes dialog dan focus. Jangan menganggap GET200 membuktikan form browser bekerja.

### Mutations browser pada staging disposable (belum dilakukan)

15. Siapkan deployment staging dengan database website khusus berisi fixture sintetis, upload storage khusus, fake/sandbox mail, dan user editor/superadmin uji. Pastikan tidak ada data pelanggan atau koneksi produksi. Terapkan migration produk dahulu. Jangan memakai database lokal yang bernilai.
16. Login sebagai editor uji; buat Produk QA Sintetis ber-slug unik, isikan data bilingual uji, upload PNG fixture sintetis, simpan, buka detail. Catat image/thumb/OG, title/meta/alt. Edit hanya metadata lalu pastikan slug, status, sort, packaging, highlights, notes dan gambar tetap sama; bandingkan daftar sebelum/sesudah. Shopee kosong harus boleh; javascript:, HTTP dan domain spoof ditolak; tautan aktif hanya dari owner yang sudah diverifikasi.
17. Periksa modal/unsaved guard, error validation, loading/disabled saat save, filter active/inactive, izin user/settings dan cerita privat. Untuk website-only, kedua flag tetap false dan seluruh moderation endpoint tertutup.
18. Uji browser submission, Turnstile test/sandbox, retry, peninjauan privasi/redaksi superadmin, reaction/report dan moderation lifecycle hanya pada staging cerita khusus yang terisolasi dengan konfigurasi provider uji. Jangan mengubah flag produksi untuk QA. Private/pending tidak boleh ada di public Network response; editor403, approval private tetap private, hide segera menghilangkan public story.
19. Jangan menghapus media asli dalam QA. Gunakan snapshot/reset hanya untuk data staging yang secara eksplisit disposable. Setelah lulus, catat tanggal/browser/viewport/screenshot dan minta owner menerima hasil visual sebelum menyetujui rilis.

## 5. Shared hosting

Stack tetap Laravel12/React18/Inertia/TypeScript/Vite; produksi tidak membutuhkan Node/SSR, Redis, Supabase, atau persistent worker baru. Composer minimum PHP8.2; vendor platform check yang dipasang juga >=8.2. Hanya PHP8.4.23 tersedia untuk pengujian ini (8.2 tidak diuji). Verifikasi PHP CLI dan web hosting konsisten, PDO MySQL/GD/mbstring/dom/fileinfo/curl serta dependensi normal Laravel tersedia, public/storage/cache writable, dan cron yang ada berfungsi.

Target NyanHosting 1CPU/1GB/cPanel dari brief adalah spesifikasi yang diberikan pemilik, bukan audit runtime. Tidak ada load/PageSpeed/CWV/CPU/concurrency benchmark. PHPUnit peak72MB adalah ukuran proses test lokal, bukan kapasitas request hosting. Query/pagination/bundle optimizations lama tetap ada; uji migration pada MySQL/MariaDB staging dan backup restore diperlukan. Tidak ada dependency yang berubah.

## 6. Git dan paket rilis

Index kosong; tidak ada git add/commit/push. .env nyata dan public/build ignored; hanya .env.example yang tracked dari pola environment. Scan nama/pola credential pada working tree tidak menemukan private key/token umum; nilai contoh secret kosong/null. Ini pemeriksaan terbatas, bukan jaminan semua kemungkinan secret terdeteksi. Tidak ada arsip, dump database, node_modules/vendor atau hasil QA yang masuk daftar berkas source untuk commit.

Dua font lokal beserta OFL dan PNG logo owner tersedia. Semua file baru aplikasi/route/migration/controller/service/model/policy/frontend/test telah diinventarisasi. Metadata candidate lama mencakup228 source/aset dan hash masih sama: acceptance tidak mengubah code aplikasi/frontend. PNG dan media sebelumnya tetap utuh. Jangan lupa commit file untracked yang diperlukan.

Build terbaru19,48detik/3456modules PASS. ZIP kandidat yang sudah ada diverifikasi ulang: storage/app/private/releases/gpsfood-v2-build-20261025-165029-b3976330.zip, 55 entri,556307byte. SHA25686acba0ca4a6320b4472b919a6cebe824e44fd3955a99036946c63bb948fdad5. Seluruh isi masih identik dengan build terbaru. Font/logo tidak berada dalam ZIP build dan harus ikut source release. Arsip tidak ditimpa/dibuat ulang karena tidak ada perubahan frontend.

**Source belum berada pada commit rilis yang direview.** Setelah approval, pemilik harus commit/push sendiri, build ulang dari commit yang bersih, buat ZIP unik dan catat SHA commit/hash build. Kecocokan snapshot kerja/ZIP bukan pengganti kecocokan commit rilis.

Usulan grouping commit (semua dilakukan pemilik setelah approval; gunakan pemilihan hunk pada file yang tumpang tindih):

| Grup | Isi |
|---|---|
| 1. Stabilization F01–F06 | Deletion guard, admin:create, upload CSRF/context, filter, TestCase isolation dan regresinya |
| 2. Brand/product/CMS/SEO | Fonts/OFL/logo, UI/catalog/detail, commerce helper/rule, product migration/request/resource/form dan tests |
| 3. Ruang Cerita default off | Connection/config/flag, models/services/middleware/policy/controllers/routes, migration terpisah, UI/moderation/CTA dan tests |
| 4. Acceptance/docs | ReleaseReadinessTest, AGENTS/CLAUDE corrections, konteks/runbook/laporan ini |

Grouping bukan izin deployment intermediate commit; paket harus dibangun dari SHA akhir lengkap yang direview. Jangan stage .env, media runtime, QA harness, ZIP, database atau cache.

## 7. Fix task ini

- Runbook maintenance dipindah sebelum git pull; migration website dibatasi path yang direview, disertai matriks kompatibilitas, risiko DDL/rollback dan langkah melewati setup cerita saat disabled.
- AGENTS.md dan CLAUDE.md dikoreksi: Vite build tidak menjalankan tsc; type check wajib terpisah.
- ReleaseReadinessTest baru:8 cases/151 assertions, meliputi flag off tanpa koneksi, approval off, data preservation, legacy SQL, read-before-migration, expected write incompatibility, simpan metadata/gambar tanpa perubahan lain, MySQL/MariaDB grammar tanpa koneksi dan kompilasi route cache.
- Tidak ada rewrite UI, perubahan dependency, atau perubahan implementasi bisnis.

## 8. Validasi yang benar-benar dieksekusi

Prefix executable PHP pada command di bawah adalah C:\Users\chris\.config\herd\bin\php84\php.exe; shell command menggunakan rtk proxy. Tidak menjalankan composer test karena script itu melakukan config:clear di workspace.

| Command / pemeriksaan | Hasil |
|---|---|
| PHP vendor/bin/phpunit --do-not-cache-result tests/Feature/TestIsolationTest.php | PASS2tests/13assertions sebelum suite/upload |
| PHP vendor/bin/phpunit --do-not-cache-result tests/Feature/ReleaseReadinessTest.php | PASS8tests/151assertions |
| PHP vendor/bin/phpunit --do-not-cache-result | PASS157tests/1064assertions,156passed,1skip symlink Windows;13,968detik,72MB |
| node node_modules/typescript/bin/tsc --noEmit | PASS |
| node --experimental-strip-types tests/frontend/commerce.test.mjs | PASS |
| PHP syntax -l seluruh59PHP berubah (harness lint.php) | PASS |
| PHP vendor/bin/pint --test seluruh59PHP berubah/baru | PASS |
| npm run build | PASS3456modules,19,48detik |
| node storage/app/private/acceptance-20261026-1792948629635/http-check.mjs | PASS16HTML defaultoff,2endpointoff,79aset,enabledsyntheticpage/feed/cursor/category |
| PHP storage/app/private/acceptance-20261026-1792948629635/verify-release.php | PASS ZIP SHA256/55entries vs rebuilt frontend |
| git diff --check | PASS |
| git diff --exit-code -- composer.json composer.lock package.json package-lock.json | PASS tidak berubah |
| Browser/screenshot/interaksi/console/mobile/reduced-motion | NOT VERIFIED — browser inventory kosong |
| PHP8.2, MySQL runtime ALTER/load, production/SMTP/Turnstile asli, backuprestore | NOT RUN |

Vite memberi warning dua absolute font URLs tidak di-resolve saat build; file runtime dan preload diuji HTTP200. Browser font rendering tetap perlu manual. Source/type/build/HTTP bukan pengganti visual QA.

## 9. Keputusan dan langkah pemilik

**CONDITIONAL GO — website V2, cerita disabled.** Tidak ada blocker code baru yang ditemukan pada skenario lokal. Syarat sebelum approval produksi:

1. Selesaikan QA browser manual tiga viewport beserta CMS/form/save yang relevan pada fixture/staging terisolasi, dan terima hasil visual.
2. Operator verifikasi schema/migrations, PHP/ext, flag off, backup dan restore; uji product ALTER pada MySQL/MariaDB staging setara hosting. Jangan memasang code baru ke trafik sebelum migration sukses.
3. Review seluruh source termasuk untracked; setelah rilis disetujui, lakukan logical commits/push sendiri. Build ulang dan paketkan dari SHA final bersih, simpan hash/backup dan cocokkan SHA server.
4. Jalankan urutan cPanel pada runbook: maintenance sebelum pull -> targeted website migration -> aset/cache -> verifikasi flags false -> buka trafik -> health check. Tidak perlu migration/credential database cerita untuk website-only.
5. Jika verifikasi gagal, pertahankan maintenance atau pulihkan source/build berpasangan, sambil mempertahankan schema tambahan dan media. Jangan rollback database otomatis.

**NO-GO — aktivasi Ruang Cerita.** Memerlukan database khusus, Turnstile domain/secret, kebijakan non-draft disahkan, moderator/retensi/escalation/logging/backup, browser lifecycle dan MySQL concurrency QA. Prasyarat cerita ini tidak menghalangi website-only setelah syarat di atas terpenuhi.

Belum diverifikasi: pengalaman browser dan lingkungan hosting aktual. Risiko utama rilis adalah jendela code/schema tidak cocok, aset/commit tidak berpasangan, serta melepas flag cerita sebelum kesiapan operasional.

## 10. Inventaris Git akhir

Awal:53modified/41untracked. Akhir:55modified/43untracked. Diff tracked keseluruhan:55files,+500/-510; tidak termasuk isi43untracked dan mencakup pekerjaan V2/F01–F06 sebelumnya. Index kosong.

~~~text
 M .env.example
 M AGENTS.md
 M CLAUDE.md
 M DEPLOY.md
 M app/Console/Commands/AdminCreateCommand.php
 M app/Http/Controllers/Admin/CertificationController.php
 M app/Http/Controllers/Admin/MessageController.php
 M app/Http/Controllers/Admin/ProductController.php
 M app/Http/Controllers/HomeController.php
 M app/Http/Controllers/NewsController.php
 M app/Http/Controllers/ProductController.php
 M app/Http/Controllers/SitemapController.php
 M app/Http/Middleware/HandleInertiaRequests.php
 M app/Http/Requests/Admin/StoreProductRequest.php
 M app/Http/Requests/Admin/UpdateProductRequest.php
 M app/Http/Resources/ProductResource.php
 M app/Models/Product.php
 M app/Services/ImageProcessor.php
 M config/brand_assets.php
 M config/database.php
 M resources/css/app.css
 M resources/js/Components/Footer.tsx
 M resources/js/Components/Header.tsx
 M resources/js/Components/Packshot.tsx
 M resources/js/Components/ProductCard.tsx
 M resources/js/Components/ProductPackshot.tsx
 M resources/js/Components/admin/ImageUploadField.tsx
 M resources/js/Components/admin/Sidebar.tsx
 M resources/js/Layouts/SiteLayout.tsx
 M resources/js/Pages/Admin/Certifications/Form.tsx
 M resources/js/Pages/Admin/Products/Form.tsx
 M resources/js/Pages/Home.tsx
 M resources/js/Pages/Products/KuicipCatalog.tsx
 M resources/js/Pages/Products/ProductDetail.tsx
 M resources/js/Pages/Products/TekoCatalog.tsx
 M resources/js/lib/i18n.tsx
 M resources/js/types/index.ts
 M resources/views/app.blade.php
 M routes/console.php
 M routes/web.php
 M tailwind.config.js
 M tests/Feature/Admin/ArticleCrudTest.php
 M tests/Feature/Admin/ImageUploadTest.php
 M tests/Feature/Admin/MessageManagementTest.php
 M tests/Feature/Admin/ProductCrudTest.php
 M tests/Feature/Admin/TranslationTest.php
 M tests/Feature/Auth/AuthenticationTest.php
 M tests/Feature/Auth/EmailVerificationTest.php
 M tests/Feature/Auth/PasswordConfirmationTest.php
 M tests/Feature/Auth/PasswordResetTest.php
 M tests/Feature/Auth/PasswordUpdateTest.php
 M tests/Feature/Auth/RegistrationTest.php
 M tests/Feature/ExampleTest.php
 M tests/Feature/ProfileTest.php
 M tests/TestCase.php
?? app/Console/Commands/PruneStories.php
?? app/Http/Controllers/Admin/StoryController.php
?? app/Http/Controllers/StoryController.php
?? app/Http/Middleware/StoryAvailable.php
?? app/Http/Requests/StoreStoryRequest.php
?? app/Http/Resources/ProductSummaryResource.php
?? app/Models/Story.php
?? app/Models/StoryModerationEvent.php
?? app/Models/StoryRecord.php
?? app/Models/StoryReport.php
?? app/Policies/StoryPolicy.php
?? app/Rules/ShopeeUrl.php
?? app/Services/StorySubmission.php
?? app/Services/TurnstileVerifier.php
?? app/Support/StoryDatabase.php
?? app/Support/StoryIdentity.php
?? config/stories.php
?? database/migrations/2026_10_25_100000_add_commerce_and_seo_to_products_table.php
?? database/migrations/stories/2026_10_25_110000_create_story_tables.php
?? docs/GPSFOOD_PROJECT_CONTEXT.md
?? docs/GPSFOOD_V2_ACCEPTANCE.md
?? docs/GPSFOOD_V2_DEPLOYMENT.md
?? public/fonts/archivo-black-latin-OFL.txt
?? public/fonts/archivo-black-latin.woff2
?? public/fonts/dm-sans-latin-OFL.txt
?? public/fonts/dm-sans-latin.woff2
?? public/images/logo/logo-gpsfood.png
?? resources/js/Components/BrandLoader.tsx
?? resources/js/Components/ProductCatalog.tsx
?? resources/js/Components/admin/Pagination.tsx
?? resources/js/Components/sections/StoryInvitation.tsx
?? resources/js/Pages/Admin/Stories/Index.tsx
?? resources/js/Pages/Admin/Stories/Show.tsx
?? resources/js/Pages/Kuicip/Cerita.tsx
?? resources/js/lib/commerce.ts
?? tests/Feature/Admin/CertificationPdfDeletionTest.php
?? tests/Feature/Admin/PdfUploadTest.php
?? tests/Feature/AdminCreateCommandTest.php
?? tests/Feature/ProductExperienceTest.php
?? tests/Feature/ReleaseReadinessTest.php
?? tests/Feature/StoryPlatformTest.php
?? tests/Feature/TestIsolationTest.php
?? tests/frontend/commerce.test.mjs
~~~
