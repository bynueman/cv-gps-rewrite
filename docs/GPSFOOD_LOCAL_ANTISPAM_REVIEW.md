# GPS FOOD — review anti-spam first-party, lokal

> Local acceptance telah diterima pemilik. Untuk rilis source/build Stage A yang diotorisasi berikutnya, gunakan [runbook rilis Stage A](GPSFOOD_ANTISPAM_STAGE_A_RELEASE.md). Status “belum commit/push” di laporan implementasi ini merekam fase review sebelumnya; status rilis aktual ada pada laporan artefak final.

26 Oktober 2026 (Asia/Jakarta). Baseline/HEAD: `e39cef2896837675670bedd7b3622eae5c09e504`.

**Implementasi lokal selesai. GO untuk review pemilik; NO-GO untuk mengaktifkan Stage B sebelum approval operasional dan QA nyata.** Tidak ada commit, push, deployment, akses cPanel, perubahan environment nyata atau mutation cerita produksi. Status produksi Stage A berasal dari informasi pemilik, bukan inspeksi server oleh agen.

## 1. Berkas yang diubah

Baru:

- `app/Services/StoryAntispam.php` — provider selection, issuance/verifikasi lokal, MAC/session/expiry/attempts/lock dan retry grant.
- `resources/js/Components/StoryVerification.tsx` — field matematika, renewal, status/error, timeout dan widget Turnstile opsional.
- `tests/Feature/StoryAntispamTest.php` —15kasus first-party dan abuse/security.
- `docs/GPSFOOD_LOCAL_ANTISPAM_REVIEW.md` — laporan ini.

Dimodifikasi:

- `config/stories.php` — STORIES_ANTISPAM_PROVIDER; default kode turnstile agar deployment lama tetap ketat.
- `.env.example` — contoh pemilihan local, tanpa credential atau perubahan .env nyata.
- `app/Http/Requests/StoreStoryRequest.php` — validasi honeypot dan field sesuai provider, pesan Indonesia.
- `app/Services/StorySubmission.php` — local verification sebelum query cerita; legacy Turnstile mempertahankan retry yang sudah ada.
- `app/Http/Controllers/StoryController.php` — endpoint challenge, props provider, site key hanya untuk Turnstile.
- `routes/web.php` — POST challenge dengan gate yang sama, CSRF dan throttle10/menit.
- `resources/js/Pages/Kuicip/Cerita.tsx` — integrasi verification, honeypot, payload sesuai provider dan pemeliharaan form saat gagal.
- `tests/Feature/StoryPlatformTest.php` — konfigurasi Turnstile eksplisit untuk regresi legacy.
- `tests/Feature/StorySoftLaunchTest.php` — endpoint challenge juga tertutup di Stage A.
- `tests/frontend/story-soft-launch.test.mjs` — markup provider lokal/tidak dikenal, input aksesibel, hidden trap, chrome/no promo.
- `tests/Support/LocalPreview.php` — provider local eksplisit pada profil localhost terisolasi.
- `docs/GPSFOOD_STORIES_SOFT_LAUNCH.md` — konfigurasi/preflight/upgrade/operasi first-party dan pergantian provider.
- `docs/GPSFOOD_LOCAL_PREVIEW.md` — preview matematika nyata, tanpa reset fixture.
- `docs/GPSFOOD_PROJECT_CONTEXT.md` — catatan scope dan batas task.

Tidak mengubah Home, Header, Footer, ProductCatalog, produk/CMS, StoryCard, StoryDetailModal, schema/migration, autentikasi/policy, database guard, retention/prune atau StoryAvailability.

## 2. Cara kerja anti-spam lokal

Hanya dua provider: local dan turnstile. Unknown/none/disabled/typo gagal tertutup503; client tidak dapat mengganti mode lewat payload.

Pada local, challenge diterbitkan melalui POST /kuicip/cerita/challenge setelah CSRF, throttle dan gate publik lolos. Penjumlahan acak operand1–9 disertai nonce acak128bit, purpose, waktu penerbitan server serta hash session. Token dilindungi encryption+MAC Laravel memakai APP_KEY yang sudah ada; jawaban hanya ada di cache server, bukan prop/token frontend. JSON hanya token, question, expires_in dan wait_seconds.

Batas tetap: berlaku300detik, minimal umur2detik menurut waktu server, maksimal3jawaban salah pernonce. Signature tamper, session berbeda, expired/future token, trap terisi atau missing/malformed fields ditolak. Cache lock mengendalikan verifikasi serentak. Setelah benar, cache menyimpan UUID/hash payload, bukan jawaban; hanya retry identik yang boleh memakai grant itu, tidak kiriman baru. Grant tidak memperpanjang expiry awal. Setelah expired, soal baru dapat dipakai dengan UUID/payload yang sama untuk retry tanpa duplikasi atau token penghapusan baru.

Honeypot contact_website harus kosong; field hidden, tabindex=-1, autocomplete off dan aria-hidden, tanpa interaksi bagi pengunjung/assistive technology. Timing tidak mempercayai timestamp browser. UI memberi waktu siap singkat, mendeteksi expiry, menyediakan tombol soal baru dan mempertahankan cerita/consent pada kegagalan. Hanya sukses202 yang mereset body/consent.

MAC/locking menggunakan fitur Laravel yang sudah terpasang: [encryption](https://laravel.com/docs/12.x/encryption), [atomic locks](https://laravel.com/docs/12.x/cache#atomic-locks). Tidak menambah dependency atau layanan eksternal.

## 3. Batas keamanan dan abuse

Aritmetika/honeypot **mudah diotomatisasi**, bukan bot protection kuat. Sesuai untuk soft launch kecil yang dimonitor, bersama quota, cooldown, duplicate checks dan mandatory moderation. Unlisted bukan private.

Challenge issuance10/menit perIP dan submission5/menit tetap dipisah. Wrong/invalid local verification ditolak sebelum query/write stories atau konsumsi kuota, dibuktikan query-log test. Throttle, cache dan sessions sendiri tetap memakai resource aplikasi; distributed bots tidak dijamin tertahan. Shared IP dapat berbagi limit.

Production local mewajibkan persistent session/cache driver file/database dengan atomic locks. array/null/failover-array tidak diterima. Cache TTL dapat lazy secara fisik; pantau resource cache/disk dan gunakan housekeeping yang disetujui operator. Tidak menambah cron penghapusan cerita. Session/key/cache berubah memerlukan soal baru; APP_KEY tidak boleh dirotasi sembarangan.

Tidak mencatat jawaban, token, body, session ID atau credential dalam log baru. Cache menyimpan metadata challenge dan hash payload, tanpa teks cerita. Tidak ada API property berisi jawaban.

## 4. Fitur yang dipertahankan

CSRF (termasuk endpoint issuance), auth/superadmin/policies/noindex, policy approval gate, kuota harian persistent200, cooldown60detik, duplicate detection, idempotency, pending-default, independent consent, public/private separation, social reuse opt-in, deletion-token hash/receipt, report/reaction dan moderation audit tetap aktif. Legacy TurnstileVerifier tidak diubah dan jalur yang dipilih eksplisit tetap lulus tes.

No promotion tetap berlaku. Tidak ada perubahan desain homepage, navbar/footer, katalog atau card/modal. Tidak ada seeding data, tambahan schema atau penjadwalan prune.

## 5. Tes aktual

| Pemeriksaan | Hasil |
|---|---|
| TestIsolationTest | PASS2tests/13assertions |
| StoryAntispamTest | PASS15tests/208assertions |
| Gabungan StoryAntispam, StoryPlatform, StorySoftLaunch, LocalPreviewGuard, ReleaseReadiness, Seo | PASS64tests/705assertions |
| Frontend soft-launch/card-modal/splash/commerce | PASS21tests |
| TypeScript noEmit | PASS |
| Pint10PHP berubah | PASS |
| PHP syntax10file | PASS |
| Vite production build lokal | PASS3458modules/23,58detik |
| git diff --check | PASS |

Command:

```powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe vendor/bin/phpunit --do-not-cache-result tests/Feature/TestIsolationTest.php
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe vendor/bin/phpunit --do-not-cache-result tests/Feature/StoryAntispamTest.php tests/Feature/StoryPlatformTest.php tests/Feature/StorySoftLaunchTest.php tests/Feature/LocalPreviewGuardTest.php tests/Feature/ReleaseReadinessTest.php tests/Feature/SeoTest.php
rtk proxy node --experimental-strip-types --test tests/frontend/story-soft-launch.test.mjs tests/frontend/story-cards.test.mjs tests/frontend/splash.test.mjs tests/frontend/commerce.test.mjs
rtk proxy node node_modules/typescript/bin/tsc --noEmit
rtk proxy npm run build
rtk git diff --check
```

Pint dan syntax hanya pada10file PHP terkait. Tidak menjalankan audit/suite seluruh aplikasi.

HTTP nyata localhost dengan session file/cache file: landing200, provider=local, siteKey=null, noindex PASS, missingCSRF419, challenge200 dengan hanya empat field yang diizinkan, wrong-answer422 dengan field error challenge_answer. Round trip memakai cookie session+CSRF nyata; tidak memakai browser atau fake token. Dua font WOFF2 dilayani200. Hash database stories.sqlite preview identik sebelum/sesudah; tidak memasukkan story baru dalam profil persisten.

Warning build URL font absolut tetap ada; dua font lulus HTTP. Commerce harness mengeluarkan warning useLayoutEffect server-renderer yang sudah ada, bukan hasil Console browser.

**VISUAL BROWSER QA NOT VERIFIED:** konektor browser kosong. Tidak mengklaim screenshot/layout/focus/Console, MySQL/PHP8.2 hosting, load test, spam resistance di lapangan atau CAPTCHA Turnstile nyata sudah diuji. PHP lokal8.4; MySQL hosting tetap perlu operator QA. Turnstile nyata bukan syarat provider local.

## 6. Preview lokal

Aktif dan merespons200 saat verifikasi:

http://127.0.0.1:8766/kuicip/cerita

Hard refresh untuk memuat bundle baru. Profil lokal memakai local provider dan fixture fiksi, policy LOCAL-DEMO-NOT-APPROVED; itu bukan approval Stage B produksi. Tidak perlu setup/reset/reseed.

Jika server berhenti, dari root repository:

```powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe -S 127.0.0.1:8766 -t public scripts/local-preview-router.php
```

Uji interaktif pemilik: isi cerita fiksi dan consent, salahkan jawaban lalu minta soal baru; pastikan seluruh tulisan/consent tetap. Periksa mobile/desktop, keyboard, reader announcements, expiry, session baru serta server errors. Submission yang dilakukan manual di preview persisten akan membuat fixture baru; jangan memakai data pribadi.

## 7. Environment produksi yang diperlukan nanti

Hanya setelah otorisasi rilis dan pemasangan backend+frontend yang cocok, melalui editor privat operator:

```dotenv
STORIES_ENABLED=true
STORIES_POLICIES_APPROVED=false
STORIES_ANTISPAM_PROVIDER=local
```

Pertahankan DB stories/user, abuse key, APP_KEY dan session/cache file/database yang sudah berfungsi. Cloudflare keys tidak diperlukan untuk local. Tanpa explicit mode, default kode masih turnstile demi kompatibilitas; jangan mengasumsikan perubahan source saja sudah memilih local.

Stage A tetap tidak menerima submission/feed/challenge. .env.example berisi pilihan local sebagai contoh, bukan perubahan environment produksi. Tidak menjalankan key:generate atau mencetak secret.

## 8. Migration

**Tidak ada migration baru.** Dedicated stories schema tidak berubah. Karena menurut pemilik DB dan admin Stage A sudah siap, jangan mengulang migration/reset/seed. Mode database cache memerlukan tabel cache/cache_locks Laravel yang sudah ada; jika tidak siap, berhenti dan perbaiki konfigurasi atau pilih file cache yang benar-benar writable, bukan migrate:fresh.

## 9. Deployment manual, setelah approval terpisah

Lihat [runbook lengkap](GPSFOOD_STORIES_SOFT_LAUNCH.md), terutama bagian3 dan bagian upgrade dari Stage A e39:

1. Otorisasi commit/push/rilis baru; sekarang masih working tree, belum ada SHA rilis baru atau ZIP untuk perubahan ini.
2. Backup source/build dan data secara privat. Pertahankan enabled=true, policies_approved=false.
3. Maintenance, update source ke commit baru yang ditinjau dan upload/extract frontend build yang berasal dari commit sama. ZIP lama e39 tidak mewakili local provider. Upload frontend saja tidak mengganti backend verification.
4. Pilih provider local pada .env privat, pastikan session/cache persistent+locks. Tidak migration/seed.
5. optimize:clear → config:cache → route:cache → view:cache → up.
6. Preflight yang hanya mencetak provider/gate/boolean driver/lock, bukan seluruh config. Pastikan local, public_enabled=0, driver dan lock didukung.
7. Verifikasi superadmin tetap dapat moderasi dan endpoint publik masih503 di Stage A.
8. Setelah approval operasional, uji controlled real submission Stage B dengan verifikasi yang dipilih, pending/public/private/approve/hide/removal dan cleanup melalui workflow resmi.
9. Darurat: policies_approved=false, refresh config; enabled=true mempertahankan admin. Jangan drop/reset/prune data untuk menutup publik.

Turnstile opsional dapat dipilih kemudian dengan provider=turnstile beserta key/hostname yang sah dan policy/QA provider terkait. Ganti provider saat publik ditutup dan minta pengunjung refresh form.

## 10. Gate Stage B yang tersisa

Pemilik masih harus menyetujui teks/versi kebijakan non-draft, retensi cerita/cadangan, kontak/privasi, moderator utama/pengganti, alur laporan/response spam, backup/restore dan QA browser nyata. Memilih local menghapus kewajiban Cloudflare, bukan kewajiban approval dan moderasi.

## 11. Rekomendasi dan Git

**GO untuk review lokal** dan persiapan rilis terpisah setelah pemilik menerima perubahan. **NO-GO untuk Stage B sekarang.**

Seluruh perubahan masih lokal dan unstaged. HEAD tetap e39cef2896837675670bedd7b3622eae5c09e504. Tidak membuat commit, push, ZIP rilis atau deployment, tidak mengakses cPanel maupun database/cerita/CMS produksi. Build lokal ignored dibuat ulang; arsip rilis historis tidak ditimpa.
