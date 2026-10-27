# GPS FOOD — rilis gabungan V3 + penghapusan mandiri dipensiunkan

Template sumber untuk release engineer. Salinan operator dengan SHA/ZIP aktual dibuat setelah push di storage/app/private/releases/. **Tidak ada perintah hosting di dokumen ini yang dijalankan oleh agen.**

Rilis: __RELEASE_SHA__
Baseline produksi yang terakhir dikonfirmasi: 1cbfa8b39cdda83d9bd7b3df8b191dbd998a74de
ZIP: __ZIP_NAME__
SHA-256 ZIP: __ZIP_SHA256__
Ukuran: __ZIP_BYTES__ byte; __ZIP_FILES__ file
SHA-256 build/manifest.json: __MANIFEST_SHA256__

## Keputusan dan batas

GO untuk penyerahan artefak lokal yang terverifikasi. Deployment produksi tetap NO-GO sampai seluruh preflight, backup, schema, worker dan pemeriksaan kritis di bawah lulus. PHP CLI/MySQL/cron/alat Linux di NyanHosting belum diakses atau diverifikasi oleh agen.

Tidak ada bulk APPLY, seed, reset database, migrasi stories, perubahan otomatis .env, atau npm di hosting. Perubahan manual policy version hanya sesudah persetujuan eksplisit pada bagian D. Angka 126 temuan/10 record adalah dry-run preview lokal, bukan jumlah produksi. Sumber berasal dari Git; ZIP hanya berisi build/. Upload, logo, font, .htaccess dan database dipertahankan.

Review terfokus: endpoint pensiun, penerbitan receipt, idempotensi, otorisasi/CSRF redaksi, transaksi audit, legacy hash/consent, policy gate dan regresi V3. Tidak ada audit seluruh repo.

Laporan implementasi dan daftar berkas: GPSFOOD_FINAL_V3_STORY_REMOVAL_RELEASE.md dan GPSFOOD_SMART_BILINGUAL_CMS_V3.md. Daftar berkas commit aktual tersedia dalam artefak release metadata dan melalui git show --name-status pada SHA rilis.

## A — Preflight operator, tanpa membuka akses publik

Gunakan satu sesi Bash cPanel Terminal. Perintah berhenti saat gagal. Jangan mengatasi gate dengan force/reset/clean. Jika sesi putus, pulihkan variabel dari catatan backup secara hati-hati; jangan langsung menjalankan artisan up.

~~~bash
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
test "$PREVIOUS" = 1cbfa8b39cdda83d9bd7b3df8b191dbd998a74de
git fetch origin main
test "$(git rev-parse origin/main)" = "$RELEASE"
git merge-base --is-ancestor "$PREVIOUS" "$RELEASE"
git merge-base --is-ancestor c9779ed2694a54844b88fe86ddaf0e4325fa3b27 "$RELEASE"
git status --short
git diff --quiet -- . ':!public/.htaccess'
test -z "$(git ls-files --others --exclude-standard)"
test -z "$(git diff --name-only "$PREVIOUS" "$RELEASE" -- public/.htaccess)"
df -h "$APP" /home/gpsfoodi
du -sh public/build public/uploads
type -a php
PHP=$(php -r 'echo PHP_BINARY;')
test -x "$PHP"
"$PHP" -v
"$PHP" -r 'if (PHP_VERSION_ID < 80200) exit(1); foreach (["pdo_mysql","mbstring","dom","curl","openssl","fileinfo","zip","gd"] as $e) { echo $e.": ".(extension_loaded($e)?"OK":"MISSING").PHP_EOL; if (!extension_loaded($e)) exit(1); } echo "pcntl: ".(extension_loaded("pcntl")?"available":"absent; outer timeout required").PHP_EOL; require "vendor/autoload.php";'
FLOCK=$(command -v flock)
TIMEOUT=$(command -v timeout)
test -x "$FLOCK"
test -x "$TIMEOUT"
"$FLOCK" --version
"$TIMEOUT" --version
command -v unzip
~~~

PHP adalah path absolut yang ditemukan dari PHP_BINARY, bukan path hosting yang dikarang. Bila shell php menjalankan versi yang salah, minta path CLI PHP 8.2+ yang tepat dari hosting lalu ulangi preflight. Timeout harus mendukung GNU --kill-after. Bila flock/timeout tidak tersedia/diizinkan, **NO-GO worker dan deployment** sampai hosting menyediakan mekanisme batas waktu dan lock yang teruji. Cron tiap menit harus diizinkan paket hosting.

Periksa ruang disk untuk dua build, ZIP yang diekstrak, arsip upload, dump kedua DB dan backup sumber. Jika terdapat perubahan server lain, origin berubah, atau HEAD berbeda dari baseline, berhenti untuk review. .htaccess custom boleh berbeda karena release ini tidak mengubahnya; jangan stash/restore/menimpa file tersebut.

Siapkan lokasi privat dan simpan metadata awal; lokasi di luar document root:

~~~bash
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
BACKUP=/home/gpsfoodi/private-backups/cms-v3-"$STAMP"
mkdir -p "$BACKUP"
chmod 700 /home/gpsfoodi/private-backups "$BACKUP"
printf '%s\n' "$PREVIOUS" > "$BACKUP/source.sha"
git status --short > "$BACKUP/git-status.txt"
cat > "$BACKUP/preflight-check.php" <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    App\Support\StoryDatabase::ensureConfigured();
    $main = config('database.default');
    $db = Illuminate\Support\Facades\DB::connection($main);
    $schema = Illuminate\Support\Facades\Schema::connection($main);
    $queue = config('queue.connections.database');
    $tables = [];
    foreach (['settings', 'migrations', $queue['table'], config('queue.failed.table')] as $table) {
        $tables[$table] = $schema->hasTable($table);
    }
    $story = array_intersect_key(config('stories'), array_flip(['enabled','policies_approved','antispam_provider','retention_days']));
    $invariants = ['stories'=>$story, 'session_driver'=>config('session.driver'), 'session_cookie'=>config('session.cookie'), 'cache_store'=>config('cache.default')];
    $data = [
        'main_connection'=>$main, 'main_driver'=>$db->getDriverName(),
        'mysql_mariadb_version'=>$db->selectOne('select version() as version')->version,
        'queue_default'=>config('queue.default'), 'translation_connection'=>'database', 'translation_queue'=>'translations',
        'queue_db'=>$queue['connection'] ?: $main, 'queue_table'=>$queue['table'], 'retry_after'=>$queue['retry_after'],
        'failed_jobs_db'=>config('queue.failed.database'), 'failed_jobs_table'=>config('queue.failed.table'),
        'required_tables'=>$tables, 'value_column'=>$schema->hasColumn('settings','value'),
        'v3_table'=>$schema->hasTable('content_translations'), 'v3_column'=>$schema->hasColumn('settings','value_en'),
        'v3_migration_logged'=>Illuminate\Support\Facades\DB::connection($main)->table('migrations')->where('migration','2026_10_26_110000_create_content_translations_table')->exists(),
        'configured_policy_version'=>config('stories.policy_version'), 'story_public_enabled'=>App\Support\StoryAvailability::publicEnabled(),
        'invariants'=>$invariants, 'invariant_hash'=>hash('sha256',json_encode($invariants)),
    ];
    echo json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
    if (!in_array($db->getDriverName(),['mysql','mariadb'],true) || $queue['driver']!=='database'
        || ($queue['connection'] ?: $main)!==$main || (int)$queue['retry_after']<90
        || config('queue.failed.database')!==$main || in_array(false,$tables,true)
        || $db->getDatabaseName()!=='gpsfoodi_gsp' || config('database.connections.stories.database')!=='gpsfoodi_kuicipstories'
        || !$data['value_column'] || !$story['enabled'] || !$story['policies_approved']
        || $story['antispam_provider']!=='local' || config('cache.default')==='array') exit(1);
} catch (Throwable $e) {
    fwrite(STDERR,'Preflight failed: '.get_class($e).PHP_EOL); exit(1);
}
PHP
"$PHP" "$BACKUP/preflight-check.php" | tee "$BACKUP/preflight-before.json"
MAIN_CONNECTION=$("$PHP" -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo config("database.default");')
~~~

Pastikan MySQL/MariaDB mendukung JSON dan engine tabel website adalah InnoDB; verifikasi di phpMyAdmin sebelum deployment. Sumber menggunakan API schema Laravel standar, tanpa SQL khusus SQLite. Runtime MySQL belum diuji oleh agen. Koneksi queue dan failed_jobs harus database website yang sama, bukan stories. Cache harus persisten dan mendukung lock, misalnya database dengan tabel cache/cache_locks yang sudah tersedia atau file dengan permission benar. Jangan mengubah konfigurasi session/cache hanya untuk melewati pemeriksaan.

Pada deployment pertama, v3_table/v3_column/v3_migration_logged ketiganya harus false. Bila schema parsial atau marker tidak sesuai, berhenti; MySQL DDL tidak menjamin rollback transaksi schema. Jangan retry migrasi secara buta.

Upload ZIP ke direktori privat berikut menggunakan cPanel File Manager, lalu verifikasi:

~~~bash
mkdir -p "$APP/storage/app/private/releases"
ZIP="$APP/storage/app/private/releases/$ZIP_NAME"
test -s "$ZIP"
printf '%s  %s\n' "$ZIP_SHA" "$ZIP" | sha256sum -c -
unzip -t "$ZIP"
~~~

## B — Backup wajib sebelum perubahan sumber/schema

Export **gpsfoodi_gsp (website) dan gpsfoodi_kuicipstories (stories) secara terpisah** melalui cPanel/phpMyAdmin dengan schema dan data lengkap. Jangan tampilkan password pada command line. Simpan file di BACKUP di atas atau lokasi privat lain, unduh salinan aman, verifikasi checksum dan hasil restore di lingkungan terpisah yang disetujui. gzip -t/ukuran saja bukan bukti dump dapat direstore.

~~~bash
read -r -p 'Path absolut backup main DB terverifikasi: ' MAIN_DB_BACKUP
read -r -p 'Path absolut backup stories DB terverifikasi: ' STORIES_DB_BACKUP
test -s "$MAIN_DB_BACKUP"
test -s "$STORIES_DB_BACKUP"
test "$MAIN_DB_BACKUP" != "$STORIES_DB_BACKUP"
sha256sum "$MAIN_DB_BACKUP" "$STORIES_DB_BACKUP" > "$BACKUP/databases.sha256"
cp -p .env "$BACKUP/production.env"
cp -p public/.htaccess "$BACKUP/public.htaccess"
sha256sum public/.htaccess > "$BACKUP/preserved-files.sha256"
tar -czf "$BACKUP/build-before.tgz" -C public build
tar -czf "$BACKUP/uploads-before.tgz" -C public uploads
git archive --format=tar.gz --output="$BACKUP/source-before.tgz" "$PREVIOUS"
tar -tzf "$BACKUP/build-before.tgz" > "$BACKUP/build-inventory.txt"
tar -tzf "$BACKUP/uploads-before.tgz" > "$BACKUP/uploads-inventory.txt"
tar -tzf "$BACKUP/source-before.tgz" > "$BACKUP/source-inventory.txt"
chmod 600 "$BACKUP/production.env"
sha256sum "$BACKUP/"*.tgz > "$BACKUP/archives.sha256"
read -r -p 'Backup kedua DB sudah diverifikasi dan main DB dapat direstore? ketik VERIFIED: ' BACKUP_CHECK
test "$BACKUP_CHECK" = VERIFIED
~~~

NO-GO tanpa backup main DB terverifikasi. .env hanya disalin sebagai backup, tidak diubah. Upload tidak dipindah/dihapus. Backup stories adalah perlindungan pemulihan, bukan izin migrasi atau mengubah isinya.

## C — Maintenance, source, migrasi terarah, build

~~~bash
MAINTENANCE_SECRET=$("$PHP" -r 'echo bin2hex(random_bytes(24));')
"$PHP" artisan down --secret="$MAINTENANCE_SECRET"
~~~

### C.1 — Fingerprint privat record historis, tanpa dump isi cerita

Sesudah maintenance aktif dan sebelum pull/migrasi, catat batas ID dan fingerprint HMAC record historis. Helper hanya membaca dedicated stories DB, memproses 200 row per batch dan mencetak count/fingerprint; isi/token tidak dicetak atau disimpan pada log. Simpan hasil di backup privat, bukan laporan publik. Tujuannya membandingkan row lama sebelum/sesudah, bukan memverifikasi kepemilikan.

~~~bash
cat > "$BACKUP/stories-snapshot.php" <<'PHP'
<?php
require 'vendor/autoload.php'; $a=require 'bootstrap/app.php'; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    App\Support\StoryDatabase::ensureConfigured();
    $db=Illuminate\Support\Facades\DB::connection('stories');
    if ($db->getDatabaseName()!=='gpsfoodi_kuicipstories') exit(1);
    $baseline=getenv('STORY_BASELINE');
    $old=$baseline ? json_decode(file_get_contents($baseline),true,512,JSON_THROW_ON_ERROR) : null;
    $max=$old ? (int)$old['max_id'] : (int)$db->table('stories')->max('id');
    $key=(string)config('stories.abuse_key'); if ($key==='') exit(1);
    $hash=hash_init('sha256',HASH_HMAC,$key);
    $count=0;
    $db->table('stories')->where('id','<=',$max)->orderBy('id')->chunkById(200,function($rows) use($hash,&$count) {
        foreach($rows as $row) { hash_update($hash,json_encode((array)$row,JSON_THROW_ON_ERROR)."\n"); $count++; }
    });
    $out=['max_id'=>$max,'rows'=>$count,'fingerprint'=>hash_final($hash)];
    echo json_encode($out,JSON_PRETTY_PRINT).PHP_EOL;
    if ($old && ($old['rows']!==$out['rows'] || !hash_equals($old['fingerprint'],$out['fingerprint']))) exit(1);
} catch(Throwable $e) { fwrite(STDERR,'Historical snapshot failed: '.get_class($e).PHP_EOL); exit(1); }
PHP
"$PHP" "$BACKUP/stories-snapshot.php" > "$BACKUP/stories-before.json"
~~~

~~~bash
git pull --ff-only origin main
test "$(git rev-parse HEAD)" = "$RELEASE"
sha256sum -c "$BACKUP/preserved-files.sha256"
cmp .env "$BACKUP/production.env"
"$PHP" artisan migrate --database="$MAIN_CONNECTION" --path=database/migrations/2026_10_26_110000_create_content_translations_table.php --pretend --force
~~~

Tinjau SQL pretend. Baru jalankan jika hanya membuat content_translations (id, content_type, content_id, revision UUID, status, fields JSON, timestamps, unique type+id) dan menambah nullable settings.value_en. Tidak ada migrasi stories, penghapusan sumber atau update konten. Migration prerequisite lama: settings dan migration jobs 0001_01_01_000002_create_jobs_table sudah terpasang (jobs/failed_jobs; job_batches bukan kebutuhan workflow ini).

~~~bash
"$PHP" artisan migrate --database="$MAIN_CONNECTION" --path=database/migrations/2026_10_26_110000_create_content_translations_table.php --force
"$PHP" artisan migrate:status --database="$MAIN_CONNECTION" --path=database/migrations/2026_10_26_110000_create_content_translations_table.php
"$PHP" <<'PHP'
<?php
require 'vendor/autoload.php';
$a=require 'bootstrap/app.php'; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$s=Illuminate\Support\Facades\Schema::connection(config('database.default'));
foreach (['id','content_type','content_id','revision','status','fields','created_at','updated_at'] as $c) {
    if (!$s->hasColumn('content_translations',$c)) exit(1);
}
if (!$s->hasColumn('settings','value_en')) exit(1);
if (!Illuminate\Support\Facades\DB::table('migrations')->where('migration','2026_10_26_110000_create_content_translations_table')->exists()) exit(1);
echo "V3 schema verified\n";
PHP
STAGE=$(mktemp -d "$APP/storage/app/private/releases/v3-stage-XXXXXXXX")
ZIP_PATH="$ZIP" STAGE_PATH="$STAGE" "$PHP" <<'PHP'
<?php
$zip=new ZipArchive;
if ($zip->open(getenv('ZIP_PATH'),ZipArchive::CHECKCONS)!==true) exit(1);
if ($zip->numFiles!==__ZIP_FILES__) exit(1);
$seen=[];
for ($i=0;$i<$zip->numFiles;$i++) {
    $name=$zip->getNameIndex($i);
    if (!str_starts_with($name,'build/') || str_contains($name,'\\') || str_contains($name,':')
        || preg_match('~(^|/)\.\.?(/|$)|//~',$name) || str_ends_with($name,'/') || isset($seen[$name])) exit(1);
    $seen[$name]=true;
}
$manifest=json_decode($zip->getFromName('build/manifest.json'),true,512,JSON_THROW_ON_ERROR);
if (!isset($manifest['resources/js/app.tsx'])) exit(1);
foreach ($manifest as $item) {
    foreach (array_merge([$item['file']],$item['css']??[],$item['assets']??[]) as $asset) {
        if (!isset($seen['build/'.$asset])) exit(1);
    }
    foreach (array_merge($item['imports']??[],$item['dynamicImports']??[]) as $key) {
        if (!isset($manifest[$key])) exit(1);
    }
}
if (!$zip->extractTo(getenv('STAGE_PATH'))) exit(1);
for ($i=0;$i<$zip->numFiles;$i++) {
    $name=$zip->getNameIndex($i);
    if (!hash_equals(hash('sha256',$zip->getFromIndex($i)),hash_file('sha256',getenv('STAGE_PATH').'/'.$name))) exit(1);
}
$zip->close(); echo "Archive paths, manifest and extraction verified\n";
PHP
test -f "$STAGE/build/manifest.json"
test ! -e "$BACKUP/build-before-install"
mv public/build "$BACKUP/build-before-install"
mv "$STAGE/build" public/build
find public/build -type d -exec chmod 755 {} +
find public/build -type f -exec chmod 644 {} +
printf '%s  %s\n' '__MANIFEST_SHA256__' 'public/build/manifest.json' | sha256sum -c -
sha256sum -c "$BACKUP/preserved-files.sha256"
"$PHP" artisan config:clear
"$PHP" artisan route:clear
"$PHP" artisan view:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" "$BACKUP/preflight-check.php" | tee "$BACKUP/preflight-after.json"
"$PHP" -r '$b=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR); $a=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR); if (!hash_equals($b["invariant_hash"],$a["invariant_hash"]) || !$a["v3_table"] || !$a["v3_column"] || !$a["v3_migration_logged"]) exit(1); echo "Story/session/cache invariants and V3 schema match\n";' "$BACKUP/preflight-before.json" "$BACKUP/preflight-after.json"
~~~

