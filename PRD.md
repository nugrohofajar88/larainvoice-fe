# PRD: Pioneer CNC Frontend

## 1. Ringkasan Produk

Dokumen ini mendefinisikan kebutuhan produk untuk `pioneer-cnc`, yaitu aplikasi frontend operasional Pioneer CNC yang digunakan oleh user internal untuk mengakses dashboard, master data, transaksi, produksi, pembayaran, dan laporan.

Frontend ini dibangun dengan Laravel dan Blade, lalu terhubung ke backend API `pioneer-cnc-be` sebagai sumber data utama.

Peran utama aplikasi ini adalah:
- menyediakan antarmuka kerja harian untuk user internal
- menerjemahkan proses bisnis menjadi halaman dan alur operasional
- menjaga pengalaman penggunaan tetap cepat, jelas, dan sesuai hak akses user
- menjadi lapisan presentasi yang aman di atas backend API

## 2. Latar Belakang

Operasional Pioneer CNC melibatkan banyak aktivitas lintas modul:

- pengelolaan master data cabang, customer, user, sales, mesin, material, dan komponen
- transaksi invoice dan machine order
- pencatatan pembayaran
- tindak lanjut produksi dan upload file pekerjaan
- pemantauan laporan operasional

Tanpa frontend terpusat, user akan kesulitan menjalankan workflow harian secara konsisten. Diperlukan aplikasi yang:

- mudah dipahami user operasional
- mengikuti pembatasan role dan permission
- menampilkan data backend dengan format yang ramah pakai
- mendukung alur kerja cabang dan pusat

## 3. Tujuan

- Menyediakan antarmuka operasional terpadu untuk Pioneer CNC.
- Memudahkan user internal menjalankan proses bisnis harian.
- Menampilkan data backend secara jelas dan konsisten.
- Menyesuaikan tampilan dan navigasi dengan role dan permission user.
- Mengurangi kesalahan input melalui form, filter, validasi, dan struktur halaman yang jelas.
- Menjadi frontend utama yang stabil untuk sistem internal Pioneer CNC.

## 4. Non-Goals

- Tidak menggantikan backend sebagai sumber logika bisnis utama.
- Tidak mencakup aplikasi mobile native.
- Tidak mencakup portal customer eksternal.
- Tidak mencakup payment gateway online.
- Tidak mencakup dashboard BI eksternal.

## 5. Aktor

- `Super Admin`
  Mengakses seluruh modul dan dapat bekerja lintas cabang.

- `Admin Cabang`
  Mengelola data dan transaksi operasional cabangnya.

- `Sales`
  Melihat dan mengelola data yang berkaitan dengan customer dan transaksi sesuai akses.

- `Tim Produksi`
  Mengelola daftar pekerjaan, status produksi, dan file pendukung.

- `Manajemen`
  Mengakses dashboard dan laporan yang tersedia sesuai role.

## 6. Problem Statement

Tanpa frontend yang terstruktur, user operasional berisiko mengalami:

- navigasi yang membingungkan antar modul
- data backend yang sulit dipahami dalam bentuk mentah
- workflow transaksi dan produksi yang tidak konsisten
- hak akses menu yang tidak jelas
- kesulitan memantau pembayaran dan laporan
- terlalu banyak langkah manual untuk pekerjaan yang seharusnya rutin

## 7. Scope Produk

### In Scope

- halaman login dan logout
- dashboard utama
- master data
- transaksi invoice
- transaksi machine order
- pembayaran invoice
- produksi dan list penjualan
- laporan operasional
- export laporan
- tampilan sesuai role dan permission
- integrasi frontend ke backend API

### Out of Scope

- aplikasi customer self-service
- integrasi marketplace atau e-commerce
- notifikasi push real-time tingkat lanjut
- offline mode
- pengelolaan multi-brand di luar Pioneer CNC

## 8. Prinsip Produk

- `Operational first`
  Tampilan harus membantu pekerjaan harian, bukan sekadar terlihat bagus.

- `Role-aware`
  User hanya melihat menu dan aksi yang relevan dengan hak aksesnya.

- `Simple flow`
  Alur halaman harus mudah dipahami oleh user non-teknis.

- `Backend-driven`
  Frontend mengikuti aturan bisnis dari backend dan tidak menduplikasi logika inti secara berlebihan.

- `Consistent UI`
  Tabel, filter, form, status, badge, dan aksi harus terasa konsisten lintas modul.

## 9. Arsitektur Konseptual Frontend

Frontend ini menggunakan pola server-rendered Laravel:

1. User mengakses halaman frontend.
2. Controller Laravel frontend membaca session/token user.
3. Controller frontend memanggil backend API.
4. Data backend diolah di sisi server.
5. Halaman dirender dengan Blade.

