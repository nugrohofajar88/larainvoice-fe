# Optimasi Performa Halaman Machine Order

Dokumen ini merangkum audit awal performa halaman Machine Order dan rencana optimasi bertahap agar loading halaman menjadi lebih cepat, lebih ringan, dan tetap aman untuk kebutuhan operasional.

## Ringkasan Masalah

Halaman Machine Order terasa lambat terutama karena terlalu banyak data dimuat saat initial page load.

Saat halaman dibuka, server memanggil beberapa endpoint API sekaligus untuk kebutuhan:

- daftar machine order
- summary status
- customers
- machines
- components
- cost types
- sales
- branches

Sebagian data tersebut sebenarnya hanya dibutuhkan saat modal create/edit dibuka, bukan saat user baru melihat list tabel.

## Temuan Teknis

### 1. Overfetching Data Master

Di halaman index, data master besar ikut dipreload:

- pelanggan
- mesin
- komponen
- sales

Padahal data tersebut lebih banyak dipakai oleh modal create/edit.

Dampak:

- response controller lebih lambat
- payload HTML lebih besar
- Alpine perlu menginisialisasi data besar di browser

### 2. Multi Request ke API

Method `fetchApiCollection()` melakukan loop paging per endpoint.

Artinya:

- satu resource bisa memicu beberapa request
- total request API bisa membengkak
- latency akumulatif makin terasa

### 3. Summary dan List Masih Menambah Beban Request

Sekarang summary card sudah dipisah dari filter tabel, itu benar dari sisi UX.

Namun konsekuensinya:

- ada tambahan fetch dataset untuk summary
- jika summary masih mengambil semua order lalu dihitung, itu tetap mahal

### 4. Data Form dan Data List Belum Dipisah

Kebutuhan halaman list dan kebutuhan modal masih tercampur.

Padahal kebutuhan list jauh lebih ringan daripada kebutuhan form.

## Sumber Beban Utama

Urutan kemungkinan bottleneck:

1. preload `components`
2. preload `machines`
3. preload `customers`
4. preload `sales`
5. fetch summary status
6. ukuran payload Blade ke browser
7. inisialisasi data Alpine yang besar

JavaScript frontend bukan penyebab utama, kecuali setelah data yang dikirim memang sudah terlalu besar.

## Tujuan Optimasi

1. Mempercepat initial load halaman index
2. Mengurangi jumlah request API saat halaman pertama dibuka
3. Mengurangi ukuran payload yang dikirim ke browser
4. Memisahkan kebutuhan data list dan data modal
5. Menjaga UX tetap nyaman untuk admin

## Strategi Optimasi

### Tahap 1: Lazy Load Data Modal

Jangan preload semua master data besar saat halaman index dibuka.

Data yang sebaiknya tidak ikut initial load:

- customers
- machines
- components
- sales

Data tersebut cukup diambil saat:

- user klik `Buat Order Mesin`
- user klik `Edit`
- atau saat modal benar-benar dibutuhkan

Keuntungan:

- halaman list lebih cepat tampil
- HTML lebih kecil
- browser lebih ringan

Tradeoff:

- modal pertama kali buka akan sedikit loading

### Tahap 2: Fetch Berdasarkan Cabang

Setelah cabang dipilih, baru ambil:

- pelanggan cabang tersebut
- sales cabang tersebut
- mesin cabang tersebut
- komponen cabang tersebut

Keuntungan:

- jumlah data jauh lebih kecil
- dropdown lebih relevan
- pencarian lebih cepat

### Tahap 3: Remote Search untuk Data Besar

Untuk data dengan volume besar, jangan kirim semuanya ke frontend.

Gunakan pencarian remote untuk:

- pelanggan
- mesin
- komponen

Pola yang disarankan:

- user ketik keyword
- frontend kirim request pencarian
- API mengembalikan hasil top N

Keuntungan:

- sangat mengurangi payload
- dropdown tetap responsif walau data master besar

### Tahap 4: Endpoint Summary Khusus

Summary card sebaiknya tidak dihitung dari fetch semua order.

Lebih ideal jika API menyediakan endpoint agregat khusus, misalnya:

- total draft
- total confirmed
- total in_production
- total ready
- total completed
- total cancelled

Keuntungan:

- cepat
- ringan
- tidak tergantung pagination

### Tahap 5: Cache Data Master Ringan

Data yang relatif jarang berubah bisa di-cache:

- cost types
- branches

Cache bisa dilakukan:

- di backend Laravel
- atau di sisi frontend setelah load pertama

## Rencana Implementasi Bertahap

### Tahap A

Optimasi paling cepat dengan perubahan moderat:

- hapus preload `customers`, `machines`, `components`, `sales`
- load data tersebut saat modal dibuka
- pertahankan preload untuk:
  - list order
  - summary status
  - branches
  - cost types

Target hasil:

- initial load jauh lebih ringan

### Tahap B

Optimasi berbasis cabang:

- saat pilih cabang, request data master per cabang
- reset dropdown yang tergantung cabang

Target hasil:

- dropdown lebih cepat dan lebih relevan

### Tahap C

Optimasi remote search:

- komponen tidak lagi preload penuh
- pelanggan tidak lagi preload penuh
- mesin bisa dipertimbangkan remote jika datanya besar

Target hasil:

- performa tetap stabil walau master data bertambah

### Tahap D

Optimasi summary status:

- pindah ke endpoint agregat khusus

Target hasil:

- card summary independen dan cepat

## Quick Win Yang Paling Direkomendasikan

Jika ingin dampak paling terasa dengan effort paling masuk akal, kerjakan dulu ini:

1. lazy load data modal
2. fetch data berdasarkan cabang
3. pertahankan hanya data ringan pada initial load

Ini biasanya sudah cukup untuk membuat halaman terasa jauh lebih cepat.

## Dampak Yang Diharapkan

Jika tahap awal diterapkan:

- initial render lebih cepat
- ukuran HTML menurun
- memori browser lebih ringan
- Alpine init lebih cepat
- UI tabel lebih responsif

Kemungkinan tradeoff:

- modal create/edit pertama kali buka ada loading singkat

Tradeoff ini masih jauh lebih sehat daripada membuat seluruh halaman list menjadi berat.

## Rekomendasi Teknis Implementasi

### Di Controller

Pisahkan data untuk:

- index list
- summary status
- modal create/edit

Jangan campur semua kebutuhan menjadi satu payload awal.

### Di Frontend

Tambahkan state loading untuk modal:

- `masterDataLoaded`
- `masterDataLoading`

Saat modal dibuka:

- jika belum load, fetch dulu
- tampilkan loading state
- setelah selesai, baru user isi form

### Di API

Jika memungkinkan, siapkan endpoint:

- summary machine order
- master data by branch
- component search
- customer search
- machine search

## Prioritas Implementasi

Urutan kerja yang direkomendasikan:

1. lazy load data modal
2. fetch by branch
3. remote search komponen
4. remote search pelanggan dan mesin
5. endpoint summary khusus

## Kesimpulan

Akar masalah performa halaman Machine Order kemungkinan besar bukan JavaScript frontend, tetapi arsitektur loading data yang terlalu berat di awal.

Penyebab utama:

- terlalu banyak data master dipanggil saat halaman dibuka
- terlalu banyak request API untuk kebutuhan yang belum tentu langsung dipakai user
- data modal dan data list belum dipisah

Solusi terbaik:

- buat initial load tetap ringan
- pindahkan data besar ke lazy load
- ambil data berdasarkan cabang
- gunakan remote search untuk master data besar

Dokumen ini bisa dipakai sebagai acuan refactor bertahap agar optimasi tetap aman dan terukur.
