@extends('layouts.app')
@section('title', 'Piutang & Aging')
@section('page-title', 'Laporan Piutang & Aging')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div><h1 class="page-title">Piutang & Aging</h1><p class="text-sm text-slate-500 mt-1">Invoice belum lunas berdasarkan umur tagihan</p></div>
    <a href="{{ route('laporan.export.piutang', request()->query()) }}" class="btn-outline">Export Excel</a>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
    <div class="stat-card"><div class="stat-icon bg-orange-100"><svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div><div><p class="text-xs text-slate-500">Total Item</p><p class="text-2xl font-bold">{{ number_format($totalItems) }}</p></div></div>
    <div class="stat-card"><div class="stat-icon bg-red-100"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div><p class="text-xs text-slate-500">Total Rupiah</p><p class="text-2xl font-bold">Rp {{ number_format($totalAmount) }}</p></div></div>
</div>
<div class="card overflow-hidden">
    <form action="{{ route('laporan.piutang') }}" method="GET">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th>No Invoice</th>
                        <th>Pelanggan</th>
                        <th>Cabang</th>
                        <th>Tanggal</th>
                        <th class="text-right">Total Tagihan</th>
                        <th class="text-right">Terbayar</th>
                        <th class="text-right">Total Piutang</th>
                        <th>Status</th>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="number" value="{{ request('number') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari no...">
                        </th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="customer" value="{{ request('customer') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari pelanggan...">
                        </th>
                        <th class="py-2 px-4 shadow-inner">
                            <select name="branch_id" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all">
                                <option value="">Semua cabang</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch['id'] }}" {{ (string) request('branch_id') === (string) $branch['id'] ? 'selected' : '' }}>{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner text-center">
                            <button type="submit" class="hidden"></button>
                            @if(request()->anyFilled(['number', 'customer', 'branch_id']))
                                <a href="{{ route('laporan.piutang') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    @php 
                        $piutang = $inv['receivable_amount'] ?? ($inv['grand_total'] - $inv['paid']);
                    @endphp
                    <tr>
                        <td><span class="text-slate-400 font-mono text-xs">{{ ($invoices->currentPage() - 1) * $invoices->perPage() + $loop->iteration }}</span></td>
                        <td class="font-mono text-xs text-brand font-bold">{{ $inv['number'] }}</td>
                        <td class="font-medium text-slate-800">{{ $inv['customer'] }}</td>
                        <td class="text-slate-500 text-xs">{{ $inv['branch'] ?? '-' }}</td>
                        <td class="text-slate-500 text-xs">{{ date('d/m/Y', strtotime($inv['date'])) }}</td>
                        <td class="text-right font-mono">Rp {{ number_format($inv['grand_total']) }}</td>
                        <td class="text-right font-mono text-green-700">Rp {{ number_format($inv['paid']) }}</td>
                        <td class="text-right font-mono font-semibold text-red-600">Rp {{ number_format($piutang) }}</td>
                        <td><span class="{{ $inv['status']==='dp' ? 'badge-warning' : 'badge-danger' }} text-xs">{{ $inv['status']==='dp' ? 'DP' : 'Belum Bayar' }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            Tidak ada data piutang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($invoices->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection

