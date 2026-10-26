# Kuicip Ruang Cerita — runbook soft launch tanpa promosi

26 Oktober 2026, Asia/Jakarta. Baseline website V2: `671ee9603dcc2da048cb821355e36a529494a91d`. Dokumen ini adalah rencana manual, **bukan bukti deployment atau persetujuan kebijakan**. Semua operasi server dilakukan pemilik, bukan agen. Ikuti dokumen ini untuk aktivasi cerita; instruksi cerita lama di GPSFOOD_V2_DEPLOYMENT.md adalah riwayat.

## Keputusan dan ruang lingkup

| Fase | Flag produksi | Perilaku | Keputusan saat persiapan lokal |
|---|---|---|---|
| Mati | enabled=false, policies_approved=false | Landing informatif; API dan moderasi404 | Default rilis tetap seperti ini |
| A — persiapan admin | enabled=true, policies_approved=false | Superadmin dapat moderasi setelah DB siap; form/feed publik tertutup503 | CONDITIONAL GO untuk prosedur persiapan manual setelah backup, DB, build dan preflight lulus |
| B — unlisted | enabled=true, policies_approved=true, versi sah non-draft | Direct URL menerima kiriman pending; feed hanya approved+public+consent | NO-GO sampai seluruh gate operasional dan QA nyata disahkan |
| Darurat publik | enabled=true, policies_approved=false | API publik tertutup; admin tetap dapat menangani insiden | Tidak menghapus data |

URL publik: https://gpsfood.id/kuicip/cerita . Admin: https://gpsfood.id/admin/stories .

**Unlisted bukan private.** Siapa pun dapat membagikan atau menemukan URL. Ini bukan undangan/password gate. Navbar/footer asli tetap ada pada halaman cerita. Promosi homepage, footer dan katalog Kuicip dihapus; tidak menambah promosi lain. Sitemap tetap tidak memasukkan cerita; response landing memakai X-Robots-Tag noindex,nofollow dan React mempertahankan meta robots. Route dapat terlihat dalam definisi routing JavaScript; tidak ada janji kerahasiaan alamat.

Tidak ada desain V2.1. Slogan/identitas Kuicip, homepage V2 selain blok promosi yang diminta dihapus, CMS produk, logo, font, SEO perusahaan dan jalur Shopee/WhatsApp dipertahankan.

## Yang sudah tersedia dan dipakai ulang

- Submission anonim20–1500 karakter plain text, kategori, pilihan public/private, terms, consent publikasi tersendiri dan reuse sosial opsional.
- New story selalu pending. Feed hanya public+approved+publication_consent+published_at, tanpa soft-deleted;6 per halaman, cursor dan kategori.
- Kartu preview lima baris dan modal Headless UI untuk teks lengkap; dukungan/laporan berbagi handlers/state.
- Token penghapusan acak256bit ditampilkan sekali; DB menyimpan hash. Kode cerita saja tidak cukup. Penghapusan pemilik meredaksi body, mencabut consent/publikasi dan soft-delete.
- Superadmin saja: daftar20 per halaman, filter status/visibility/reports, detail privat, approve/reject/hide, review laporan dan riwayat audit terbatas/paginasi.
- CSRF, middleware auth/superadmin/noindex dan policy tetap aktif.
- Semua model cerita memakai koneksi stories. Tidak ada join FK lintas DB; actor_id mengacu secara logis ke user website.
- Batas HTTP per jenis endpoint; cooldown dan kuota memakai tabel persistent dengan transaksi/lock. Idempotency UUID dan hash request mencegah retry menggandakan kiriman.

Perubahan rilis: hapus tiga jalur promosi; satukan gate halaman/API agar versi produksi kosong, terlalu panjang atau berawalan draft (termasuk huruf besar) tidak membuka form; tampilkan periode retensi dari config dan kontak privasi perusahaan; perbaiki guard DB agar migrator yang sementara mengubah default menjadi stories tidak menolak dirinya sendiri; tolak reuse nama DB website termasuk alias hostname/DB_URL. Jadwal prune otomatis dihapus selama soft launch.

## 1. Backup dan preflight pemilik

Jangan menjalankan command server dari repo lokal. Pada **cPanel Terminal milik gpsfoodi**:

```bash
cd /home/gpsfoodi/gsp-laravel
pwd -P
php -v
git branch --show-current
git status --short
git rev-parse HEAD
```

