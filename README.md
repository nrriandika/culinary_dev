# WebGIS Kuliner Nusantara

Platform informasi kuliner khas & destinasi wisata Indonesia (spesifikasi: folder `specs/`).
Stack: **Laravel 13 + Filament 5**, PostgreSQL, Leaflet (OpenStreetMap).

## Status tahap 1

- [x] Login, logout, reset password, halaman profil (Filament)
- [x] Hanya akun `is_active = true` yang bisa login / mengakses panel
- [x] Pencatatan `last_login_at`
- [x] Dashboard: ringkasan jumlah kuliner, wisata, wilayah + peta marker + konten terbaru
- [x] Skema awal: `provinces`, `regencies`, `categories`, `culinaries`, `destinations`
- [x] Tema light (aksen teal), sidebar menu di kiri
- [x] Modul CMS: Kuliner, Resep (bahan + langkah), Wisata, Kategori, Provinsi (+ kab/kota),
      Kabupaten/Kota, Media Manager, Pengaturan Website, Administrator (khusus Super Admin)
- [x] Search & filter di setiap tabel + pencarian global (Ctrl+K)
- [x] Publikasi konten: draft/published, aksi massal publikasikan / jadikan draft
- [x] Pemilih koordinat berbasis peta (klik/geser marker) pada form kuliner, wisata, wilayah
- [ ] Website pengunjung (Beranda, Peta Interaktif, Katalog, Detail, Pencarian, SEO)
- [ ] Impor data dari UGM

## Menjalankan

1. Isi kredensial DB di `.env`:
   ```
   DB_USERNAME=...
   DB_PASSWORD=...
   ```
2. Buat tabel — pilih salah satu:
   - `php artisan migrate --seed` (tabel + akun admin + data contoh), **atau**
   - jalankan SQL [database/sql/culinary_dev_schema.sql](database/sql/culinary_dev_schema.sql)
     di psql/pgAdmin, lalu `php artisan db:seed`.
3. Symlink folder upload foto (sekali saja): `php artisan storage:link`
4. Jalankan server:
   ```
   php artisan serve
   ```
5. Buka http://localhost:8000/admin — login default dari `.env`:
   `ADMIN_EMAIL=admin@kuliner.test`, `ADMIN_PASSWORD=password` (**ganti sebelum production**).

Tambah admin lain: `php artisan make:filament-user`.

## Performa (development)

- OPcache wajib aktif di `php.ini` (`zend_extension=opcache`, `opcache.enable=1`,
  `opcache.enable_cli=1`, `opcache.revalidate_freq=0`). Tanpa OPcache tiap request ±0,7–1 detik.
- Komponen & ikon Filament di-cache: `php artisan filament:optimize`.
  **Setelah menambah Resource/Page/Widget baru**, jalankan `php artisan filament:optimize-clear`
  (atau `filament:optimize` lagi), kalau tidak menu baru tidak muncul.
- Panel memakai mode SPA (`->spa()`): pindah menu tanpa reload halaman penuh.
- `php artisan serve` di Windows hanya 1 worker. Untuk server lokal yang lebih responsif
  pakai Laragon / Herd (nginx + PHP-FPM/CGI multi-proses).

## Test

```
php artisan test
```
Test memakai SQLite in-memory (lihat `phpunit.xml`), tidak menyentuh database PostgreSQL.

## Struktur penting

| Path | Isi |
|---|---|
| `app/Providers/Filament/AdminPanelProvider.php` | Konfigurasi panel admin & autentikasi |
| `app/Models/User.php` | `canAccessPanel()` — aturan siapa boleh masuk |
| `app/Filament/Pages/Dashboard.php` | Halaman dashboard |
| `app/Filament/Widgets/` | Statistik, peta, tabel konten terbaru |
| `database/seeders/` | Akun admin & data contoh |
