# GPS FOOD — persistent local preview

26 Oktober 2026. Ini profil **localhost saja**, terpisah dari .env/database/media utama. Produksi tetap stories disabled. Kebijakan LOCAL-DEMO-NOT-APPROVED adalah konfigurasi pengujian, bukan persetujuan legal/bisnis.

## Status pemulihan

Sebelum interupsi: file helper, perubahan UI dan guard sudah dibuat sebagian. Percobaan awal setup gagal karena nama method setup bertabrakan dengan PHPUnit; diperbaiki menjadi prepareDatabases. Percobaan berikutnya menerapkan schema website tetapi migration cerita ditolak saat Laravel Migrator sementara mengganti default connection ke stories. State diperiksa ulang (migration cerita kosong, akun kosong), lalu guard disesuaikan hanya untuk dua target canonical milik profil. Tidak menghapus atau mengulang schema secara destruktif.

Server port8767 sempat tidak dijalankan karena automatic approval review terkena usage limit, bukan keputusan tindakan unsafe. Setelah pengguna meminta resume, command status dan setup mendapat approval melalui mekanisme yang sama; tidak memakai workaround untuk menghindari review. Setup kini selesai dan port8766 memakai profil persisten. Server readonly lama milik task dan server sementara8767 sudah dihentikan secara spesifik.

## Isolasi

State ignored: storage/app/private/gpsfood-local-preview/ . Dua database berbeda: website.sqlite dan stories.sqlite. Root canonical, marker kepemilikan, environment path, public, bootstrap dan storage harus tepat di profil; symlink keluar root ditolak. Konfigurasi hanya sqlite dengan file yang ditentukan, tanpa remote host/credential atau default fallback. APP_ENV aplikasi di-force local hanya oleh bootstrap development yang terpisah, HTTP dibatasi loopback/Hostlocalhost dan PHP cli-server; produksi tidak memuat helper ini.

TestCase bootstrap digunakan agar .env utama tidak dimuat dan konfigurasi aman dipasang sebelum provider. Profil lalu memakai file sessions/cache dan random app key sendiri dalam direktori ignored. Helper membutuhkan Composer dev dependencies (PHPUnit/Faker): jangan digunakan di hosting atau sebagai bootstrap produksi. Semua log aplikasi null dan mail fake; HTTP eksternal diblokir, kecuali Siteverify fake yang tetap berada di proses lokal.

File uploads masuk public/ di direktori profil, bukan public/uploads asli. Router hanya melayani ekstensi statis yang diizinkan; /index.php tidak mengeksekusi bootstrap utama dan /.env ditolak. Source image/font/build public dibaca saja. Tidak ada migrate:fresh, db:wipe, truncate atau penghapusan media.

## Menjalankan ulang

Dari root repository, gunakan executable yang telah diverifikasi:

~~~powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe scripts/local-preview.php status
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe scripts/local-preview.php setup
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe -S 127.0.0.1:8766 -t public scripts/local-preview-router.php
~~~

Setup hanya migration pending pada koneksi sqlite/path database/migrations dan stories/path database/migrations/stories. Guard diverifikasi sebelum migration. Tidak mereset data. Jalankan setup pertama kali atau bila migration baru ditambahkan; restart server biasa tidak membutuhkan setup. Ctrl+C menghentikan server, database/session/key tetap ada.

State berisi22 produk seed repository,15 fixture cerita idempoten dan dua record QA (satu sudah dihapus melalui token). Sembilan cerita publik approved tersedia untuk pagination6+3. Status private pending/reviewed, public pending, rejected, hidden, kategori berbeda, panjang sampai1490 karakter, reactions dan unresolved report tersedia. Fixture dikenali melalui UUID demo tetap; firstOrCreate mempertahankan keputusan moderator yang sudah ada. Setup ulang terbukti tidak menggandakan record. Jangan mengasumsikan urutan fixture sama dengan primary key; pilih melalui filter CMS.

## Akun pemilik — tindakan manual yang dipilih pengguna

Akun pemilik belum dibuat. Pengguna memilih menjalankan prosedur setelah pekerjaan selesai. Tidak mengambil atau mereset akun database utama.

~~~powershell
rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe scripts/local-preview.php admin:create preview-superadmin@gpsfood.test --superadmin
~~~

Masukkan password unik pada prompt tersembunyi (minimal8 karakter). Jangan menyertakannya pada argumen command atau chat. Preview entrypoint menolak password pada argv dan menolak email yang sudah ada; role/password akun lain tidak diubah. Prompt tidak fallback ke input terlihat bila terminal tidak mendukung hidden input. Akun tersimpan hanya di website.sqlite preview.

Login pemilik **belum diuji** karena password ditentukan pemilik. Jalur login aplikasi benar-benar diuji lewat HTTP dengan akun QA superadmin/editor disposable; password QA acak hanya di RAM/stdin dan tidak dicatat. Akun QA dibersihkan setelah test; tidak menjadi kredensial handoff.

## Alur manual