Harus repo yang benar, branch main dan tidak ada perubahan operator yang belum ditangani. Jika berbeda/dirty: berhenti, catat perubahan dan tangani manual; jangan reset/clean/force checkout. PHP CLI dan web harus konsisten>=8.2, PDO MySQL, mbstring, openssl/curl dan dependensi normal Laravel tersedia. Pengujian agen hanya PHP8.4; PHP8.2/MySQL hosting belum dijalankan.

Sebelum update: ambil backup cPanel source+build+uploads+.env+DB website. Jika DB stories sudah ada, ekspor terpisah melalui cPanel/phpMyAdmin. Simpan di lokasi privat di luar public, izin terbatas, dengan enkripsi dan masa simpan yang disetujui pemilik. Jangan menaruh backup SQL/.env di public atau Git. Verifikasi satu prosedur restore pada database uji terisolasi, tidak pada DB website/TMC/Finance. Backup provider mingguan saja belum membuktikan RPO/RTO yang sesuai.

Catat SHA sumber dan salinan public/build lama sebagai pasangan recovery. Jangan menghapus upload atau mengubah produk/CMS. Periksa ruang disk dan kemampuan rollback source+build sebelum maintenance.

Pemilik menyiapkan nilai berikut dari laporan rilis; placeholder wajib diganti, bukan dijalankan apa adanya:

```bash
releaseSha='<SHA_COMMIT_DARI_LAPORAN_RILIS>'
releaseZip='/home/gpsfoodi/private-releases/<ZIP_DARI_LAPORAN_RILIS>.zip'
```

## 2. Database dan user khusus

Di cPanel → MySQL Databases:

1. Buat database baru, contoh **gpsfoodi_kuicipstories**. Gunakan nama prefiks aktual cPanel, berbeda dari seluruh nama DB website. Jangan memilih DB website maupun TMC/Finance.
2. Buat user baru, contoh **gpsfoodi_story**, dengan password acak kuat melalui generator/password manager. Jangan menaruh password dalam argumen terminal, chat, Git, screenshot atau laporan.
3. Hubungkan user hanya ke DB stories tersebut. Untuk migration: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES. Jangan beri privilege global atau akses ke DB lain.
4. Sesudah migration, cabut CREATE/ALTER/INDEX/REFERENCES; runtime cukup SELECT/INSERT/UPDATE/DELETE pada DB stories. Pemilik harus memverifikasi grants di cPanel; keberhasilan koneksi saja bukan bukti isolasi privilege.
5. Tidak menyalin SQLite/fixture preview, tidak seeding produksi, tidak membuat kiriman palsu sebagai testimonial.

Pada editor .env server, isi secara privat:

```dotenv
STORIES_ENABLED=false
STORIES_POLICIES_APPROVED=false
STORIES_POLICY_VERSION=draft-v1
STORY_DB_DRIVER=mysql
STORY_DB_HOST=localhost
STORY_DB_PORT=3306
STORY_DB_DATABASE=<NAMA_DB_STORIES_AKTUAL>
STORY_DB_USERNAME=<USER_KHUSUS_STORIES>
STORY_DB_PASSWORD=<PASSWORD_DARI_PASSWORD_MANAGER>
STORIES_ABUSE_KEY=<NILAI_ACAK_UNIK_MINIMAL_32_KARAKTER>
STORIES_DAILY_QUOTA=200
STORIES_COOLDOWN_SECONDS=60
STORIES_RETENTION_DAYS=<JUMLAH_HARI_YANG_AKAN_DISAHKAN_PEMILIK>
```

Gunakan host/port yang diberi hosting, bukan mengasumsikan localhost bila penyedia menetapkan nilai lain. Driver mysql juga dapat dipakai ke MariaDB melalui PDO MySQL; dukungan aktual tetap harus diverifikasi. Jangan mengubah DB_* utama. Guard memakai perbandingan nama DB yang konservatif di seluruh koneksi MySQL/MariaDB website, sehingga nama stories wajib berbeda sekalipun host memakai alias atau server berbeda.

Buat abuse key lewat generator lokal tepercaya dan simpan sebagai secret; jangan pakai contoh/test key, jangan output ke laporan. Jangan merotasi APP_KEY sebagai bagian task ini. Perubahan abuse key mengubah hash deduplikasi dan mengganggu retry request lama; rotasi hanya melalui prosedur insiden.

