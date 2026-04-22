@extends('layouts.app')
@section('title', 'KPI Sales')
@section('page-title', 'Laporan KPI Sales')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div><h1 class="page-title">KPI Sales</h1><p class="text-sm text-slate-500 mt-1">Performa & pencapaian target sales</p></div>
    <a href="{{ route('laporan.export.kpi-sales', request()->query()) }}" class="btn-outline">Export Excel</a>
</div>
<div class="card card-body mb-5 grid grid-cols-1 md:grid-cols-5 gap-3">
    <div>
        <label class="form-label">Periode</label>
        <select name="period_type" form="kpi-filter-form" class="form-select">
            <option value="monthly" {{ request('period_type', 'monthly') === 'monthly' ? 'selected' : '' }}>Bulanan</option>
            <option value="yearly" {{ request('period_type') === 'yearly' ? 'selected' : '' }}>Tahunan</option>
        </select>
    </div>
    <div>
        <label class="form-label">Tahun</label>
        <input type="number" name="year" form="kpi-filter-form" class="form-input" value="{{ request('year', now()->year) }}">
    </div>
    <div>
        <label class="form-label">Bulan</label>
        <select name="month" form="kpi-filter-form" class="form-select" {{ request('period_type', 'monthly') === 'yearly' ? 'disabled' : '' }}>
            @foreach(range(1, 12) as $month)
                <option value="{{ $month }}" {{ (int) request('month', now()->month) === $month ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create(now()->year, $month, 1)->translatedFormat('F') }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label">Cabang</label>
        <select name="branch_id" form="kpi-filter-form" class="form-select">
            <option value="">Semua Cabang</option>
            @foreach($branches as $branch)
                <option value="{{ $branch['id'] }}" {{ (string) request('branch_id') === (string) $branch['id'] ? 'selected' : '' }}>{{ $branch['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" form="kpi-filter-form" class="btn-primary">Terapkan</button>
        @if(request()->anyFilled(['period_type', 'year', 'month', 'branch_id', 'name']))
            <a href="{{ route('laporan.kpi-sales') }}" class="btn-outline">Reset</a>
        @endif
    </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <div class="stat-card"><div class="stat-icon bg-orange-100"><svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div><div><p class="text-xs text-slate-500">Total Invoice</p><p class="text-2xl font-bold">{{ number_format($summary['total_invoice'] ?? 0) }}</p></div></div>
    <div class="stat-card"><div class="stat-icon bg-green-100"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div><p class="text-xs text-slate-500">Total Omzet</p><p class="text-2xl font-bold">Rp {{ number_format($summary['total_omzet'] ?? 0) }}</p></div></div>
    <div class="stat-card"><div class="stat-icon bg-sky-100"><svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.5l6-6 4 4 8-8M21 21H3"/></svg></div><div><p class="text-xs text-slate-500">Target {{ $summary['period_label'] ?? '' }}</p><p class="text-2xl font-bold">Rp {{ number_format($summary['target'] ?? 0) }}</p></div></div>
</div>
<div class="card overflow-hidden">
    <form id="kpi-filter-form" action="{{ route('laporan.kpi-sales') }}" method="GET">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th>Sales</th>
                        <th>Cabang</th>
                        <th class="text-right">Total Invoice</th>
                        <th class="text-right">Total Omzet</th>
                        <th class="text-right">Pencapaian</th>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="name" value="{{ request('name') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari sales...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner text-center">
                            <button type="submit" class="hidden"></button>
                            @if(request()->anyFilled(['name', 'period_type', 'year', 'month', 'branch_id']))
                                <a href="{{ route('laporan.kpi-sales') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $item)
                    <tr>
                        <td><span class="text-slate-400 font-mono text-xs">{{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}</span></td>
                        <td class="font-medium text-slate-900">{{ $item['name'] }}</td>
                        <td class="text-slate-500">{{ $item['branch'] ?? '-' }}</td>
                        <td class="text-right font-mono text-slate-600">{{ number_format($item['total_invoice'] ?? 0) }}</td>
                        <td class="text-right font-mono font-semibold">Rp {{ number_format($item['total_omzet'] ?? 0) }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-brand h-full" style="width: {{ min(($item['achievement_pct'] ?? 0), 100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-slate-700">{{ number_format(($item['achievement_pct'] ?? 0), 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            Tidak ada data sales ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($sales->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $sales->links() }}
        </div>
    @endif
</div>
@endsection

