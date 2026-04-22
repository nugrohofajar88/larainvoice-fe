# Pioneer CNC

Frontend web application untuk operasional Pioneer CNC. Project ini dibangun dengan Laravel dan berfungsi sebagai layer UI yang berkomunikasi ke API backend `pioneer-cnc-be`.

Repository ini menangani:
- autentikasi dan session user
- master data
- transaksi invoice
- pembayaran
- produksi dan list penjualan
- laporan operasional

Backend API belum dibahas di README ini. Dokumentasi backend bisa dibuat terpisah di repository `pioneer-cnc-be`.

## Stack

- PHP 8.2+
- Laravel 12
- Livewire 4
- Vite
- Tailwind CSS

## Gambaran Singkat

Project `pioneer-cnc` bukan backend data utama. Aplikasi ini mengambil data dari API backend melalui konfigurasi `BACKEND_API_URL`.

Secara sederhana alurnya seperti ini:

1. User login dari aplikasi frontend.
2. Frontend menyimpan token/session hasil autentikasi.
3. Halaman-halaman frontend memanggil API backend untuk data master, transaksi, pembayaran, dan laporan.
4. Frontend merender data ke Blade views untuk kebutuhan operasional harian.

## Fitur Utama

- Dashboard
- Master data:
  cabang, user, pelanggan, sales, mesin, plat, komponen, kategori, tipe biaya
- Transaksi:
  invoice, machine order, pembayaran
- Produksi:
  list penjualan, upload file, update status
- Laporan:
  ranking pelanggan, KPI sales, rekap invoice, rekap pembayaran, rekap penjualan, piutang, stok
- Role dan permission berbasis menu

## Struktur Penting

- `app/Http/Controllers`
  controller halaman dan integrasi ke backend API
- `resources/views`
  Blade templates untuk seluruh halaman
- `routes/web.php`
  routing utama aplikasi
- `config/services.php`
  konfigurasi URL backend API

## Setup Lokal

1. Clone repository ini.
2. Install dependency PHP dan Node.js.
3. Copy `.env`.
4. Set URL backend API.
5. Jalankan aplikasi.

Contoh:

```powershell
composer install
copy .env.example .env
php artisan key:generate
npm install
```

Lalu ubah `.env`:

```env
APP_NAME="Pioneer CNC"
APP_URL=http://localhost:8000
BACKEND_API_URL=http://localhost:8001/api
```

## Menjalankan Project

Untuk development:

```powershell
composer run dev
```

Command ini akan menjalankan:
- Laravel development server
- queue listener
- log watcher
- Vite dev server

Kalau ingin manual:

```powershell
php artisan serve
npm run dev
```

## Build Asset

Untuk build production asset:

```powershell
npm run build
```

## Testing

Menjalankan test:

```powershell
composer run test
```

## Environment Penting

Beberapa konfigurasi yang paling sering dipakai:

```env
APP_URL=http://localhost:8000
BACKEND_API_URL=http://localhost:8001/api
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

## Integrasi Dengan Backend

Frontend ini mengandalkan backend API untuk hampir semua data utama. Pastikan repository `pioneer-cnc-be` sudah berjalan dan URL API-nya sesuai dengan `BACKEND_API_URL`.

Secara default, project ini membaca konfigurasi dari:

```php
config('services.pioneer.api_url')
```

Lihat file:
`config/services.php`

## Catatan Pengembangan

- Jika halaman gagal memuat data, cek lebih dulu apakah backend API aktif.
- Jika login berhasil tetapi menu tidak sesuai, cek data role dan permission dari backend.
- Jika asset tidak tampil benar, pastikan Vite berjalan atau hasil build sudah dibuat.
- Jika ada masalah session atau cache, coba jalankan:

```powershell
php artisan optimize:clear
```

## Scripts Yang Tersedia

Di `composer.json`:

- `composer run setup`
- `composer run dev`
- `composer run test`

Di `package.json`:

- `npm run dev`
- `npm run build`

## Status

Repository ini adalah frontend aplikasi internal Pioneer CNC dan aktif digunakan untuk kebutuhan operasional.
