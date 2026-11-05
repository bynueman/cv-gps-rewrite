# GPS FOOD — final consent Ruang Cerita, deployment manual cPanel

Release: `__RELEASE_SHA__`; ZIP: `__ZIP_NAME__`.
ZIP SHA-256: `__ZIP_SHA256__`; __ZIP_BYTES__ byte; __ZIP_FILES__ entries.
Manifest SHA-256: `__MANIFEST_SHA256__`.

Hanya operator menjalankan perintah ini. Agen tidak mengakses produksi.
GO penyerahan artefak; NO-GO deployment sampai preflight, backup, schema dan
smoke test lulus. HEAD/migration/cron produksi belum diketahui. Persetujuan
visual lokal pemilik sudah diberikan; catat persetujuan wording/prosedur privasi
sebelum mengubah policy produksi. Tidak ada migration baru untuk consent tunggal.
Smart Bilingual V3 (`c9779ed2694a54844b88fe86ddaf0e4325fa3b27`) tetap termasuk.
Jangan menjalankan npm di hosting, reset stories, migrate:fresh, bulk repair APPLY,
queue:clear, queue:retry all, force-push, hard reset atau git clean.

## 1. Preflight dan identitas sumber

Jalankan bertahap dalam satu sesi Bash cPanel Terminal. Berhenti saat gate gagal.
PHP CLI harus cocok dengan PHP web (8.2+) dan extension aplikasi. Jangan ubah
DB/anti-spam/session/cache/queue agar sekadar melewati gate. Bila hosting tidak
menyediakan prerequisite yang diperlukan, NO-GO dan hubungi hosting.

```bash
bash
set -euo pipefail
umask 077
APP=/home/gpsfoodi/gsp-laravel
RELEASE=__RELEASE_SHA__
ZIP_NAME=__ZIP_NAME__
ZIP_SHA=__ZIP_SHA256__
cd "$APP"
test "$(pwd -P)" = "$APP"
test "$(git branch --show-current)" = main
test "$(git remote get-url origin)" = https://github.com/bynueman/cv-gps-rewrite.git
PREVIOUS=$(git rev-parse HEAD)
printf 'Server HEAD sebelum deployment: %s\n' "$PREVIOUS"
git status --short
git diff --cached --quiet
git fetch origin main
test "$(git rev-parse origin/main)" = "$RELEASE"
git merge-base --is-ancestor "$PREVIOUS" "$RELEASE"
git merge-base --is-ancestor c9779ed2694a54844b88fe86ddaf0e4325fa3b27 "$RELEASE"
test -z "$(git ls-files --others --exclude-standard)"
mapfile -t HOST_FILES < <(git diff --name-only)
for FILE in "${HOST_FILES[@]}"; do
  case "$FILE" in .gitignore|*/.gitignore|public/.htaccess) ;; *) echo "NO-GO perubahan lain: $FILE"; exit 1;; esac
  test -f "$FILE"
  test -z "$(git diff --name-only "$PREVIOUS" "$RELEASE" -- "$FILE")"
done
test -z "$(git diff --name-only "$PREVIOUS" "$RELEASE" -- public/.htaccess)"
git diff --stat "$PREVIOUS" "$RELEASE"
git diff --name-status "$PREVIOUS" "$RELEASE" -- database/migrations composer.json composer.lock
PHP=$(php -r 'echo PHP_BINARY;')
test -x "$PHP"
"$PHP" -v
"$PHP" -r 'if(PHP_VERSION_ID<80200)exit(1); foreach(["pdo_mysql","mbstring","dom","curl","openssl","fileinfo","zip","gd"] as $e)if(!extension_loaded($e)){fwrite(STDERR,"Missing: $e\n");exit(1);} require "vendor/autoload.php";'
command -v unzip
command -v sha256sum
FLOCK=$(command -v flock)
TIMEOUT=$(command -v timeout)
"$FLOCK" --version
"$TIMEOUT" --version
df -h "$APP" /home/gpsfoodi
du -sh public/build public/uploads
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
BACKUP=/home/gpsfoodi/private-backups/story-consent-"$STAMP"
mkdir -p "$BACKUP"
chmod 700 /home/gpsfoodi/private-backups "$BACKUP"
printf '%s\n' "$PREVIOUS" > "$BACKUP/source.sha"
git status --short > "$BACKUP/git-status.txt"
git diff --binary > "$BACKUP/hosting-changes.patch"
sha256sum public/.htaccess > "$BACKUP/hosting.sha256"
for FILE in "${HOST_FILES[@]}"; do sha256sum "$FILE" >> "$BACKUP/hosting.sha256"; done
```