Catatan:
- aplikasi ini **tidak bergantung pada Axios sebagai mekanisme utama pengambilan data**
- integrasi utama ke backend dilakukan di controller Laravel menggunakan HTTP client
- beberapa interaksi UI tertentu memakai JavaScript ringan dan `fetch()`

## 10. Modul Utama

### 10.1 Auth

Fungsi utama:
- login
- logout
- pembentukan session frontend
- penyimpanan token API hasil autentikasi

Acceptance:
- user valid dapat login dan diarahkan ke dashboard
- user invalid menerima pesan error yang jelas
- logout mengakhiri session frontend

### 10.2 Dashboard

Fungsi utama:
- menampilkan ringkasan bisnis utama
- menampilkan statistik dan status operasional ringkas

Acceptance:
- dashboard tampil cepat
- data mengikuti hak akses cabang user
- data yang tampil cukup untuk orientasi awal user setelah login

### 10.3 Master Data

Modul yang termasuk:
- cabang
- pengguna
- role
- pelanggan
- sales
- mesin
- plat
- plat size
- bahan plat
- machine type
- menu
- kategori komponen
- komponen
- tipe biaya
- harga cutting

Acceptance:
- user dapat membuka list data master yang diizinkan
- form dan tabel konsisten antar modul
- pencarian, filter, dan pagination tersedia jika dibutuhkan

### 10.4 Transaksi Invoice

Fungsi utama:
- melihat daftar invoice
- membuat invoice
- melihat detail invoice
- print invoice
- print SPK

Acceptance:
- form invoice dapat memuat master data yang dibutuhkan
- detail invoice mudah dibaca
- status transaksi dan pembayaran mudah dikenali

### 10.5 Machine Order

Fungsi utama:
- membuat dan mengelola machine order
- menampilkan komponen, biaya, dan payment terkait order

Acceptance:
- user dapat melihat dan membuat machine order sesuai akses
- tampilan machine order mendukung alur operasional nyata

### 10.6 Pembayaran

Fungsi utama:
- memilih invoice untuk dibayar
- menginput data pembayaran
- upload bukti pembayaran
- download file bukti pembayaran

Acceptance:
- user dapat membuat pembayaran dari halaman yang sesuai
- file bukti bayar dapat diunduh ulang
- form pembayaran sederhana dan jelas

### 10.7 Produksi dan List Penjualan

Fungsi utama:
- melihat daftar pekerjaan
- membuka detail pekerjaan
- update status produksi
- upload file produksi
- download file terkait produksi

Acceptance:
- tim produksi dapat bekerja dari satu modul yang jelas
- status produksi mudah dilacak
- file kerja dapat dikelola dari UI

### 10.8 Laporan

Modul laporan yang tersedia:
- ranking pelanggan
- KPI sales
- rekap invoice
- rekap pembayaran
- rekap penjualan plat
- rekap penjualan jasa cutting
- piutang
- stok

Acceptance:
- user dapat memfilter laporan sesuai kebutuhan
- laporan dapat diexport
- tampilan tabel dan filter konsisten

## 11. User Flow Tingkat Tinggi

### 11.1 Login

1. User membuka halaman login.
2. User memasukkan kredensial.
3. Frontend mengirim request ke backend auth.
4. Jika sukses, frontend menyimpan session/token.
5. User diarahkan ke dashboard.

### 11.2 Navigasi Berdasarkan Permission

1. User berhasil login.
2. Frontend membaca permission dari session atau hasil backend.
3. Menu yang tidak diizinkan tidak ditampilkan.
4. Jika user memaksa akses URL tanpa permission, sistem menolak akses.

### 11.3 Akses Halaman Data

1. User membuka halaman modul.
2. Controller frontend memanggil backend API.
3. Data diterima dan dipetakan untuk kebutuhan view.
4. Blade merender list, form, atau detail.

### 11.4 Submit Form

1. User mengisi form.
2. Frontend mengirim data ke controller frontend.
3. Controller frontend meneruskan request ke backend API.
4. Backend memvalidasi dan memproses data.
5. Frontend menampilkan hasil sukses atau error.

## 12. Functional Requirements

### 12.1 Login dan Session

- Sistem harus menyediakan halaman login.
- Sistem harus menyimpan session user setelah login berhasil.
- Sistem harus mendukung logout dari frontend.

### 12.2 Navigasi dan Menu

- Sistem harus menampilkan menu berdasarkan permission user.
- Sistem harus menjaga active state menu sesuai halaman.
- Sistem harus menyediakan struktur navigasi yang konsisten.

### 12.3 List dan Tabel

- Sistem harus menampilkan tabel data untuk modul utama.
- Sistem harus mendukung pagination pada list yang panjang.
- Sistem harus menampilkan empty state jika data kosong.
- Sistem harus mendukung filter pada modul yang relevan.

### 12.4 Form

