# Rencana Penambahan Fitur Order Jasa

Dokumen ini merangkum rencana awal penambahan fitur `Order Servis` dan `Order Pelatihan` pada sistem Pioneer CNC.

Fokus dokumen ini adalah menyamakan pemahaman bisnis, ruang lingkup, struktur data, alur kerja, dan tahapan implementasi sebelum development dimulai.

## Ringkasan Arah Fitur

Alih-alih membuat dua modul yang sepenuhnya terpisah, fitur ini disarankan dibangun dalam satu keluarga modul bernama `Order Jasa`.

Di dalamnya terdapat dua tipe order:

- `service`
- `training`

Keduanya memiliki pola bisnis yang mirip:

- order dibuat lebih dulu sebagai dokumen operasional
- nominal jasa belum harus ditentukan saat order dibuat
- harga final baru muncul saat invoice diterbitkan
- order dapat memiliki satu atau lebih pegawai yang ditugaskan
- order memiliki pelanggan, lokasi, jadwal, dan status operasional

Dengan pendekatan ini, sistem menjadi lebih konsisten, mudah dirawat, dan lebih mudah dikembangkan jika nanti ada jenis jasa lain.

## Tujuan

1. Menyediakan modul order operasional untuk jasa servis dan pelatihan.
2. Memisahkan proses penugasan kerja dari proses penagihan invoice.
3. Memungkinkan pencatatan detail pekerjaan walaupun harga belum final.
4. Mendukung penugasan lebih dari satu user atau pegawai dalam satu order.
5. Menyediakan dasar yang rapi untuk integrasi ke invoice, pembayaran, dan laporan.

## Latar Belakang Bisnis

Saat ini sistem sudah memiliki alur `invoice`, `machine order`, `pembayaran`, dan `produksi`. Namun ada kebutuhan baru untuk mencatat pekerjaan jasa yang sifatnya berbeda dari penjualan barang atau pembuatan mesin.

Karakteristik kebutuhan baru:

- `Order Servis` dibuat saat pelanggan meminta jasa servis
- `Order Pelatihan` dibuat saat pelanggan meminta pelatihan
- harga belum tentu diketahui saat order dibuat
- tagihan baru dibentuk ketika invoice diterbitkan
- selama proses servis dapat terjadi penggunaan atau penjualan komponen tambahan
- selama proses order perlu dicatat siapa pelanggan, di mana lokasi kerja, kapan jadwalnya, dan siapa saja pegawai yang ditugaskan

Karena itu dibutuhkan dokumen order yang fokus pada operasional, bukan langsung pada penagihan.

## Prinsip Solusi

- `Operational first`
  Order dipakai sebagai dokumen kerja lapangan dan administrasi operasional.

- `Invoice separated`
  Invoice tetap menjadi dokumen tagihan yang berdiri sendiri, tetapi dapat dibuat dari order.

- `Multi-assignee`
  Satu order dapat ditangani oleh lebih dari satu user atau pegawai.

- `Type-based design`
  Struktur utama dibuat sama, lalu field tertentu disesuaikan menurut tipe `service` atau `training`.

- `Progressive complexity`
  Versi awal dibuat cukup lengkap untuk operasional inti, tetapi tidak terlalu rumit agar implementasi tetap terkontrol.

## Ruang Lingkup

### In Scope

- modul `Order Jasa`
- dua tipe order: `service` dan `training`
- list, filter, create, detail, edit, dan update status order
- penugasan banyak user pada satu order
- pencatatan detail khusus per tipe order
- pencatatan komponen pada order servis
- pembuatan invoice dari order jasa
- histori log perubahan status dan catatan penting order

### Out of Scope Untuk Tahap Awal

- kalender penjadwalan lintas order
- notifikasi real-time ke pegawai
- portal pelanggan eksternal
- multi-invoice per satu order
- approval workflow bertingkat
- integrasi stok otomatis yang kompleks untuk komponen servis

## Usulan Modul dan Pendekatan

### Pendekatan Yang Disarankan

Bangun satu modul induk bernama `Order Jasa`.

Header order, status, assignment user, relasi invoice, dan log aktivitas dibuat seragam. Detail spesifik jasa dibedakan berdasarkan `order_type`.

Keuntungan pendekatan ini:

- mengurangi duplikasi antara servis dan pelatihan
- memudahkan reuse pola UI, status, dan permission
- mempermudah laporan gabungan jasa
- memudahkan penambahan tipe jasa baru di masa depan

### Alternatif Yang Tidak Disarankan Untuk Awal

Membuat modul `Order Servis` dan `Order Pelatihan` sebagai dua domain penuh yang terpisah.

Risiko:

- banyak logika yang akan terduplikasi
- maintenance lebih mahal
- integrasi invoice dan laporan menjadi lebih rumit

## Struktur Konseptual Data

### 1. Header Order Jasa

Data umum yang disarankan:

- nomor order
- tipe order: `service` atau `training`
- cabang
- pelanggan
- tanggal order
- tanggal rencana mulai
- lokasi
- status
- catatan internal
- referensi invoice jika sudah ditagihkan