Jika composer.json/lock berbeda dari HEAD server, tinjau dependency release
sebelum melanjutkan; bila vendor belum cocok, NO-GO sampai dependency dipasang
melalui prosedur hosting yang disetujui. Jangan mengasumsikan deployment c9779ed
atau 0c6a826 pernah dilakukan. Hosting edits di atas dibiarkan utuh; jangan stash,
restore atau overwrite .gitignore/.htaccess. Jika release menyentuh file custom
yang sama, NO-GO sampai merge manual ditinjau.

## 2. Backup wajib dan pemeriksaan konfigurasi/schema read-only

Export database main dan stories yang BENAR menurut konfigurasi server secara
terpisah melalui cPanel/phpMyAdmin, lengkap schema/data. Verifikasi checksum dan
restore di lingkungan terpisah yang disetujui. Ukuran/CRC saja bukan bukti restore.
Pastikan tidak ada penulis background/cron saat snapshot final; jeda worker melalui
cPanel dan catat cron untuk dipulihkan. Jangan menghapus job yang sudah antre.

```bash
read -r -p 'Path absolut backup MAIN DB terverifikasi: ' MAIN_DUMP
read -r -p 'Path absolut backup STORIES DB terverifikasi: ' STORY_DUMP
test -s "$MAIN_DUMP"
test -s "$STORY_DUMP"
test "$MAIN_DUMP" != "$STORY_DUMP"
read -r -p 'Kedua backup sudah diuji restore? Ketik VERIFIED: ' CONFIRM
test "$CONFIRM" = VERIFIED
sha256sum "$MAIN_DUMP" "$STORY_DUMP" > "$BACKUP/databases.sha256"
cp -p .env "$BACKUP/production.env"
cp -p public/.htaccess "$BACKUP/public.htaccess"
for FILE in "${HOST_FILES[@]}"; do mkdir -p "$BACKUP/hosting/$(dirname "$FILE")"; cp -p "$FILE" "$BACKUP/hosting/$FILE"; done
git archive --format=tar.gz --output="$BACKUP/source-before.tgz" "$PREVIOUS"
tar -czf "$BACKUP/build-before.tgz" -C public build
tar -czf "$BACKUP/uploads-before.tgz" -C public uploads
tar -tzf "$BACKUP/source-before.tgz" > "$BACKUP/source-files.txt"
tar -tzf "$BACKUP/build-before.tgz" > "$BACKUP/build-files.txt"
tar -tzf "$BACKUP/uploads-before.tgz" > "$BACKUP/upload-files.txt"
find public/uploads -type f -print0 | sort -z | xargs -0 -r sha256sum > "$BACKUP/uploads.sha256"
sha256sum "$BACKUP/"*.tgz > "$BACKUP/archives.sha256"
chmod 600 "$BACKUP/production.env"
```

Buat helper privat; hanya nama koneksi/flags dan hash konfigurasi yang dicetak,
bukan credential, body cerita, atau token. Database stories harus benar-benar
terpisah; hentikan bila schema/log migration tidak konsisten.

