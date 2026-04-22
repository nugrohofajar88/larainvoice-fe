@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
@php
    $generatedAt = $generatedAt ?? null;
    $cacheTtlSeconds = $cacheTtlSeconds ?? null;
@endphp

<div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="text-sm text-slate-500 mt-1">
            Selamat datang, <span class="font-semibold text-slate-700">{{ session('user_name') }}</span> -
            {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
        </p>
        <p class="text-xs text-slate-400 mt-2">
            Menampilkan data untuk
            <span class="font-semibold text-slate-600">{{ $selectedBranch['city'] ?? 'Semua Cabang' }}</span>
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2 text-sm">
        @if(\App\Helpers\AuthHelper::isSuperAdmin())
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 text-sm">
                <span class="text-slate-500">Tampil:</span>
                <select name="branch_id" class="form-select w-44" onchange="this.form.submit()">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch['id'] }}" {{ (string) $selectedBranchId === (string) $branch['id'] ? 'selected' : '' }}>
                            {{ $branch['city'] }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif

        <a
            href="{{ route('dashboard', array_filter(['branch_id' => $selectedBranchId, 'refresh' => 1], fn ($value) => $value !== null && $value !== '')) }}"
            class="btn btn-outline btn-sm"
        >
            Refresh Data
        </a>
    </div>
</div>

<div class="grid grid-cols-1 gap-3 mb-6 md:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Pelanggan Aktif</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['pelanggan_aktif']) }}</p>
        <p class="mt-1 text-xs text-slate-500">Pelanggan yang bertransaksi di periode berjalan.</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Cakupan Data</p>
        <p class="mt-2 text-lg font-bold text-slate-900">{{ $selectedBranch['name'] ?? 'Semua Cabang' }}</p>
        <p class="mt-1 text-xs text-slate-500">Mode tampilan dashboard untuk cabang yang sedang dipilih.</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Sinkronisasi</p>
        <p class="mt-2 text-lg font-bold text-slate-900">
            {{ $generatedAt ? \Carbon\Carbon::parse($generatedAt)->timezone(config('app.timezone'))->format('d M Y H:i') : '-' }}
        </p>
        <p class="mt-1 text-xs text-slate-500">
            Data dashboard di-cache {{ $cacheTtlSeconds ? (int) ($cacheTtlSeconds / 60) : 1 }} menit.
        </p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <div class="stat-card group hover:shadow-md transition-shadow">
        <div class="stat-icon bg-orange-100 group-hover:bg-brand group-hover:shadow-lg group-hover:shadow-brand/20 transition-all">
            <svg class="w-6 h-6 text-brand group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-xs text-slate-500 font-medium mb-1">Invoice Bulan Ini</p>
            <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_invoice_bulan_ini']) }}</p>
            <div class="flex items-center gap-1 mt-1">
                <span class="badge-success text-xs">Lunas: {{ $stats['invoice_lunas'] }}</span>
                <span class="badge-warning text-xs">DP: {{ $stats['invoice_dp'] }}</span>
            </div>
        </div>
    </div>

    <div class="stat-card group hover:shadow-md transition-shadow">
        <div class="stat-icon bg-green-100 group-hover:bg-green-500 group-hover:shadow-lg group-hover:shadow-green-500/20 transition-all">
            <svg class="w-6 h-6 text-green-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-xs text-slate-500 font-medium mb-1">Omzet Bulan Ini</p>
            <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($stats['total_omzet_bulan_ini'] / 1000000, 1) }}jt</p>
            <div class="flex items-center gap-1 mt-1 text-xs text-green-600 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                Nilai mengikuti filter cabang
            </div>
        </div>
    </div>

    <div class="stat-card group hover:shadow-md transition-shadow">
        <div class="stat-icon bg-red-100 group-hover:bg-red-500 group-hover:shadow-lg group-hover:shadow-red-500/20 transition-all">
            <svg class="w-6 h-6 text-red-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-xs text-slate-500 font-medium mb-1">Total Piutang</p>
            <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($stats['total_piutang'] / 1000000, 2) }}jt</p>
            <div class="mt-1">
                <span class="badge-danger">{{ $stats['invoice_belum_bayar'] }} belum bayar</span>
            </div>
        </div>
    </div>

    <div class="stat-card group hover:shadow-md transition-shadow">
        <div class="stat-icon bg-sky-100 group-hover:bg-sky-500 group-hover:shadow-lg group-hover:shadow-sky-500/20 transition-all">
            <svg class="w-6 h-6 text-sky-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-xs text-slate-500 font-medium mb-1">Produksi Berjalan</p>
            <p class="text-2xl font-bold text-slate-900">{{ $stats['produksi_berjalan'] }}</p>
            <div class="mt-1">
                <span class="badge-info">Aktif saat ini</span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">
    <div class="card xl:col-span-2">
        <div class="card-header">
            <div>
                <h2 class="font-semibold text-slate-800">Grafik Omzet</h2>
                <p class="text-xs text-slate-500 mt-0.5">6 bulan terakhir</p>
            </div>
            <span class="badge-brand">Rp {{ number_format($stats['total_omzet_bulan_ini'] / 1000000, 1) }}jt {{ now()->translatedFormat('M') }}</span>
        </div>
        <div class="card-body">
            <canvas id="revenueChart" class="max-h-64"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="font-semibold text-slate-800">Status Produksi</h2>
                <p class="text-xs text-slate-500 mt-0.5">Distribusi seluruh status, sinkron dengan List Penjualan</p>
            </div>
        </div>
        <div class="card-body">
            <canvas id="prodChart" class="max-h-52 mx-auto"></canvas>
            <div class="mt-4 space-y-2">
                @foreach([
                    ['label' => 'Pending', 'val' => $prodChart['pending'], 'color' => 'bg-amber-500', 'tab' => 'pending'],
                    ['label' => 'In-progress', 'val' => $prodChart['in_process'], 'color' => 'bg-sky-500', 'tab' => 'in-process'],
                    ['label' => 'Completed', 'val' => $prodChart['completed'], 'color' => 'bg-green-500', 'tab' => 'completed'],
                    ['label' => 'Cancelled', 'val' => $prodChart['cancelled'], 'color' => 'bg-rose-500', 'tab' => 'cancelled'],
                ] as $item)
                    <a
                        href="{{ route('sales-list.index', ['tab' => $item['tab']]) }}"
                        class="flex items-center justify-between rounded-xl px-2 py-2 text-sm transition hover:bg-slate-50"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full {{ $item['color'] }}"></span>
                            <span class="text-slate-600">{{ $item['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-800">{{ $item['val'] }}</span>
                            <span class="text-xs text-slate-400">Buka</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="card xl:col-span-2">
        <div class="card-header">
            <h2 class="font-semibold text-slate-800">Invoice Terbaru</h2>
            <a href="{{ route('invoice.index') }}" class="text-sm text-brand hover:text-brand-dark font-medium">Lihat Semua -></a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>No Invoice</th>
                        <th>Pelanggan</th>
                        <th class="hidden md:table-cell">Cabang</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('invoice.show', $inv['id']) }}" class="text-brand hover:text-brand-dark font-medium text-xs">
                                    {{ $inv['number'] }}
                                </a>
                            </td>
                            <td class="font-medium text-slate-800">{{ $inv['customer'] }}</td>
                            <td class="hidden md:table-cell text-slate-500">{{ $inv['branch'] }}</td>
                            <td class="font-semibold">Rp {{ number_format($inv['grand_total']) }}</td>
                            <td>
                                @php
                                    $statusMap = [
                                        'lunas' => ['label' => 'Lunas', 'class' => 'badge-success'],
                                        'dp' => ['label' => 'DP', 'class' => 'badge-warning'],
                                        'belum-bayar' => ['label' => 'Belum Bayar', 'class' => 'badge-danger'],
                                    ];
                                    $s = $statusMap[$inv['status']] ?? ['label' => $inv['status'], 'class' => 'badge-neutral'];
                                @endphp
                                <span class="{{ $s['class'] }}">{{ $s['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">
                                Belum ada invoice untuk cabang yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="font-semibold text-slate-800">Top Pelanggan</h2>
            <a href="{{ route('laporan.ranking-pelanggan') }}" class="text-sm text-brand hover:text-brand-dark font-medium">Lihat -></a>
        </div>
        <div class="card-body pt-0">
            <div class="space-y-3">
                @forelse($customers as $i => $cust)
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold shrink-0 {{ $i === 0 ? 'bg-yellow-100 text-yellow-700' : ($i === 1 ? 'bg-slate-100 text-slate-600' : 'bg-orange-50 text-orange-600') }}">
                            {{ $i + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $cust['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $cust['branch'] }}</p>
                            <p class="text-xs font-medium text-slate-400 mt-1">Rp {{ number_format($cust['total_amount'] ?? 0) }}</p>
                        </div>
                        <div class="text-right">
                            @if($cust['ranking_now'] < $cust['ranking_last'])
                                <span class="text-green-600 text-xs">Naik</span>
                            @elseif($cust['ranking_now'] > $cust['ranking_last'])
                                <span class="text-red-500 text-xs">Turun</span>
                            @else
                                <span class="text-slate-400 text-xs">Tetap</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 py-6 text-center">
                        Belum ada pelanggan untuk cabang yang dipilih.
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const revenueChartEl = document.getElementById('revenueChart');

if (revenueChartEl) {
    const rCtx = revenueChartEl.getContext('2d');
    new Chart(rCtx, {
        type: 'bar',
        data: {
            labels: @json($revenueChart['labels']),
            datasets: [{
                label: 'Omzet (Rp)',
                data: @json($revenueChart['data']),
                backgroundColor: 'rgba(249,115,22,0.15)',
                borderColor: 'rgba(249,115,22,1)',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.parsed.y)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: (val) => 'Rp ' + (val / 1000000).toFixed(0) + 'jt',
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
}

const productionChartEl = document.getElementById('prodChart');

if (productionChartEl) {
    const pCtx = productionChartEl.getContext('2d');
    new Chart(pCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In-progress', 'Completed', 'Cancelled'],
            datasets: [{
                data: [
                    {{ $prodChart['pending'] }},
                    {{ $prodChart['in_process'] }},
                    {{ $prodChart['completed'] }},
                    {{ $prodChart['cancelled'] }}
                ],
                backgroundColor: ['#F59E0B', '#38BDF8', '#22C55E', '#F43F5E'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            cutout: '70%'
        }
    });
}
</script>
@endpush