- Sistem harus menyediakan form create/view/update pada modul yang relevan.
- Sistem harus menampilkan pesan error validasi dengan jelas.
- Sistem harus menjaga input user saat submit gagal bila memungkinkan.

### 12.5 Detail View

- Sistem harus menyediakan halaman detail untuk entitas penting seperti invoice, machine order, dan produksi.
- Sistem harus menampilkan informasi ringkas dan mudah dipindai.

### 12.6 Download dan Export

- Sistem harus mendukung download file dari backend untuk file mesin, file produksi, file pembayaran, dan dokumen terkait.
- Sistem harus menyediakan export pada halaman laporan yang mendukungnya.

### 12.7 Integrasi API

- Controller frontend harus dapat memanggil backend API menggunakan token user.
- Frontend harus menangani error backend dengan pesan yang cukup jelas untuk user.
- Frontend harus membatasi data berdasarkan hasil permission dan role dari backend.

## 13. Business Rules Frontend

### 13.1 Frontend Bukan Sumber Kebenaran

- Semua logika bisnis inti tetap berasal dari backend.
- Frontend hanya menampilkan, memvalidasi ringan, dan meneruskan request.

### 13.2 Permission

- Menu hanya ditampilkan jika permission tersedia.
- Route halaman tetap harus dilindungi middleware permission meskipun menu sudah disembunyikan.

### 13.3 Cabang

- Beberapa halaman harus otomatis mengikuti cabang user non super admin.
- Pilihan cabang hanya muncul pada alur yang diizinkan untuk super admin.

### 13.4 File

- Download file harus dilakukan melalui endpoint frontend yang meneruskan request ke backend.
- User tidak boleh mengakses file tanpa otorisasi yang sesuai.

## 14. Non-Functional Requirements

- Halaman harus cukup cepat untuk kebutuhan operasional harian.
- UI harus dapat digunakan di desktop dan tetap layak di layar yang lebih kecil.
- Tampilan harus konsisten lintas modul.
- Session user harus stabil untuk penggunaan sehari-hari.
- Error yang umum harus bisa dipahami user non-teknis.

## 15. Acceptance Criteria

### AC-01 Login

- User valid dapat login dan masuk ke dashboard.
- User invalid menerima pesan error yang jelas.

### AC-02 Permission Menu

- User hanya melihat menu yang sesuai hak akses.
- User tanpa permission tidak dapat membuka halaman terkait.

### AC-03 Master Data

- User dapat membuka list master data sesuai akses.
- Data list tampil dengan pagination atau filter yang sesuai.

### AC-04 Invoice

- User dapat membuat dan melihat invoice dari frontend.
- Print invoice dan SPK dapat diakses dari alur yang sesuai.

### AC-05 Pembayaran

- User dapat mencatat pembayaran dari halaman frontend.
- Bukti pembayaran dapat diunduh dari frontend.

### AC-06 Produksi

- User dapat melihat daftar kerja produksi.
- Status produksi dan file pendukung dapat dikelola dari halaman terkait.

### AC-07 Laporan

- User dapat membuka laporan yang diizinkan.
- Export laporan berjalan dari frontend ke endpoint export yang sesuai.

## 16. Risiko dan Edge Cases

- backend API tidak aktif sehingga halaman gagal dimuat
- token session tidak sinkron dengan backend
- permission backend berubah tetapi session frontend belum refresh
- file ada di backend tetapi path atau endpoint download tidak konsisten
- beberapa halaman memakai `fetch()` ringan, sementara mayoritas alur memakai controller server-side, sehingga pola pengembangan bisa menjadi tidak seragam bila tidak dijaga

## 17. Open Questions

- Apakah frontend ini akan tetap dominan server-rendered, atau sebagian modul akan dipindahkan ke SPA-like interaction?
- Apakah dependency `axios` masih perlu dipertahankan jika praktik utama sudah memakai controller Laravel dan `fetch()`?
- Apakah semua laporan perlu tampilan filter yang lebih seragam antar modul?
- Apakah dibutuhkan komponen UI bersama yang lebih formal untuk tabel, filter, dan form?

## 18. Rekomendasi Implementasi

- Pertahankan backend sebagai sumber logika bisnis utama.
- Konsolidasikan pola integrasi data agar tetap konsisten.
- Gunakan komponen Blade atau partial bersama untuk elemen UI yang sering berulang.
- Pastikan pesan error dari backend diterjemahkan menjadi feedback yang mudah dipahami user.
- Audit dependency frontend secara berkala, termasuk `axios`, agar tidak ada package yang tersisa tanpa penggunaan nyata.

## 19. Lampiran Ringkas

File penting:

- `routes/web.php`
- `app/Http/Controllers`
- `resources/views`
- `config/services.php`
- `README.md`

Command penting:

```powershell
composer install
php artisan key:generate
npm install
composer run dev
npm run build
php artisan optimize:clear
```
