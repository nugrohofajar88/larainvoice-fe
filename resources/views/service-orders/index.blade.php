@extends('layouts.app')
@section('title', 'Order Jasa')
@section('page-title', 'Order Jasa')

@section('content')
@php
    $sortBy = request('sort_by', 'id');
    $sortDir = request('sort_dir', 'desc');
    $isSuperAdmin = \App\Helpers\AuthHelper::isSuperAdmin();
    $userBranchId = session('branch_id');
    $defaultBranchId = $isSuperAdmin ? '' : (string) $userBranchId;

    $sortUrl = function ($column) use ($sortBy, $sortDir) {
        $dir = 'asc';
        if ($sortBy === $column && $sortDir === 'asc') { $dir = 'desc'; }
        return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_dir' => $dir, 'page' => 1]);
    };

    $sortIcon = function ($column) use ($sortBy, $sortDir) {
        $isAsc = $sortBy === $column && $sortDir === 'asc';
        $isDesc = $sortBy === $column && $sortDir === 'desc';
        return '<div class="flex flex-col ml-1.5 opacity-40 group-hover:opacity-100 transition-opacity">
            <svg class="w-2 h-2 ' . ($isAsc ? 'text-brand opacity-100' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h16l-8-8z"/></svg>
            <svg class="w-2 h-2 ' . ($isDesc ? 'text-brand opacity-100' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M12 20l8-8H4l8 8z"/></svg>
        </div>';
    };

    $branchOptions = collect($branches ?? [])->map(fn ($branch) => [
        'id' => $branch['id'],
        'name' => $branch['name'] ?? 'Cabang',
    ])->values();

    $statusLabels = [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'invoiced' => 'Invoiced',
        'cancelled' => 'Cancelled',
    ];
    $editableStatusLabels = collect($statusLabels)->except(['invoiced'])->all();

    $statusBadgeStyles = [
        'draft' => 'badge-neutral',
        'confirmed' => 'badge-info',
        'in_progress' => 'badge-warning',
        'completed' => 'badge-success',
        'invoiced' => 'badge-success',
        'cancelled' => 'badge-danger',
    ];

    $typeLabels = [
        'service' => 'Servis',
        'training' => 'Pelatihan',
    ];

    $typeBadgeStyles = [
        'service' => 'badge-info',
        'training' => 'badge-warning',
    ];

    $statusCounts = collect($statusSummary ?? []);
    $typeCounts = collect($typeSummary ?? []);
    $selectedStatus = request('status', '');
    $selectedType = request('order_type', '');
@endphp

<div
    x-data="serviceOrderPage({
        branches: {{ \Illuminate\Support\Js::from($branchOptions) }},
        isSuperAdmin: {{ $isSuperAdmin ? 'true' : 'false' }},
        defaultBranchId: {{ \Illuminate\Support\Js::from($defaultBranchId) }},
        canCreate: {{ \App\Helpers\MenuHelper::hasPermission('service-order', 'create') ? 'true' : 'false' }},
        canEdit: {{ \App\Helpers\MenuHelper::hasPermission('service-order', 'edit') ? 'true' : 'false' }},
        canDelete: {{ \App\Helpers\MenuHelper::hasPermission('service-order', 'delete') ? 'true' : 'false' }},
        canDetail: {{ \App\Helpers\MenuHelper::hasPermission('service-order', 'detail') ? 'true' : 'false' }},
    })"
    x-init="init()"
    x-cloak
>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">Order Jasa</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola order servis dan pelatihan. Harga final ditentukan saat invoice diterbitkan.</p>
        </div>

        @if(\App\Helpers\MenuHelper::hasPermission('service-order', 'create'))
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = !open" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Buat Order Jasa</span>
                <svg class="w-3 h-3 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition class="absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-lg shadow-xl z-20" style="display:none">
                <button type="button" onclick="window.openServiceOrderModal && window.openServiceOrderModal('service'); return false;" @click="open = false" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 rounded-t-lg text-sm font-medium text-slate-700">
                    Servis
                </button>
                <button type="button" onclick="window.openServiceOrderModal && window.openServiceOrderModal('training'); return false;" @click="open = false" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 rounded-b-lg text-sm font-medium text-slate-700">
                    Pelatihan
                </button>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-2.5 mb-5">
        <button type="button" onclick="setFilter('status','')" class="card relative overflow-hidden border px-3.5 py-2.5 text-left transition-all {{ $selectedStatus === '' ? 'border-brand bg-brand/5 shadow-lg shadow-brand/10' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/70' }}">
            <span class="absolute inset-x-0 top-0 h-0.5 {{ $selectedStatus === '' ? 'bg-brand' : 'bg-slate-100' }}"></span>
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Semua</p>
                    @if($selectedStatus === '')
                        <span class="inline-flex items-center rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Aktif</span>
                    @endif
                </div>
                <p class="text-xl font-black leading-none text-slate-900">{{ (int) $statusCounts->sum() }}</p>
            </div>
        </button>
        @foreach($statusLabels as $key => $label)
        <button type="button" onclick="setFilter('status','{{ $selectedStatus === $key ? '' : $key }}')" class="card relative overflow-hidden border px-3.5 py-2.5 text-left transition-all {{ $selectedStatus === $key ? 'border-brand bg-brand/5 shadow-lg shadow-brand/10' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/70' }}">
            <span class="absolute inset-x-0 top-0 h-0.5 {{ $selectedStatus === $key ? 'bg-brand' : 'bg-slate-100' }}"></span>
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</p>
                    @if($selectedStatus === $key)
                        <span class="inline-flex items-center rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Aktif</span>
                    @endif
                </div>
                <p class="text-xl font-black leading-none text-slate-900">{{ (int) ($statusCounts->get($key, 0)) }}</p>
            </div>
        </button>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <form action="{{ route('order-jasa.index') }}" method="GET" id="filterForm">
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
            <input type="hidden" name="status" value="{{ $selectedStatus }}">

            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">NO</th>
                            <th>
                                <a href="{{ $sortUrl('order_number') }}" class="flex items-center group">
                                    <span>NO. ORDER</span>{!! $sortIcon('order_number') !!}
                                </a>
                            </th>
                            <th>TIPE</th>
                            <th>PELANGGAN</th>
                            <th>JUDUL</th>
                            <th>
                                <a href="{{ $sortUrl('order_date') }}" class="flex items-center group">
                                    <span>TANGGAL</span>{!! $sortIcon('order_date') !!}
                                </a>
                            </th>
                            <th>STATUS</th>
                            <th class="w-24 text-center">AKSI</th>
                        </tr>
                        <tr class="bg-slate-50/50">
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..."
                                    class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none"
                                    onchange="document.getElementById('filterForm').submit()">
                            </th>
                            <th class="py-2 px-4 shadow-inner">
                                <select name="order_type" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand outline-none">
                                    <option value="">Semua</option>
                                    <option value="service" {{ $selectedType === 'service' ? 'selected' : '' }}>Servis</option>
                                    <option value="training" {{ $selectedType === 'training' ? 'selected' : '' }}>Pelatihan</option>
                                </select>
                            </th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $index => $order)
                        <tr class="hover:bg-slate-50">
                            <td class="text-center text-slate-500">{{ ($orders->currentPage() - 1) * $orders->perPage() + $index + 1 }}</td>
                            <td class="font-mono text-xs font-semibold text-slate-800">{{ $order['order_number'] ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $typeBadgeStyles[$order['order_type']] ?? 'badge-neutral' }}">
                                    {{ $typeLabels[$order['order_type']] ?? ($order['order_type'] ?? '-') }}
                                </span>
                            </td>
                            <td>{{ $order['customer'] ?? '-' }}</td>
                            <td class="max-w-xs truncate" title="{{ $order['title'] ?? '' }}">{{ $order['title'] ?? '-' }}</td>
                            <td class="text-slate-600 text-sm">{{ $order['order_date'] ?? '-' }}</td>
                            <td>
                                @if(\App\Helpers\MenuHelper::hasPermission('service-order', 'edit'))
                                    <select
                                        class="w-full min-w-[10rem] text-xs font-semibold bg-white border border-slate-200 rounded-lg px-2.5 py-2 focus:ring-1 focus:ring-brand outline-none transition-all"
                                        @change="changeListStatus({{ $order['id'] }}, '{{ $order['status'] ?? 'draft' }}', $event)"
                                        {{ in_array($order['status'] ?? 'draft', ['invoiced', 'cancelled'], true) ? 'disabled' : '' }}
                                    >
                                        @foreach($editableStatusLabels as $key => $label)
                                            <option value="{{ $key }}" {{ ($order['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="badge {{ $statusBadgeStyles[$order['status']] ?? 'badge-neutral' }}">
                                        {{ $statusLabels[$order['status']] ?? ($order['status'] ?? '-') }}
                                    </span>
                                @endif
                                @if(!empty($order['has_invoice']))
                                    <span class="block text-[10px] text-green-600 mt-0.5">Invoice</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-1">
                                    @if(\App\Helpers\MenuHelper::hasPermission('service-order', 'detail'))
                                    <button type="button" @click="openDetail({{ $order['id'] }})" class="p-1.5 rounded text-slate-500 hover:text-brand hover:bg-slate-100" title="Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    @endif
                                    @if(\App\Helpers\MenuHelper::hasPermission('service-order', 'edit'))
                                    <button type="button" @click="openEdit({{ $order['id'] }})" class="p-1.5 rounded text-slate-500 hover:text-brand hover:bg-slate-100" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endif
                                    @if(\App\Helpers\MenuHelper::hasPermission('service-order', 'delete'))
                                    <button type="button" @click="destroy({{ $order['id'] }}, '{{ $order['order_number'] ?? '' }}')" class="p-1.5 rounded text-slate-500 hover:text-red-500 hover:bg-red-50" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-400">Belum ada data order jasa.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($orders->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $orders->withQueryString()->links() }}
        </div>
        @endif
    </div>

    <div
        x-show="fetchingOrder"
        class="fixed inset-0 z-[70] flex items-center justify-center px-4"
        style="display: none;"
        x-transition.opacity
    >
        <div class="absolute inset-0 bg-slate-950/45 backdrop-blur-[2px]"></div>
        <div class="relative w-full max-w-md rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-2xl">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand/10 text-brand">
                    <svg class="h-7 w-7 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900" x-text="fetchingMode === 'detail' ? 'Membuka detail order jasa' : 'Membuka form edit order jasa'"></h3>
                    <p class="mt-1 text-sm text-slate-500">Data sedang diambil dari backend. Mohon tunggu sebentar.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ==== Modal Create/Edit/Detail ==== --}}
    <div x-show="modalOpen" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" style="display:none">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click.outside="closeModal()" class="bg-white rounded-xl shadow-2xl w-full max-w-4xl my-8 max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900" x-text="modalTitle"></h3>
                        <p class="text-xs text-slate-500 mt-0.5" x-text="modalSubtitle"></p>
                    </div>
                    <button @click="closeModal()" class="p-2 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <template x-if="loading">
                        <div class="py-16 text-center text-slate-400">Memuat...</div>
                    </template>

                    <form x-show="!loading" @submit.prevent="save()" x-ref="serviceOrderForm">

                        <div x-show="masterLoading" class="mb-4 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-700" style="display: none;">
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <div>
                                    <p class="font-semibold">Sedang mengambil data master</p>
                                    <p class="mt-1 text-xs text-blue-600">Pelanggan, petugas, dan komponen sedang dimuat. Silakan tunggu sebentar.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <template x-if="isSuperAdmin && !detailMode">
                                <div>
                                    <label class="form-label">Cabang <span class="text-red-500">*</span></label>
                                    <div class="relative" x-data="{ open: false, query: '' }" @click.outside="open = false">
                                        <button
                                            type="button"
                                            @click="if (editMode) return; open = !open; if (open) { query = ''; $nextTick(() => $refs.branchSearch?.focus()); }"
                                            class="w-full form-input flex items-center justify-between gap-3 text-left"
                                            :class="open ? 'border-brand ring-1 ring-brand' : ''"
                                            :disabled="editMode"
                                        >
                                            <span class="truncate" x-text="branchById(formData.branch_id)?.name || 'Pilih cabang'"></span>
                                            <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" x-transition class="absolute z-40 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-2xl overflow-hidden" style="display:none;">
                                            <div class="p-3 border-b border-slate-100">
                                                <input x-ref="branchSearch" x-model="query" type="text" class="form-input" placeholder="Cari cabang...">
                                            </div>
                                            <div class="max-h-64 overflow-y-auto">
                                                <template x-for="branch in searchOptions(branches, query, ['name'])" :key="branch.id">
                                                    <button
                                                        type="button"
                                                        @click="formData.branch_id = String(branch.id); open = false; query = ''; handleBranchChange();"
                                                        class="w-full px-4 py-3 text-left hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0"
                                                    >
                                                        <div class="font-medium text-slate-800" x-text="branch.name"></div>
                                                    </button>
                                                </template>
                                                <div x-show="searchOptions(branches, query, ['name']).length === 0" class="px-4 py-6 text-sm text-slate-400 text-center">
                                                    Tidak ada cabang yang cocok.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div :class="!isSuperAdmin || detailMode ? 'md:col-span-2' : ''">
                                <label class="form-label">Status <span class="text-red-500">*</span></label>
                                <template x-if="detailMode">
                                    <div class="form-input bg-slate-50 text-slate-700 font-semibold">
                                        <span x-text="statusLabel(detailData.status || formData.status)"></span>
                                    </div>
                                </template>
                                <template x-if="!detailMode">
                                    <select x-model="formData.status" class="form-input" :disabled="detailMode || !canEditStatusField()">
                                        <template x-for="status in formStatusOptions()" :key="status.value">
                                            <option :value="status.value" x-text="status.label"></option>
                                        </template>
                                    </select>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">Pelanggan <span class="text-red-500">*</span></label>
                                <div class="relative" x-data="{ open: false, query: '' }" @click.outside="open = false">
                                    <button
                                        type="button"
                                        @click="if (detailMode || (isSuperAdmin && !formData.branch_id)) return; open = !open; if (open) { query = ''; $nextTick(() => $refs.customerSearch?.focus()); }"
                                        class="w-full form-input flex items-center justify-between gap-3 text-left"
                                        :class="open ? 'border-brand ring-1 ring-brand' : ''"
                                        :disabled="detailMode || (isSuperAdmin && !formData.branch_id)"
                                    >
                                        <span class="truncate" x-text="customerById(formData.customer_id)?.full_name || (masterLoading ? 'Sedang memuat pelanggan...' : (isSuperAdmin && !formData.branch_id ? 'Pilih cabang terlebih dahulu' : 'Pilih pelanggan'))"></span>
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-transition class="absolute z-40 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-2xl overflow-hidden" style="display:none;">
                                        <div class="p-3 border-b border-slate-100">
                                            <input x-ref="customerSearch" x-model="query" type="text" class="form-input" placeholder="Cari pelanggan...">
                                        </div>
                                        <div class="max-h-64 overflow-y-auto">
                                            <template x-for="customer in searchOptions(filteredCustomers(), query, ['full_name'])" :key="customer.id">
                                                <button
                                                    type="button"
                                                    @click="formData.customer_id = String(customer.id); open = false; query = '';"
                                                    class="w-full px-4 py-3 text-left hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0"
                                                >
                                                    <div class="font-medium text-slate-800" x-text="customer.full_name"></div>
                                                </button>
                                            </template>
                                            <div x-show="searchOptions(filteredCustomers(), query, ['full_name']).length === 0" class="px-4 py-6 text-sm text-slate-400 text-center">
                                                Tidak ada pelanggan yang cocok.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" x-model="formData.customer_id" required>
                            </div>
                            <div>
                                <label class="form-label">Tanggal Order <span class="text-red-500">*</span></label>
                                <input type="date" x-model="formData.order_date" :disabled="detailMode" class="form-input" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">Tipe Order</label>
                                <div class="form-input bg-slate-50 text-slate-700 font-semibold">
                                    <span x-text="typeLabel(formData.order_type)"></span>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">
                                    <span x-text="formData.order_type === 'training' ? 'Nama Pelatihan' : 'Judul Pekerjaan / Nama Servis'"></span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" x-model="formData.title" :disabled="detailMode" class="form-input" required maxlength="255">
                            </div>
                        </div>

                        <template x-if="formData.order_type === 'training'">
                            <div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="form-label">Kategori Pelatihan</label>
                                        <input type="text" x-model="formData.category" :disabled="detailMode" class="form-input" maxlength="255">
                                    </div>
                                    <div>
                                        <label class="form-label">Tempat Pelatihan</label>
                                        <input type="text" x-model="formData.location" :disabled="detailMode" class="form-input" maxlength="255">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="form-label">Tanggal Mulai</label>
                                        <input type="date" x-model="formData.planned_start_date" :disabled="detailMode" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Durasi (hari)</label>
                                        <input type="number" min="1" x-model.number="formData.duration_days" :disabled="detailMode" class="form-input">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="formData.order_type !== 'training'">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="form-label">Jenis Servis</label>
                                    <input type="text" x-model="formData.category" :disabled="detailMode" class="form-input" maxlength="255">
                                </div>
                                <div>
                                    <label class="form-label">Lokasi Pengerjaan</label>
                                    <input type="text" x-model="formData.location" :disabled="detailMode" class="form-input" maxlength="255">
                                </div>
                                <div>
                                    <label class="form-label">Tanggal Rencana Pengerjaan</label>
                                    <input type="date" x-model="formData.planned_start_date" :disabled="detailMode" class="form-input">
                                </div>
                            </div>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="detailMode || formData.actual_finish_date">
                            <div x-show="detailMode || formData.actual_finish_date">
                                <label class="form-label">Tanggal Selesai Aktual</label>
                                <input type="date" x-model="formData.actual_finish_date" :disabled="detailMode" class="form-input">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <span x-text="formData.order_type === 'training' ? 'Ringkasan Materi / Agenda' : 'Keluhan / Scope Pekerjaan'"></span>
                            </label>
                            <textarea x-model="formData.description" :disabled="detailMode" rows="3" class="form-input"></textarea>
                        </div>

                        {{-- Assignment --}}
                        <div class="mb-4 p-4 border border-slate-200 rounded-lg bg-slate-50/50">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-bold text-slate-800">Petugas Ditugaskan</h4>
                                <button type="button" x-show="!detailMode" @click="addAssignment()" class="btn btn-sm btn-outline">+ Tambah Petugas</button>
                            </div>
                            <template x-if="formData.assignments.length === 0">
                                <p class="text-xs text-slate-400 italic">Belum ada petugas ditugaskan.</p>
                            </template>
                            <template x-for="(a, idx) in formData.assignments" :key="idx">
                                <div class="grid grid-cols-12 gap-2 mb-2 items-start rounded-xl border border-slate-200 p-3 bg-white">
                                    <div class="col-span-12 md:col-span-5">
                                        <div class="relative" x-data="{ open: false, query: '' }" @click.outside="open = false">
                                            <button
                                                type="button"
                                                @click="if (detailMode || (isSuperAdmin && !formData.branch_id)) return; open = !open; if (open) { query = ''; $nextTick(() => $refs.userSearch?.focus()); }"
                                                class="w-full form-input flex items-center justify-between gap-3 text-left text-sm"
                                                :class="open ? 'border-brand ring-1 ring-brand' : ''"
                                                :disabled="detailMode || (isSuperAdmin && !formData.branch_id)"
                                            >
                                                <span class="truncate" x-text="userById(a.user_id)?.name || (masterLoading ? 'Sedang memuat petugas...' : (isSuperAdmin && !formData.branch_id ? 'Pilih cabang terlebih dahulu' : 'Pilih user'))"></span>
                                                <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="open" x-transition class="absolute z-40 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-2xl overflow-hidden" style="display:none;">
                                                <div class="p-3 border-b border-slate-100">
                                                    <input x-ref="userSearch" x-model="query" type="text" class="form-input" placeholder="Cari petugas...">
                                                </div>
                                                <div class="max-h-64 overflow-y-auto">
                                                    <template x-for="user in searchOptions(filteredUsers(), query, ['name', 'branch_name'])" :key="user.id">
                                                        <button
                                                            type="button"
                                                            @click="a.user_id = String(user.id); open = false; query = '';"
                                                            class="w-full px-4 py-3 text-left hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0"
                                                        >
                                                            <div class="font-medium text-slate-800" x-text="user.name"></div>
                                                            <div x-show="user.branch_name" class="text-xs text-slate-400 mt-0.5" x-text="user.branch_name"></div>
                                                        </button>
                                                    </template>
                                                    <div x-show="searchOptions(filteredUsers(), query, ['name', 'branch_name']).length === 0" class="px-4 py-6 text-sm text-slate-400 text-center">
                                                        Tidak ada petugas yang cocok.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" x-model="a.user_id" required>
                                    </div>
                                    <div class="col-span-12 md:col-span-3">
                                        <select x-model="a.role" :disabled="detailMode" class="form-input text-sm">
                                            <option value="">Peran</option>
                                            <option value="lead">Lead</option>
                                            <option value="teknisi">Teknisi</option>
                                            <option value="trainer">Trainer</option>
                                            <option value="helper">Helper</option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-3">
                                        <input type="text" x-model="a.notes" :disabled="detailMode" placeholder="Catatan (opsional)" class="form-input text-sm">
                                    </div>
                                    <div class="col-span-12 md:col-span-1 flex md:justify-center">
                                        <button type="button" x-show="!detailMode" @click="removeAssignment(idx)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 text-red-500 hover:bg-red-50 transition-all" title="Hapus petugas">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Components (service only) --}}
                        <template x-if="formData.order_type === 'service'">
                            <div class="mb-4 p-4 border border-slate-200 rounded-lg bg-slate-50/50">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800">Komponen Terpakai</h4>
                                        <p class="text-[11px] text-slate-500">Komponen yang dipakai saat servis. Harga final ditentukan saat invoice.</p>
                                    </div>
                                    <button type="button" x-show="!detailMode" @click="addComponent()" class="btn btn-sm btn-outline">+ Tambah Komponen</button>
                                </div>
                                <template x-if="formData.components.length === 0">
                                    <p class="text-xs text-slate-400 italic">Belum ada komponen tercatat.</p>
                                </template>
                                <template x-for="(c, idx) in formData.components" :key="idx">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start rounded-xl border border-slate-200 p-3 bg-white">
                                        <div class="md:col-span-5">
                                            <label class="block md:hidden text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Komponen</label>
                                            <select x-model="c.component_id" @change="onComponentChange(idx)" :disabled="detailMode" class="form-input text-sm">
                                                <option value="">Pilih komponen</option>
                                                <template x-for="comp in filteredComponents()" :key="comp.id">
                                                    <option :value="comp.id" x-text="comp.label || (comp.name + (comp.type_size ? ' - ' + comp.type_size : ''))"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block md:hidden text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Qty</label>
                                            <input type="number" min="1" x-model.number="c.qty" :disabled="detailMode" placeholder="Qty" class="form-input w-full h-10 text-center text-sm">
                                        </div>
                                        <div class="md:col-span-4">
                                            <label class="block md:hidden text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Catatan</label>
                                            <input type="text" x-model="c.notes" :disabled="detailMode" placeholder="Catatan" class="form-input text-sm">
                                        </div>
                                        <div class="md:col-span-1 flex md:justify-center md:pt-0 pt-1">
                                            <button type="button" x-show="!detailMode" @click="removeComponent(idx)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 text-red-500 hover:bg-red-50 transition-all" title="Hapus komponen">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">Catatan</label>
                                <textarea x-model="formData.notes" :disabled="detailMode" rows="2" class="form-input"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Catatan Internal</label>
                                <textarea x-model="formData.internal_notes" :disabled="detailMode" rows="2" class="form-input"></textarea>
                            </div>
                        </div>

                        <template x-if="detailMode && formData.completion_notes">
                            <div class="mb-4">
                                <label class="form-label">Catatan Penyelesaian</label>
                                <textarea x-model="formData.completion_notes" disabled rows="2" class="form-input"></textarea>
                            </div>
                        </template>

                        {{-- Detail-only: status + invoice info + logs --}}
                        <template x-if="detailMode">
                            <div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="form-label">Status Saat Ini</label>
                                        <div class="flex items-center gap-2">
                                            <span class="badge" :class="statusBadgeClass(detailData.status)" x-text="statusLabel(detailData.status)"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Invoice Terkait</label>
                                        <template x-if="detailData.invoice">
                                            <div class="text-sm">
                                                <span class="font-mono font-semibold" x-text="detailData.invoice.invoice_number"></span>
                                                <span class="block text-xs text-slate-500">Rp <span x-text="formatMoney(detailData.invoice.grand_total)"></span></span>
                                            </div>
                                        </template>
                                        <template x-if="!detailData.invoice">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-400">Belum ada invoice</span>
                                                <button type="button"
                                                    x-show="canCreateInvoice()"
                                                    @click="openCreateInvoice()"
                                                    class="btn btn-sm btn-primary">
                                                    + Buat Invoice
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <h4 class="text-sm font-bold text-slate-800 mb-2">Riwayat Aktivitas</h4>
                                    <div class="border border-slate-200 rounded-lg divide-y divide-slate-100 max-h-64 overflow-y-auto">
                                        <template x-for="log in detailData.logs" :key="log.id">
                                            <div class="px-3 py-2">
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="font-semibold text-slate-700" x-text="log.action_label"></span>
                                                    <span class="text-slate-400" x-text="log.created_at"></span>
                                                </div>
                                                <div class="text-xs text-slate-500 mt-0.5">
                                                    <template x-if="log.from_status_label">
                                                        <span><span x-text="log.from_status_label"></span> -> </span>
                                                    </template>
                                                    <span x-text="log.to_status_label"></span>
                                                    <template x-if="log.handled_by"><span class="ml-1">oleh <span x-text="log.handled_by"></span></span></template>
                                                </div>
                                                <template x-if="log.note">
                                                    <div class="text-xs text-slate-600 italic mt-0.5" x-text="log.note"></div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!detailData.logs || detailData.logs.length === 0">
                                            <div class="p-3 text-xs text-slate-400 text-center">Belum ada aktivitas.</div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </form>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-200 bg-slate-50">
                    <button type="button" @click="closeModal()" class="btn btn-outline">Tutup</button>
                    <button type="button" x-show="!detailMode" @click="save()" :disabled="saving" class="btn btn-primary">
                        <span x-show="!saving">Simpan</span>
                        <span x-show="saving">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==== Modal Create Invoice ==== --}}
    <div x-show="invoiceModalOpen" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm" style="display:none">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click.outside="invoiceModalOpen = false" class="bg-white rounded-xl shadow-2xl w-full max-w-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900">Buat Invoice dari Order Jasa</h3>
                    <button @click="invoiceModalOpen = false" class="p-2 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitInvoice()" class="p-6">
                    <div class="mb-4">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" x-model="invoiceData.transaction_date" class="form-input" required>
                    </div>

                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <label class="form-label mb-0">Item yang Ditagihkan</label>
                            <button type="button" @click="addInvoiceItem()" class="btn btn-sm btn-outline">+ Tambah Item</button>
                        </div>
                        <template x-for="(it, idx) in invoiceData.items" :key="idx">
                            <div class="grid grid-cols-12 gap-2 mb-2 items-start">
                                <div class="col-span-5">
                                    <input type="text" x-model="it.description" placeholder="Deskripsi" class="form-input text-sm" required>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" step="0.01" min="0.01" x-model.number="it.qty" placeholder="Qty" class="form-input text-sm" required>
                                </div>
                                <div class="col-span-2">
                                    <input type="text" x-model="it.unit" placeholder="Unit" class="form-input text-sm">
                                </div>
                                <div class="col-span-2">
                                    <input type="number" step="0.01" min="0" x-model.number="it.price" placeholder="Harga" class="form-input text-sm" required>
                                </div>
                                <div class="col-span-1">
                                    <button type="button" @click="removeInvoiceItem(idx)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 text-red-500 hover:bg-red-50 transition-all" title="Hapus item">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Grand Total <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" x-model.number="invoiceData.grand_total" class="form-input" required>
                        <p class="text-[11px] text-slate-500 mt-1">Total item: Rp <span x-text="formatMoney(itemsTotal())"></span> - kamu boleh override (diskon/markup diterapkan sebagai selisih).</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Catatan Invoice</label>
                        <textarea x-model="invoiceData.notes" rows="2" class="form-input"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="invoiceModalOpen = false" class="btn btn-outline">Batal</button>
                        <button type="submit" :disabled="creatingInvoice" class="btn btn-primary">
                            <span x-show="!creatingInvoice">Terbitkan Invoice</span>
                            <span x-show="creatingInvoice">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function setFilter(name, value) {
    const form = document.getElementById('filterForm');
    const existing = form.querySelector('input[name="' + name + '"]');
    if (existing) { existing.value = value; }
    else {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }
    form.submit();
}