### 2. Assignment Pegawai

Satu order dapat memiliki lebih dari satu user.

Data yang disarankan:

- order id
- user id
- peran opsional di order, misalnya `lead`, `teknisi`, `trainer`, `helper`
- catatan penugasan bila diperlukan

### 3. Detail Khusus Order Servis

Field yang disarankan:

- nama servis atau judul pekerjaan
- jenis servis
- keluhan pelanggan atau scope pekerjaan
- lokasi pengerjaan
- tanggal rencana pengerjaan
- tanggal realisasi bila diperlukan
- hasil servis atau closing note

### 4. Detail Khusus Order Pelatihan

Field yang disarankan:

- nama pelatihan
- jenis atau kategori pelatihan
- tempat pelatihan
- tanggal mulai
- durasi hari
- ringkasan materi atau agenda
- catatan pelaksanaan

### 5. Komponen Pada Order Servis

Servis dapat membutuhkan komponen tambahan selama proses berlangsung.

Karena itu perlu wadah pencatatan komponen di level order.

Data yang disarankan:

- order id
- component id
- nama komponen snapshot
- qty
- catatan penggunaan
- status tagih, misalnya `billable` atau `internal`

Catatan:
- harga komponen tidak wajib dipatok saat komponen dicatat di order
- harga final dapat diputuskan saat pembuatan invoice

### 6. Log Order

Perubahan penting sebaiknya tercatat sebagai log.

Contoh event log:

- order dibuat
- status berubah
- pegawai ditugaskan atau dicabut
- komponen ditambahkan
- invoice diterbitkan
- order dibatalkan

## Alur Bisnis Yang Disarankan

### Alur Order Servis

1. User membuat `Order Servis`.
2. User mengisi pelanggan, lokasi, jadwal, deskripsi pekerjaan, dan pegawai yang ditugaskan.
3. Order berjalan tanpa harus memiliki nominal jasa final.
4. Jika selama proses ada penggunaan komponen, komponen dicatat pada order.
5. Setelah pekerjaan selesai atau siap ditagihkan, user membuat invoice dari order.
6. Saat membuat invoice, user menentukan item jasa servis dan komponen yang ditagihkan.
7. Pembayaran tetap diproses melalui modul pembayaran yang sudah ada.

### Alur Order Pelatihan

1. User membuat `Order Pelatihan`.
2. User mengisi pelanggan, nama pelatihan, tempat, tanggal mulai, durasi, dan pegawai yang ditugaskan.
3. Order berjalan tanpa harus memiliki nominal pelatihan final.
4. Setelah pelatihan selesai atau siap ditagihkan, user membuat invoice dari order.
5. Saat membuat invoice, user menentukan item jasa pelatihan yang akan ditagihkan.
6. Pembayaran diproses melalui modul pembayaran yang sudah ada.

## Relasi Dengan Invoice

### Prinsip Dasar

`Order Jasa` adalah sumber operasional, sedangkan `Invoice` adalah sumber tagihan.

Artinya:

- order dapat ada tanpa invoice
- invoice dibuat dari order saat tagihan siap diterbitkan
- nilai uang tidak wajib ada di order

### Rekomendasi Tahap Awal

Batasi dulu:

- satu `Order Jasa` hanya memiliki maksimal satu invoice utama

Alasan:

- implementasi lebih sederhana
- status order lebih mudah dipahami
- meminimalkan kompleksitas outstanding tagihan

Jika nanti bisnis membutuhkan termin, DP, atau penagihan bertahap, dukungan multi-invoice bisa dipertimbangkan pada fase berikutnya.

### Rekomendasi Komponen Servis Saat Buat Invoice

Saat invoice dibuat dari `Order Servis`, user dapat:

- memilih jasa servis sebagai item invoice
- memilih komponen yang ingin ditagihkan
- menentukan harga final jasa dan komponen di tahap invoice

Dengan pendekatan ini:

- order tetap fleksibel
- invoice tetap akurat sebagai dokumen penagihan
- komponen internal yang tidak ditagihkan tetap bisa tercatat

## Status Order Yang Disarankan

### Opsi Praktis Tahap Awal

Gunakan status umum yang sama untuk kedua tipe order:

- `draft`
- `confirmed`
- `in_progress`
- `completed`
- `invoiced`
- `cancelled`

Kelebihan:

- implementasi lebih sederhana
- konsisten lintas tipe order
- lebih mudah dibuatkan filter, badge, dan permission

### Opsi Lanjutan Jika Perlu Lebih Detail

Untuk servis:

- `draft`
- `confirmed`
- `in_progress`
- `waiting_parts`
- `completed`
- `invoiced`
- `cancelled`

Untuk pelatihan:

- `draft`
- `confirmed`
- `scheduled`
- `on_going`
- `completed`
- `invoiced`
- `cancelled`

Rekomendasi:
- pakai status umum dulu untuk fase awal
- detail yang lebih spesifik bisa ditambahkan setelah kebutuhan operasionalnya benar-benar terasa