```bash
cat > "$BACKUP/check.php" <<'PHP'
<?php
require 'vendor/autoload.php'; $a=require 'bootstrap/app.php';
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Support\StoryDatabase::ensureConfigured();
$main=config('database.default'); $db=Illuminate\Support\Facades\DB::connection($main);
$schema=Illuminate\Support\Facades\Schema::connection($main); $q=config('queue.connections.database');
$story=Illuminate\Support\Facades\DB::connection('stories');
foreach([$db,$story] as $connection)if(!in_array($connection->getDriverName(),['mysql','mariadb'],true))exit(1);
foreach(['settings','migrations',$q['table'],config('queue.failed.table')] as $table)if(!$schema->hasTable($table))exit(1);
if($q['driver']!=='database'||($q['connection']?:$main)!==$main||(int)$q['retry_after']<90||config('queue.failed.database')!==$main)exit(1);
$v3=[$schema->hasTable('content_translations'),$schema->hasColumn('settings','value_en'),$db->table('migrations')->where('migration','2026_10_26_110000_create_content_translations_table')->exists()];
if(count(array_unique($v3))!==1)exit(1);
$invariants=['database'=>config('database'),'stories'=>array_diff_key(config('stories'),['policy_version'=>true]),'queue'=>config('queue'),'cache'=>config('cache'),'session'=>config('session')];
$required=defined(App\Support\StoryAvailability::class.'::REQUIRED_POLICY_VERSION') ? constant(App\Support\StoryAvailability::class.'::REQUIRED_POLICY_VERSION') : null;
echo json_encode(['main_connection'=>$main,'v3_installed'=>$v3[0],'queue_default'=>config('queue.default'),'translation_connection'=>'database','translation_queue'=>'translations','retry_after'=>$q['retry_after'],'configured_policy'=>config('stories.policy_version'),'required_policy'=>$required,'story_enabled'=>config('stories.enabled'),'policies_approved'=>config('stories.policies_approved'),'provider'=>config('stories.antispam_provider'),'public_enabled'=>App\Support\StoryAvailability::publicEnabled(),'invariant_hash'=>hash('sha256',json_encode($invariants))],JSON_PRETTY_PRINT).PHP_EOL;
PHP
"$PHP" "$BACKUP/check.php" | tee "$BACKUP/before.json"
MAIN_CONNECTION=$("$PHP" -r 'require "vendor/autoload.php";$a=require "bootstrap/app.php";$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();echo config("database.default");')
"$PHP" artisan migrate:status --database="$MAIN_CONNECTION"
"$PHP" artisan migrate:status --database=stories --path=database/migrations/stories
ZIP="$APP/storage/app/private/releases/$ZIP_NAME"
test -s "$ZIP"
printf '%s  %s\n' "$ZIP_SHA" "$ZIP" | sha256sum -c -
unzip -t "$ZIP"
```

`required_policy=null` pada sumber lama berarti konstanta versi belum tersedia
sebelum retirement token; setelah pull konstanta wajib ada dan cocok dengan v4.
Ini bukan alasan menganggap versi konfigurasi lama sudah menyetujui wording baru.

Upload ZIP ke lokasi privat tersebut sebelum verifikasi. Pastikan konfigurasi
sebelum/sesudah tidak berubah kecuali policy_version yang disetujui. Verifikasi
cache persisten dan mendukung lock, session persisten, abuse key, provider CAPTCHA
yang benar dan key/hostname Turnstile bila dipakai. Jangan cetak secrets.

## 3. Maintenance, pull, hanya migration V3 jika belum terpasang