## 3. Turnstile nyata

Pemilik membuka Cloudflare Dashboard → Turnstile → Add widget, beri nama Kuicip Ruang Cerita production, pilih Managed dan tambahkan hostname **gpsfood.id**. Hostname dimasukkan tanpa scheme/path. Pre-clearance tidak diperlukan untuk implementasi ini.

Salin site key dan secret ke editor .env privat:

```dotenv
TURNSTILE_SITE_KEY=<SITE_KEY_WIDGET_PRODUKSI>
TURNSTILE_SECRET_KEY=<SECRET_KEY_WIDGET_PRODUKSI>
TURNSTILE_HOSTNAME=gpsfood.id
```

Jangan memakai test keys Cloudflare atau nilai preview lokal. Aplikasi memvalidasi hostname tunggal secara tepat; jika domain www juga melayani halaman tanpa canonical redirect, selesaikan konsistensi hostname sebelum B. Jangan memakai daftar comma-separated pada TURNSTILE_HOSTNAME.

Server memanggil Siteverify dengan timeout5detik dan menerima hanya HTTP sukses, success=true, hostname tepat serta action **story_submit**. Provider gagal, timeout, hostname/action salah atau secret kosong menolak kiriman. Cloudflare menerapkan expiry300detik dan single-use token; replay baru ditolak. Retry identik dengan UUID yang sudah tersimpan mengembalikan receipt tanpa token penghapusan baru, bukan menciptakan cerita baru.

Tes lokal memalsukan Siteverify hanya dalam harness/testing atau profil preview yang dibatasi environment/path. Produksi menolak secret test umum dan preview SQLite. Tidak ada bypass produksi pada service.