Tidak perlu composer install karena composer.json/lock tidak berubah dalam rilis ini. Jangan menjalankan optimize:clear/cache:clear selama validasi worker: pertahankan penghitung kuota/anti-spam. Jangan mengubah session/cache atau retention cerita. Policy version hanya melalui persetujuan manual bagian D. Blok di atas membandingkan invariants/config dengan preflight setelah cache dibangun kembali dan memverifikasi schema baru. enabled=true, policies_approved=true, antispam_provider=local dan retention harus tetap berlaku; policy_version sengaja diverifikasi terpisah.

Maintenance diperlukan karena model baru memuat metadata translation pada query publik. Jangan melayani request sebelum migrasi dan build cocok. Jika migrasi/build gagal, tetap maintenance dan gunakan recovery.


## D — Persetujuan panduan baru: gate wajib, bukan persetujuan yang diasumsikan

Versi baru yang dipersiapkan kode: **kuicip-v3-support-removal-2026-10-27**. Pemilik harus membaca dan secara eksplisit menyetujui teks ID/EN pada laporan rilis, proses peninjauan privasi, jalur kontak, serta peralihan token lama. Catat siapa/tanggal/referensi persetujuan di catatan operasional privat. Agen tidak menyatakan persetujuan itu sudah diberikan.