```bash
MAINTENANCE_SECRET=$("$PHP" -r 'echo bin2hex(random_bytes(24));')
"$PHP" artisan down --secret="$MAINTENANCE_SECRET"
git pull --ff-only origin main
test "$(git rev-parse HEAD)" = "$RELEASE"
sha256sum -c "$BACKUP/hosting.sha256"
cmp .env "$BACKUP/production.env"
"$PHP" artisan migrate:status --database="$MAIN_CONNECTION"
"$PHP" artisan migrate:status --database=stories --path=database/migrations/stories
V3_INSTALLED=$("$PHP" -r '$d=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);echo $d["v3_installed"]?"yes":"no";' "$BACKUP/before.json")
if test "$V3_INSTALLED" = no; then
  "$PHP" artisan migrate --database="$MAIN_CONNECTION" --path=database/migrations/2026_10_26_110000_create_content_translations_table.php --pretend --force
  read -r -p 'SQL hanya content_translations + settings.value_en, prerequisite MySQL valid? Ketik APPROVED: ' SQL_REVIEW
  test "$SQL_REVIEW" = APPROVED
  "$PHP" artisan migrate --database="$MAIN_CONNECTION" --path=database/migrations/2026_10_26_110000_create_content_translations_table.php --force
fi
"$PHP" "$BACKUP/check.php" | tee "$BACKUP/schema-after.json"
"$PHP" -r '$d=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);if(!$d["v3_installed"])exit(1);' "$BACKUP/schema-after.json"
```

Jika migration lain masih pending, hentikan untuk penilaian prasyarat; jangan
menjalankan migrate tanpa --path. Jika V3 sudah installed, blok melewatinya.
Schema parsial, JSON/InnoDB tidak didukung, jobs/failed_jobs tidak tersedia atau
migration gagal: NO-GO, tetap maintenance. Tidak ada migration/reset cerita.
Pastikan dump final mencakup semua penulisan sebelum maintenance; ulang backup
bila masih ada penulisan sejak snapshot awal. Jangan membuka publik saat schema
dan source belum cocok.

## 4. Install hanya build yang cocok

```bash
STAGE=$(mktemp -d "$APP/storage/app/private/releases/story-stage-XXXXXXXX")
ZIP_PATH="$ZIP" STAGE_PATH="$STAGE" "$PHP" <<'PHP'
<?php
$z=new ZipArchive;if($z->open(getenv('ZIP_PATH'),ZipArchive::CHECKCONS)!==true||$z->numFiles!==__ZIP_FILES__)exit(1);
$seen=[];for($i=0;$i<$z->numFiles;$i++){$n=$z->getNameIndex($i);if(!str_starts_with($n,'build/')||str_contains($n,'\\')||str_contains($n,':')||preg_match('~(^|/)\.\.?(/|$)|//~',$n)||str_ends_with($n,'/')||isset($seen[$n]))exit(1);$seen[$n]=true;}
$m=json_decode($z->getFromName('build/manifest.json'),true,512,JSON_THROW_ON_ERROR);if(!isset($m['resources/js/app.tsx'],$m['resources/js/Pages/Kuicip/Cerita.tsx']))exit(1);
foreach($m as $item){foreach(array_merge([$item['file']],$item['css']??[],$item['assets']??[]) as $f)if(!isset($seen['build/'.$f]))exit(1);foreach(array_merge($item['imports']??[],$item['dynamicImports']??[]) as $k)if(!isset($m[$k]))exit(1);}
if(!$z->extractTo(getenv('STAGE_PATH')))exit(1);for($i=0;$i<$z->numFiles;$i++){$n=$z->getNameIndex($i);if(!hash_equals(hash('sha256',$z->getFromIndex($i)),hash_file('sha256',getenv('STAGE_PATH').'/'.$n)))exit(1);}
$z->close();echo "ZIP paths, manifest and extraction verified\n";
PHP
printf '%s  %s\n' '__MANIFEST_SHA256__' "$STAGE/build/manifest.json" | sha256sum -c -
test ! -e "$BACKUP/build-before-install"
mv public/build "$BACKUP/build-before-install"
mv "$STAGE/build" public/build
find public/build -type d -exec chmod 755 {} +
find public/build -type f -exec chmod 644 {} +
sha256sum -c "$BACKUP/hosting.sha256"
sha256sum -c "$BACKUP/uploads.sha256"
```

## 5. Policy manual, cache, queue dan cron

