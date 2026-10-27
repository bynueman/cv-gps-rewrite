# GPS FOOD — rilis gabungan Smart Bilingual CMS V3 + pensiun penghapusan mandiri

Pekerjaan lokal dimulai dari main yang bersih pada c9779ed2694a54844b88fe86ddaf0e4325fa3b27. Commit V3 itu sudah di-push, tetapi **belum dideploy** menurut pemilik. Baseline produksi yang terakhir dikonfirmasi tetap 1cbfa8b39cdda83d9bd7b3df8b191dbd998a74de. Satu deployment manual baru harus mencakup kedua perubahan.

Agen tidak mengakses cPanel/produksi, tidak mengubah .env/DB live dan tidak menjalankan repair APPLY. Artifact SHA, daftar file, ZIP/inventory dan runbook dengan nilai literal dibuat setelah push di storage/app/private/releases/.

## Perubahan aplikasi

- StoryRemovalPanel dan seluruh input/handler/state token penghapusan publik dihapus; tidak ada panel kosong pengganti.
- Receipt menampilkan code, copy code, pending review dan bantuan melalui email site.company.email dari setting email_primary/config company.email. Tidak membuat alamat baru.
- StoryReceiptData tidak lagi memiliki deletion_token. Bahkan receipt lama yang memuat field ekstra tidak menampilkannya.
- StorySubmission tidak membuat random removal token dan menyimpan deletion_token_hash=NULL untuk record baru.
- Respons sukses pertama maupun retry sama: code dan accepted=true; tanpa deletion_token.
- POST /kuicip/cerita/removal (stories.removal) tetap terdaftar sebagai endpoint pensiun410, no-store/noindex, CSRF dan rate limit5/menit. Controller tidak membaca payload, tidak membuka database stories, dan tidak membedakan kode/token valid, salah, kosong atau record tidak ada.
- CAPTCHA challenge token, CSRF dan token keamanan lain tetap bekerja; hanya fitur **deletion token** yang dihentikan.
- Tidak ada migration stories. Kolom nullable yang ada tetap dipertahankan; tidak ada pembersihan hash lama atau mass update consent/version/body.
- Retry identik memeriksa HMAC menggunakan versi policy record aslinya. Receipt historis tidak menjadi persetujuan baru atau menulis ulang record.
- Form mengirim policy_version yang ditampilkan; versi hilang/tidak cocok ditolak422 agar form lama tidak menyetujui panduan baru secara diam-diam.

Contoh respons baru:

~~~json
{"code":"KC-0001","accepted":true}
~~~

## Penanganan privasi administratif

Kode cerita hanya referensi; bukan autentikasi atau bukti kepemilikan. Tidak ada verifikasi kepemilikan otomatis yang diklaim atau diimplementasikan.

Pengguna menghubungi kontak resmi dengan referensi kode. Tim menilai kewenangan dan konteks secara privat dengan data minimum. Jangan meminta token lama/isi cerita/data sensitif di ruang publik atau menyalinnya ke audit CMS. Pemegang token lama tetap dapat mengajukan permintaan; token tidak lagi menjalankan penghapusan mandiri. Bila meragukan, eskalasi ke pemilik; hide sementara tetap tersedia.

Workflow minimal yang ditambahkan:
1. Hanya superadmin dapat POST admin/stories/{id}/redact; role policy, middleware stories terpisah, CSRF dan throttle tetap berlaku.
2. Operator mengisi referensi kasus PR-..., konfirmasi kode target, dan attestation bahwa permintaan/kewenangan telah ditinjau melalui proses privat.
3. Dialog ConfirmDialog yang sudah dipakai CMS menampilkan kode dan dampak permanen sebelum POST.
4. Dalam satu transaksi stories DB: reason teks lama pada audit dan details laporan terkait dikosongkan; event/report ID, status, actor/action/time tetap dipertahankan. Satu audit privacy_redacted berisi referensi kasus aman.
5. Body menjadi [removed], status hidden, published_at NULL dan soft-delete. Tidak hard-delete record/relasi. Consent, consented_at, policy_version dan hash legacy tidak diubah.
6. Tombstone tetap dapat dicari/dibuka superadmin untuk audit, tanpa aksi publikasi/moderasi ulang. Report handling pada cerita aktif tidak berubah; laporan pada tombstone ditampilkan read-only.
7. Duplikasi POST yang sudah berhasil tidak menambah audit atau mengulang redaksi. Kegagalan audit membatalkan seluruh transaksi, termasuk pembersihan teks terkait.