function serviceOrderPage(opts) {
    const STATUS_FLOW = ['draft', 'confirmed', 'in_progress', 'completed', 'invoiced'];
    const STATUS_EDITABLE = ['draft', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    const STATUS_LABELS = {
        draft: 'Draft', confirmed: 'Confirmed', in_progress: 'In Progress',
        completed: 'Completed', invoiced: 'Invoiced', cancelled: 'Cancelled'
    };
    const STATUS_BADGES = {
        draft: 'badge-neutral', confirmed: 'badge-info', in_progress: 'badge-warning',
        completed: 'badge-success', invoiced: 'badge-success', cancelled: 'badge-danger'
    };

    return {
        branches: opts.branches,
        isSuperAdmin: opts.isSuperAdmin,
        defaultBranchId: opts.defaultBranchId,
        canCreate: opts.canCreate,
        canEdit: opts.canEdit,
        canDelete: opts.canDelete,
        canDetail: opts.canDetail,

        masterLoaded: false,
        masterBranchId: null,
        masterLoading: false,
        customers: [],
        users: [],
        components: [],

        modalOpen: false,
        invoiceModalOpen: false,
        editMode: false,
        detailMode: false,
        loading: false,
        fetchingOrder: false,
        fetchingMode: 'detail',
        saving: false,
        creatingInvoice: false,

        modalTitle: '',
        modalSubtitle: '',

        formData: {},
        detailData: {},
        invoiceData: {},

        init() {
            this.formData = this.emptyForm();
            this.invoiceData = { transaction_date: '', items: [], grand_total: 0, notes: '' };
            window.openServiceOrderModal = (type) => this.openCreate(type);
        },

        emptyForm() {
            return {
                id: null,
                branch_id: this.defaultBranchId || '',
                order_type: 'service',
                order_date: new Date().toISOString().slice(0, 10),
                customer_id: '',
                title: '',
                category: '',
                description: '',
                location: '',
                planned_start_date: '',
                duration_days: null,
                actual_finish_date: '',
                completion_notes: '',
                notes: '',
                internal_notes: '',
                status: 'draft',
                assignments: [],
                components: [],
            };
        },

        async ensureMaster(branchId = null, force = false) {
            const requestedBranchId = branchId ?? this.formData.branch_id ?? '';
            const normalizedBranchId = String(requestedBranchId || '');

            if (this.isSuperAdmin && !normalizedBranchId) {
                this.customers = [];
                this.users = [];
                this.components = [];
                this.masterLoaded = false;
                this.masterBranchId = '';
                this.masterLoading = false;
                return;
            }

            if (this.masterLoaded && (!this.isSuperAdmin || (!force && this.masterBranchId === normalizedBranchId))) {
                return;
            }

            try {
                this.masterLoading = true;
                const params = {};
                if (this.isSuperAdmin && normalizedBranchId) params.branch_id = normalizedBranchId;
                const res = await axios.get(`{{ route('order-jasa.master-data') }}`, { params });
                this.customers = (res.data.customers || []).map((customer) => ({
                    id: customer.id,
                    full_name: customer.full_name || customer.name || 'Pelanggan',
                    branch_id: customer.branch_id || null,
                }));
                this.users = (res.data.users || []).map((user) => ({
                    id: user.id,
                    name: user.name || 'User',
                    branch_id: user.branch_id || null,
                    branch_name: user.branch?.name || user.branch_name || '',
                    is_super_admin: !!user.is_super_admin,
                }));
                this.components = (res.data.components || []).map((component) => ({
                    id: component.id,
                    name: component.name || 'Komponen',
                    type_size: component.type_size || '',
                    label: (component.name || 'Komponen') + (component.type_size ? ' - ' + component.type_size : ''),
                    qty: Number(component.qty || 0),
                    branch_id: component.branch_id || null,
                    branch_name: component.branch?.name || component.branch_name || '',
                    category_name: component.component_category?.name || component.component_category_name || '',
                }));
                this.masterLoaded = true;
                this.masterBranchId = this.isSuperAdmin ? normalizedBranchId : null;
            } catch (e) {
                console.error(e);
                window.toast.error(e.response?.data?.message || 'Gagal memuat master data.');
            } finally {
                this.masterLoading = false;
            }
        },

        filteredCustomers() {
            const bid = parseInt(this.formData.branch_id, 10);
            if (!bid) return this.customers;
            return this.customers.filter(c => parseInt(c.branch_id, 10) === bid);
        },
        filteredUsers() {
            const bid = parseInt(this.formData.branch_id, 10);
            if (!bid) return this.users;
            return this.users.filter(u => parseInt(u.branch_id, 10) === bid || u.is_super_admin);
        },
        filteredComponents() {
            const bid = parseInt(this.formData.branch_id, 10);
            if (!bid) return this.components;
            return this.components.filter(c => parseInt(c.branch_id, 10) === bid);
        },
        searchOptions(items, query, fields = []) {
            const keyword = String(query || '').toLowerCase().trim();
            if (!keyword) return items;
            return (items || []).filter((item) => fields.some((field) => String(item?.[field] || '').toLowerCase().includes(keyword)));
        },
        branchById(id) {
            return this.branches.find((branch) => String(branch.id) === String(id)) || null;
        },
        customerById(id) {
            return this.customers.find((customer) => String(customer.id) === String(id)) || null;
        },
        userById(id) {
            return this.users.find((user) => String(user.id) === String(id)) || null;
        },

        typeLabel(type) {
            return type === 'training' ? 'Pelatihan' : 'Servis';
        },
        statusLabel(status) { return STATUS_LABELS[status] || status || '-'; },
        statusBadgeClass(status) { return STATUS_BADGES[status] || 'badge-neutral'; },
        formStatusOptions() {
            return STATUS_EDITABLE.map((status) => ({ value: status, label: STATUS_LABELS[status] }));
        },
        canEditStatusField() {
            return this.canEdit || !this.editMode;
        },

        async openCreate(type) {
            this.formData = this.emptyForm();
            this.formData.order_type = type;
            this.editMode = false;
            this.detailMode = false;
            this.loading = true;
            this.modalOpen = true;
            this.modalTitle = type === 'training' ? 'Buat Order Pelatihan' : 'Buat Order Servis';
            this.modalSubtitle = 'Harga jasa ditentukan saat invoice diterbitkan.';

            try {
                await this.ensureMaster(this.formData.branch_id, true);
            } finally {
                this.loading = false;
            }
        },

        async openEdit(id) {
            this.fetchingOrder = true;
            this.fetchingMode = 'edit';
            this.loading = true;
            this.editMode = true;
            this.detailMode = false;
            try {
                const res = await axios.get(`{{ route('order-jasa.show', ['id' => '__ID__']) }}`.replace('__ID__', id));
                this.formData = this.mapDetailToForm(res.data);
                await this.ensureMaster(this.formData.branch_id, true);
                this.modalTitle = (this.formData.order_type === 'training' ? 'Edit Order Pelatihan' : 'Edit Order Servis') + ' - ' + (res.data.order_number || '');
                this.modalSubtitle = '';
                this.modalOpen = true;
            } catch (e) {
                console.error(e);
                window.toast.error(e.response?.data?.message || 'Gagal memuat data order.');
                this.closeModal();
            } finally {
                this.fetchingOrder = false;
                this.loading = false;
            }
        },

        async openDetail(id) {
            this.fetchingOrder = true;
            this.fetchingMode = 'detail';
            this.loading = true;
            this.editMode = false;
            this.detailMode = true;
            try {
                const res = await axios.get(`{{ route('order-jasa.show', ['id' => '__ID__']) }}`.replace('__ID__', id));
                this.formData = this.mapDetailToForm(res.data);
                await this.ensureMaster(this.formData.branch_id, true);
                this.detailData = res.data;
                this.modalTitle = (this.formData.order_type === 'training' ? 'Detail Order Pelatihan' : 'Detail Order Servis') + ' - ' + (res.data.order_number || '');
                this.modalSubtitle = '';
                this.modalOpen = true;
            } catch (e) {
                console.error(e);
                window.toast.error(e.response?.data?.message || 'Gagal memuat detail order.');
                this.closeModal();
            } finally {
                this.fetchingOrder = false;
                this.loading = false;
            }
        },

        mapDetailToForm(d) {
            return {
                id: d.id,
                branch_id: d.branch_id || '',
                order_type: d.order_type,
                order_date: d.order_date,
                customer_id: d.customer_id,
                title: d.title || '',
                category: d.category || '',
                description: d.description || '',
                location: d.location || '',
                planned_start_date: d.planned_start_date || '',
                duration_days: d.duration_days,
                actual_finish_date: d.actual_finish_date || '',
                completion_notes: d.completion_notes || '',
                notes: d.notes || '',
                internal_notes: d.internal_notes || '',
                status: d.status,
                assignments: (d.assignments || []).map(a => ({
                    user_id: a.user_id, role: a.role || '', notes: a.notes || ''
                })),
                components: (d.components || []).map(c => ({
                    component_id: c.component_id, qty: c.qty, notes: c.notes || ''
                })),
            };
        },

        closeModal() {
            this.modalOpen = false;
            this.loading = false;
            this.fetchingOrder = false;
            this.formData = this.emptyForm();
            this.detailData = {};
        },

        addAssignment() {
            this.formData.assignments.push({ user_id: '', role: '', notes: '' });
        },
        removeAssignment(idx) {
            this.formData.assignments.splice(idx, 1);
        },

        validateForm() {
            if (this.isSuperAdmin && !this.formData.branch_id) {
                window.toast.error('Cabang wajib dipilih.');
                return false;
            }

            if (!this.formData.order_date) {
                window.toast.error('Tanggal order wajib diisi.');
                return false;
            }

            if (!this.formData.customer_id) {
                window.toast.error('Pelanggan wajib dipilih.');
                return false;
            }

            if (!String(this.formData.title || '').trim()) {
                window.toast.error('Judul order wajib diisi.');
                return false;
            }

            const emptyAssignment = (this.formData.assignments || []).find((assignment) => !assignment.user_id);
            if (emptyAssignment) {
                window.toast.error('Setiap petugas yang ditambahkan wajib memilih user.');
                return false;
            }

            if (this.formData.order_type === 'training') {
                const duration = Number(this.formData.duration_days || 0);
                if (this.formData.duration_days !== null && this.formData.duration_days !== '' && duration < 1) {
                    window.toast.error('Durasi pelatihan minimal 1 hari.');
                    return false;
                }
            }

            if (this.formData.order_type === 'service') {
                const invalidComponent = (this.formData.components || []).find((component) => {
                    return component.component_id && (!(Number(component.qty) > 0));
                });

                if (invalidComponent) {
                    window.toast.error('Qty komponen servis harus lebih dari 0.');
                    return false;
                }
            }

            return true;
        },

        async handleBranchChange() {
            if (!this.isSuperAdmin) {
                return;
            }

            this.formData.customer_id = '';
            this.formData.assignments = [];
            this.formData.components = this.formData.order_type === 'service' ? [] : this.formData.components;
            await this.ensureMaster(this.formData.branch_id, true);
        },

        addComponent() {
            this.formData.components.push({ component_id: '', qty: 1, notes: '' });
        },
        removeComponent(idx) {
            this.formData.components.splice(idx, 1);
        },
        onComponentChange(idx) {
            // noop for now — could prefill notes based on component
        },

        async save() {
            if (this.saving) return;
            if (!this.validateForm()) return;
            this.saving = true;
            try {
                const url = this.editMode
                    ? `{{ route('order-jasa.update', ['id' => '__ID__']) }}`.replace('__ID__', this.formData.id)
                    : `{{ route('order-jasa.store') }}`;
                const method = this.editMode ? 'put' : 'post';
                const payload = { ...this.formData };
                const currentOrderType = payload.order_type;
                if (this.editMode) delete payload.order_type;
                if (currentOrderType === 'training') delete payload.components;
                if (payload.status === 'invoiced') {
                    payload.status = this.editMode ? (this.detailData.status || this.formData.status || 'draft') : 'draft';
                }
                if (currentOrderType === 'service' && Array.isArray(payload.components)) {
                    payload.components = payload.components.map((component) => ({
                        component_id: component.component_id,
                        qty: component.qty,
                        notes: component.notes || '',
                        billable: true,
                    }));
                }

                const res = await axios[method](url, payload);
                window.toast.success(res.data.message || 'Order jasa berhasil disimpan.');
                this.closeModal();
                window.location.reload();
            } catch (e) {
                console.error(e);
                const msg = e.response?.data?.message || 'Gagal menyimpan';
                const errors = e.response?.data?.errors;
                let detail = msg;
                if (errors && typeof errors === 'object') {
                    const firstKey = Object.keys(errors)[0];
                    if (firstKey) detail = msg + ': ' + (Array.isArray(errors[firstKey]) ? errors[firstKey][0] : errors[firstKey]);
                }
                window.toast.error(detail);
            } finally {
                this.saving = false;
            }
        },

        async destroy(id, orderNumber) {
            if (!confirm('Hapus order jasa ' + (orderNumber || '') + '? Tindakan ini tidak bisa dibatalkan.')) return;
            try {
                const res = await axios.delete(`{{ route('order-jasa.destroy', ['id' => '__ID__']) }}`.replace('__ID__', id));
                window.toast.success(res.data.message || 'Order jasa berhasil dihapus.');
                window.location.reload();
            } catch (e) {
                console.error(e);
                const msg = e.response?.data?.message || 'Gagal menghapus';
                window.toast.error(msg);
            }
        },

        async changeStatus(newStatus) {
            const note = newStatus === 'cancelled' ? prompt('Alasan pembatalan (opsional):') : null;
            try {
                const res = await axios.patch(
                    `{{ route('order-jasa.update-status', ['id' => '__ID__']) }}`.replace('__ID__', this.formData.id),
                    { status: newStatus, note }
                );
                window.toast.success(res.data.message || 'Status order jasa berhasil diubah.');
                await this.openDetail(this.formData.id);
            } catch (e) {
                console.error(e);
                const msg = e.response?.data?.message || 'Gagal mengubah status';
                window.toast.error(msg);
            }
        },
        async changeListStatus(id, previousStatus, event) {
            if (!this.canEdit) return;

            const select = event.target;
            const newStatus = select.value;

            if (!newStatus || newStatus === previousStatus) {
                select.value = previousStatus;
                return;
            }

            const note = newStatus === 'cancelled' ? prompt('Alasan pembatalan (opsional):') : null;

            try {
                const res = await axios.patch(
                    `{{ route('order-jasa.update-status', ['id' => '__ID__']) }}`.replace('__ID__', id),
                    { status: newStatus, note }
                );
                window.toast.success(res.data.message || 'Status order jasa berhasil diubah.');
                window.location.reload();
            } catch (e) {
                console.error(e);
                select.value = previousStatus;
                const msg = e.response?.data?.message || 'Gagal mengubah status';
                window.toast.error(msg);
            }
        },

        canCreateInvoice() {
            if (!this.canCreate) return false;
            const current = this.detailData.status;
            return ['confirmed', 'in_progress', 'completed'].includes(current) && !this.detailData.invoice;
        },

        openCreateInvoice() {
            // Prefill based on order
            const items = [];
            items.push({
                description: (this.formData.order_type === 'training' ? 'Jasa pelatihan: ' : 'Jasa servis: ') + (this.formData.title || ''),
                qty: 1, unit: 'jasa', price: 0,
            });
            // Add recorded service components into invoice draft
            if (this.formData.order_type === 'service') {
                (this.formData.components || []).filter(c => c.component_id).forEach(c => {
                    const comp = this.components.find(cc => parseInt(cc.id, 10) === parseInt(c.component_id, 10));
                    items.push({
                        description: 'Komponen: ' + (comp?.name || 'Komponen'),
                        qty: c.qty || 1, unit: 'pcs', price: 0,
                    });
                });
            }
            this.invoiceData = {
                transaction_date: new Date().toISOString().slice(0, 10),
                items,
                grand_total: 0,
                notes: '',
            };
            this.invoiceModalOpen = true;
        },

        addInvoiceItem() {
            this.invoiceData.items.push({ description: '', qty: 1, unit: 'jasa', price: 0 });
        },
        removeInvoiceItem(idx) {
            this.invoiceData.items.splice(idx, 1);
        },
        itemsTotal() {
            return (this.invoiceData.items || []).reduce((sum, it) => sum + (Number(it.qty) || 0) * (Number(it.price) || 0), 0);
        },

        validateInvoiceData() {
            if (!this.invoiceData.transaction_date) {
                window.toast.error('Tanggal invoice wajib diisi.');
                return false;
            }

            if (!Array.isArray(this.invoiceData.items) || this.invoiceData.items.length === 0) {
                window.toast.error('Tambahkan minimal satu item invoice.');
                return false;
            }

            const invalidItem = this.invoiceData.items.find((item) => {
                return !String(item.description || '').trim() || !(Number(item.qty) > 0) || Number(item.price) < 0;
            });

            if (invalidItem) {
                window.toast.error('Pastikan semua item invoice memiliki deskripsi, qty, dan harga yang valid.');
                return false;
            }

            if (Number(this.invoiceData.grand_total) < 0) {
                window.toast.error('Grand total tidak boleh negatif.');
                return false;
            }

            return true;
        },

        async submitInvoice() {
            if (this.creatingInvoice) return;
            if (!this.validateInvoiceData()) return;
            this.creatingInvoice = true;
            try {
                const payload = {
                    transaction_date: this.invoiceData.transaction_date,
                    grand_total: this.invoiceData.grand_total || this.itemsTotal(),
                    notes: this.invoiceData.notes,
                    items: this.invoiceData.items,
                };
                const res = await axios.post(
                    `{{ route('order-jasa.create-invoice', ['id' => '__ID__']) }}`.replace('__ID__', this.formData.id),
                    payload
                );
                window.toast.success(res.data.message || 'Invoice berhasil dibuat dari order jasa.');
                this.invoiceModalOpen = false;
                this.closeModal();
                window.location.reload();
            } catch (e) {
                console.error(e);
                const msg = e.response?.data?.message || 'Gagal membuat invoice';
                window.toast.error(msg);
            } finally {
                this.creatingInvoice = false;
            }
        },

        formatMoney(n) {
            return (Number(n) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },
    };
}
</script>
@endsection
