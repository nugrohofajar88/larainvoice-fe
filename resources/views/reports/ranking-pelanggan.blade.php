@php
// Generic report layout macro
$pageTitle = $pageTitle ?? 'Laporan';
$subtitle  = $subtitle  ?? '';
@endphp
@extends('layouts.app')
@section('title', 'Ranking Pelanggan')
@section('page-title', 'Laporan Ranking Pelanggan')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div><h1 class="page-title">Ranking Pelanggan</h1><p class="text-sm text-slate-500 mt-1">Perbandingan ranking bulan lalu & berjalan</p></div>
    <a href="{{ route('laporan.export.ranking-pelanggan', request()->query()) }}" class="btn-outline"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Export Excel</a>
</div>
<div class="card card-body mb-5 grid grid-cols-2 md:grid-cols-4 gap-3">
    <div><label class="form-label">Cabang</label><select class="form-select"><option>Semua</option></select></div>
    <div><label class="form-label">Bulan</label><input type="month" class="form-input" value="{{ date('Y-m') }}"></div>
</div>
<div class="card overflow-hidden">
    <form action="{{ route('laporan.ranking-pelanggan') }}" method="GET">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th>Pelanggan</th>
                        <th>Cabang</th>
                        <th class="text-center">Ranking</th>
                        <th class="text-right">Trend</th>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="name" value="{{ request('name') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari pelanggan...">
                        </th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="branch" value="{{ request('branch') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari cabang...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner text-center">
                            <button type="submit" class="hidden"></button>
                            @if(request()->anyFilled(['name', 'branch']))
                                <a href="{{ route('laporan.ranking-pelanggan') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $item)
                    <tr>
                        <td><span class="text-slate-400 font-mono text-xs">{{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}</span></td>
                        <td class="font-medium text-slate-900">{{ $item['name'] }}</td>
                        <td class="text-slate-500">{{ $item['branch'] }}</td>
                        <td class="text-center">
                            <div class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 font-bold text-slate-700">
                                {{ $item['ranking_now'] }}
                            </div>
                        </td>
                        <td class="text-right">
                            @if($item['ranking_now'] < $item['ranking_last'])
                                <span class="text-green-600 font-bold text-xs flex items-center justify-end gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                    Naik
                                </span>
                            @elseif($item['ranking_now'] > $item['ranking_last'])
                                <span class="text-red-500 font-bold text-xs flex items-center justify-end gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                    Turun
                                </span>
                            @else
                                <span class="text-slate-400 text-xs">â€”</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            Tidak ada data ranking ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($customers->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection

