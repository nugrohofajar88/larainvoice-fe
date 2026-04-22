# Rancangan Kontrol Edit Berdasarkan Status Order Mesin

Dokumen ini menjadi acuan awal untuk mengatur field, tombol, dan aksi yang boleh dilakukan pada halaman Order Mesin berdasarkan status order.

## Tujuan

1. Mencegah perubahan data penting setelah order masuk tahap lanjut.
2. Membuat alur kerja admin, sales, dan produksi lebih aman.
3. Menjadi dasar implementasi kontrol `disabled`, `readonly`, dan visibilitas tombol di UI.

## Status Yang Digunakan

- `draft`
- `confirmed`
- `in_production`
- `ready`
- `completed`
- `cancelled`

## Prinsip Umum

1. Semakin lanjut status order, semakin sedikit data yang boleh diubah.
2. Data inti order tidak boleh berubah setelah order sudah berjalan di produksi.
3. Perubahan komponen setelah produksi berjalan sebaiknya tidak dilakukan langsung, tetapi melalui mekanisme revisi.
4. Status `completed` dan `cancelled` pada dasarnya bersifat readonly.

## Rancangan Hak Edit Per Status

### 1. Draft

Status awal, semua data masih bisa diubah.

Boleh edit:
- Header order
- Cabang
- Pelanggan
- Sales
- Status
- Mesin
- Qty
- Base Price
- Tanggal order
- Estimasi mulai
- Estimasi selesai
- Aktual selesai
- Diskon
- Biaya tambahan
- Komponen order
- Pembayaran order
- Catatan

Tujuan:
- Memberi keleluasaan penuh saat order masih disiapkan.

### 2. Confirmed

Order sudah disetujui dan siap dijalankan, data inti mulai dikunci.

Header inti dikunci:
- Cabang
- Pelanggan
- Mesin
- Qty

Masih boleh edit:
- Sales
- Status
- Base Price
- Tanggal dan estimasi
- Diskon
- Biaya tambahan
- Komponen order
- Pembayaran order
- Catatan

Tujuan:
- Menjaga identitas order tetap konsisten.
- Masih memberi ruang koreksi administratif sebelum masuk produksi.

Catatan:
- Jika ingin lebih ketat, `Base Price` dan `Diskon` juga bisa ikut dikunci di tahap ini.

### 3. In Production

Order sudah masuk tahap pengerjaan produksi.

Dikunci:
- Semua header inti
- Komponen order
- Biaya tambahan

Masih boleh edit:
- Status
- Estimasi mulai
- Estimasi selesai
- Aktual selesai
- Pembayaran order
- Catatan internal

Tujuan:
- Mencegah perubahan material dan struktur order saat produksi sudah berjalan.

Catatan:
- Jika komponen perlu diubah, sebaiknya dibuat mekanisme revisi khusus, bukan edit langsung.

### 4. Ready

Produksi sudah selesai dan order siap diserahkan.

Dikunci:
- Header order
- Komponen order
- Biaya tambahan
- Diskon

Masih boleh edit:
- Status
- Aktual selesai
- Pembayaran order
- Catatan administratif

Tujuan:
- Menjaga hasil produksi tetap final sambil tetap memungkinkan pelunasan atau update administratif.

### 5. Completed

Order sudah selesai sepenuhnya.

Semua data readonly.

Boleh:
- Lihat detail

Tidak boleh:
- Edit header
- Edit komponen
- Edit biaya
- Edit pembayaran
- Edit catatan

Tujuan:
- Menjadikan order sebagai data final.

Catatan:
- Jika diperlukan perubahan, harus melalui aksi khusus seperti `reopen` atau permission khusus admin.

### 6. Cancelled

Order dibatalkan.

Semua data readonly.

Boleh:
- Lihat detail

Tidak boleh:
- Tambah komponen
- Ubah pembayaran
- Ubah biaya
- Ubah header

Tujuan:
- Menjaga histori pembatalan tetap konsisten.

Catatan:
- Disarankan ada field/alasan pembatalan pada tahap berikutnya.

## Rancangan Kontrol UI

Supaya implementasi rapi, kontrol sebaiknya tidak ditulis tersebar per field, tetapi menggunakan helper terpusat di Alpine/JS.

Contoh helper yang disarankan:

- `canEditHeader`
- `canEditCoreHeader`
- `canEditPricing`
- `canEditComponents`
- `canEditCosts`
- `canEditPayments`
- `canEditNotes`
- `canEditProductionDates`
- `isReadOnlyStatus`

## Mapping Rekomendasi Helper

### canEditCoreHeader

Field:
- Cabang
- Pelanggan
- Mesin
- Qty

Rule:
- hanya `draft`

### canEditHeader

Field:
- Sales
- Status
- Tanggal order

Rule:
- `draft`
- `confirmed`

### canEditPricing

Field:
- Base Price
- Diskon

Rule:
- `draft`
- `confirmed`

### canEditComponents

Field/Aksi:
- Tambah komponen
- Hapus komponen
- Ubah qty komponen
- Cari/pilih komponen tambahan

Rule:
- `draft`
- `confirmed`

### canEditCosts

Field/Aksi:
- Tambah biaya
- Hapus biaya
- Edit biaya tambahan

Rule:
- `draft`
- `confirmed`

### canEditPayments

Field/Aksi:
- Tambah pembayaran
- Edit pembayaran
- Hapus pembayaran

Rule:
- `draft`
- `confirmed`
- `in_production`
- `ready`

### canEditProductionDates

Field:
- Estimasi mulai
- Estimasi selesai
- Aktual selesai

Rule:
- `draft`
- `confirmed`
- `in_production`
- `ready`

### canEditNotes

Field:
- Catatan publik
- Catatan internal

Rule:
- `draft`
- `confirmed`
- `in_production`
- `ready`

### isReadOnlyStatus

Rule:
- `completed`
- `cancelled`

Efek:
- seluruh field readonly
- seluruh tombol tambah/hapus/edit disembunyikan atau disabled

## Rekomendasi Implementasi Tahap Awal

Untuk versi pertama, implementasi bisa dibuat sederhana seperti ini:

- `draft`
  semua editable

- `confirmed`
  cabang, pelanggan, mesin, qty dikunci
  komponen, biaya, pembayaran masih editable

- `in_production`
  header inti, komponen, biaya dikunci
  pembayaran dan tanggal produksi masih editable

- `ready`
  hampir semua dikunci
  pembayaran dan aktual selesai masih editable

- `completed`
  full readonly

- `cancelled`
  full readonly

## Rekomendasi UX

1. Field yang dikunci sebaiknya tetap tampil, tetapi dalam keadaan `disabled` atau `readonly`.
2. Tombol seperti `Tambah Komponen`, `Tambah Biaya`, dan `Tambah Pembayaran` sebaiknya otomatis hilang jika tidak boleh dipakai.
3. Jika user mencoba edit pada status terkunci, tampilkan pesan yang jelas.

Contoh pesan:
- `Komponen tidak bisa diubah saat order sudah masuk produksi.`
- `Order dengan status completed hanya bisa dilihat.`
- `Data inti order tidak bisa diubah setelah status confirmed.`

## Tahap Lanjutan Yang Bisa Ditambahkan

1. Mekanisme `reopen order`
2. Mekanisme `revisi komponen`
3. Permission khusus untuk override status lock
4. Audit log perubahan status dan perubahan data penting
5. Alasan pembatalan untuk status `cancelled`

## Kesimpulan

Rancangan awal yang direkomendasikan:

- `draft`: semua editable
- `confirmed`: data inti dikunci, komponen/biaya/pembayaran masih bisa diubah
- `in_production`: komponen dan biaya dikunci, pembayaran dan tanggal produksi masih bisa diubah
- `ready`: hampir semua dikunci, hanya administratif tertentu yang masih bisa diubah
- `completed`: readonly
- `cancelled`: readonly

Dokumen ini bisa langsung dipakai sebagai dasar implementasi helper kontrol status di halaman Order Mesin.