Selama versi konfigurasi masih lama, kode produksi menutup form dan API cerita melalui policy gate. Jangan membuka maintenance dengan menganggap flag policies_approved=true lama juga menyetujui panduan baru.

Setelah persetujuan nyata:
1. **Pemilik sendiri**, melalui editor .env privat cPanel, mengubah hanya nilai STORIES_POLICY_VERSION menjadi kuicip-v3-support-removal-2026-10-27.
2. Pertahankan STORIES_ENABLED=true, STORIES_POLICIES_APPROVED=true, STORIES_ANTISPAM_PROVIDER=local, retention, database, credential, APP_KEY, abuse key, session dan cache.
3. Jangan menyalin seluruh .env atau menampilkan isinya. Tidak ada command otomatis untuk mengedit .env dalam runbook ini.
4. Bila pemilik belum menyetujui, **NO-GO pembukaan publik**. Pertahankan maintenance/tunda deployment; jangan membuat persetujuan palsu atau menulis ulang policy_version pada row lama.

Verifikasi perubahan environment terbatas pada versi panduan dan bangun ulang config:

~~~bash
"$PHP" -r 'require "vendor/autoload.php"; $b=Dotenv\Dotenv::parse(file_get_contents($argv[1])); $a=Dotenv\Dotenv::parse(file_get_contents(".env")); if (($a["STORIES_POLICY_VERSION"]??"")!=="kuicip-v3-support-removal-2026-10-27") exit(1); unset($b["STORIES_POLICY_VERSION"],$a["STORIES_POLICY_VERSION"]); ksort($b);ksort($a);if($b!==$a)exit(1);echo "Only the owner-approved policy version changed\n";' "$BACKUP/production.env"
"$PHP" artisan config:clear
"$PHP" artisan config:cache
"$PHP" "$BACKUP/preflight-check.php" | tee "$BACKUP/preflight-approved.json"
"$PHP" -r '$a=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);if($a["configured_policy_version"]!=="kuicip-v3-support-removal-2026-10-27" || !$a["story_public_enabled"])exit(1);echo "New policy version configured\n";' "$BACKUP/preflight-approved.json"
sha256sum .env > "$BACKUP/approved-env.sha256"
STORY_BASELINE="$BACKUP/stories-before.json" "$PHP" "$BACKUP/stories-snapshot.php" > "$BACKUP/stories-after.json"
~~~

Row cerita lama tetap menyimpan policy_version dan consent aslinya. Form lama harus reload dan meninjau panduan; POST tanpa versi yang cocok ditolak422. Retry identik dengan UUID lama dapat mengambil receipt kode/accepted tanpa membuat record atau persetujuan baru; gunakan challenge CAPTCHA baru bila grant lama sudah tidak sesuai/kedaluwarsa.

### Proses permintaan privasi dan token historis

Kontak resmi berasal dari setting email_primary/config company.email, bukan alamat baru. Pengguna menyertakan kode hanya sebagai referensi. Jangan meminta token lama atau teks privat di ruang publik, form umum, log, atau catatan audit CMS. Pada kontak awal cukup referensi kode; data tambahan hanya bila perlu melalui proses privat yang disetujui, dengan akses/retensi terbatas.

Tidak ada verifikasi kepemilikan otomatis dalam aplikasi. Operator harus menilai kewenangan/konteks dan bukti minimum melalui proses privat yang disetujui; kode saja tidak cukup. Pemegang token lama tetap dapat meminta bantuan, tetapi token tidak lagi menjadi perintah penghapusan melalui API. Bila verifikasi meragukan, eskalasi ke pemilik; hide sementara dapat dipakai untuk melindungi privasi tanpa redaksi permanen.

Setelah operator berwenang menyetujui:
- Superadmin mencari kode di CMS, memeriksa target dan membuka Tindak lanjuti permintaan privasi.
- Isi referensi kasus aman PR-..., ketik kode target, centang peninjauan/kewenangan, lalu konfirmasi dialog.
- Transaksi meredaksi body, menyembunyikan dan soft-delete. Catatan reason lama dan report details terkait dikosongkan agar salinan teks tidak tertinggal.
- Identitas event/report, actor, action/status/time, consent asli, policy_version dan legacy hash tetap ada. Satu audit privacy_redacted berisi referensi kasus, tanpa teks/identitas pemohon.
- Tombstone tetap dapat dicari superadmin untuk audit; tidak dapat dipublikasikan ulang atau dipakai endpoint publik.
- Data uji baru boleh direduksi melalui proses ini; jangan menjalankannya pada cerita historis nyata hanya untuk smoke test.


## E — Uji satu draft perusahaan, bukan produk/klaim nyata

Sebelum cron, pastikan tidak ada job translation lain yang menunggu/reserved; jangan membersihkannya. Jika ada, berhenti dan review pemilik dahulu.

~~~bash
"$PHP" <<'PHP'
<?php
require 'vendor/autoload.php'; $a=require 'bootstrap/app.php'; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$n=Illuminate\Support\Facades\DB::table(config('queue.connections.database.table'))->where('queue','translations')->count();
echo "translation_jobs_before_sample=$n\n";
if ($n!==0) exit(1);
echo 'failed_jobs_before='.Illuminate\Support\Facades\DB::connection(config('queue.failed.database'))->table(config('queue.failed.table'))->where('queue','translations')->count().PHP_EOL;
PHP
~~~

Akses URL maintenance bypass yang dihasilkan artisan down secara privat, login CMS sebagai operator. Jangan membagikan secret atau cookie. Buat **draft artikel baru**:
- Slug: cms-v3-release-probe-__SHORT_SHA__
- Judul ID: Kuicip
- Excerpt ID: Halo dunia
- Body ID: satu paragraf Halo dunia
- EN kosong; tanggal/kategori valid; published=false; featured=false; tanpa gambar/tags.
Klik Simpan sekali, buka edit dan lihat pending. Ini sampel publik tetap, bukan cerita anonim atau perubahan klaim produk.

~~~bash
cd "$APP"
"$FLOCK" -n storage/framework/translations-worker.lock "$TIMEOUT" --kill-after=5s 55s "$PHP" artisan queue:work database --queue=translations --once --max-time=50 --max-jobs=1 --memory=128 --tries=1 --timeout=40 --force
"$PHP" <<'PHP'
<?php
require 'vendor/autoload.php'; $a=require 'bootstrap/app.php'; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p=App\Models\Article::where('slug','cms-v3-release-probe-__SHORT_SHA__')->firstOrFail();
$s=$p->translationState;
$valid=!$p->published && $s?->status==='ready' && preg_match('/hello.*world/i',$p->excerpt_en)
    && preg_match('/hello.*world/i',strip_tags($p->body_en));
echo json_encode(['draft_id'=>$p->id,'status'=>$s?->status,'excerpt_en'=>$p->excerpt_en,'body_english_persisted'=>(bool)preg_match('/hello.*world/i',strip_tags($p->body_en)),'verified'=>(bool)$valid],JSON_PRETTY_PRINT).PHP_EOL;
if (!$valid) exit(1);
echo 'failed_jobs_after='.Illuminate\Support\Facades\DB::connection(config('queue.failed.database'))->table(config('queue.failed.table'))->where('queue','translations')->count().PHP_EOL;
PHP
~~~

--force di atas hanya mengizinkan satu worker queue terjemahan pada maintenance, bukan Git force-push. Jangan gunakan force pada cron reguler. Bandingkan failed_jobs sebelum/sesudah dan panel: kegagalan provider yang ditangani aplikasi muncul di content_translations, sehingga failed_jobs kosong saja bukan bukti sukses. Jika quota/timeout/error, sumber dan fallback tetap tersedia; NO-GO aktivasi cron sampai tes provider+persistence berhasil. Jangan memalsukan ready, clear queue, retry seluruh failed jobs, atau memanggil repair APPLY.

Setelah berhasil dan pemeriksaan G selesai, draft probe dapat dipertahankan untuk catatan atau dihapus hanya melalui CMS berdasarkan slug dan ID yang baru dibuat. Jangan mempublikasikannya. scripts/translation-probe.php adalah probe lokal yang bergantung autoload-dev/TestCase, **bukan perintah production**.

## F — Cron cPanel setelah worker berhasil

Koneksi job selalu database, queue translations; tidak memakai queue default. Table default jobs/failed_jobs mengikuti konfigurasi yang sudah diverifikasi. Tidak perlu jadwal status/cleanup baru; status ditulis oleh job. Scheduler analytics yang sudah ada tetap dipertahankan.

- Job timeout 40 detik, tries=1; retry_after harus minimal 90 detik.
- Worker maksimal 50 detik antar-job, 5 job per proses, memory threshold 128 MB.
- GNU timeout membatasi keseluruhan proses 55 detik + kill-after 5 detik, termasuk hosting tanpa PCNTL. flock mencegah overlap. Timeout CLI Laravel berlaku per-job bila PCNTL tersedia.
- Exit 124/137 atau job terhenti adalah kegagalan untuk diperiksa, bukan tanda semua terjemahan selesai.
- Job mengenali revisi/source/target hash; job lama tidak menimpa manual/revisi baru. Worker terhenti dapat meninggalkan reservation sampai retry_after; pengiriman berikutnya melewati tries=1, menandai failed. Gunakan retry panel setelah review atau dry-run scoped untuk stalled (>5 menit). Jangan mass queue:retry.
- Provider maksimal 4 HTTP attempt/job, 2/5 detik connect/request timeout, satu retry timeout/5xx. Cache 30 hari; 12 request/menit lokal, 4.000 karakter/hari. Budget/rate lokal menunda 5/65 detik. Kuota provider/harian dan error berhenti untuk retry eksplisit.
- Manual English/legacy dan data stories tidak diproses otomatis oleh backfill.

Cetak command menggunakan path yang baru ditemukan (hasil adalah command yang ditempel pada kolom Command cPanel; jadwal dipilih setiap menit):

