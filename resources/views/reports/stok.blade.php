@extends('layouts.app')
@section('title', 'Stok & Pergerakan')
@section('page-title', 'Laporan Stok & Pergerakan')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div><h1 class="page-title">Stok & Pergerakan</h1><p class="text-sm text-slate-500 mt-1">Inventaris plat & riwayat stock movement</p></div>
    <a href="{{ route('laporan.export.stok', request()->query()) }}" class="btn-outline">Export Excel</a>
</div>
<div class="card overflow-hidden">
    <form action="{{ route('laporan.stok') }}" method="GET">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th>Cabang</th>
                        <th>Nama Plat</th>
                        <th>Tipe</th>
                        <th>Ukuran</th>
                        <th class="text-right">Harga</th>
                        <th class="text-right">Stok</th>
                        <th class="text-right">Min. Stok</th>
                        <th>Status</th>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <select name="branch_id" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all">
                                <option value="">Semua cabang</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch['id'] }}" {{ (string) request('branch_id') === (string) $branch['id'] ? 'selected' : '' }}>{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="name" value="{{ request('name') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari nama...">
                        </th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="type" value="{{ request('type') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari tipe...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner text-center">
                            <button type="submit" class="hidden"></button>
                            @if(request()->anyFilled(['name', 'type', 'branch_id']))
                                <a href="{{ route('laporan.stok') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plates as $p)
                    <tr>
                        <td><span class="text-slate-400 font-mono text-xs">{{ ($plates->currentPage() - 1) * $plates->perPage() + $loop->iteration }}</span></td>
                        <td class="text-slate-500 text-xs">{{ $p['branch'] ?? '-' }}</td>
                        <td class="font-semibold text-slate-800">{{ $p['name'] }}</td>
                        <td><span class="badge-info text-xs">{{ $p['type'] }}</span></td>
                        <td class="text-slate-500 text-xs">{{ $p['size'] }}</td>
                        <td class="text-right font-mono text-xs">Rp {{ number_format($p['price']) }}</td>
                        <td class="text-right font-bold text-sm">{{ $p['stock'] }} {{ $p['unit'] }}</td>
                        <td class="text-right font-mono text-xs">{{ number_format($p['minimum_stock'] ?? 0) }}</td>
                        <td>
                            @if(($p['status'] ?? '') === 'Habis')<span class="badge-danger text-xs">Habis</span>
                            @elseif(($p['status'] ?? '') === 'Minimum')<span class="badge-warning text-xs">Minimum</span>
                            @else<span class="badge-success text-xs">Aman</span>@endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            Tidak ada data stok ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($plates->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $plates->links() }}
        </div>
    @endif
</div>
@endsection