## Hak Edit Berdasarkan Tahap Order

### Draft

Boleh edit:

- header order
- pelanggan
- jadwal
- lokasi
- tipe dan detail jasa
- user yang ditugaskan
- komponen servis
- catatan

### Confirmed

Masih boleh edit:

- jadwal
- lokasi
- assignment user
- catatan
- detail tertentu sesuai kebutuhan operasional

Sebaiknya mulai kunci:

- identitas pelanggan
- tipe order

### In Progress

Masih boleh edit:

- progress note
- assignment user bila diperlukan
- komponen servis
- catatan pelaksanaan

Sebaiknya dikunci:

- data header inti

### Completed

Mayoritas data readonly.

Masih boleh:

- lihat detail
- membuat invoice jika belum ada

### Invoiced

Order bersifat final secara operasional.

Perubahan dibatasi agar konsisten dengan invoice yang sudah terbit.

### Cancelled

Order readonly dan menyimpan histori pembatalan.

## Usulan Struktur Tabel Awal

Nama tabel dapat disesuaikan, tetapi secara konseptual disarankan ada:

- `service_orders`
- `service_order_assignments`
- `service_order_components`
- `service_order_logs`

Catatan:
- walaupun namanya `service_orders`, tabel ini menyimpan order jasa secara umum
- kolom `order_type` membedakan `service` dan `training`

### Kolom Inti `service_orders`

Kolom konseptual yang disarankan:

- `id`
- `order_number`
- `order_type`
- `branch_id`
- `customer_id`
- `status`
- `order_date`
- `planned_start_date`
- `location`
- `title`
- `service_category` atau `training_category`
- `description`
- `duration_days`
- `completion_notes`
- `invoice_id`
- `created_by`
- `updated_by`

Catatan:
- penamaan final nanti menyesuaikan standar backend yang sudah ada
- beberapa field dapat dibuat nullable karena hanya relevan untuk tipe tertentu

## UI Yang Disarankan

### Halaman List

Tampilkan:

- nomor order
- tipe order
- pelanggan
- judul order
- lokasi
- jadwal
- status
- jumlah pegawai ditugaskan
- status invoice

Filter yang disarankan:

- tipe order
- status
- cabang
- pelanggan
- rentang tanggal

### Form Create atau Edit

Bagian utama:

- informasi umum order
- detail jasa sesuai tipe
- lokasi dan jadwal
- penugasan pegawai
- komponen servis jika tipe `service`
- catatan internal

### Detail Order

Tampilkan:

- ringkasan order
- daftar pegawai yang ditugaskan
- detail khusus servis atau pelatihan
- komponen servis
- histori log
- status invoice terkait

## Dependency Modul Existing

Fitur ini kemungkinan akan menyentuh atau bergantung pada:

- master `pelanggan`
- master `pengguna`
- master `komponen`
- master `cabang`
- modul `invoice`
- modul `pembayaran`
- permission dan menu

## Risiko dan Hal Yang Perlu Diputuskan

Sebelum development, ada beberapa keputusan penting yang perlu difinalkan:

1. Apakah satu order hanya boleh punya satu invoice, atau nanti perlu multi-invoice.
2. Apakah komponen servis otomatis memotong stok atau hanya dicatat administratif.
3. Apakah assignment user hanya untuk referensi, atau nanti dipakai untuk workload dan kalender.
4. Apakah status akan dibuat umum lintas tipe, atau berbeda per jenis order sejak awal.
5. Apakah harga jasa kadang perlu disimpan sebagai estimasi di order walaupun belum final.

## Tahapan Implementasi Yang Disarankan

### Tahap 1: Finalisasi Kebutuhan

- sepakati field final untuk servis
- sepakati field final untuk pelatihan
- sepakati status order
- sepakati aturan create invoice dari order

### Tahap 2: Desain Backend API

- buat struktur tabel
- buat endpoint list, create, show, update, update status
- buat endpoint assignment user
- buat endpoint komponen servis
- buat endpoint create invoice dari order

### Tahap 3: Integrasi Frontend Dasar

- tambah menu modul
- buat halaman list
- buat modal atau halaman create-edit
- buat halaman detail
- tampilkan log dan status

### Tahap 4: Integrasi Invoice

- buat alur create invoice dari order jasa
- tarik data order ke form invoice
- tentukan item yang ikut ditagihkan

### Tahap 5: Penyempurnaan Operasional

- validasi status dan kontrol edit
- perbaikan UX
- filter lanjutan
- laporan dasar

## Rekomendasi Akhir

Untuk fase awal, pendekatan yang paling aman dan efisien adalah:

- bangun satu modul `Order Jasa`
- gunakan `order_type` untuk membedakan `service` dan `training`
- gunakan status umum terlebih dahulu
- dukung multi-user assignment sejak awal
- simpan komponen servis di level order
- batasi satu order ke satu invoice utama

Pendekatan ini paling seimbang antara kebutuhan bisnis saat ini dan kemudahan implementasi bertahap.