~~~bash
printf 'cd %q && %q -n storage/framework/translations-worker.lock %q --kill-after=5s 55s %q artisan queue:work database --queue=translations --stop-when-empty --max-time=50 --max-jobs=5 --memory=128 --tries=1 --timeout=40 >> storage/logs/translations-worker.log 2>&1\n' "$APP" "$FLOCK" "$TIMEOUT" "$PHP"
~~~

Pastikan path absolut dan runtime yang sama di lingkungan Cron. Uji command cetakan secara manual dengan queue kosong, lalu pasang Cron Jobs setiap menit hanya sesudah E berhasil dan pemilik menyetujui gate. Jangan mengganti cron scheduler lama. Log dibatasi oleh kebijakan rotasi hosting; jangan biarkan file tumbuh tanpa pemantauan. Tidak ada secret/source content pada payload job, hanya type/id/revision; tetap simpan log di storage, di luar public.

## G — Health check sebelum artisan up

Dengan cookie bypass maintenance, verifikasi di browser:
- Homepage 200, logo GPS FOOD dan font sama; teaser/nav Ruang Cerita tetap.
- Katalog kedua merek, detail Original/Truffle dan satu varian lain, Putri Teko.
- ID/EN switch dan persistensi; enam catatan tidak menghasilkan bullet kosong; fallback ID berlabel bila EN belum siap.
- Shopee/WhatsApp CTA dan URL yang benar.
- Admin produk/artikel/settings, status pending→ready pada draft probe, English tersimpan setelah reload.
- Manual EN tetap utuh saat sumber ID berubah pada draft probe; bukan mengubah produk marketing nyata.
- Login, hak editor/superadmin, admin moderation GET dan pencarian kode cerita milik operator.
- /kuicip/cerita, CAPTCHA local, consent/UI; noindex tetap dan tidak masuk sitemap. Jangan mengirim/mengubah cerita, report, token, atau moderasi historis. Uji baru hanya dengan data sintetis sendiri sesuai prosedur di bawah.

Untuk curl di terminal yang sama, ambil cookie bypass secara privat:

~~~bash
curl -fsSL -A gpsfood-release-testbot -c "$BACKUP/maintenance.cookies" "https://gpsfood.id/$MAINTENANCE_SECRET" -o /dev/null
for PAGE in / /products/kuicip /products/kuicip/original /products/putri-teko /news /mitra /contact /export /kuicip/cerita /sitemap.xml; do
  curl -fsS -A gpsfood-release-testbot -b "$BACKUP/maintenance.cookies" -o /dev/null -w '%{http_code}\n' "https://gpsfood.id$PAGE"
done
printf '%s\n' "$RELEASE"
test "$(git rev-parse HEAD)" = "$RELEASE"
sha256sum -c "$BACKUP/preserved-files.sha256"
~~~

Periksa manifest dan seluruh asset build berdasarkan SHA ZIP, bukan hanya halaman HTML. Font/asset di luar build dipertahankan dari produksi; dua referensi font lokal sudah memunculkan warning pada build baseline, sehingga pemeriksaan visual/font adalah gate nyata. Initial SEO/JSON-LD tetap ID; localStorage bahasa tidak tersedia untuk crawler awal, tanpa URL EN/SSR baru.


### Uji cerita sintetis setelah persetujuan policy

Dengan bypass maintenance dan CAPTCHA local, kirim satu cerita **sintetis privat** yang jelas berlabel uji tanpa data pribadi. Simpan kode, bukan token. Periksa di Network bahwa respons tepat code/accepted dan tidak memuat deletion_token; reload CMS untuk memeriksa pending, hash baru NULL, versi baru, dan consent privat tidak menjadi publik. Verifikasi consent publik/sosial tetap terpisah pada form.

Di server, pemeriksaan read-only contoh berikut hanya untuk kode sintetis yang baru dibuat. Isi TEST_STORY_CODE dari receipt uji; jangan memakai kode historis:

~~~bash
read -r -p 'Kode cerita sintetis BARU untuk smoke test (KC-...): ' TEST_STORY_CODE
TEST_STORY_CODE="$TEST_STORY_CODE" "$PHP" <<'PHP'
<?php
require 'vendor/autoload.php';$a=require 'bootstrap/app.php';$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$code=getenv('TEST_STORY_CODE');if(!preg_match('/^KC-[0-9]{4,20}$/',$code))exit(1);
$s=App\Models\Story::findOrFail((int)substr($code,3));
$valid=$s->deletion_token_hash===null && $s->status==='pending' && $s->visibility==='private'
    && !$s->publication_consent && $s->policy_version===App\Support\StoryAvailability::REQUIRED_POLICY_VERSION;
echo json_encode(['code'=>$s->displayCode(),'pending_private_no_token_with_new_policy'=>(bool)$valid]).PHP_EOL;
if(!$valid)exit(1);
PHP
~~~

Receipt code tidak mengotorisasi redaksi. Uji admin dengan data sintetis milik operator: editor tidak boleh memproses privasi; superadmin meninjau lalu mengonfirmasi redaksi. Setelah itu body adalah [removed], history/case reference ada, consent/version tetap, dan re-approve target ditolak. Jangan memakai request pengguna nyata untuk membuktikan tombol.