Fitur ini hanya dijalankan pada record yang benar-benar dipilih dan dikonfirmasi operator kelak. Tidak ada redaksi terhadap data pemilik/produksi selama pengembangan atau deployment otomatis.

## Panduan ID/EN baru — disiapkan, belum dinyatakan disahkan

Versi yang dipersiapkan: **kuicip-v3-support-removal-2026-10-27**. Pada production, publicEnabled mensyaratkan flag enabled/approved yang sudah ada dan versi konfigurasi yang persis cocok. Versi lama atau whitespace tidak lolos. Nonproduction preview tetap berlabel fiksi/bukan persetujuan bisnis.

Teks bantuan Indonesia:
> Untuk mengajukan penghapusan cerita, hubungi tim GPS FOOD melalui kontak resmi dan sertakan kode cerita sebagai referensi. Tim akan meninjau permintaan untuk melindungi privasi dan mencegah penyalahgunaan.

> Kode cerita adalah referensi, bukan bukti kepemilikan. Permintaan tidak dipenuhi secara otomatis.

English:
> To request story removal, contact the GPS FOOD team through the official contact channel and include the story code as a reference. The team will review the request to protect privacy and prevent misuse.

> A story code is a reference, not proof of ownership. Requests are not fulfilled automatically.

Receipt menjelaskan cerita diterima dan menunggu review, menyimpan kode, dan tidak langsung tampil publik. Penjelasan public/private, publikasi versus social reuse, anonymous/technical metadata, no auto-translation, retensi dan noindex tetap ada.

**Persetujuan pemilik atas teks/prosedur/versi ini masih gate operasional.** Bukan otomatis disetujui hanya karena keputusan produk atau commit/push diotorisasi. Runbook menyediakan langkah manual pemilik untuk mengganti hanya STORIES_POLICY_VERSION setelah approval, mempertahankan flag true/true/local dan konfigurasi lainnya. Tidak ada edit .env otomatis. Historical policy_version dan consent tetap asli.

## Regresi yang dipertahankan

Submission anonim/public/private, pending moderation, consent terpisah, CAPTCHA local (signed challenge/expiry), honeypot, rate limit/cooldown/quota, duplicate detection, idempotensi, story-code search/copy, feed approved, preview lima baris, modal penuh, react/report, audit dan retensi manual tetap memakai implementasi yang ada. Tidak ada perubahan logo, font, homepage teaser/navbar, Shopee/WhatsApp atau desain perusahaan.

UGC tidak dikirim ke translation service. Implementasi V3 (queue database/translations, hash/revisi, manual override, stable keys, safe fallback, articles/settings dan repair dry-run) dipertahankan. Sumber TranslationService/workflow/job/migrasi V3 tidak diubah pada task ini. Uji ReleaseReadiness lama diperbarui agar menguji teks/urutan ID/EN tetap sama serta key V3 unik, bukan menuntut JSON legacy tanpa key secara byte-identik.

## Deployment gabungan dan gate

Gunakan hanya template [runbook gabungan](GPSFOOD_FINAL_V3_STORY_REMOVAL_RUNBOOK.template.md) dan salinan final literal di releases; jangan memakai ZIP c9779ed lama.

