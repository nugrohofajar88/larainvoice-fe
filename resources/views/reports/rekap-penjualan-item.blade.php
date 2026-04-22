@extends('layouts.app')
@section('title', $title)
@section('page-title', $title)
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        <p class="text-sm text-slate-500 mt-1">Ringkasan penjualan berdasarkan item invoice.</p>
    </div>
    <a href="{{ route(str_replace('laporan.', 'laporan.export.', $routeName), request()->query()) }}" class="btn-outline">Export Excel</a>
</div>
<div class="card card-body mb-5 grid grid-cols-1 md:grid-cols-4 gap-3">
    <div><label class="form-label">Cabang</label><select name="branch_id" form="sales-item-filter-form" class="form-select"><option value="">Semua cabang</option>@foreach($branches as $branch)<option value="{{ $branch['id'] }}" {{ (string) request('branch_id') === (string) $branch['id'] ? 'selected' : '' }}>{{ $branch['name'] }}</option>@endforeach</select></div>
    <div><label class="form-label">Tanggal Dari</label><input type="date" name="date_from" form="sales-item-filter-form" class="form-input" value="{{ request('date_from') }}"></div>
    <div><label class="form-label">Tanggal Sampai</label><input type="date" name="date_to" form="sales-item-filter-form" class="form-input" value="{{ request('date_to') }}"></div>
    <div class="flex items-end gap-2">
        <button type="submit" form="sales-item-filter-form" class="btn-primary">Terapkan</button>
        @if(request()->anyFilled(['number', 'customer', 'description', 'branch_id', 'date_from', 'date_to']))
            <a href="{{ route($routeName) }}" class="btn-outline">Reset</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
    <div class="stat-card">
        <div class="stat-icon bg-orange-100">
            <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div><p class="text-xs text-slate-500">Total Item</p><p class="text-2xl font-bold">{{ number_format($totalRows) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-sky-100">
            <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div><p class="text-xs text-slate-500">Total Qty</p><p class="text-2xl font-bold">{{ number_format($totalQty) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-violet-100">
            <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div><p class="text-xs text-slate-500">Total Cancel</p><p class="text-2xl font-bold">{{ number_format($totalCancel) }}</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green-100">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div><p class="text-xs text-slate-500">Total Omzet Non-Cancel</p><p class="text-2xl font-bold">Rp {{ number_format($totalOmzet) }}</p></div>
    </div>
</div>

<div class="card overflow-hidden">
    <form id="sales-item-filter-form" action="{{ route($routeName) }}" method="GET">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th>No Invoice</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Cabang</th>
                        <th>Deskripsi Item</th>
                        <th class="text-right">Qty</th>
                        @if($showMinutes)
                            <th class="text-right">Menit</th>
                        @endif
                        <th class="text-right">Harga</th>
                        <th class="text-right">Subtotal</th>
                        <th>Status Produksi</th>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="number" value="{{ request('number') }}"
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari invoice...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="customer" value="{{ request('customer') }}"
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari pelanggan...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="description" value="{{ request('description') }}"
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari item...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        @if($showMinutes)
                            <th class="py-2 px-4 shadow-inner"></th>
                        @endif
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner text-center">
                            <button type="submit" class="hidden"></button>
                            @if(request()->anyFilled(['number', 'customer', 'description', 'branch_id', 'date_from', 'date_to']))
                                <a href="{{ route($routeName) }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td><span class="text-slate-400 font-mono text-xs">{{ ($rows->currentPage() - 1) * $rows->perPage() + $loop->iteration }}</span></td>
                        <td class="font-mono text-xs text-brand font-bold">{{ $row['number'] }}</td>
                        <td class="text-slate-500 text-xs">{{ $row['date'] ? date('d/m/Y', strtotime($row['date'])) : '-' }}</td>
                        <td class="font-medium text-slate-800">{{ $row['customer'] }}</td>
                        <td class="text-slate-500 text-xs">{{ $row['branch'] }}</td>
                        <td>
                            <div class="font-medium text-slate-800">{{ $row['description'] }}</div>
                            <div class="text-xs text-slate-400 mt-1">{{ $row['item_type'] }} | {{ $row['machine'] ?: '-' }} | {{ $row['petugas'] ?: '-' }}</div>
                        </td>
                        <td class="text-right font-mono">{{ number_format($row['qty']) }}</td>
                        @if($showMinutes)
                            <td class="text-right font-mono">{{ number_format($row['minutes']) }}</td>
                        @endif
                        <td class="text-right font-mono">Rp {{ number_format($row['price']) }}</td>
                        <td class="text-right font-mono font-semibold">Rp {{ number_format($row['subtotal']) }}</td>
                        <td><span class="badge-info">{{ $row['production_status'] }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $showMinutes ? 11 : 10 }}" class="py-12 text-center text-slate-400">
                            Tidak ada data penjualan ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($rows->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endsection
