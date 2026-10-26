# Rilis anti-spam first-party — Stage A, NyanHosting

> **Dokumen historis.** Pemilik menyatakan Ruang Cerita produksi sudah terbuka. Untuk rilis UI/UX beserta promosi homepage dan navbar, gunakan [runbook Ruang Cerita V2 terbaru](GPSFOOD_RUANG_CERITA_V2_RELEASE.md). Jangan menerapkan ulang aktivasi Stage A, inisialisasi DB, atau penutupan promosi dari dokumen ini.

26 Oktober 2026, Asia/Jakarta. Ini prosedur **manual pemilik**, bukan deployment oleh agen. Baseline server menurut pemilik: e39cef2896837675670bedd7b3622eae5c09e504. Local acceptance sudah diterima pemilik. SHA/ZIP/hash final harus diambil dari laporan artefak rilis, bukan ditebak dari nama branch.

Target setelah deployment:

```dotenv
STORIES_ENABLED=true
STORIES_POLICIES_APPROVED=false
STORIES_ANTISPAM_PROVIDER=local
```

Public landing tetap informatif/direct URL; challenge/feed/submission/mutations publik tetap tertutup. Superadmin tetap dapat membuka /admin/stories. **Bukan izin Stage B.**

## Preflight dan backup

Command berikut untuk cPanel Terminal Bash di akun gpsfoodi, bukan untuk dijalankan agen. Jangan melanjutkan setelah error atau SHA/hash berbeda.

```bash
cd /home/gpsfoodi/gsp-laravel
pwd -P
php -v
git branch --show-current
git remote get-url origin
git rev-parse HEAD
git status --short
```

Harus repo/branch/remote benar. Jika server tidak pada baseline yang diharapkan, berhenti dan tinjau perubahan. Jika ada perubahan lokal belum ditinjau, jangan pull/reset/clean atau menimpanya. Custom public/.htaccess harus dipertahankan; bandingkan dengan daftar path rilis dan backup terlebih dahulu. Rilis ini tidak mengubah public/.htaccess, public/uploads, homepage, katalog, dependency/lockfile atau migration.

1. Konfirmasi backup **DB website dan DB stories terpisah** melalui fasilitas cPanel/phpMyAdmin yang sudah digunakan. Jangan menulis password pada command line. Backup harus privat, akses terbatas/encrypted sesuai kebijakan, serta prosedur restore sudah diketahui.
2. Backup source/build/media/config sebelum perubahan, misalnya:

```bash
umask 077
previousSha=$(git rev-parse HEAD)
releaseBackup="$HOME/gpsfood-release-backups/$(date +%Y%m%d-%H%M%S)"
mkdir -p "$releaseBackup"
git archive --format=tar.gz --output="$releaseBackup/source-before.tgz" "$previousSha"
git rev-parse HEAD > "$releaseBackup/source-before.sha"
tar -czf "$releaseBackup/build-before.tgz" -C public build
cp -p .env "$releaseBackup/app.env"
cp -p public/.htaccess "$releaseBackup/public.htaccess"
sha256sum public/.htaccess > "$releaseBackup/public-htaccess.sha256"
if [ -d public/uploads ]; then tar -czf "$releaseBackup/uploads-before.tgz" -C public uploads; fi
if [ -d storage/app/public ]; then tar -czf "$releaseBackup/storage-public-before.tgz" -C storage/app public; fi
```

Backup git archive hanya mencakup source tracked. Salin juga perubahan operator/untracked yang memang harus dipertahankan melalui prosedur backup cPanel; jangan menganggap semuanya ada dalam snapshot Git. Jangan menaruh .env, SQL atau backup di public, Git atau chat.

3. Tentukan operator rollback, waktu maintenance dan lokasi backup. Jika backup/checkpoint tidak siap, NO-GO.

## Upload dan verifikasi paket

4. Dari laporan final, isi nilai literal yang benar:

```bash
releaseSha='<SHA_COMMIT_RILIS>'
releaseZip='/home/gpsfoodi/<NAMA_ZIP_FINAL>.zip'
releaseZipSha='<SHA256_ZIP_FINAL>'
manifestSha='<SHA256_BUILD_MANIFEST_FINAL>'
```

5. Upload ZIP baru melalui File Manager ke **/home/gpsfoodi/**, di luar public. Jangan memakai ZIP historis e39 atau ZIP dengan nama entry Windows/backslash.

```bash
chmod 600 "$releaseZip"
sha256sum "$releaseZip"
test "$(sha256sum "$releaseZip" | awk '{print $1}')" = "$releaseZipSha"
unzip -t "$releaseZip"
unzip -Z1 "$releaseZip"
```

Seluruh entry harus file build/manifest.json atau build/assets/..., memakai slash POSIX, tanpa backslash, traversal, duplicate, private data atau surrounding directory. Paket final sudah diverifikasi perentry/CRC/bytes di lokal; operator tetap wajib membandingkan hash transport.

## Maintenance, source dan assets

6. Bekukan trafik. Untuk menguji sebelum keluar maintenance, gunakan bypass sementara yang dihasilkan Laravel:

```bash
php artisan down --retry=60 --with-secret
```

Buka URL bypass hanya dalam browser operator secara privat. Jangan menaruh URL/token ini dalam laporan atau screenshot publik. Bypass maintenance **tidak** melewati login/role/CSRF.

7. Fetch dan pastikan main menunjuk SHA rilis yang disetujui sebelum pull:

```bash
git fetch origin main
test "$(git rev-parse origin/main)" = "$releaseSha"
git merge-base --is-ancestor HEAD origin/main
git diff --name-only HEAD..origin/main
git pull --ff-only origin main
test "$(git rev-parse HEAD)" = "$releaseSha"
```

Jika main bergerak lebih jauh dari SHA yang ditinjau, berhenti; jangan pull commit lain, force, reset atau checkout menimpa pekerjaan operator. Jika ada perubahan lokal yang beririsan dengan path rilis, tangani manual sebelum lanjut.

8. Pasang hanya Vite assets dari ZIP yang cocok:

```bash
unzip -o "$releaseZip" -d /home/gpsfoodi/gsp-laravel/public
test -f /home/gpsfoodi/gsp-laravel/public/build/manifest.json
test "$(unzip -p "$releaseZip" build/manifest.json | sha256sum | awk '{print $1}')" = "$manifestSha"
test "$(sha256sum public/build/manifest.json | awk '{print $1}')" = "$manifestSha"
sha256sum -c "$releaseBackup/public-htaccess.sha256"
```

Root arsip build/ diekstrak ke public sehingga hasilnya public/build/manifest.json, bukan public/build/build. Jangan mengekstrak source/backup ke public. Jangan menghapus public/uploads atau custom .htaccess. Tidak perlu menghapus chunk lama; manifest menunjuk aset rilis baru.

9. **Tidak ada migration baru.** Jangan menjalankan story migrations, migrate:fresh/reset/rollback, seeders atau mengubah data. Dependency/lockfile tidak berubah; tidak perlu install dependency baru. Tidak menjalankan npm/Node di hosting. Source backend dan build frontend harus terpasang berpasangan.

## Konfigurasi Stage A dan pemeriksaan

10. Di editor .env privat server, set/konfirmasi hanya:

```dotenv
STORIES_ENABLED=true
STORIES_POLICIES_APPROVED=false
STORIES_ANTISPAM_PROVIDER=local
```

Pertahankan APP_KEY, STORIES_ABUSE_KEY, koneksi/user stories dan credential lain. Cloudflare keys tidak wajib untuk local dan boleh tetap tersimpan privat untuk provider opsional. Jangan key:generate. Driver session/cache harus file atau database persistent; cache harus mendukung atomic lock. Tabel cache/cache_locks yang sudah ada harus siap jika memakai database cache; jika tidak, berhenti dan perbaiki konfigurasi, bukan migrate/reset data.

11. Refresh cache:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

12. Preflight hanya mencetak hasil boolean, bukan secret:

```bash
php artisan tinker --execute='try { $ok = app()->environment("production") && !config("app.debug") && config("stories.enabled") === true && config("stories.policies_approved") === false && config("stories.antispam_provider") === "local" && !App\Support\StoryAvailability::publicEnabled() && in_array(config("session.driver"), ["file", "database"], true) && in_array(config("cache.stores.".config("cache.default").".driver"), ["file", "database"], true); $lock = Illuminate\Support\Facades\Cache::lock("story-antispam-preflight", 5)->get(fn () => true); if (!$ok || !$lock) { throw new \RuntimeException; } echo "STAGE_A_CONFIGURATION_AND_LOCK_OK\n"; } catch (\Throwable) { fwrite(STDERR, "STAGE_A_PREFLIGHT_FAILED\n"); exit(1); }'
php artisan route:list --path=kuicip/cerita
php artisan route:list --path=admin/stories
```

Jika gagal, tetap maintenance/Stage A; jangan menampilkan seluruh config atau mengaktifkan policies_approved demi menyelesaikan pemeriksaan.

13. Melalui browser operator yang memiliki cookie bypass maintenance, periksa:
- Superadmin login → /admin/stories200, menu/filter/detail/report/history normal. Guest menuju login, editor403. Jangan membuat/mengubah cerita produksi untuk uji pemasangan Stage A.
- /kuicip/cerita200 menampilkan belum dibuka, tanpa form/challenge aktif; noindex,nofollow.
- /kuicip/cerita/feed503. POST challenge/submission tetap503 bila CSRF sah; tanpa CSRF dapat419 lebih dahulu. Ini perlindungan yang benar, bukan alasan mematikan CSRF.
- Homepage/navbar/footer/katalog tanpa kampanye/tautan Ruang Cerita. Produk/detail/Shopee dan CMS lainnya tetap normal.
- Assets/font tidak404, tidak ada build/build, manifest cocok, .htaccess/uploads utuh.
- Pastikan hasil server sama dengan flag Stage A; **jangan membuka Stage B**.

14. Hanya jika semua pemeriksaan yang berlaku lulus:

```bash
php artisan up
```

15. Smoke test ulang tanpa bypass: homepage, catalog/detail, fonts/assets dan admin login/stories; public landing belum dibuka dan feed/challenge tetap terlindungi. Catat source SHA, ZIP/hash, manifest hash, waktu/operator dan hasil tanpa credential/isi cerita.

## Emergency dan rollback

- Untuk menutup publik sambil mempertahankan admin: enabled=true, policies_approved=false, lalu refresh config. Ini sudah target Stage A.
- Bila subsystem/database bermasalah total, enabled=false menutup juga moderasi; data tetap disimpan. Jangan drop/prune/reset sebagai cara menonaktifkan.
- Bila source/assets baru bermasalah: maintenance, flag publik tetap false, gunakan **pasangan source/build sebelumnya**. Cara yang mempertahankan history adalah pemilik membuat commit revert normal pada repo pengembangan untuk rilis ini, mereview/push secara normal, lalu server pull --ff-only recovery commit yang ditetapkan. Jangan force-reset production.
- Setelah source recovery yang isinya cocok baseline terpasang, pulihkan build sebelumnya:

```bash
tar -xzf "$releaseBackup/build-before.tgz" -C public
test "$(sha256sum public/build/manifest.json | awk '{print $1}')" = "$(tar -xOzf "$releaseBackup/build-before.tgz" build/manifest.json | sha256sum | awk '{print $1}')"
sha256sum -c "$releaseBackup/public-htaccess.sha256"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Tetap policies_approved=false saat kembali ke source e39 yang memerlukan Turnstile. Jangan mengembalikan seluruh .env/uploads dari snapshot secara buta atau menjalankan migration down. Restore DB bukan bagian rollback kode ini. Jika perlu disaster recovery cPanel, operator menggunakan backup lengkap dan merekonsiliasi source/build/config secara terencana. Keluar maintenance hanya setelah smoke test recovery lulus.

## Keputusan

**Stage A: GO bersyarat untuk deployment manual** setelah push/ZIP terverifikasi, backup, PHP8.2 hosting, driver/lock dan pemeriksaan operator lulus. Ini bukan klaim produksi sudah dipasang.

**Stage B: NO-GO.** Tetap memerlukan persetujuan teks/versi kebijakan, retensi/cadangan/privasi, moderator/response spam dan otorisasi terpisah. Aritmetika ringan bukan proteksi kuat terhadap distributed bots; tidak ada CAPTCHA-disabled mode.