POST lama /kuicip/cerita/removal tetap410 dengan CSRF valid dan respons generik; input kosong/acak/kode valid memberikan hasil sama, tidak membaca cerita. Tanpa CSRF yang valid,419 tetap benar. Gunakan request browser same-origin dengan token CSRF sesi, bukan menonaktifkan CSRF. Jangan memasukkan legacy token nyata pada uji ini.

Setelah uji sintetis selesai, fingerprint historis harus tetap sama (helper membatasi ID sebelum deployment):

~~~bash
STORY_BASELINE="$BACKUP/stories-before.json" "$PHP" "$BACKUP/stories-snapshot.php" > "$BACKUP/stories-after-smoke.json"
sha256sum -c "$BACKUP/approved-env.sha256"
~~~

Hanya sesudah semua pemeriksaan kritis lulus:

~~~bash
"$PHP" artisan up
curl -fsS -A gpsfood-release-testbot -o /dev/null -w '%{http_code}\n' https://gpsfood.id/
curl -fsS -A gpsfood-release-testbot -o /dev/null -w '%{http_code}\n' https://gpsfood.id/kuicip/cerita
git status --short --branch
~~~

Jika pengecekan publik akhir gagal, aktifkan maintenance kembali dan recovery. Jangan melaporkan sukses produksi sebelum pemilik menyelesaikan langkah ini.

## Legacy dry-run terpisah, tanpa APPLY

Jalankan setelah schema valid; tidak ada API/queue/data/cache write dari perintah ini. Catat hasil produksi yang sebenarnya.

~~~bash
"$PHP" artisan translations:repair --dry-run --type=products --batch=10
"$PHP" artisan translations:repair --dry-run --type=articles --batch=10
"$PHP" artisan translations:repair --dry-run --type=brands --batch=10
"$PHP" artisan translations:repair --dry-run --type=settings --batch=10
~~~

Lanjutkan cursor --after per tipe sesuai output. Tidak ada --apply pada deployment ini. Backfill nanti memerlukan backup baru, scope/batch, review hasil aktual dan persetujuan pemilik terpisah. English legacy yang tidak diketahui versinya, klaim sensitif dan brand naming memerlukan review manusia.

## Recovery sumber/build yang aman

Tetap/aktifkan maintenance dan hentikan sementara cron translations baru melalui cPanel. Sebelum menjalankan kode lama, pemilik harus menutup API publik cerita dengan STORIES_POLICIES_APPROVED=false melalui edit privat manual, lalu config:cache. Pertahankan STORIES_ENABLED=true untuk moderasi dan kontak resmi tetap tersedia. Ini gate recovery agar endpoint token lama tidak aktif kembali. Jangan membuka cerita publik pada kode lama. Jangan mereset history main, menghapus uploads, restore .env secara otomatis, atau rollback schema additive. Pertahankan semua ID/EN dan data stories. Redaksi privasi yang sudah sah tidak dipulihkan dari backup. Riwayat persetujuan/hashes tetap ada; jangan mengaktifkan ulang penghapusan mandiri.

Setelah memastikan tidak ada perubahan sumber lain selain .htaccess custom:

~~~bash
cd "$APP"
test -n "$BACKUP"
test -d "$BACKUP/build-before-install"
test -n "$PREVIOUS"
git diff --quiet -- . ':!public/.htaccess'
RECOVERY_BRANCH=recovery/cms-v3-"$STAMP"
git switch -c "$RECOVERY_BRANCH" "$PREVIOUS"
test "$(git rev-parse HEAD)" = "$PREVIOUS"
test ! -e "$BACKUP/build-v3-quarantine"
mv public/build "$BACKUP/build-v3-quarantine"
mv "$BACKUP/build-before-install" public/build
sha256sum -c "$BACKUP/preserved-files.sha256"
"$PHP" artisan config:clear
"$PHP" artisan route:clear
"$PHP" artisan view:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
~~~

Jika tahap instalasi build belum sempat memindah build lama, jangan jalankan blok swap; gunakan build lama yang masih berada di public/build dan verifikasi terhadap backup. Jika hanya arsip build-before.tgz tersedia, validasi dan ekstrak di lokasi privat lalu pasang folder build saja. Jangan menimpa public/ secara keseluruhan.

Verifikasi source/build baseline, .htaccess, uploads, homepage, login dan cerita dengan cookie bypass sebelum artisan up. Schema V3 yang additive boleh tetap ada ketika kode baseline dipakai. Pemulihan database dari backup hanya keputusan terpisah bila ada bukti korupsi/data loss; jangan otomatis menghilangkan tulisan baru. Remote main tetap commit V3; cabang recovery lokal sengaja menunjuk baseline untuk pemulihan tanpa rewrite history.


Tidak ada migration stories baru. Satu migration website V3 yang belum dideploy tetap wajib: database/migrations/2026_10_26_110000_create_content_translations_table.php. Jangan menggunakan ZIP V3 c9779ed lama; ia tidak berisi perubahan receipt/UI terbaru.