1. Buka http://127.0.0.1:8766/kuicip/cerita . Banner harus mengatakan PREVIEW LOKAL, data fiksi, CAPTCHA simulasi dan kebijakan belum disahkan.
2. Tulis cerita fiksi20–1500 karakter, pilih kategori dan visibility. Setujui terms serta publication consent bila publik; social reuse opsional. Klik Kirim untuk ditinjau. Simpan token penghapusan pribadi yang tampil sekali. Jeda kirim60detik dan rate limits tetap berlaku.
3. Buat akun dengan command di atas; buka http://127.0.0.1:8766/admin/login . Login dengan password pilihan sendiri.
4. Buka http://127.0.0.1:8766/admin/stories . Pilih pending story, isi alasan dan approve.
5. Refresh feed. Public approved muncul; approval private tidak memublikasikannya. Filter kategori/load more, Dukung, laporan, hide, dan removal token dapat dicoba dengan data fiksi.
6. Buka CMS products/articles/partners/certifications/messages/users/settings/profile. Semua memakai login yang sama, tanpa autologin. Editor tetap tidak bisa mengakses moderasi/private.

Homepage: http://127.0.0.1:8766/ . Gunakan hardrefresh untuk memastikan asset terbaru dan splash putih. Profil ini menerima mutations yang normal, berbeda dari harness GET-only lama.

## Turnstile dan flags

Default config/stories.php tetap enabled=false dan policies_approved=false. Hanya bootstrap preview mengaktifkan kedua flag di memory serta local_preview=true. isLocalPreview mensyaratkan APP_ENV local, marker dan semua path canonical milik profil. Production menolak file preview walaupun flag lokal dipalsukan. Tidak ada secret produksi yang dibutuhkan/disimpan dalam source.

Frontend hanya pada profil valid menggunakan token demo; server TurnstileVerifier tetap dipanggil, lalu Laravel HTTP fake menerima token demo yang benar dan menolak token salah. Hostname/action diverifikasi oleh service yang sama. Tidak ada request Cloudflare asli dan tidak ada perubahan bypass pada verifier produksi. Banner dan policy version menjelaskan mode uji.

## Hasil HTTP dan perbaikan

Alur real backend persisten lulus: CSRF419, fake-verifier wrong-token422, valid submission202 pending, idempotent retry tanpa token ulang, cooldown429, guest401/API, login superadmin dan editor, editor403,17 halaman CMS200, approval->feed, atomic/dedup reactions, reporting, private404/never-public, removal token->redaction dan hilang dari feed. Story IDs/feed bertahan setelah proses server dihentikan dan dijalankan ulang.

Dua bug terkonfirmasi diperbaiki: route throttle numerik sebelumnya berbagi bucket domain/IP sehingga feed menghabiskan kuota submission; kini masing-masing jenis endpoint cerita mempunyai prefix sendiri dengan batas angka tetap. Counter pending report sekarang mengecualikan cerita soft-deleted sehingga tidak menunjukkan laporan yang tidak bisa ditinjau. Tambahan regression tests melindungi keduanya.

## CMS UI/UX — NOT VISUALLY VERIFIED

Browser connector inventory kosong. Tidak ada screenshot atau klaim visual/console/mobile lulus. Pemeriksaan source mencakup login/dashboard/sidebar/topbar/product/article/partner/certification/message/user/settings/profile/moderation. Typography global DM Sans/Archivo, cream surfaces, tombol biru dan focus styles tetap digunakan; tidak menambah hero/animasi marketing pada admin.

Temuan source yang jelas: login tidak memakai logo GPS FOOD dan sidebar masih placeholder teks GPS. Keduanya kini memakai PNG asli dengan rasio terjaga; ukuran logo sidebar collapsed disesuaikan agar sesuai lebar76px. Banner lokal juga tampil di login/CMS agar data uji tidak disalahartikan sebagai data utama. Table/form/toast/dialog/empty/loading/status layout lain dipertahankan tanpa desain ulang berdasarkan dugaan visual.

Checklist screenshot manual pada375×812,768×1024,1440×1000:

- Homepage splash + normal Inertia navigation + reduced-motion; cek Console dan hardrefresh.
- /kuicip/cerita: form public/private, checkbox, receipt, error429, filter, load-more, report popup; pastikan tidak overflow dan private tidak ada pada Network response.
- /admin/login dan /admin: logo/fonts, cards/charts, buttons, fokus keyboard.
- /admin/products/1/edit: tab bahasa, SEO collapsible, input errors, upload, unsaved dialog.
- /admin/stories dan detail: private warning, consent, moderation reason/actions/history/reports; cek layar sempit dan sidebar collapse/mobile menu.
- Article, partner, certification, messages, users, settings, profile: form/table scroll, badges, toast/confirm/loading/empty states, Tab/ShiftTab/Escape. Simpan screenshot aktual dengan nama viewport-page-state.

## Berkas task ini

- app/Console/Commands/AdminCreateCommand.php
- app/Support/StoryDatabase.php
- app/Http/Middleware/HandleInertiaRequests.php
- app/Http/Controllers/Admin/StoryController.php
- routes/web.php
- scripts/local-preview.php (baru)
- scripts/local-preview-router.php (baru)
- tests/Support/LocalPreview.php (baru)
- tests/Support/LocalStoryDemo.php (baru)
- tests/Feature/LocalPreviewGuardTest.php (baru)
- tests/Feature/AdminCreateCommandTest.php
- tests/Feature/StoryPlatformTest.php
- resources/js/types/index.ts
- resources/js/Pages/Kuicip/Cerita.tsx
- resources/js/Pages/Admin/Login.tsx
- resources/js/Components/admin/Sidebar.tsx
- resources/js/Layouts/AdminLayout.tsx
- docs/GPSFOOD_LOCAL_PREVIEW.md (baru)
- docs/GPSFOOD_PROJECT_CONTEXT.md (append)
- docs/GPSFOOD_V2_ACCEPTANCE.md (catatan)
- docs/GPSFOOD_V2_DEPLOYMENT.md (catatan)

State/helper HTTP QA di storage/app/private seluruhnya ignored dan bukan source untuk commit/deployment. .env, dependency, splash dan F01–F06 dipertahankan. ZIP lama tetap historis dan tidak diperbarui; build baru wajib dipasangkan dengan source bila kelak rilis disetujui.

## Validasi dan batas

Hasil terbaru: PHPUnit164tests/1126assertions,163passed dan1skip symlinkWindows. Guard canonical/production dan prompt hidden juga diuji targeted7tests/29assertions setelah pengetatan terakhir. TypeScript noEmit, splash Node7cases, commerce Nodeasserts lulus. PHP syntax12file/Pint12file/diffcheck PASS; build3456modules/15,67detik PASS. HTTP index.php/.env404, private-storage403 dan Hostproduksi404 (koneksi tetaplocalhost) terverifikasi.

PHP runtime lokal8.4.23, Laravel12. Tidak ada MySQL runtime/staging, provider CAPTCHA asli, SMTP, load test, atau verifikasi visual browser. Website produksi tetap tidak diubah dan aktivasi cerita produksi NO-GO sampai setup/kebijakan/moderasi/QA terpisah siap. Akun/password pemilik adalah satu langkah manual yang sengaja belum dilakukan atas pilihan pengguna.

## Update kartu/modal — 26 Oktober 2026

Feed kini memakai preview lima baris, tanpa memotong data. Klik Baca selengkapnya untuk membaca body lengkap di modal Headless UI. Card dan modal berbagi state/handlers dukungan/laporan; open/close tidak memanggil API. Dataset dan aturan publikasi tidak berubah, dan tidak ada setup database yang perlu diulang.

Setelah hardrefresh di http://127.0.0.1:8766/kuicip/cerita, periksa375×812,768×1024 dan1440×1000: campuran cerita pendek/panjang, dua cerita panjang berturut, ellipsis lima baris, action sejajar dan tidak ada overflow horizontal. Buka beberapa kartu bergantian; pastikan code/body yang tampil benar, paragraf utuh, isi panjang dapat di-scroll, serta header/close/actions tetap terjangkau. Tab/ShiftTab harus terperangkap di dialog, Escape/backdrop/X menutup, focus kembali ke CTA, dan halaman belakang tidak bergulir. Emulasikan reduced-motion dan periksa Console. Tekan Dukung/laporkan dalam modal, tutup dan buka lagi: counter/feedback harus sama dengan card. Network tidak boleh menambah request feed/detail/react hanya karena open/close. Filter dan load-more tetap bekerja setelah modal ditutup.

Frontend Node5tests dan StoryPlatform23tests lulus; hasil frontend mencakup server markup/controlled props, bukan layout/focus DOM. Visual browser dan screenshot **NOT RUN**, karena tidak ada browser terhubung.



## Upgrade verifikasi lokal — 26 Oktober 2026

Profil localhost sekarang memilih stories.antispam_provider=local di memory. CAPTCHA halaman menggunakan soal penjumlahan nyata yang diverifikasi Laravel, bukan token fake Turnstile. Banner tetap menjelaskan data fiksi/kebijakan belum disahkan. Konfigurasi provider di .env produksi tidak disentuh.

Gunakan server yang sudah ada di http://127.0.0.1:8766/kuicip/cerita; hard refresh sesudah build. Bila belum berjalan: rtk proxy C:\Users\chris\.config\herd\bin\php84\php.exe -S 127.0.0.1:8766 -t public scripts/local-preview-router.php . Tidak setup/reset/reseed database.

Soal dimuat hanya pada form yang enabled di profil lokal. Isi cerita fiksi, consent dan jawaban, tunggu minimal2detik setelah soal terbit; jawaban/token berlaku5menit. Minta soal baru untuk renewal. Cerita/consent tidak dihapus saat verifikasi gagal. Untuk memeriksa gate A tanpa mengubah profil persisten gunakan StorySoftLaunchTest/StoryAntispamTest yang mengubah config hanya pada aplikasi testing terisolasi.

Tests menguji SQL/cache/session dengan fixture/temp storage; tidak menulis atau menyalin database preview yang sudah ada. Jika memilih jalur Turnstile dalam harness preview terisolasi, fake verifier lama tetap khusus local/testing, bukan bypass production.
