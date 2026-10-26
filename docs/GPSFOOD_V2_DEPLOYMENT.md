# GPS FOOD V2 — Runbook rilis manual

> Untuk aktivasi cerita dua tahap tanpa promosi, gunakan [runbook soft launch terbaru](GPSFOOD_STORIES_SOFT_LAUNCH.md). Instruksi cerita di dokumen V2 ini adalah riwayat; rilis soft launch menghapus jadwal prune otomatis.

**Profil preview lokal:** scripts/local-preview* dan tests/Support adalah harness development saja. Jangan menyalin storage/app/private/gpsfood-local-preview (SQLite, app key, session, uploads) ke hosting/ZIP; tetap ignored. Jangan memakai bootstrap preview di produksi. Lihat [panduan lokal](GPSFOOD_LOCAL_PREVIEW.md). Default flags produksi tetap false.

**Update splash 26 Oktober:** frontend/shell berubah setelah acceptance awal. ZIP kandidat tanggal25Oktober di bawah adalah artefak historis, belum memuat splash baru. Gunakan build terbaru lalu buat ZIP baru dari commit rilis final; sertakan kedua partial Blade baru pada source. Jangan memasangkan shell baru dengan ZIP lama. Detail terbaru di konteks bagian29.

Status 26 Oktober 2026: **CONDITIONAL GO untuk website V2 dengan cerita disabled**, setelah syarat acceptance terpenuhi; **belum ada deployment**. Lihat [laporan acceptance dan QA manual](GPSFOOD_V2_ACCEPTANCE.md). Ruang Cerita produksi **NO-GO** sampai database khusus, Turnstile, kebijakan, moderator, dan staging disahkan. Dokumen ini melengkapi DEPLOY.md; fakta hosting lama tetap harus diverifikasi operator. Spesifikasi NyanHosting (1 CPU/1 GB RAM/I/O 50 MB/s/Singapura/backup mingguan) berasal dari informasi pemilik, bukan pengukuran server.

## 1. Sebelum rilis

- Review seluruh diff, termasuk perbaikan F01–F06 yang sudah ada. Jangan membuang perubahan pengguna atau dokumen konteks yang masih untracked.
- Pastikan PHP produksi >=8.2 dan ekstensi Laravel/GD/PDO MySQL tersedia; hasil lokal memakai PHP 8.4.23, sehingga kompatibilitas runtime hosting perlu staging.
- Verifikasi APP_DEBUG=false, HTTPS, cookie sesi aman, batas upload, mail, cron, dan document root public pada hosting. Jangan menyalin .env lokal ke server.
- Backup source/commit lama, public/build beserta manifest, upload asli, database website, dan database cerita jika sudah ada. Simpan backup di luar public, kontrol aksesnya, dan lakukan restore drill. Backup mingguan yang diiklankan bukan bukti pemulihan teruji.
- Periksa daftar migration pending sebelum eksekusi. Jangan menjalankan db:seed pada data yang sudah dikelola bisnis.
- Review data Kuicip bersama pemilik: seed repository berisi delapan varian dan belum berisi Ayam Geprek. Database produksi tidak diperiksa; tidak ada produk atau tautan marketplace yang dibuat otomatis.

## 2. Source dan build harus satu rilis

Rilis final diotorisasi pemilik untuk commit/push ke upstream yang sudah ada. SHA aktual, konfirmasi push, lokasi ZIP unik dan SHA-256 dicatat pada laporan rilis serta metadata JSON pendamping di storage/app/private/releases (ignored). ZIP final dibuat ulang **sesudah** commit berhasil dipush; jangan gunakan kandidat 25 Oktober.

Validasi lokal minimum: TypeScript --noEmit, tes isolasi, PHP terarah story/product/CMS/splash, Node story/splash/commerce, Vite build, git diff --check dan HTTP preview. Tidak perlu mengulang full suite. Vite tidak melakukan type checking. Tidak ada perubahan dependency.

## 3. ZIP frontend

Contoh PowerShell lokal; nama unik menghindari overwrite arsip:

~~~powershell
$releaseDirectory = Join-Path (Get-Location) 'storage/app/private/releases'
New-Item -ItemType Directory -Path $releaseDirectory -Force | Out-Null
$releaseName = 'gpsfood-v2-build-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '-' + [guid]::NewGuid().ToString('N') + '.zip'
$releaseZip = Join-Path $releaseDirectory $releaseName
Compress-Archive -LiteralPath 'public/build' -DestinationPath $releaseZip
Get-FileHash -LiteralPath $releaseZip -Algorithm SHA256
~~~

Arsip harus berisi build/manifest.json dan build/assets/*; ekstrak dari direktori public hosting agar menjadi public/build, bukan public/build/build. ZIP tidak berisi .env, database, vendor atau node_modules. public/fonts dan public/images/logo adalah aset source terpisah: ikut commit/pull atau upload sebagai aset statis sesuai prosedur, termasuk lisensi OFL. Jangan menganggap ZIP build sudah memuat font/logo.

Paket **historis, jangan dipakai**: storage/app/private/releases/gpsfood-v2-build-20261025-165029-b3976330.zip. 55 entri, 556307 byte; SHA-256 86acba0ca4a6320b4472b919a6cebe824e44fd3955a99036946c63bb948fdad5. Semua entri sudah dibaca ulang dan dibandingkan hash dengan public/build. Metadata/source file hashes berada pada JSON pendamping. Lokasi ini ignored oleh Git.

## 4. Migration dan konfigurasi

| Migration baru | Koneksi | Efek |
|---|---|---|
| database/migrations/2026_10_25_100000_add_commerce_and_seo_to_products_table.php | default website | Menambah kolom nullable Shopee, berat, SEO ID/EN dan alt; tidak mengisi/mengganti produk lama |
| database/migrations/stories/2026_10_25_110000_create_story_tables.php | stories | Membuat stories, moderation events, reports, reactions, limits pada DB khusus |

Migration cerita berada di subdirektori; migrate website biasa tidak mengeksekusinya. Gunakan path dan koneksi eksplisit. Guard menolak koneksi tak lengkap dan pasangan database/host/port yang sama dengan website; jangan menggunakan alias host untuk mencoba berbagi database. Buat database serta user khusus dengan privilege hanya pada database cerita, tanpa akses TMC/Finance. Proses PHP tetap sama: ini isolasi persistence, bukan isolasi proses/server.

Untuk rilis website ini hanya dua flag berikut yang diperlukan, tetap false; tidak memerlukan database cerita atau Turnstile. Agent tidak mengubah .env lokal maupun produksi.

~~~dotenv
STORIES_ENABLED=false
STORIES_POLICIES_APPROVED=false
~~~

Konfigurasi berikut **hanya referensi fase aktivasi cerita mendatang**, bukan langkah rilis sekarang:

~~~dotenv
STORIES_ENABLED=false
STORIES_POLICIES_APPROVED=false
STORIES_POLICY_VERSION=<versi-kebijakan-disahkan>
STORIES_ABUSE_KEY=<random-secret-minimum-32-karakter>
STORIES_DAILY_QUOTA=200
STORIES_COOLDOWN_SECONDS=60
STORIES_RETENTION_DAYS=90
STORY_DB_DRIVER=mysql
STORY_DB_HOST=<host-database-cerita>
STORY_DB_PORT=3306
STORY_DB_DATABASE=<database-cerita-khusus>
STORY_DB_USERNAME=<user-cerita-khusus>
STORY_DB_PASSWORD=<secret>
TURNSTILE_SITE_KEY=<site-key-domain-produksi>
TURNSTILE_SECRET_KEY=<secret-server>
TURNSTILE_HOSTNAME=<hostname-domain-tanpa-skema>
~~~

Nilai quota/cooldown/retention adalah default operasional yang perlu persetujuan pemilik. Sesi/cache limiter lintas request harus memakai driver persistent yang berfungsi (konfigurasi aplikasi yang sudah ada); array hanya untuk test. Secret tidak boleh dimasukkan ke VITE_* atau log.

Turnstile harus lolos Siteverify server, hostname dan action story_submit; gangguan provider, secret kosong, atau public test secret di production memblokir kiriman. Tidak ada mode bypass production. Konfigurasi hostname harus sama persis dengan hasil provider. Token berlaku singkat dan sekali pakai menurut [dokumentasi Cloudflare](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/).

## 5. Urutan operator di cPanel — belum dijalankan

Sesuaikan placeholder dan lokasi menurut hosting aktual. Tarik commit yang sama dengan paket; git pull saja tidak membangun frontend.

~~~bash
cd /home/<cpanel-user>/<app-directory>
git status --short
git branch --show-current
git rev-parse HEAD
php -v
php -m
# STOP jika working tree kotor, backup belum tersedia, atau PHP/DB tidak cocok.
# Backup DB website via cPanel/phpMyAdmin; backup source, uploads dan build di luar public.
# Simpan URL bypass acak keluaran down secara privat untuk health check operator.
php artisan down --retry=60 --with-secret
git pull --ff-only origin main
git rev-parse HEAD
# STOP jika HEAD tidak sama dengan SHA release pada laporan/metadata ZIP.
php artisan migrate:status --database=<website-connection>
php artisan migrate --database=<website-connection> --path=database/migrations/2026_10_25_100000_add_commerce_and_seo_to_products_table.php --force
~~~

**Urutan ini wajib:** maintenance memakai code lama sebelum git pull; code baru hanya boleh menerima trafik setelah migration website sukses, font/logo/build lengkap, dan cache diperbarui. Jalankan hanya path migration produk yang direview. Jika ada migration lama yang belum diterapkan, SHA berbeda, kegagalan ALTER TABLE atau deployment terputus, hentikan rilis dan tetap maintenance; jangan retry/drop/rollback database secara buta. MySQL DDL dapat autocommit/meninggalkan schema parsial, sehingga periksa schema serta tabel migrations bersama operator sebelum pemulihan.

Untuk **website V2 dengan cerita disabled**, pastikan STORIES_ENABLED=false dan STORIES_POLICIES_APPROVED=false; STORY_DB_* dan Turnstile boleh belum dikonfigurasi. **Lewati seluruh migration stories**. Database cerita dan persetujuan kebijakan bukan prasyarat rilis website dengan flag tetap off.

Matriks kompatibilitas yang diuji lokal pada fixture SQLite:

| Kombinasi | Hasil / batas |
|---|---|
| Data lama + migration produk up | Semua row/kolom lama dipertahankan; delapan kolom baru null |
| Operasi kolom lama + schema baru | Legacy insert/update bekerja; metadata baru tidak ikut berubah |
| Code V2 baca schema lama | Halaman publik memakai fallback Shopee resmi, field lain null; edit CMS tetap terbuka |
| Code V2 simpan field baru + schema lama | Gagal SQL; **tidak boleh ada trafik CMS dalam jendela ini** |
| Rollback code setelah migration | Pertahankan kolom tambahan nullable; jangan menjalankan down |

Ini verifikasi terhadap schema repository dan SQLite sintetis, bukan pemeriksaan database produksi atau boot aplikasi lama di hosting. Validasi ALTER pada MySQL/MariaDB staging yang setara hosting tetap diperlukan. Migration up tidak menghapus/mengubah kolom lama; down menghapus kolom baru beserta nilainya, sehingga rollback schema bersifat destruktif terhadap metadata baru.

**Lewati blok berikut untuk rilis ini.** Setelah backup dan konfigurasi DB cerita diverifikasi, hanya jika modul cerita akan disiapkan di fase terpisah:

~~~bash
php artisan migrate:status --database=stories --path=database/migrations/stories
php artisan migrate --database=stories --path=database/migrations/stories --force
~~~

Upload ZIP melalui File Manager ke lokasi private, verifikasi hash, lalu ekstrak ke public dengan backup manifest/build lama tersimpan. Pastikan tidak ada folder bertingkat salah. Pertahankan chunk lama selama masa verifikasi agar tab pengguna yang masih membuka versi sebelumnya tidak kehilangan aset. Jangan menjalankan penghapusan massal build/uploads. Bila ada pergantian dependency di rilis lain, composer install --no-dev --optimize-autoloader hanya sesuai lock yang direview; V2 ini tidak mengubah lock.

~~~bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan tinker --execute="dump(config('stories.enabled'), config('stories.policies_approved'));"
~~~

Kedua flag harus tercetak false. Selagi maintenance, operator membuka URL bypass privat yang dicetak down --with-secret pada browsernya; cookie bypass hanya untuk operator. Jalankan health check bagian7 melalui sesi itu, termasuk halaman publik, CMS, font/logo/build tanpa 404, Shopee dan WhatsApp. Pengunjung lain tetap mendapat maintenance. Verifikasi /kuicip/cerita/feed =404 serta halaman cerita disabled. Jangan membagikan URL/cookie bypass atau menyalinnya ke laporan.

**Hanya setelah semua pemeriksaan lolos:**

~~~bash
php artisan up
~~~

Periksa HTTP publik kembali setelah up. Jika gagal sebelum atau sesudah up, tetap/masuk maintenance dan ikuti pemulihan bagian8.

Tidak perlu Node, SSR, Redis, Supabase, atau persistent worker baru di hosting. Cron Laravel yang sudah ada harus diverifikasi. Pada baseline V2 historis, stories:prune dijadwalkan 02:00 saat enabled. Rilis soft launch menghapus jadwal itu: pruning hanya manual setelah pemilik mengesahkan retensi dan backup. Command menghapus maksimal1000 cerita per run lebih tua dari retention beserta relasi; ketika fitur disabled, command tidak menyentuh record. Lihat runbook soft launch untuk gate operasional terbaru.

## 6. Gate aktivasi Ruang Cerita

Tetap STORIES_ENABLED=false sampai semua prasyarat berikut dipenuhi:

1. DB terpisah dan credential terbatas, migration, backup/restore, cache/session dan cron teruji.
2. Turnstile domain produksi terverifikasi, bukan test key; failure/outage dan CSRF diverifikasi di browser staging.
3. Owner mengesahkan versi kebijakan: penggunaan privat, consent publikasi, izin reuse sosial, retensi/audit/backup/log provider, penghapusan, konten sensitif dan panduan komunitas. Draf UI bukan nasihat hukum atau janji waktu respons.
4. Superadmin moderator ditunjuk, akses privat dilatih dan diaudit, hide/report/escalation/runbook insiden diuji. Editor tidak mendapat akses default.
5. Browser desktop/mobile, keyboard, reduced motion, XSS escaping, form/retry/token, filter/load-more, 429 serta MySQL concurrency diverifikasi menggunakan data sintetis.

Baru setelah review owner: STORIES_POLICIES_APPROVED=true, versi kebijakan non-draft, dan STORIES_ENABLED=true, lalu refresh config cache dan health check. Ini langkah operator mendatang, bukan tindakan agen.

Disabled: halaman /kuicip/cerita hanya memberi informasi; tidak mengirim form/feed. Endpoint submission/reaction/report/removal dan moderasi ditolak. Menonaktifkan flag menjadi kill switch cepat, tetapi menunda juga self-removal: siapkan jalur kontak operator untuk permintaan privasi saat layanan tutup.

## 7. Health check rilis

- Homepage, /products, dua katalog dan slug lama, /news, /mitra, /export, legalitas, /contact, login admin dan CRUD masih bekerja.
- Logo proporsional, font WOFF2 lokal 200, tidak ada chunk 404 atau /public/build/build. Periksa desktop/mobile, zoom dan keyboard; browser belum tersedia pada QA agen.
- Cari/filter/empty state, gambar thumbnail, Shopee override valid mengalahkan fallback toko resmi, fallback muncul jika kosong/invalid; WA memakai nomor Pengaturan dan pesan ter-encode.
- HTML awal memiliki title/description/canonical/OG/JSON-LD yang benar, tanpa offers/rating fiktif; sitemap hanya public. Indonesia canonical, EN UI memakai metadata EN yang ada tanpa membuat URL indeks duplikat.
- Cerita pending/privat/rejected/hidden tidak ada pada feed, approval privat tetap privat, report/reaction private 404, editor 403, tamu login redirect. Admin dan cerita noindex.
- Kiriman valid pending, consent terpisah, token hanya sekali; code saja tidak menghapus. Retry UUID sama tidak menggandakan; token respons pertama yang hilang tidak bisa diambil ulang. Siapkan prosedur bantuan owner tanpa mempublikasikan isi privat.
- Periksa error metrics/429/queue moderasi tanpa merekam body/token/raw IP ke log aplikasi. Review logging/WAF/backup pihak hosting terpisah.

## 8. Rollback

Nonaktifkan cerita dan refresh config bila insiden. Pulihkan pasangan source commit dan manifest/build dari backup rilis yang sama; hindari reset/clean pada working tree yang memiliki perubahan operator. Pertahankan upload nyata. Kolom tambahan nullable dapat tetap ada saat rollback source; jangan otomatis migrate:rollback karena down migration akan menghapus data baru. Restore database hanya dengan keputusan operator dan rencana kehilangan data yang disepakati. DB cerita mempunyai backup/restore terpisah; jangan menyentuh TMC/Finance.

## 9. Bukti lokal 25 Oktober 2026 dan batas

Hasil berikut adalah riwayat implementasi V2. Verifikasi terbaru 26 Oktober: **157 tests/1064 assertions, satu skip**, lint/Pint59PHP, build19,48detik, HTTP16HTML/79aset; browser tetap NOT VERIFIED. Bukti dan syarat rilis terkini ada di [laporan acceptance](GPSFOOD_V2_ACCEPTANCE.md).

- PHPUnit lengkap: 149 tests, 913 assertions, 148 lulus dan satu symlink dilewati Windows; targeted story/product 25 tests/234 assertions.
- TypeScript --noEmit PASS; sintaks 58 PHP berubah PASS; Pint check 58 file PASS; Node commerce asserts PASS; git diff --check PASS.
- Vite build PASS (3456 modules, 18.29 detik). Font absolute URL memberi warning resolusi saat build tetapi keduanya 200 pada HTTP lokal; wajib diupload sebagai public/fonts.
- HTTP sintetis: 14 halaman HTML, tiga aset, detail SEO, feed 6+1/cursor/category/private exclusion PASS. Harness mengabaikan .env, SQLite memori, media sementara; server sudah dihentikan.
- Browser: NOT RUN, inventory browser connector kosong. Rendering React/XSS runtime, mobile, aksesibilitas visual, interaksi dan Turnstile asli belum diuji.
- Tidak ada MySQL concurrency/load test, PageSpeed/Core Web Vitals, audit produksi, mail delivery, backup restore atau provider production test. Jangan mengubah hasil lokal menjadi klaim kapasitas hosting.

Optimasi terverifikasi: resource ringkas kartu tanpa description/highlights/notes, eager loading brand (test <=3 query brand pada katalog), related limit tiga, thumbnail/srcset, font dua file (18604/36932 byte; angka final lihat aset), feed maksimum enam, moderasi/report pagination20, history50, indeks dan quota DB. Katalog masih mengambil semua produk aktif per brand; dataset seed 22 produk. Tambahkan pagination server saat pertumbuhan atau pengukuran payload membutuhkannya. Chunk app sekitar352 KB dan shared GSAP156 KB masih bottleneck; dashboard/chart dan rich editor dipisah chunk admin. Tidak ada refactor dependency atau janji concurrency.


## 10. Shopee pada rilis final

Destinasi resmi pemilik: https://shopee.co.id/gpsfood?categoryId=100629&entryPoint=ShopByPDP&itemId=49919300654 . Ini fallback toko, bukan tautan SKU terverifikasi. Config company.shopee_storefront (opsional env SHOPEE_STOREFRONT_URL) menjadi satu sumber. URL produk valid diprioritaskan; kosong/invalid memakai fallback tervalidasi; jika fallback juga invalid tombol ditiadakan. Tidak ada update massal data, harga/stok/Offer fiktif. Katalog mempertahankan detail + Shopee sekunder, detail memakai Shopee utama + WhatsApp sekunder.

Saat ekstrak ZIP di direktori public yang **sudah diverifikasi**, build/manifest.json harus menjadi public/build/manifest.json. Simpan build lama di backup private terlebih dahulu; ekstrak menimpa manifest/chunk senama tanpa menghapus chunk lama. Cocokkan SHA-256 arsip dengan laporan sebelum ekstrak (sha256sum /full/private/path/archive.zip). Verifikasi setiap entry manifest menunjuk file yang ada dan fonts/logo ikut Git pull. Jangan jalankan npm di hosting. Bila pull melampaui SHA yang disetujui, hentikan sebelum migration; jangan reset atau force checkout. Untuk recovery, pulihkan source dan build dari backup pasangan sebelumnya dengan prosedur operator yang mempertahankan .env/uploads. Jangan migration down.
