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
            <button @click="open = !open" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Buat Order Jasa</span>
                <svg class="w-3 h-3 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-lg shadow-xl z-20" style="display:none">
                <button type="button" @click="openCreate('service'); open = false" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 rounded-t-lg text-sm font-medium text-slate-700 flex items-center gap-2">
                    <span class="badge badge-info">Servis</span>
                    Order Servis
                </button>
                <button type="button" @click="openCreate('training'); open = false" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 rounded-b-lg text-sm font-medium text-slate-700 flex items-center gap-2">
                    <span class="badge badge-warning">Pelatihan</span>
                    Order Pelatihan
                </button>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-2.5 mb-5">
        <button type="button" onclick="setFilter('status','')" class="card border px-3.5 py-3 text-left transition-all {{ $selectedStatus === '' ? 'border-brand shadow-lg shadow-brand/10' : 'border-slate-200 hover:border-slate-300' }}">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Semua</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ (int) $statusCounts->sum() }}</p>
                </div>
                <span class="badge badge-neutral">Semua</span>
            </div>
        </button>
        @foreach($statusLabels as $key => $label)
        <button type="button" onclick="setFilter('status','{{ $selectedStatus === $key ? '' : $key }}')" class="card border px-3.5 py-3 text-left transition-all {{ $selectedStatus === $key ? 'border-brand shadow-lg shadow-brand/10' : 'border-slate-200 hover:border-slate-300' }}">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">{{ $label }}</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ (int) ($statusCounts->get($key, 0)) }}</p>
                </div>
                <span class="badge {{ $statusBadgeStyles[$key] }}">{{ $label }}</span>
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
                            <th class="text-center">PETUGAS</th>
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
                            <td class="text-center">
                                @if(($order['assigned_count'] ?? 0) > 0)
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>{{ $order['assigned_count'] }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusBadgeStyles[$order['status']] ?? 'badge-neutral' }}">
                                    {{ $statusLabels[$order['status']] ?? ($order['status'] ?? '-') }}
                                </span>
                                @if(!empty($order['has_invoice']))
                                    <span class="block text-[10px] text-green-600 mt-0.5">✓ Invoice</span>
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
                            <td colspan="9" class="text-center py-10 text-slate-400">Belum ada data order jasa.</td>
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
                        {{-- Branch (super admin only) --}}
                        <template x-if="isSuperAdmin && !detailMode">
                            <div class="mb-4">
                                <label class="form-label">Cabang <span class="text-red-500">*</span></label>
                                <select x-model="formData.branch_id" :disabled="editMode" class="form-input" required>
                                    <option value="">Pilih cabang</option>
                                    <template x-for="b in branches" :key="b.id">
                                        <option :value="b.id" x-text="b.name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">Tipe Order</label>
                                <div class="form-input bg-slate-50 text-slate-700 font-semibold">
                                    <span x-text="typeLabel(formData.order_type)"></span>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Tanggal Order <span class="text-red-500">*</span></label>
                                <input type="date" x-model="formData.order_date" :disabled="detailMode" class="form-input" required>
                            </div>
                        </div>

                        {{-- Customer --}}
                        <div class="mb-4">
                            <label class="form-label">Pelanggan <span class="text-red-500">*</span></label>
                            <select x-model="formData.customer_id" :disabled="detailMode" class="form-input" required>
                                <option value="">Pilih pelanggan</option>
                                <template x-for="c in filteredCustomers()" :key="c.id">
                                    <option :value="c.id" x-text="c.full_name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <span x-text="formData.order_type === 'training' ? 'Nama Pelatihan' : 'Judul Pekerjaan / Nama Servis'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="formData.title" :disabled="detailMode" class="form-input" required maxlength="255">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">
                                    <span x-text="formData.order_type === 'training' ? 'Kategori Pelatihan' : 'Jenis Servis'"></span>
                                </label>
                                <input type="text" x-model="formData.category" :disabled="detailMode" class="form-input" maxlength="255">
                            </div>
                            <div>
                                <label class="form-label">
                                    <span x-text="formData.order_type === 'training' ? 'Tempat Pelatihan' : 'Lokasi Pengerjaan'"></span>
                                </label>
                                <input type="text" x-model="formData.location" :disabled="detailMode" class="form-input" maxlength="255">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="form-label">
                                    <span x-text="formData.order_type === 'training' ? 'Tanggal Mulai Pelatihan' : 'Tanggal Rencana Pengerjaan'"></span>
                                </label>
                                <input type="date" x-model="formData.planned_start_date" :disabled="detailMode" class="form-input">
                            </div>
                            <template x-if="formData.order_type === 'training'">
                                <div>
                                    <label class="form-label">Durasi (hari)</label>
                                    <input type="number" min="1" x-model.number="formData.duration_days" :disabled="detailMode" class="form-input">
                                </div>
                            </template>
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
                                <div class="grid grid-cols-12 gap-2 mb-2 items-start">
                                    <div class="col-span-5">
                                        <select x-model="a.user_id" :disabled="detailMode" class="form-input text-sm" required>
                                            <option value="">Pilih user</option>
                                            <template x-for="u in filteredUsers()" :key="u.id">
                                                <option :value="u.id" x-text="u.name + (u.branch_name ? ' (' + u.branch_name + ')' : '')"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="col-span-3">
                                        <select x-model="a.role" :disabled="detailMode" class="form-input text-sm">
                                            <option value="">Peran</option>
                                            <option value="lead">Lead</option>
                                            <option value="teknisi">Teknisi</option>
                                            <option value="trainer">Trainer</option>
                                            <option value="helper">Helper</option>
                                        </select>
                                    </div>
                                    <div class="col-span-3">
                                        <input type="text" x-model="a.notes" :disabled="detailMode" placeholder="Catatan (opsional)" class="form-input text-sm">
                                    </div>
                                    <div class="col-span-1">
                                        <button type="button" x-show="!detailMode" @click="removeAssignment(idx)" class="p-2 rounded text-red-500 hover:bg-red-50" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
                                    <div class="grid grid-cols-12 gap-2 mb-2 items-start">
                                        <div class="col-span-5">
                                            <select x-model="c.component_id" @change="onComponentChange(idx)" :disabled="detailMode" class="form-input text-sm">
                                                <option value="">Pilih komponen</option>
                                                <template x-for="comp in filteredComponents()" :key="comp.id">
                                                    <option :value="comp.id" x-text="comp.name + (comp.type_size ? ' - ' + comp.type_size : '')"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <input type="number" min="1" x-model.number="c.qty" :disabled="detailMode" placeholder="Qty" class="form-input text-sm">
                                        </div>
                                        <div class="col-span-2">
                                            <label class="flex items-center gap-2 text-xs mt-2">
                                                <input type="checkbox" x-model="c.billable" :disabled="detailMode">
                                                <span>Billable</span>
                                            </label>
                                        </div>
                                        <div class="col-span-2">
                                            <input type="text" x-model="c.notes" :disabled="detailMode" placeholder="Catatan" class="form-input text-sm">
                                        </div>
                                        <div class="col-span-1">
                                            <button type="button" x-show="!detailMode" @click="removeComponent(idx)" class="p-2 rounded text-red-500 hover:bg-red-50" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
                                            <div class="flex gap-1" x-show="!detailData.invoice">
                                                <template x-for="opt in availableStatusTransitions()" :key="opt.value">
                                                    <button type="button" @click="changeStatus(opt.value)" class="text-[10px] px-2 py-1 rounded border border-slate-300 hover:bg-slate-100">
                                                        → <span x-text="opt.label"></span>
                                                    </button>
                                                </template>
                                            </div>
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
                                                        <span><span x-text="log.from_status_label"></span> → </span>
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
                                    <button type="button" @click="removeInvoiceItem(idx)" class="p-2 rounded text-red-500 hover:bg-red-50" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Grand Total <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" x-model.number="invoiceData.grand_total" class="form-input" required>
                        <p class="text-[11px] text-slate-500 mt-1">Total item: Rp <span x-text="formatMoney(itemsTotal())"></span> — kamu boleh override (diskon/markup diterapkan sebagai selisih).</p>
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

        masterLoaded: false,
        customers: [],
        users: [],
        components: [],

        modalOpen: false,
        invoiceModalOpen: false,
        editMode: false,
        detailMode: false,
        loading: false,
        saving: false,
        creatingInvoice: false,

        modalTitle: '',
        modalSubtitle: '',

        formData: this.emptyForm(),
        detailData: {},
        invoiceData: {},

        init() {},

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

        async ensureMaster() {
            if (this.masterLoaded) return;
            try {
                const params = {};
                if (this.isSuperAdmin && this.formData.branch_id) params.branch_id = this.formData.branch_id;
                const res = await axios.get(`{{ route('order-jasa.master-data') }}`, { params });
                this.customers = res.data.customers || [];
                this.users = res.data.users || [];
                this.components = res.data.components || [];
                this.masterLoaded = true;
            } catch (e) {
                console.error(e);
                if (window.showToast) showToast('Gagal memuat master data', 'error');
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

        typeLabel(type) {
            return type === 'training' ? 'Pelatihan' : 'Servis';
        },
        statusLabel(status) { return STATUS_LABELS[status] || status || '-'; },
        statusBadgeClass(status) { return STATUS_BADGES[status] || 'badge-neutral'; },

        async openCreate(type) {
            await this.ensureMaster();
            this.formData = this.emptyForm();
            this.formData.order_type = type;
            this.editMode = false;
            this.detailMode = false;
            this.modalTitle = type === 'training' ? 'Buat Order Pelatihan' : 'Buat Order Servis';
            this.modalSubtitle = 'Harga jasa ditentukan saat invoice diterbitkan.';
            this.modalOpen = true;
        },

        async openEdit(id) {
            await this.ensureMaster();
            this.loading = true;
            this.modalOpen = true;
            this.editMode = true;
            this.detailMode = false;
            try {
                const res = await axios.get(`{{ route('order-jasa.show', ['id' => '__ID__']) }}`.replace('__ID__', id));
                this.formData = this.mapDetailToForm(res.data);
                this.modalTitle = (this.formData.order_type === 'training' ? 'Edit Order Pelatihan' : 'Edit Order Servis') + ' - ' + (res.data.order_number || '');
                this.modalSubtitle = '';
            } catch (e) {
                console.error(e);
                if (window.showToast) showToast('Gagal memuat data order', 'error');
                this.closeModal();
            } finally {
                this.loading = false;
            }
        },

        async openDetail(id) {
            await this.ensureMaster();
            this.loading = true;
            this.modalOpen = true;
            this.editMode = false;
            this.detailMode = true;
            try {
                const res = await axios.get(`{{ route('order-jasa.show', ['id' => '__ID__']) }}`.replace('__ID__', id));
                this.formData = this.mapDetailToForm(res.data);
                this.detailData = res.data;
                this.modalTitle = (this.formData.order_type === 'training' ? 'Detail Order Pelatihan' : 'Detail Order Servis') + ' - ' + (res.data.order_number || '');
                this.modalSubtitle = '';
            } catch (e) {
                console.error(e);
                if (window.showToast) showToast('Gagal memuat detail', 'error');
                this.closeModal();
            } finally {
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
                    component_id: c.component_id, qty: c.qty, billable: !!c.billable, notes: c.notes || ''
                })),
            };
        },

        closeModal() {
            this.modalOpen = false;
            this.loading = false;
            this.formData = this.emptyForm();
            this.detailData = {};
        },

        addAssignment() {
            this.formData.assignments.push({ user_id: '', role: '', notes: '' });
        },
        removeAssignment(idx) {
            this.formData.assignments.splice(idx, 1);
        },

        addComponent() {
            this.formData.components.push({ component_id: '', qty: 1, billable: true, notes: '' });
        },
        removeComponent(idx) {
            this.formData.components.splice(idx, 1);
        },
        onComponentChange(idx) {
            // noop for now — could prefill notes based on component
        },

        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const url = this.editMode
                    ? `{{ route('order-jasa.update', ['id' => '__ID__']) }}`.replace('__ID__', this.formData.id)
                    : `{{ route('order-jasa.store') }}`;
                const method = this.editMode ? 'put' : 'post';
                const payload = { ...this.formData };
                if (this.editMode) delete payload.order_type;
                if (payload.order_type === 'training') delete payload.components;

                const res = await axios[method](url, payload);
                if (window.showToast) showToast(res.data.message || 'Berhasil disimpan', 'success');
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
                if (window.showToast) showToast(detail, 'error');
            } finally {
                this.saving = false;
            }
        },

        async destroy(id, orderNumber) {
            if (!confirm('Hapus order jasa ' + (orderNumber || '') + '? Tindakan ini tidak bisa dibatalkan.')) return;
            try {
                const res = await axios.delete(`{{ route('order-jasa.destroy', ['id' => '__ID__']) }}`.replace('__ID__', id));
                if (window.showToast) showToast(res.data.message || 'Berhasil dihapus', 'success');
                window.location.reload();
            } catch (e) {
                console.error(e);
                const msg = e.response?.data?.message || 'Gagal menghapus';
                if (window.showToast) showToast(msg, 'error');
            }
        },

        availableStatusTransitions() {
            const current = this.detailData.status || this.formData.status;
            if (['invoiced', 'cancelled'].includes(current)) return [];
            const currentIdx = STATUS_FLOW.indexOf(current);
            const options = [];
            for (let i = currentIdx + 1; i < STATUS_FLOW.length - 1; i++) {
                // exclude 'invoiced' as it can only be set via createInvoice
                options.push({ value: STATUS_FLOW[i], label: STATUS_LABELS[STATUS_FLOW[i]] });
            }
            options.push({ value: 'cancelled', label: 'Cancel' });
            return options;
        },

        async changeStatus(newStatus) {
            const note = newStatus === 'cancelled' ? prompt('Alasan pembatalan (opsional):') : null;
            try {
                const res = await axios.patch(
                    `{{ route('order-jasa.update-status', ['id' => '__ID__']) }}`.replace('__ID__', this.formData.id),
                    { status: newStatus, note }
                );
                if (window.showToast) showToast(res.data.message || 'Status diubah', 'success');
                await this.openDetail(this.formData.id);
            } catch (e) {
                console.error(e);
                const msg = e.response?.data?.message || 'Gagal mengubah status';
                if (window.showToast) showToast(msg, 'error');
            }
        },

        canCreateInvoice() {
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
            // Add billable components for service
            if (this.formData.order_type === 'service') {
                (this.formData.components || []).filter(c => c.billable && c.component_id).forEach(c => {
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

        async submitInvoice() {
            if (this.creatingInvoice) return;
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
                if (window.showToast) showToast(res.data.message || 'Invoice berhasil dibuat', 'success');
                this.invoiceModalOpen = false;
                this.closeModal();
                window.location.reload();
            } catch (e) {
                console.error(e);
                const msg = e.response?.data?.message || 'Gagal membuat invoice';
                if (window.showToast) showToast(msg, 'error');
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