Versi wajib aktual: **kuicip-v4-single-consent-2026-11-05**. Bandingkan
configured_policy pada before.json dengan konstanta kode baru. Persetujuan visual
tidak otomatis mendokumentasikan persetujuan bisnis atas wording baru: satu
acknowledgement, izin publikasi melalui radio Publik sesudah admin approval,
Privat tidak dipublikasikan, tanpa izin media sosial, dan penghapusan lewat
kontak resmi dengan review. Pemilik meninjau ID/EN dan mencatat persetujuan.
Jika belum disetujui, NO-GO aktivasi form; jangan memalsukan versi/flag.

Melalui editor privat cPanel, pemilik/operator mengubah HANYA
`STORIES_POLICY_VERSION=kuicip-v4-single-consent-2026-11-05` setelah approval.
Tidak ada perintah otomatis mengedit .env. STORIES_ENABLED/POLICIES_APPROVED
harus true sesuai approval yang sudah ada; bila tidak, hentikan dan putuskan
aktivasi terpisah. Jangan mengganti provider, keys, database, queue atau retensi.

```bash
"$PHP" artisan config:clear
"$PHP" artisan route:clear
"$PHP" artisan view:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" "$BACKUP/check.php" | tee "$BACKUP/after.json"
"$PHP" -r '$b=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);$a=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);if(!hash_equals($b["invariant_hash"],$a["invariant_hash"])||!$a["v3_installed"]||!$a["public_enabled"]||$a["required_policy"]!=="kuicip-v4-single-consent-2026-11-05"||$a["configured_policy"]!==$a["required_policy"])exit(1);' "$BACKUP/before.json" "$BACKUP/after.json"
sha256sum .env > "$BACKUP/approved-env.sha256"
"$PHP" artisan queue:failed
```

Periksa juga `TRANSLATIONS_ENABLED` dan budget provider melalui konfigurasi privat
yang sudah disetujui; jangan menggantinya atau mengaktifkan job hanya karena
default kode baru tersedia. V3 baru menambah config translations sehingga
konfigurasi ini diperiksa tersendiri, bukan disamakan dengan config lama yang
mungkin belum memiliki V3.

Jangan optimize:clear/cache:clear di release ini: pertahankan counter anti-spam,
lock dan cache yang sedang dipakai. Queue job memakai connection database,
queue translations; jangan ubah queue.default atau hapus job. Catat cron lama
melalui cPanel; jika worker sudah teruji/aktif, verifikasi dan pulihkan jadwalnya.

Jika cron V3 masih pending, aktivasi TERPISAH setelah approval: buat satu draft
artikel sintetis melalui CMS, judul/slug `cms-v3-release-probe-__SHORT_SHA__`, ID
excerpt/body “Halo dunia”, English kosong, unpublished. Catat ID; jangan memakai
cerita anonim atau produk nyata. Pastikan tidak ada antrean produksi lain yang
belum disetujui untuk diproses. Jalankan satu job selama maintenance:

```bash
"$FLOCK" -n storage/framework/translations-worker.lock "$TIMEOUT" --kill-after=5s 55s "$PHP" artisan queue:work database --queue=translations --once --max-time=50 --max-jobs=1 --memory=128 --tries=1 --timeout=40 --force
```

Verifikasi draft tersebut ready, EN tersimpan setelah reload, sumber/manual EN
tetap utuh dan tidak published. Periksa status aplikasi serta failed_jobs; exit
worker 0 saja bukan bukti provider berhasil. Jika draft belum diproses, jangan
drain antrean; tinjau job yang ada. Jika provider/hosting gagal, NO-GO aktivasi
cron. Jangan menjalankan scripts/translation-probe.php di produksi (khusus lokal).
Sesudah tes dan approval cron, cetak command cPanel tiap menit:

```bash
printf 'cd %q && %q -n storage/framework/translations-worker.lock %q --kill-after=5s 55s %q artisan queue:work database --queue=translations --stop-when-empty --max-time=50 --max-jobs=5 --memory=128 --tries=1 --timeout=40 >> storage/logs/translations-worker.log 2>&1\n' "$APP" "$FLOCK" "$TIMEOUT" "$PHP"
```

Uji command cetakan secara manual. Pertahankan scheduler analytics/cron lama,
hindari worker duplikat, pastikan retry_after >=90, lock berfungsi dan log dirotasi.
Jika gate worker/hosting V3 yang diperlukan belum lulus, deployment NO-GO.

## 6. Smoke test dalam maintenance, lalu aktivasi

```bash
curl -fsSL -A gpsfood-release-testbot -c "$BACKUP/maintenance.cookies" "https://gpsfood.id/$MAINTENANCE_SECRET" -o /dev/null
for PAGE in / /products/kuicip /products/kuicip/original /products/putri-teko /news /mitra /contact /export /kuicip/cerita /sitemap.xml; do
  test "$(curl -sS -A gpsfood-release-testbot -b "$BACKUP/maintenance.cookies" -o /dev/null -w '%{http_code}' "https://gpsfood.id$PAGE")" = 200
done
printf '%s  %s\n' '__MANIFEST_SHA256__' public/build/manifest.json | sha256sum -c -
test "$(git rev-parse HEAD)" = "$RELEASE"
sha256sum -c "$BACKUP/hosting.sha256"
sha256sum -c "$BACKUP/approved-env.sha256"
```

Browser operator: buka URL bypass maintenance secara privat, lalu cek ID/EN,
mobile, logo GPS FOOD, font, katalog, admin/CMS, moderation dan tautan kebijakan.
Di Network, semua asset JS/CSS dari manifest harus 200 dan console tanpa error.
Uji form sintetis sendiri (bukan cerita pengguna):

1. Tepat satu checkbox required; terms/publication payload invalid ditolak 422
   dengan CAPTCHA/CSRF valid. Refresh expired CAPTCHA mempertahankan body.
2. Kirim Privat, respons 202 hanya code/accepted, simpan kode. CMS pending;
   approval superadmin tetap Privat dan tidak masuk feed.
3. Kirim Publik setelah cooldown dengan body berbeda, masih pending sebelum
   approval. Setelah approval, baru tampil. New social_consent false,
   deletion_token_hash null, policy version sesuai; cek read-only berdasarkan
   kode sintetis baru, bukan record historis. Tidak ada token pada receipt.
4. Retry request identik mengembalikan kode yang sama. Endpoint removal lama
   dengan CSRF sesi valid mengembalikan 410 dan tidak mengubah cerita. 419 tanpa
   CSRF tetap benar. Jangan masukkan token legacy asli.
5. Editor tidak boleh memoderasi/redaksi; superadmin dapat meninjau dan meredaksi
   hanya cerita sintetis tersebut, dengan audit. Jangan mengubah cerita historis.
6. Pastikan tidak ada penulisan pada record historis/consent/hash lama; gunakan
   snapshot DB privat sebelum/sesudah bila perlu. Tidak ada tes PHPUnit produksi.

Jangan keluar maintenance bila pemeriksaan kritis gagal. Setelah semua lulus:

```bash
read -r -p 'Backup, schema, assets, policy, queue dan smoke test lulus? Ketik GO: ' GO
test "$GO" = GO
"$PHP" artisan up
test "$(curl -sS -o /dev/null -w '%{http_code}' https://gpsfood.id/kuicip/cerita)" = 200
```

Jika gagal sesudah up, aktifkan maintenance lagi dan review. Recovery: pertahankan
backup/ZIP, jangan rollback DB otomatis (migration V3 additive). Di bawah
maintenance, pulihkan source ke cabang recovery dari PREVIOUS dan build backup
yang cocok melalui prosedur operator yang ditinjau; jaga hosting edits/uploads.
Jangan force/reset/clean atau restore database yang membuang penulisan baru.