Rujukan resmi: [dashboard](https://developers.cloudflare.com/turnstile/get-started/widget-management/dashboard/), [hostname](https://developers.cloudflare.com/turnstile/additional-configuration/hostname-management/), [Siteverify](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/). Pemeriksaan provider nyata hanya oleh pemilik; jangan mengirim secret lewat curl argument atau merekam payload Siteverify dalam log.

## 4. Update source dan build yang cocok

Seluruh langkah ini tetap manual. Bekukan trafik sebelum perubahan sumber:

```bash
cd /home/gpsfoodi/gsp-laravel
php artisan down --retry=60
git fetch origin main
git cat-file -e "$releaseSha^{commit}"
git merge --ff-only "$releaseSha"
test "$(git rev-parse HEAD)" = "$releaseSha"
```

Berhenti jika ada error, SHA berbeda, local changes atau fast-forward tidak mungkin. Tidak force/reset. Hanya SHA yang dilaporkan dan ditinjau yang boleh dilanjutkan.

Upload ZIP final ke private-releases di luar public. Bandingkan hash literal dari laporan:

```bash
sha256sum "$releaseZip"
unzip -l "$releaseZip"
unzip -o "$releaseZip" -d /home/gpsfoodi/gsp-laravel/public
test -f /home/gpsfoodi/gsp-laravel/public/build/manifest.json
php artisan optimize:clear
```

Arsip harus hanya berisi build/ beserta manifest/chunks, tanpa .env, DB, dump, credentials atau node_modules/vendor. Agen memverifikasi setiap byte ZIP terhadap public/build setelah commit rilis. Jangan membuat nested public/build/build. Pertahankan backup build sebelumnya; tidak perlu menghapus chunk lama dalam prosedur ini. Jangan menjalankan npm/Node/Vite pada hosting. Composer dependency tidak berubah dalam rilis ini.

## 5. Preflight DB dan migration yang tepat

Dengan flags masih false dan config cache lama sudah dibersihkan, jalankan pemeriksaan tanpa membocorkan konfigurasi:

```bash
php artisan tinker --execute='try { App\Support\StoryDatabase::ensureConfigured(); Illuminate\Support\Facades\DB::connection("stories")->select("SELECT 1"); echo "STORY_DB_PREFLIGHT_OK\n"; } catch (\Throwable) { fwrite(STDERR, "STORY_DB_PREFLIGHT_FAILED; inspect private configuration and grants.\n"); exit(1); }'
php artisan migrate:status --database=stories --path=database/migrations/stories/2026_10_25_110000_create_story_tables.php
```

Database baru mungkin belum mempunyai tabel migrations; pesan tersebut bukan alasan menjalankan perintah reset. Periksa nama koneksi/grants dari cPanel dan pastikan DB memang kosong khusus stories. Bila tabel stories sudah ada tetapi migration belum tercatat, berhenti dan rekonsiliasi schema; jangan menjalankan up berulang atau menghapus tabel. Error DDL MySQL dapat meninggalkan schema parsial.

Hanya setelah preflight, backup, koneksi dan path benar:

```bash
php artisan migrate --database=stories --path=database/migrations/stories/2026_10_25_110000_create_story_tables.php --force
php artisan migrate:status --database=stories --path=database/migrations/stories/2026_10_25_110000_create_story_tables.php
```

Migration menyatakan connection=stories dan seluruh Schema memakai stories. Ia membuat stories, story_moderation_events, story_reports, story_reactions dan story_limits; tabel migrations Laravel juga berada pada koneksi stories. Tidak ada migration produk/website yang perlu dijalankan untuk rilis ini. Tidak memakai migrate:fresh, migrate:reset, rollback, down atau seed. Cabut privilege DDL user runtime setelah sukses.

## 6. Stage A — persiapan admin

Ubah hanya dua flag server melalui editor privat:

```dotenv
STORIES_ENABLED=true
STORIES_POLICIES_APPROVED=false
```

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Checklist A wajib dicatat pemilik:

- Login superadmin yang sudah ada → /admin/stories200, menu Kuicip — Ruang Cerita terlihat, filter/detail/audit/report screens berfungsi. Database baru seharusnya kosong, tidak diisi demo.
- Guest menuju login, editor403 untuk index/detail/update/report-review; admin response noindex,nofollow.
- /kuicip/cerita200 menunjukkan belum dibuka, tanpa form aktif/feed. /kuicip/cerita/feed503. POST submission/removal/react/report juga tertutup503 (permintaan tanpa CSRF dapat419 lebih dahulu; itu bukan kegagalan gate).
- Navbar, footer, homepage dan katalog tidak mempunyai link/kampanye Ruang Cerita. Slogan merek tetap boleh ada. Sitemap tidak mencantumkan URL cerita.
- Session/cache hosting harus persistent, bukan array; APP_DEBUG=false dan APP_ENV=production. Gunakan cache file/database yang sudah berfungsi tanpa Redis baru. Pastikan direktori Laravel terkait writable.
- Cron aggregate yang sudah ada tetap berfungsi. **Tidak ada jadwal stories:prune dalam rilis soft launch**. Periksa cPanel Cron Jobs juga tidak mempunyai perintah prune langsung dari konfigurasi lama.
- Verifikasi backup, grants, schema dan log server tanpa mencetak story body, token, credentials atau seluruh config. Belum ada approval kebijakan publik pada tahap ini.

DB/schema/grants PHP8.2/MySQL dan UI produksi belum diuji oleh agen. Jika A gagal: kembalikan enabled=false, refresh config, lanjutkan website V2 biasa. Jangan buka B.

## 7. Gate persetujuan operasional sebelum B

Pemilik wajib mencatat persetujuan nyata, termasuk versi/tanggal/penanggung jawab:

1. Teks Indonesia/Inggris yang benar-benar tampil di halaman: komunitas, privasi, batas layanan, consent, hak penghapusan, kontak perusahaan dan periode retensi. Periksa pula praktik metadata hosting/Cloudflare. Draft internal/test fixture tidak dihitung sebagai kebijakan bisnis.
2. Angka STORIES_RETENTION_DAYS yang disetujui; durasi cadangan, penanganan permintaan privasi dan penghapusan dari backup saat restore. Runtime tidak mengenkripsi body sendiri; akses DB/cadangan harus dibatasi dan perlindungan hosting diverifikasi.
3. Moderator superadmin utama dan pengganti, jam pemeriksaan, respons laporan, konten berisiko/identitas pribadi, eskalasi dan kontak. Jangan menyalin data identitas/isi cerita ke alasan moderasi atau tools eksternal.
4. Jadwal backup terpisah, restore test, RPO/RTO dan akses backup. Restore harus menerapkan kembali owner-removal/hide yang terjadi sesudah snapshot agar cerita terhapus tidak muncul lagi.
5. Prosedur retensi manual dengan pemilik yang bertanggung jawab, cukup sering untuk memenuhi periode yang dinyatakan. Tidak ada cron penghapusan baru yang dibuat secara diam-diam.
6. Turnstile widget asli, hostname, outbound HTTPS dan skenario failure terverifikasi melalui browser nyata.
7. Browser mobile/desktop, keyboard/modal/focus, CSRF, token receipt, console/network dan navigasi Inertia lolos. Unlisted bukan access control.

Jika teks/periode/privasi belum disetujui, tetap A. Jika perlu mengubah isi kebijakan pada source, buat perubahan/release baru dan uji sebelum B; jangan menandai versi draft sebagai approved hanya untuk membuka form.

## 8. Stage B — unlisted public soft launch

Setelah seluruh gate tercatat, pemilik menetapkan versi aktual yang disahkannya, maksimal80karakter, tidak kosong dan tidak berawalan draft. **Contoh nama versi bukan persetujuan.**

```dotenv
STORIES_ENABLED=true
STORIES_POLICIES_APPROVED=true
STORIES_POLICY_VERSION=<VERSI_AKTUAL_YANG_DISAHKAN>
```

Periode retensi terpilih harus sama dengan kebijakan yang dibaca pemilik. Kemudian:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Uji penerimaan terkontrol, tanpa seeder dan tanpa testimonial palsu:

1. Buka direct URL dalam browser guest. Navbar/footer perusahaan tetap ada; tidak muncul banner preview, fake CAPTCHA atau policy LOCAL-DEMO.
2. Kirim cerita **jelas berlabel UJI OPERASIONAL — BUKAN TESTIMONI**, tanpa data pribadi, setelah menyelesaikan Turnstile nyata. Simpan kode/token di tempat privat tanpa memasukkannya ke URL.
3. Receipt202; superadmin melihat pending. Guest feed belum memuatnya. Retry identik tidak menggandakan record dan tidak menampilkan token lagi. Token Turnstile yang dipakai dengan UUID baru harus gagal422.
4. Setelah cooldown60detik (dan budget5submission/menit), kirim satu cerita privat berlabel uji. Consent sosial opsional; privat tidak boleh menjadi publik meskipun dimoderasi approved. Biarkan privat untuk memeriksa isolasi.
5. Approve hanya cerita publik uji melalui admin dengan alasan yang aman. Feed hanya menampilkan approved public ber-consent; tidak pending/private/rejected/hidden. Di jaringan browser tidak ada body private dalam landing/feed.
6. Periksa kategori, pagination bila ada cukup data nyata, clamp lima baris, modal teks lengkap, Escape/Tab/focus return, dukungan sekali per fingerprint harian, report dan daftar laporan. Jangan membuat banyak kiriman palsu hanya untuk memenuhi pagination.
7. Review laporan melalui admin, hide cerita uji publik; ia segera hilang dari feed. Gunakan token removal untuk kedua cerita uji bila perlu; token salah tidak menghapus. User workflow removal meredaksi body dan soft-delete, bukan DROP tabel.
8. Periksa header noindex,nofollow, sitemap exclusion, no promotion pada home/nav/footer/catalog, serta website/company/product routes tetap normal.
9. Simpan hasil uji tanpa data/token sensitif. Hanya bagikan direct URL kepada kelompok review yang dipilih pemilik; tidak mengirim announcement publik otomatis.

Jangan menyebut B GO hanya karena code sudah dipush. Seluruh pengujian nyata di atas tetap tugas pemilik; agen tidak melakukan submission/moderation produksi.

## 9. Operasi, backup dan retensi

Default konservatif yang sudah ada:200kiriman/hari global,60detik cooldown per fingerprint, POST5/menit, feed60/menit, react20/menit, report5/menit, removal5/menit. Fingerprint HMAC memakai IP+user-agent+tanggal; shared IP/browser dapat berbagi limit dan fingerprint berganti hari. Ini bukan identitas permanen atau jaminan melawan semua bot. Validasi/rate limits tetap berlaku saat CAPTCHA lolos.

Shared hosting: list/feed dibatasi dan query memakai indexes. Tidak menambah worker, Node SSR, Redis atau dependency baru. Beban nyata belum dibenchmark; mulai direct-link cohort kecil, pantau error/rate-limit/CPU/DB dengan metadata yang tidak berisi cerita.

- Backup stories terpisah dari website, privat/encrypted sesuai kebijakan, akses hanya operator berwenang. Jangan mengekspor cerita ke repo/ZIP frontend.
- Jangan mengaktifkan debug/query logging/request-body logging/APM untuk cerita. Log hosting dapat tetap berisi metadata koneksi; review akses/retensi penyedia. Error query pada route cerita ditangkap agar bindings cerita tidak masuk application logger.
- Pantau pending/reports dan tutup publik sementara jika moderator tidak tersedia. Tidak menambahkan count global ke seluruh request homepage.
- Retensi **manual hanya setelah persetujuan eksplisit** dan backup/restore sudah diverifikasi. Review jumlah record lebih tua dari cutoff tanpa menampilkan body. Perintah yang sudah ada:

```bash
php artisan stories:prune
```

Perintah tersebut permanen: maksimal1000cerita per run lebih tua dari created_at minus retention_days, termasuk approved/private/rejected/soft-deleted, beserta relasi cascade/audit terkait. Ia juga membersihkan limit kedaluwarsa dan reaction lama. Jangan menjalankannya saat periode masih draft. Ketika enabled=false, command no-op. Jadwal otomatis masa depan membutuhkan keputusan/QA terpisah; runbook ini tidak mengotorisasinya.

## 10. Emergency disable dan recovery

Tutup publik sambil mempertahankan moderasi:

```dotenv
STORIES_ENABLED=true
STORIES_POLICIES_APPROVED=false
```

Untuk mematikan seluruh subsystem, set juga STORIES_ENABLED=false. Refresh config:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Public feed/submission ditutup; data stories tetap ada. Jangan drop/reset/prune sebagai cara menonaktifkan. Pertahankan dedicated DB, key/config dan backup secara privat.

Bila ada regresi source/build: maintenance, biarkan flags off, pulihkan **pasangan** source/build dari backup rilis sebelumnya secara manual tanpa menimpa .env/uploads, lalu verifikasi website. Jangan menjalankan migration down: schema tambahan dapat tetap ada. Jika kembali ke baseline V2 lama, biarkan STORIES_ENABLED=false karena jadwal prune lama ada di baseline. Restore database hanya dengan keputusan operator dan rencana kehilangan data/permintaan penghapusan yang jelas.

## 11. Bukti lokal dan batas

Pengujian kode memakai SQLite in-memory dan file SQLite sementara terpisah yang dibuat TestCase, fake provider dan temp storage. Test baru memverifikasi Stage A, policy gate produksi, migrator-default guard, alias/DB_URL, persistent reconnect isolation, noindex/sitemap, no automatic prune; frontend merender home/catalog/detail/chrome nyata dan memeriksa link promosi tidak ada serta sidebar role/flag.

Command utama:

```powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe vendor/bin/phpunit --do-not-cache-result tests/Feature/TestIsolationTest.php
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe vendor/bin/phpunit --do-not-cache-result tests/Feature/StorySoftLaunchTest.php tests/Feature/StoryPlatformTest.php tests/Feature/LocalPreviewGuardTest.php tests/Feature/ReleaseReadinessTest.php tests/Feature/SeoTest.php
rtk proxy node --experimental-strip-types --test tests/frontend/story-soft-launch.test.mjs tests/frontend/story-cards.test.mjs tests/frontend/splash.test.mjs tests/frontend/commerce.test.mjs
rtk proxy node node_modules/typescript/bin/tsc --noEmit
rtk proxy npm run build
rtk git diff --check
```

Hasil lokal aktual: isolation guard2tests/13assertions PASS; focused Laravel49tests/496assertions PASS; frontend19tests PASS; TypeScript noEmit PASS; PHP syntax8file/Pint8file PASS; Vite3457modules/14,88detik PASS; git diff --check PASS. Warning build URL font absolut tetap ada; font dilayani dari public/fonts pada runtime. Harness commerce mempunyai warning useLayoutEffect pada server renderer, bukan bukti Console browser. Konektor browser mengembalikan daftar kosong. SHA commit, branch/push, ZIP dan SHA-256 dicatat pada laporan rilis akhir. Tidak mengklaim UI browser, MySQL migration, real Turnstile, live admin login atau backup produksi terverifikasi. VISUAL BROWSER QA NOT VERIFIED jika konektor browser tetap kosong.

Dokumen ini tidak menyalakan flag, menetapkan approved policy atau menjalankan perintah hosting. Stage B tetap NO-GO sampai pemilik melengkapi gate.
