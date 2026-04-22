@extends('layouts.app')
@section('title', 'Upload File Produksi')
@section('page-title', 'Upload File Produksi')

@php
    $currentTab = $invoice['production_status'] ?? 'pending';
    $tabBackUrl = route('sales-list.index', ['tab' => $currentTab]);
    $isPending = $currentTab === 'pending';
    $isInProgress = $currentTab === 'in-process';
@endphp

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="page-title">{{ $isInProgress ? 'File Kerja Produksi' : 'Upload File Produksi' }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $isInProgress
                    ? 'Operator dapat mengunduh file kerja yang sebelumnya diunggah admin untuk kebutuhan produksi.'
                    : 'Upload lampiran kerja untuk transaksi pending sebelum diproses ke tahap berikutnya.' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ $tabBackUrl }}" class="btn btn-outline">Kembali ke {{ $isInProgress ? 'In-Progress' : 'Pending' }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-5">
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="font-semibold text-slate-800">Informasi Transaksi</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Pastikan file diunggah ke item cutting yang sesuai.</p>
                    </div>
                </div>
                <div class="card-body grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs text-slate-500">No Invoice</p>
                        <p class="font-mono font-semibold text-slate-900">{{ $invoice['number'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Tanggal</p>
                        <p class="font-semibold text-slate-900">{{ $invoice['date'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Pelanggan</p>
                        <p class="font-semibold text-slate-900">{{ $invoice['customer'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Mesin</p>
                        <p class="font-semibold text-slate-900">{{ $invoice['machine'] ?: '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="font-semibold text-slate-800">Aksi Cepat</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $isPending
                                ? 'Setelah upload, kamu bisa langsung print SPK atau lanjut process dari halaman ini.'
                                : 'Di tahap In-progress, operator tetap bisa print SPK dan melanjutkan penyelesaian pekerjaan dari halaman ini.' }}
                        </p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('invoice.spk', $invoice['id']) }}" target="_blank" class="btn btn-outline">
                            Print SPK
                        </a>
                        @if($isPending)
                            <form action="{{ route('sales-list.update-status', $invoice['id']) }}" method="POST" class="inline-flex">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="production_status" value="in-process">
                                <input type="hidden" name="tab" value="pending">
                                <button type="submit" class="btn btn-primary">
                                    Process
                                </button>
                            </form>
                        @endif
                        @if($isInProgress)
                            <form action="{{ route('sales-list.update-status', $invoice['id']) }}" method="POST" class="inline-flex">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="production_status" value="completed">
                                <input type="hidden" name="tab" value="in-process">
                                <button type="submit" class="btn btn-primary">
                                    Completed
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            @if($isPending)
                <div class="card overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="font-semibold text-slate-800">Form Upload</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Mendukung `pdf`, `dxf`, `dwg`, `ai`, `cdr`, `svg`, `png`, `jpg`, `jpeg` maksimal 20 MB per file.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(empty($uploadableItems))
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Tidak ada item cutting pada invoice ini, jadi tidak ada target upload file.
                            </div>
                        @else
                            <form action="{{ route('sales-list.upload.store', $invoice['id']) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                                @csrf

                                <div>
                                    <label for="invoice_item_id" class="form-label">Pilih Item Cutting</label>
                                    <select id="invoice_item_id" name="invoice_item_id" class="form-select" required>
                                        <option value="">Pilih item...</option>
                                        @foreach($uploadableItems as $item)
                                            <option value="{{ $item['id'] }}" {{ (string) old('invoice_item_id') === (string) $item['id'] ? 'selected' : '' }}>
                                                {{ $item['desc'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="files" class="form-label">File Lampiran</label>
                                    <input
                                        id="files"
                                        type="file"
                                        name="files[]"
                                        class="form-input"
                                        multiple
                                        required
                                        accept=".pdf,.dxf,.dwg,.ai,.cdr,.svg,.png,.jpg,.jpeg"
                                    >
                                    <p class="mt-2 text-xs text-slate-500">
                                        Gunakan file desain atau dokumen kerja yang memang dibutuhkan operator produksi.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button type="submit" class="btn btn-primary">Upload Sekarang</button>
                                    <a href="{{ $tabBackUrl }}" class="btn btn-outline">Batal</a>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="card overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="font-semibold text-slate-800">Workspace Operator</h2>
                            <p class="mt-0.5 text-xs text-slate-500">File upload dikunci pada tahap ini. Operator fokus mengunduh lampiran kerja dan menjalankan produksi.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                            Di status In-progress, halaman ini berfungsi sebagai pusat unduh file kerja untuk operator.
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-5">
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="font-semibold text-slate-800">Lampiran Tersimpan</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Dikelompokkan per item cutting.</p>
                    </div>
                </div>
                <div class="card-body space-y-4">
                    @php
                        $itemsWithFiles = collect($uploadableItems)->filter(fn ($item) => !empty($item['files'] ?? []));
                    @endphp

                    @if($itemsWithFiles->isEmpty())
                        <p class="py-4 text-sm text-center text-slate-500">
                            Belum ada file yang diunggah untuk invoice ini.
                        </p>
                    @else
                        @foreach($itemsWithFiles as $item)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $item['desc'] }}</p>
                                <div class="mt-3 space-y-2">
                                    @foreach($item['files'] as $file)
                                        <a
                                            href="{{ route('sales-list.files.download', ['id' => $invoice['id'], 'fileId' => $file['id']]) }}"
                                            class="flex items-start justify-between gap-3 rounded-xl border border-slate-100 px-3 py-2 text-sm hover:border-slate-200 hover:bg-slate-50"
                                        >
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-slate-800">{{ $file['file_name'] }}</p>
                                                <p class="text-xs text-slate-400">
                                                    {{ strtoupper($file['file_extension'] ?? '-') }} • {{ number_format(($file['file_size'] ?? 0) / 1024, 1) }} KB
                                                </p>
                                            </div>
                                            <span class="shrink-0 text-xs font-medium text-brand">Unduh</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
