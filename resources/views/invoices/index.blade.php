@extends('layouts.app')
@section('title', 'Invoice')
@section('page-title', 'Daftar Invoice')

@section('content')
@php
    $sortBy = request('sort_by', 'date');
    $sortDir = request('sort_dir', 'desc');

    $sortUrl = function($column) use ($sortBy, $sortDir) {
        $dir = 'asc';
        if ($sortBy === $column && $sortDir === 'asc') {
            $dir = 'desc';
        }
        return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_dir' => $dir, 'page' => 1]);
    };

    $sortIcon = function($column) use ($sortBy, $sortDir) {
        $isCurrent = $sortBy === $column;
        $isAsc = $isCurrent && $sortDir === 'asc';
        $isDesc = $isCurrent && $sortDir === 'desc';
        
        return '
            <div class="flex flex-col ml-1.5 opacity-40 group-hover:opacity-100 transition-opacity">
                <svg class="w-2 h-2 ' . ($isAsc ? 'text-brand opacity-100' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h16l-8-8z"/></svg>
                <svg class="w-2 h-2 ' . ($isDesc ? 'text-brand opacity-100' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M12 20l8-8H4l8 8z"/></svg>
            </div>
        ';
    };
    $isSuperAdmin = \App\Helpers\AuthHelper::isSuperAdmin();
    $userBranchName = session('user')['branch']['name'] ?? 'Cabang Saya';
@endphp

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title text-2xl font-bold text-slate-900">Daftar Invoice</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola transaksi penjualan & status produksi</p>
    </div>
    @if(\App\Helpers\MenuHelper::hasPermission('invoice', 'create'))
    <a href="{{ route('invoice.create') }}" class="btn btn-primary whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Buat Invoice Baru</span>
    </a>
    @endif
</div>

<div class="card overflow-hidden">
    <form action="{{ route('invoice.index') }}" method="GET">
        {{-- Preserve sorting state --}}
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th class="w-40">
                            <a href="{{ $sortUrl('number') }}" class="flex items-center group">
                                <span>No. Invoice</span>
                                {!! $sortIcon('number') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortUrl('customer') }}" class="flex items-center group">
                                <span>Pelanggan</span>
                                {!! $sortIcon('customer') !!}
                            </a>
                        </th>
                        <th class="w-40">
                            <a href="{{ $sortUrl('grand_total') }}" class="flex items-center group">
                                <span>Total</span>
                                {!! $sortIcon('grand_total') !!}
                            </a>
                        </th>
                        <th class="w-32">
                            <a href="{{ $sortUrl('status') }}" class="flex items-center group">
                                <span>Bayar</span>
                                {!! $sortIcon('status') !!}
                            </a>
                        </th>
                        <th class="w-32">
                            <a href="{{ $sortUrl('production_status') }}" class="flex items-center group">
                                <span>Produksi</span>
                                {!! $sortIcon('production_status') !!}
                            </a>
                        </th>
                        <th class="w-32 text-center">Aksi</th>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="number" value="{{ request('number') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Cari no...">
                        </th>
                        <th class="py-2 px-4 shadow-inner">
                            @if($isSuperAdmin)
                                <input type="text" name="customer" value="{{ request('customer') }}"
                                    class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                    placeholder="Cari pelanggan/cabang...">
                            @else
                                <input type="text" name="customer" value="{{ request('customer') }}"
                                    class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                    placeholder="Cari pelanggan...">
                                <input type="hidden" name="branch_id" value="{{ session('branch_id') }}">
                            @endif
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="status" value="{{ request('status') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Status...">
                        </th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="production_status" value="{{ request('production_status') }}" 
                                class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                placeholder="Produksi...">
                        </th>
                        <th class="py-2 px-4 shadow-inner text-center">
                            <button type="submit" class="hidden"></button>
                            @if(request()->anyFilled(['number', 'customer', 'status', 'production_status']))
                                <a href="{{ route('invoice.index') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $item)
                        <tr>
                            <td><span class="text-slate-400 font-mono text-xs">{{ ($invoices->currentPage() - 1) * $invoices->perPage() + $loop->iteration }}</span></td>
                            <td class="font-mono text-xs font-bold text-slate-900">{{ $item['number'] }}</td>
                            <td>
                                <div class="font-semibold text-slate-900">{{ $item['customer'] }}</div>
                                <div class="text-[10px] text-slate-400 capitalize">{{ $item['branch'] }}</div>
                            </td>
                            <td class="font-bold">Rp {{ number_format($item['grand_total'], 0, ',', '.') }}</td>
                            <td>
                                @if($item['status'] === 'lunas')
                                    <span class="badge badge-success">Lunas</span>
                                @elseif($item['status'] === 'dp')
                                    <span class="badge badge-warning">DP</span>
                                @else
                                    <span class="badge badge-danger">Belum</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ in_array($item['production_status'], ['selesai']) ? 'badge-success' : 'badge-neutral' }}">
                                    {{ ucfirst($item['production_status']) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('invoice.show', $item['id']) }}" 
                                       class="p-1.5 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all"
                                       title="Lihat Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    
                                    @if(\App\Helpers\MenuHelper::hasPermission('invoice', 'edit'))
                                    <a href="{{ route('invoice.edit', $item['id']) }}" 
                                       class="p-1.5 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @endif

                                    @if(\App\Helpers\MenuHelper::hasPermission('invoice', 'delete'))
                                    <button type="button" 
                                            @click="confirmModal.confirm('{{ route('invoice.destroy', $item['id']) }}')"
                                            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                            title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Tidak ada data ditemukan.
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