- Server /home/gpsfoodi/gsp-laravel; main DB gpsfoodi_gsp; stories DB gpsfoodi_kuicipstories.
- Backup terverifikasi kedua DB, .env, source/build, uploads dan .htaccess di lokasi privat.
- Maintenance, snapshot HMAC record historis tanpa mencetak isi, git pull --ff-only ke SHA final, targeted migration website V3, install ZIP baru build-only, rebuild config/route/view tanpa menghapus cache anti-spam.
- **Satu migration:** database/migrations/2026_10_26_110000_create_content_translations_table.php, koneksi website mysql yang diverifikasi. Membuat content_translations dan nullable settings.value_en. Belum dijalankan di produksi. Tidak ada migration stories atau blanket migrate.
- Pemilik menyetujui panduan baru sebelum memperbarui policy version secara privat/manual dan membuka form. Fingerprint history membuktikan row lama tetap sama, versi/consent/hash tidak dialihkan.
- Uji draft nonsensitif untuk worker database queue translations, timeout40/tries1, retry_after minimal90; worker bounded50detik/5job/128MB, GNU timeout55+5 dan flock yang path/kapabilitasnya diverifikasi operator.
- Cron tidak diaktifkan sampai uji provider+persistence hosting berhasil. Tidak ada daemon atau cron repair/backfill.
- Uji cerita sintetis privat melalui browser dengan CAPTCHA, receipt tanpa token, old endpoint410, admin superadmin redaction dan editor forbidden. Jangan memakai cerita historis nyata.
- Verifikasi HMAC record historis setelah smoke; main pages/ID/EN/CTA/noindex/feed/private isolation.
- Recovery memakai source/build sebelumnya tanpa rollback DB. Karena kode lama mempunyai endpoint token, **tutup API cerita dengan policies_approved=false sebelum memakai kode lama**, pertahankan enabled=true/admin/contact, jangan membuka kembali penghapusan mandiri. Redaksi yang sudah sah tidak dipulihkan otomatis.

## Validasi dan batas

Hasil validasi akhir lokal: **141 tes Laravel / 1.658 assertion**, exit 0 (suite akhir lengkap, termasuk CAPTCHA lintas-versi). **26 tes frontend**, TypeScript noEmit, Pint, syntax 11 file PHP, parser target PHP 8.2, dan build lulus. Warning PHPUnit hanya .env yang tidak ada pada direktori tes terisolasi; warning SSR animasi dan dua font absolut tetap seperti sebelumnya.

Smoke HTTP loopback: homepage200, cerita200/noindex, POST removal dengan CSRF valid410, kedua hash database preview identik sebelum/sesudah. Tidak ada submission atau redaksi pada preview milik pemilik. Browser connector mengembalikan apps/browsers kosong: **VISUAL BROWSER QA NOT VERIFIED**.

Redaksi hanya membersihkan record aktif dan salinan teks pada tabel terkait. Cadangan atau salinan yang sudah diterima pihak lain tidak dihapus otomatis; operator harus mengikuti akses/retensi dan proses privasi yang disetujui, tanpa menjanjikan pemenuhan instan.

GO untuk penyerahan rilis lokal setelah commit/push/ZIP terverifikasi. **NO-GO pembukaan produksi sebelum approval teks/versi baru, backup, schema, worker/cron hosting dan pemeriksaan visual/operasional pemilik lulus.** Persiapan artefak bukan bukti deployment. Semua Laravel tests memakai database in-memory/owned temporary dan fake provider; tidak ada tes ke database live. PHP lokal8.4 dan parser target8.2 tidak sama dengan verifikasi runtime hosting. DDL MySQL pretend bukan uji MySQL live.

VISUAL BROWSER QA NOT VERIFIED bila konektor tetap tidak menyediakan browser. Runtime MySQL/PHP hosting, backup, approval kebijakan baru, PHP CLI/lock/timeout dan Cron harus diverifikasi operator. Dua warning font absolut yang sudah ada perlu pemeriksaan visual pemilik. Tidak mengklaim deployment produksi selesai.

## Berkas

Daftar perubahan task cerita dan daftar gabungan sejak baseline produksi dicatat dalam metadata/file-list artefak final dari Git. Tidak ada .env, SQLite, dump/log/session/cache, credential, ZIP, vendor atau node_modules di commit.

