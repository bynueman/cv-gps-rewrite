# Dokumentasi Proyek CV Gama Putra Santosa

Dokumen ini berisi informasi menyeluruh mengenai arsitektur sistem, teknologi yang digunakan (tech stack), panduan pengembangan lokal, serta langkah-langkah detail untuk melakukan *deployment* dan *update* ke server cPanel (Shared Hosting).

---

## 1. Teknologi (Tech Stack)

Sistem ini dibangun menggunakan arsitektur monolitik modern dengan pemisahan frontend dan backend menggunakan **Inertia.js**.

- **Backend / Framework Core**: [Laravel 11.x](https://laravel.com) (PHP 8.2)
- **Frontend Framework**: [React 18](https://react.dev) dengan **TypeScript**
- **Penghubung (Bridge)**: [Inertia.js](https://inertiajs.com) (Menggantikan REST API/GraphQL dengan routing sisi server yang mengembalikan komponen React)
- **Styling**: [Tailwind CSS 3.x](https://tailwindcss.com) (Vanilla CSS)
- **Bundler**: [Vite](https://vitejs.dev)
- **Database**: MySQL 8.x / MariaDB
- **Pemrosesan Gambar**: [Intervention Image](https://image.intervention.io/) (Untuk otomatisasi konversi ke format `.webp` dan resize)
- **Ikon**: [Lucide React](https://lucide.dev/)

---

## 2. Arsitektur Sistem

- **Tidak Ada Node.js di Production**: Karena aplikasi di-host di *Shared Hosting cPanel*, server tidak memiliki environment Node.js yang persisten. Oleh karena itu, *Server-Side Rendering (SSR)* dinonaktifkan.
- **Pre-built Frontend**: Semua aset frontend (React, CSS, JS) di-*build* (dikompilasi) secara lokal di komputer developer sebelum di-upload ke server.
- **Routing**: Semua rute dikelola oleh Laravel (`routes/web.php`). Inertia kemudian memuat komponen React yang sesuai (`resources/js/Pages/`).
- **Keamanan Admin**: Halaman admin dilengkapi dengan proteksi *rate-limiting* (5 percobaan login / 3 menit) dan middleware `EnsureUserIsSuperadmin` untuk membatasi akses konfigurasi. Semua endpoint admin juga menggunakan header `X-Robots-Tag: noindex, nofollow` agar tidak diindeks oleh Google.

---

## 3. Struktur Direktori Penting

- `app/` - Logic backend Laravel (Controllers, Models, Middleware).
- `routes/web.php` - Definisi semua URL website.
- `database/migrations/` - Struktur tabel database.
- `resources/js/` - Semua kode frontend React & TypeScript.
  - `Pages/` - Komponen halaman utama (Home, Admin, dll).
  - `Components/` - Komponen UI yang bisa digunakan ulang (Card, Button).
  - `Layouts/` - Layout pembungkus halaman (SiteLayout, AdminLayout).
- `public/build/` - Folder hasil kompilasi Vite (JS/CSS) yang siap di-deploy.
- `public/uploads/` - Tempat penyimpanan file gambar dan PDF yang di-upload melalui panel admin.

---

## 4. Panduan Deployment & Update ke cPanel

Karena server menggunakan **Shared Hosting cPanel**, langkah update harus dilakukan dengan teliti. 
**PENTING**: Jangan pernah menjalankan perintah `npm` (seperti `npm run dev` atau `npm install`) di dalam Terminal cPanel.

### A. Persiapan Update (Di Komputer Lokal)

Setiap kali Anda selesai mengubah kode (terutama mengubah file UI di `resources/js/`):

1. Buka terminal di PC Anda (folder project).
2. Jalankan perintah build untuk mengkompilasi file React:
   ```bash
   npm run build
   ```
3. Commit dan push perubahan ke GitHub:
   ```bash
   git add -A
   git commit -m "Deskripsi perubahan yang dilakukan"
   git push origin main
   ```
4. **Siapkan File Build**: 
   Buka folder `public/` di komputer Anda, lalu jadikan folder `build` menjadi file ZIP (misalnya `build.zip`). File ini akan kita upload ke server.

### B. Proses Update (Di Server cPanel)

1. **Upload Frontend (File Manager)**
   - Buka **cPanel > File Manager**.
   - Masuk ke direktori public (misalnya `public_html` atau direktori public Laravel Anda).
   - Hapus folder `build` yang lama (agar file sampah tidak menumpuk).
   - Upload file `build.zip` yang dibuat di langkah A4.
   - Extract file `build.zip` tersebut sehingga folder `build` yang baru terbuat.
   - Hapus file `build.zip` dari server.

2. **Update Backend (Terminal cPanel)**
   - Buka **cPanel > Terminal**.
   - Masuk ke folder root aplikasi Laravel (bukan public_html, tapi folder utama aplikasi, misal `~/gsp-laravel`):
     ```bash
     cd ~/gsp-laravel
     ```
   - Tarik (pull) kode terbaru dari GitHub:
     ```bash
     git pull origin main
     ```
   - *Hanya jika ada perubahan/penambahan tabel database*, jalankan migration:
     ```bash
     php artisan migrate --force
     ```
   - **Bersihkan Cache Server** (SANGAT PENTING agar perubahan terlihat):
     ```bash
     php artisan optimize:clear
     php artisan config:cache
     php artisan route:cache
     php artisan view:cache
     ```

Selesai. Refresh browser Anda (tekan `Ctrl + F5` atau `Cmd + Shift + R`) untuk melihat hasilnya.

---

## 5. Catatan Tambahan (Backup)

Aplikasi ini tidak melakukan backup otomatis ke cloud. Disarankan mengatur **Cron Jobs** di cPanel untuk melakukan backup database secara berkala:

```bash
mysqldump -u [db_user] -p'[db_password]' [db_name] | gzip > ~/backups/db-backup-$(date +\%Y\%m\%d).sql.gz
```

Serta melakukan backup berkala pada folder `public/uploads/` yang berisi semua gambar/dokumen yang diunggah oleh admin.