| Status | Berkas task ini | Kelompok |
|---|---|---|
| Diubah | [app/Http/Controllers/Admin/StoryController.php](../app/Http/Controllers/Admin/StoryController.php) | Backend |
| Diubah | [app/Http/Controllers/StoryController.php](../app/Http/Controllers/StoryController.php) | Backend |
| Diubah | [app/Http/Requests/StoreStoryRequest.php](../app/Http/Requests/StoreStoryRequest.php) | Backend |
| Diubah | [app/Policies/StoryPolicy.php](../app/Policies/StoryPolicy.php) | Backend |
| Diubah | [app/Services/StorySubmission.php](../app/Services/StorySubmission.php) | Backend |
| Diubah | [app/Support/StoryAvailability.php](../app/Support/StoryAvailability.php) | Backend |
| Diubah | [docs/GPSFOOD_LOCAL_ANTISPAM_REVIEW.md](../docs/GPSFOOD_LOCAL_ANTISPAM_REVIEW.md) | Docs |
| Diubah | [docs/GPSFOOD_LOCAL_PREVIEW.md](../docs/GPSFOOD_LOCAL_PREVIEW.md) | Docs |
| Diubah | [docs/GPSFOOD_PROJECT_CONTEXT.md](../docs/GPSFOOD_PROJECT_CONTEXT.md) | Docs |
| Diubah | [docs/GPSFOOD_RUANG_CERITA_V2_RELEASE.md](../docs/GPSFOOD_RUANG_CERITA_V2_RELEASE.md) | Docs |
| Diubah | [docs/GPSFOOD_STORIES_SOFT_LAUNCH.md](../docs/GPSFOOD_STORIES_SOFT_LAUNCH.md) | Docs |
| Diubah | [docs/GPSFOOD_V2_ACCEPTANCE.md](../docs/GPSFOOD_V2_ACCEPTANCE.md) | Docs |
| Diubah | [resources/js/Components/StoryReceipt.tsx](../resources/js/Components/StoryReceipt.tsx) | Frontend |
| Dihapus | [resources/js/Components/StoryRemovalPanel.tsx](../resources/js/Components/StoryRemovalPanel.tsx) | Frontend |
| Diubah | [resources/js/Pages/Admin/Stories/Index.tsx](../resources/js/Pages/Admin/Stories/Index.tsx) | Frontend |
| Diubah | [resources/js/Pages/Admin/Stories/Show.tsx](../resources/js/Pages/Admin/Stories/Show.tsx) | Frontend |
| Diubah | [resources/js/Pages/Kuicip/Cerita.tsx](../resources/js/Pages/Kuicip/Cerita.tsx) | Frontend |
| Diubah | [routes/web.php](../routes/web.php) | Backend |
| Diubah | [tests/Feature/ReleaseReadinessTest.php](../tests/Feature/ReleaseReadinessTest.php) | Tests |
| Diubah | [tests/Feature/StoryAntispamTest.php](../tests/Feature/StoryAntispamTest.php) | Tests |
| Diubah | [tests/Feature/StoryPlatformTest.php](../tests/Feature/StoryPlatformTest.php) | Tests |
| Diubah | [tests/Feature/StorySoftLaunchTest.php](../tests/Feature/StorySoftLaunchTest.php) | Tests |
| Diubah | [tests/frontend/story-cards.test.mjs](../tests/frontend/story-cards.test.mjs) | Tests |
| Diubah | [tests/frontend/story-soft-launch.test.mjs](../tests/frontend/story-soft-launch.test.mjs) | Tests |
| Baru | [docs/GPSFOOD_FINAL_V3_STORY_REMOVAL_RELEASE.md](../docs/GPSFOOD_FINAL_V3_STORY_REMOVAL_RELEASE.md) | Docs |
| Baru | [docs/GPSFOOD_FINAL_V3_STORY_REMOVAL_RUNBOOK.template.md](../docs/GPSFOOD_FINAL_V3_STORY_REMOVAL_RUNBOOK.template.md) | Docs |
| Baru | [resources/js/Components/StoryRemovalHelp.tsx](../resources/js/Components/StoryRemovalHelp.tsx) | Frontend |
