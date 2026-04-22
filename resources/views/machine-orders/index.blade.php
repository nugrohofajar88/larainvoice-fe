@extends('layouts.app')
@section('title', 'Order Mesin')
@section('page-title', 'Machine Order')

@section('content')
@php
    $sortBy = request('sort_by', 'id');
    $sortDir = request('sort_dir', 'desc');
    $isSuperAdmin = \App\Helpers\AuthHelper::isSuperAdmin();
    $userBranchId = session('branch_id');
    $defaultBranchId = $isSuperAdmin ? '' : (string) $userBranchId;

    $sortUrl = function ($column) use ($sortBy, $sortDir) {
        $dir = 'asc';
        if ($sortBy === $column && $sortDir === 'asc') {
            $dir = 'desc';
        }
        return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_dir' => $dir, 'page' => 1]);
    };

    $sortIcon = function ($column) use ($sortBy, $sortDir) {
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

    $customerOptions = collect($customers ?? [])->map(fn ($customer) => [
        'id' => $customer['id'],
        'name' => $customer['full_name'] ?? 'Pelanggan',
        'branch_id' => $customer['branch_id'] ?? null,
    ])->values();

    $salesOptions = collect($sales ?? [])->map(fn ($item) => [
        'id' => $item['id'],
        'name' => $item['name'] ?? 'Sales',
        'branch_id' => $item['branch_id'] ?? null,
    ])->values();

    $machineOptions = collect($machines ?? [])->map(fn ($machine) => [
        'id' => $machine['id'],
        'machine_number' => $machine['machine_number'] ?? 'Mesin',
        'branch_id' => $machine['branch_id'] ?? null,
        'base_price' => (float) ($machine['base_price'] ?? 0),
        'machine_type_name' => $machine['type']['name'] ?? ($machine['machine_type_name'] ?? ''),
        'machine_components' => collect($machine['machine_components'] ?? [])->map(fn ($component) => [
            'component_id' => $component['component_id'] ?? $component['component']['id'] ?? null,
            'component_name' => $component['component']['name'] ?? '-',
            'qty' => (int) ($component['qty'] ?? 1),
        ])->values()->all(),
    ])->values();

    $componentOptions = collect($components ?? [])->map(fn ($component) => [
        'id' => $component['id'],
        'name' => $component['name'] ?? 'Komponen',
        'type_size' => $component['type_size'] ?? '',
        'label' => ($component['name'] ?? 'Komponen') . (!empty($component['type_size']) ? ' - ' . $component['type_size'] : ''),
        'qty' => (float) ($component['qty'] ?? 0),
        'branch_id' => $component['branch_id'] ?? null,
        'branch_name' => $component['branch']['name'] ?? ($component['branch_name'] ?? ''),
        'category_name' => $component['component_category']['name'] ?? ($component['component_category_name'] ?? ''),
    ])->values();

    $costTypeOptions = collect($costTypes)->map(fn ($costType) => [
        'id' => $costType['id'],
        'name' => $costType['name'] ?? 'Biaya',
        'description' => $costType['description'] ?? '',
    ])->values();

    $branchOptions = collect($branches)->map(fn ($branch) => [
        'id' => $branch['id'],
        'name' => $branch['name'] ?? 'Cabang',
        'address' => $branch['address'] ?? '-',
        'phone' => $branch['phone'] ?? '-',
        'website' => $branch['website'] ?? '-',
        'email' => $branch['email'] ?? '-',
        'city' => $branch['city'] ?? ($branch['regency'] ?? '-'),
    ])->values();

    $statusLabels = [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'in_production' => 'In Production',
        'ready' => 'Ready',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    $statusBadgeStyles = [
        'all' => 'badge-neutral',
        'draft' => 'badge-neutral',
        'confirmed' => 'badge-info',
        'in_production' => 'badge-warning',
        'ready' => 'badge-success',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger',
    ];

    $statusCounts = collect($statusSummary ?? []);
    $selectedStatus = request('status', '');
@endphp

<div
    x-data="machineOrderPage({
        costTypes: {{ \Illuminate\Support\Js::from($costTypeOptions) }},
        branches: {{ \Illuminate\Support\Js::from($branchOptions) }},
        isSuperAdmin: {{ $isSuperAdmin ? 'true' : 'false' }},
        defaultBranchId: {{ \Illuminate\Support\Js::from($defaultBranchId) }},
    })"
    x-init="init()"
    x-cloak
>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">Order Mesin</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola pesanan mesin, biaya tambahan, pembayaran, dan komponen produksi.</p>
        </div>

        @if(\App\Helpers\MenuHelper::hasPermission('machine-order', 'create'))
        <button @click="openCreate()" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Buat Order Mesin</span>
        </button>
        @endif
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-2.5 mb-5">
        <button
            type="button"
            onclick="(function(){ const form = document.getElementById('filterForm'); const status = form.querySelector('select[name=&quot;status&quot;]'); status.value = ''; form.submit(); })()"
            class="card border px-3.5 py-3 text-left transition-all {{ $selectedStatus === '' ? 'border-brand shadow-lg shadow-brand/10' : 'border-slate-200 hover:border-slate-300' }}"
        >
            <div class="flex items-center justify-between gap-2">
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Semua</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ (int) $statusCounts->sum() }}</p>
                </div>
                <span class="badge {{ $statusBadgeStyles['all'] }}">Semua</span>
            </div>
        </button>

        @foreach($statusLabels as $key => $label)
        <button
            type="button"
            onclick="(function(){ const form = document.getElementById('filterForm'); const status = form.querySelector('select[name=&quot;status&quot;]'); status.value = '{{ $selectedStatus === $key ? '' : $key }}'; form.submit(); })()"
            class="card border px-3.5 py-3 text-left transition-all {{ $selectedStatus === $key ? 'border-brand shadow-lg shadow-brand/10' : 'border-slate-200 hover:border-slate-300' }}"
        >
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
        <form action="{{ route('machine-order.index') }}" method="GET" id="filterForm">
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">NO</th>
                            <th>
                                <a href="{{ $sortUrl('order_number') }}" class="flex items-center group">
                                    <span>NO. ORDER</span>
                                    {!! $sortIcon('order_number') !!}
                                </a>
                            </th>
                            <th>PELANGGAN</th>
                            <th>MESIN</th>
                            <th>
                                <a href="{{ $sortUrl('order_date') }}" class="flex items-center group">
                                    <span>TANGGAL</span>
                                    {!! $sortIcon('order_date') !!}
                                </a>
                            </th>
                            <th class="text-right">
                                <a href="{{ $sortUrl('grand_total') }}" class="inline-flex items-center group">
                                    <span>TOTAL</span>
                                    {!! $sortIcon('grand_total') !!}
                                </a>
                            </th>
                            <th>STATUS</th>
                            <th class="w-24 text-center">AKSI</th>
                        </tr>
                        <tr class="bg-slate-50/50">
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner">
                                <input type="text" name="search" value="{{ request('search') }}"
                                    class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                    placeholder="Cari order..." onchange="document.getElementById('filterForm').submit()">
                            </th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner">
                                <select name="status" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand outline-none transition-all">
                                    <option value="">Semua status</option>
                                    @foreach(['draft' => 'Draft', 'confirmed' => 'Confirmed', 'in_production' => 'In Production', 'ready' => 'Ready', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="py-2 px-4 shadow-inner text-center">
                                @if(request()->anyFilled(['search', 'status']))
                                    <a href="{{ route('machine-order.index') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $item)
                        <tr>
                            <td class="text-center">
                                <span class="text-slate-400 font-mono text-xs">{{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}</span>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ $item['order_number'] }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">ID: {{ $item['id'] }}</span>
                                </div>
                            </td>
                            <td>{{ $item['customer'] ?? '-' }}</td>
                            <td>{{ $item['machine'] ?? '-' }}</td>
                            <td>{{ $item['order_date'] ?? '-' }}</td>
                            <td class="text-right font-semibold text-slate-700">Rp {{ number_format((float) ($item['grand_total'] ?? 0), 0, ',', '.') }}</td>
                            <td>
                                <span class="badge badge-neutral text-[10px] px-2 py-0.5">
                                    {{ ucwords(str_replace('_', ' ', $item['status'] ?? 'draft')) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-1">
                                    @if(\App\Helpers\MenuHelper::hasPermission('machine-order', 'detail'))
                                    <button type="button" @click="openDetail({{ $item['id'] }})" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all" title="Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    @endif
                                    @if(\App\Helpers\MenuHelper::hasPermission('machine-order', 'edit'))
                                    <button type="button" @click="openEdit({{ $item['id'] }})" class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    @endif
                                    @if(\App\Helpers\MenuHelper::hasPermission('machine-order', 'delete'))
                                    <button type="button" @click="deleteItem({{ $item['id'] }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-20 text-center text-slate-400 italic">Data machine order belum tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($orders->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/30">
            {{ $orders->links() }}
        </div>
        @endif
    </div>

    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center text-sm md:text-base">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="attemptClose()"></div>

            <div class="relative inline-block w-full max-w-6xl p-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                <div class="flex items-start justify-between gap-4 mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900" x-text="detailMode ? 'Detail Order Mesin' : (editMode ? 'Edit Order Mesin' : 'Buat Order Mesin')"></h3>
                        <p class="text-xs text-slate-500 mt-1">Atur pelanggan, mesin, biaya tambahan, pembayaran, dan snapshot komponen produksi.</p>
                    </div>
                    <button @click="attemptClose()" class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm()" novalidate class="space-y-5">
                    <fieldset class="group">
                        <div x-show="masterDataLoading" class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-700 mb-4">
                            <div class="flex items-center gap-4">
                                <div class="relative shrink-0">
                                    <div class="w-14 h-14 rounded-full bg-white/80 border border-blue-100 flex items-center justify-center shadow-sm">
                                        <svg class="animate-spin h-8 w-8 text-blue-500" viewBox="0 0 24 24" fill="none">
                                            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                            <path class="opacity-90" d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                        </svg>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center shadow">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-semibold text-blue-900">Sedang memuat data order</p>
                                    <p class="mt-1 text-blue-700">Mohon tunggu, sistem sedang mengambil pelanggan, sales, mesin, dan komponen.</p>
                                </div>
                            </div>
                        </div>

                        <div x-show="!detailMode && statusLockMessage" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 mb-4">
                            <p x-text="statusLockMessage"></p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div x-show="isSuperAdmin">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Cabang <span class="text-red-500">*</span></label>
                                <select x-model="formData.branch_id" @change="handleBranchChanged()" class="form-input w-full" :disabled="isFieldLocked(!canEditCoreHeader)" required>
                                    <option value="">--- Pilih Cabang ---</option>
                                    <template x-for="branch in branches" :key="branch.id">
                                        <option :value="String(branch.id)" x-text="branch.name"></option>
                                    </template>
                                </select>
                                <p x-show="isSuperAdmin && !formData.branch_id" class="mt-1 text-[11px] text-amber-600">
                                    Pilih cabang terlebih dahulu agar data pelanggan, sales, mesin, dan komponen terfilter dengan benar.
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pelanggan <span class="text-red-500">*</span></label>
                                <select x-model="formData.customer_id" class="form-input w-full" :disabled="isFieldLocked(branchSelectionRequired || !canEditCoreHeader)" required>
                                    <option value="">--- Pilih Pelanggan ---</option>
                                    <template x-for="customer in filteredCustomers" :key="customer.id">
                                        <option :value="String(customer.id)" x-text="customer.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Sales</label>
                                <select x-model="formData.sales_id" class="form-input w-full" :disabled="isFieldLocked(branchSelectionRequired || !canEditHeader)">
                                    <option value="">--- Tanpa Sales ---</option>
                                    <template x-for="sale in filteredSales" :key="sale.id">
                                        <option :value="String(sale.id)" x-text="sale.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Status <span class="text-red-500">*</span></label>
                                <select x-model="formData.status" class="form-input w-full" :disabled="detailMode || !canEditStatus">
                                    <template x-for="status in statusOptions" :key="status.value">
                                        <option :value="status.value" x-text="status.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mesin <span class="text-red-500">*</span></label>
                                <select x-model="formData.machine_id" @change="handleMachineChanged()" class="form-input w-full" :disabled="isFieldLocked(branchSelectionRequired || !canEditCoreHeader)" required>
                                    <option value="">--- Pilih Mesin ---</option>
                                    <template x-for="machine in filteredMachines" :key="machine.id">
                                        <option :value="String(machine.id)" x-text="machine.machine_number + (machine.machine_type_name ? ' - ' + machine.machine_type_name : '')"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Qty <span class="text-red-500">*</span></label>
                                <input type="number" min="1" x-model.number="formData.qty" @input="handleQtyChanged()" class="form-input w-full" :disabled="isFieldLocked(!canEditCoreHeader)" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Base Price <span class="text-red-500">*</span></label>
                                <input type="text" :value="formatCurrency(formData.base_price)" @input="handleCurrencyInput($event, 'base_price')" inputmode="numeric" class="form-input w-full font-mono text-right" :disabled="isFieldLocked(!canEditPricing)" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Order Number</label>
                                <input type="text" :value="formData.order_number || '-'" class="form-input w-full bg-slate-50" readonly>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Tgl Order <span class="text-red-500">*</span></label>
                                <input type="date" x-model="formData.order_date" class="form-input w-full" :disabled="isFieldLocked(!canEditHeader)" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Estimasi Mulai</label>
                                <input type="date" x-model="formData.estimated_start_date" class="form-input w-full" :disabled="isFieldLocked(!canEditProductionDates)">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Estimasi Selesai</label>
                                <input type="date" x-model="formData.estimated_finish_date" class="form-input w-full" :disabled="isFieldLocked(!canEditProductionDates)">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Aktual Selesai</label>
                                <input type="date" x-model="formData.actual_finish_date" class="form-input w-full" :disabled="isFieldLocked(!canEditProductionDates)">
                            </div>

                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Diskon Tipe</label>
                                <select x-model="formData.discount_type" class="form-input w-full" :disabled="isFieldLocked(!canEditPricing)">
                                    <option value="">--- Tanpa Diskon ---</option>
                                    <option value="percent">Percent</option>
                                    <option value="amount">Nominal</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Diskon Nilai</label>
                                <input
                                    type="text"
                                    :value="formData.discount_type === 'percent' ? formData.discount_value : formatCurrency(formData.discount_value)"
                                    @input="handleDiscountInput($event)"
                                    inputmode="numeric"
                                    class="form-input w-full text-right font-mono"
                                    :disabled="isFieldLocked(!canEditPricing)"
                                >
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 hidden">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Publik</label>
                                <textarea x-model="formData.notes" class="form-input w-full min-h-24" placeholder="Catatan untuk order / pelanggan"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Internal</label>
                                <textarea x-model="formData.internal_notes" class="form-input w-full min-h-24" placeholder="Catatan internal tim"></textarea>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Biaya Tambahan</h4>
                                    <p class="text-[11px] text-slate-500">Gunakan untuk ongkir, instalasi, dan biaya lainnya.</p>
                                </div>
                                <button x-show="!detailMode && canEditCosts" type="button" @click="addCostRow()" class="btn btn-outline text-xs px-3 py-1.5 rounded-lg">Tambah Biaya</button>
                            </div>

                            <div class="space-y-2">
                                <template x-if="formData.costs.length === 0">
                                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-xs text-slate-500 text-center">
                                        Belum ada biaya tambahan.
                                    </div>
                                </template>
                                <template x-for="(cost, index) in formData.costs" :key="`cost-${index}`">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end rounded-xl border border-slate-200 p-3">
                                        <div class="md:col-span-4">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Tipe Biaya</label>
                                            <select :value="String(cost.cost_type_id || '')" @change="cost.cost_type_id = String($event.target.value || ''); syncCostType(index)" class="form-input w-full text-sm" :disabled="isFieldLocked(!canEditCosts)">
                                                <option value="">--- Pilih Tipe ---</option>
                                                <template x-if="cost.cost_type_id && !costTypes.some(type => String(type.id) === String(cost.cost_type_id))">
                                                    <option :value="String(cost.cost_type_id)" :selected="String(cost.cost_type_id) === String(cost.cost_type_id)" x-text="cost.cost_name || 'Tipe biaya tersimpan'"></option>
                                                </template>
                                                <template x-for="type in costTypes" :key="type.id">
                                                    <option :value="String(type.id)" :selected="String(type.id) === String(cost.cost_type_id || '')" x-text="type.name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Biaya</label>
                                            <input type="text" x-model="cost.cost_name" class="form-input w-full text-sm" placeholder="Nama biaya" :disabled="isFieldLocked(!canEditCosts)">
                                        </div>
                                        <div class="md:col-span-1">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Qty</label>
                                            <input type="number" min="0.01" step="0.01" x-model.number="cost.qty" class="form-input w-full text-sm" :disabled="isFieldLocked(!canEditCosts)">
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Harga</label>
                                            <input type="text" :value="formatCurrency(cost.price)" @input="handleNestedCurrencyInput($event, 'costs', index, 'price')" class="form-input w-full text-sm text-right font-mono" :disabled="isFieldLocked(!canEditCosts)">
                                        </div>
                                        <div class="md:col-span-1">
                                            <button type="button" @click="removeCostRow(index)" class="w-full h-10 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-all" :disabled="isFieldLocked(!canEditCosts)" x-show="!detailMode && canEditCosts">
                                                <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                        <div class="md:col-span-12">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Deskripsi</label>
                                            <input type="text" x-model="cost.description" class="form-input w-full text-sm" placeholder="Deskripsi tambahan" :disabled="isFieldLocked(!canEditCosts)">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Komponen Order</h4>
                                    <p class="text-[11px] text-slate-500">Snapshot komponen diambil dari mesin dan bisa disesuaikan untuk order ini.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button x-show="!detailMode && canEditComponents" type="button" @click="applyMachineComponents()" class="btn btn-outline text-xs px-3 py-1.5 rounded-lg">Ambil Ulang dari Mesin</button>
                                    <button x-show="!detailMode && canEditComponents" type="button" @click="addComponentRow()" class="btn btn-outline text-xs px-3 py-1.5 rounded-lg">Tambah Komponen</button>
                                </div>
                            </div>

                            <div class="overflow-visible rounded-xl border border-slate-200">
                                <template x-if="formData.components.length === 0">
                                    <div class="bg-slate-50 px-4 py-5 text-sm text-slate-500 text-center">
                                        Belum ada komponen order.
                                    </div>
                                </template>
                                <template x-if="formData.components.length > 0">
                                    <div class="hidden md:grid bg-slate-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 grid-cols-12 gap-2">
                                        <div class="col-span-8">Komponen</div>
                                        <div class="col-span-1 text-center">Qty</div>
                                        <div class="col-span-2 text-center">Stok</div>
                                        <div class="col-span-1"></div>
                                    </div>
                                </template>
                                <template x-for="(component, index) in formData.components" :key="`component-${index}`">
                                    <div class="overflow-visible border-t border-slate-200 first:border-t-0 bg-white px-3 py-2.5">
                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start">
                                            <div class="md:col-span-8">
                                                <template x-if="!component.is_custom">
                                                    <input type="text" :value="component.search || component.component_name || 'Komponen'" class="form-input w-full h-10 text-sm bg-slate-50" readonly>
                                                </template>
                                                <template x-if="component.is_custom">
                                                    <div class="relative" @click.outside="closeComponentDropdown(index)">
                                                        <input
                                                            type="text"
                                                            x-model="component.search"
                                                            @focus="openComponentDropdown(index)"
                                                            @input="syncComponentInput(index, $event.target.value)"
                                                            @blur="handleComponentBlur(index)"
                                                            class="form-input w-full h-10 text-sm"
                                                            :class="branchSelectionRequired || !canEditComponents ? 'bg-slate-100 cursor-not-allowed opacity-75' : ''"
                                                            :disabled="detailMode || branchSelectionRequired || !canEditComponents"
                                                            :placeholder="branchSelectionRequired ? 'Pilih cabang terlebih dahulu' : 'Cari komponen...'"
                                                        >

                                                        <div
                                                            x-show="component.dropdownOpen"
                                                            x-transition
                                                            class="absolute left-0 right-0 z-40 mt-1 w-full min-w-[24rem] rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden"
                                                            style="display: none;"
                                                        >
                                                            <div class="max-h-64 overflow-y-auto">
                                                                <template x-if="branchSelectionRequired">
                                                                    <div class="px-4 py-4 text-sm text-slate-400 text-center">
                                                                        Pilih cabang terlebih dahulu.
                                                                    </div>
                                                                </template>

                                                                <template x-if="!branchSelectionRequired">
                                                                    <div>
                                                                        <template x-for="option in filteredComponentsForRow(index)" :key="`order-component-${index}-${option.id}`">
                                                                            <button
                                                                                type="button"
                                                                                @mousedown.prevent="selectComponent(index, option)"
                                                                                class="w-full px-4 py-3 text-left hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0"
                                                                            >
                                                                                <div class="font-medium text-slate-800" x-text="option.label"></div>
                                                                                <div class="text-xs text-slate-400 mt-0.5" x-text="[option.category_name || null, option.branch_name || null].filter(Boolean).join(' - ') || 'Komponen'"></div>
                                                                            </button>
                                                                        </template>

                                                                        <div x-show="filteredComponentsForRow(index).length === 0" class="px-4 py-4 text-sm text-slate-400 text-center">
                                                                            Tidak ada komponen yang cocok.
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="md:col-span-1">
                                                <label class="block md:hidden text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Qty</label>
                                                <input type="number" min="1" x-model.number="component.qty" class="form-input w-full h-10 text-center text-sm" placeholder="1" :disabled="isFieldLocked(!canEditComponents)">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block md:hidden text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Stok</label>
                                                <div class="h-10 rounded-xl border px-2 flex items-center justify-center text-sm font-semibold"
                                                :class="Number(component.qty || 0) > Number(componentStock(component) || 0) ? 'border-red-200 bg-red-50 text-red-600' : 'border-slate-200 bg-slate-50 text-slate-700'">
                                                    <span x-text="formatStock(componentStock(component))"></span>
                                                </div>
                                            </div>
                                            <div class="md:col-span-1 flex justify-center md:pt-0 pt-5">
                                                <button type="button" @click="removeComponentRow(index)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 text-red-500 hover:bg-red-50 transition-all" title="Hapus komponen" :disabled="isFieldLocked(!canEditComponents)" x-show="!detailMode && canEditComponents">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800">Pembayaran Order</h4>
                                    <p class="text-[11px] text-slate-500">Isi DP, cicilan, atau pelunasan jika sudah ada.</p>
                                </div>
                                <button x-show="!detailMode && canEditPayments" type="button" @click="addPaymentRow()" class="btn btn-outline text-xs px-3 py-1.5 rounded-lg">Tambah Pembayaran</button>
                            </div>

                            <div class="space-y-2">
                                <template x-if="formData.payments.length === 0">
                                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-xs text-slate-500 text-center">
                                        Belum ada pembayaran.
                                    </div>
                                </template>
                                <template x-for="(payment, index) in formData.payments" :key="`payment-${index}`">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end rounded-xl border border-slate-200 p-3">
                                        <div class="md:col-span-2">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal</label>
                                            <input type="date" x-model="payment.payment_date" class="form-input w-full text-sm" :disabled="isFieldLocked(!canEditPayments)">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Tipe</label>
                                            <select x-model="payment.payment_type" class="form-input w-full text-sm" :disabled="isFieldLocked(!canEditPayments)">
                                                <option value="dp">DP</option>
                                                <option value="pelunasan">Pelunasan</option>
                                                <option value="cicilan">Cicilan</option>
                                                <option value="refund">Refund</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Nominal</label>
                                            <input type="text" :value="formatCurrency(payment.amount)" @input="handleNestedCurrencyInput($event, 'payments', index, 'amount')" class="form-input w-full text-sm text-right font-mono" :disabled="isFieldLocked(!canEditPayments)">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Metode</label>
                                            <input type="text" x-model="payment.payment_method" class="form-input w-full text-sm" placeholder="Cash / Transfer" :disabled="isFieldLocked(!canEditPayments)">
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Referensi</label>
                                            <input type="text" x-model="payment.reference_number" class="form-input w-full text-sm" placeholder="No. referensi" :disabled="isFieldLocked(!canEditPayments)">
                                        </div>
                                        <div class="md:col-span-1">
                                            <button type="button" @click="removePaymentRow(index)" class="w-full h-10 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-all" :disabled="isFieldLocked(!canEditPayments)" x-show="!detailMode && canEditPayments">
                                                <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                        <div class="md:col-span-12">
                                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Catatan</label>
                                            <input type="text" x-model="payment.notes" class="form-input w-full text-sm" placeholder="Catatan pembayaran" :disabled="isFieldLocked(!canEditPayments)">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </fieldset>

                    <div class="border-t border-slate-100 pt-4 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-slate-500 text-xs uppercase tracking-wider">Subtotal</p>
                            <p class="mt-1 font-black text-slate-900" x-text="currency(subtotal)"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-slate-500 text-xs uppercase tracking-wider">Biaya Tambahan</p>
                            <p class="mt-1 font-black text-slate-900" x-text="currency(additionalCostTotal)"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-slate-500 text-xs uppercase tracking-wider">Grand Total</p>
                            <p class="mt-1 font-black text-slate-900" x-text="currency(grandTotal)"></p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-slate-500 text-xs uppercase tracking-wider">Sisa Tagihan</p>
                            <p class="mt-1 font-black text-slate-900" x-text="currency(remainingTotal)"></p>
                        </div>
                    </div>

                    <div class="mt-6 pt-2">
                        <div x-show="detailMode" class="flex justify-end">
                            <button type="button" @click="attemptClose()" class="btn btn-primary px-8 py-3 rounded-2xl min-w-[120px] justify-center">Tutup</button>
                        </div>

                        <div x-show="!detailMode" class="flex flex-col sm:flex-row sm:justify-end gap-3">
                            <button type="button" @click="attemptClose()" class="btn btn-outline px-8 py-3 rounded-2xl justify-center sm:min-w-[120px]">Batal</button>
                            <button type="button" @click="printOrder()" class="btn btn-outline px-8 py-3 rounded-2xl justify-center sm:min-w-[160px]">Cetak Pesanan</button>
                            <button x-show="canSubmitForm" type="submit" class="btn btn-primary px-10 py-3 rounded-2xl shadow-xl shadow-brand/20 flex items-center justify-center sm:min-w-[170px]" :disabled="loading">
                                <template x-if="!loading">
                                    <span x-text="editMode ? 'Simpan Perubahan' : 'Simpan Order'"></span>
                                </template>
                                <template x-if="loading">
                                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </template>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function machineOrderPage(config) {
    return {
        showModal: false,
        editMode: false,
        detailMode: false,
        loading: false,
        masterDataLoading: false,
        masterDataLoaded: false,
        masterDataBranchId: '',
        customers: config.customers || [],
        sales: config.sales || [],
        machines: config.machines || [],
        components: config.components || [],
        costTypes: config.costTypes || [],
        branches: config.branches || [],
        isSuperAdmin: config.isSuperAdmin || false,
        defaultBranchId: config.defaultBranchId || '',
        statusOptions: [
            { value: 'draft', label: 'Draft' },
            { value: 'confirmed', label: 'Confirmed' },
            { value: 'in_production', label: 'In Production' },
            { value: 'ready', label: 'Ready' },
            { value: 'completed', label: 'Completed' },
            { value: 'cancelled', label: 'Cancelled' },
        ],
        formData: {},
        originalSnapshot: '',

        get filteredCustomers() {
            if (this.isSuperAdmin && !this.formData.branch_id) {
                return this.customers;
            }

            return this.customers.filter(item => !item.branch_id || String(item.branch_id) === String(this.formData.branch_id));
        },

        get filteredSales() {
            if (this.isSuperAdmin && !this.formData.branch_id) {
                return this.sales;
            }

            return this.sales.filter(item => !item.branch_id || String(item.branch_id) === String(this.formData.branch_id));
        },

        get filteredMachines() {
            if (this.isSuperAdmin && !this.formData.branch_id) {
                return this.machines;
            }

            return this.machines.filter(item => !item.branch_id || String(item.branch_id) === String(this.formData.branch_id));
        },

        get filteredComponents() {
            if (this.isSuperAdmin && !this.formData.branch_id) {
                return this.components;
            }

            return this.components.filter(item => !item.branch_id || String(item.branch_id) === String(this.formData.branch_id));
        },

        get filteredBranchComponents() {
            const branchId = String(this.formData.branch_id || '');

            if (!branchId && this.isSuperAdmin) {
                return [];
            }

            if (!branchId) {
                return this.components;
            }

            return this.components.filter(item => !item.branch_id || String(item.branch_id) === branchId);
        },

        get branchSelectionRequired() {
            return this.isSuperAdmin && !this.formData.branch_id;
        },

        get currentStatus() {
            return String(this.formData.status || 'draft');
        },

        get isReadOnlyStatus() {
            return ['completed', 'cancelled'].includes(this.currentStatus);
        },

        get canEditCoreHeader() {
            return this.currentStatus === 'draft';
        },

        get canEditHeader() {
            return ['draft', 'confirmed'].includes(this.currentStatus);
        },

        get canEditStatus() {
            return true;
        },

        get canEditPricing() {
            return ['draft', 'confirmed'].includes(this.currentStatus);
        },

        get canEditComponents() {
            return ['draft', 'confirmed'].includes(this.currentStatus);
        },

        get canEditCosts() {
            return ['draft', 'confirmed'].includes(this.currentStatus);
        },

        get canEditPayments() {
            return ['draft', 'confirmed', 'in_production', 'ready'].includes(this.currentStatus);
        },

        get canEditProductionDates() {
            return ['draft', 'confirmed', 'in_production', 'ready'].includes(this.currentStatus);
        },

        get canSubmitForm() {
            return true;
        },

        get statusLockMessage() {
            const messages = {
                draft: 'Semua data order masih bisa diubah.',
                confirmed: 'Data inti order seperti cabang, pelanggan, mesin, dan qty sudah dikunci. Komponen, biaya, dan pembayaran masih bisa diubah.',
                in_production: 'Order sudah masuk produksi. Header inti, komponen, dan biaya tambahan dikunci. Pembayaran serta tanggal produksi masih bisa diubah.',
                ready: 'Order sudah siap. Hampir semua data dikunci, hanya pembayaran dan tanggal produksi yang masih bisa diubah.',
                completed: 'Order selesai. Seluruh data bersifat readonly.',
                cancelled: 'Order dibatalkan. Seluruh data bersifat readonly.',
            };

            return messages[this.currentStatus] || '';
        },

        get subtotal() {
            const baseTotal = Number(this.formData.base_price || 0) * Number(this.formData.qty || 0);
            const discountValue = Number(this.formData.discount_value || 0);

            if (this.formData.discount_type === 'percent') {
                return Math.max(baseTotal - (baseTotal * (discountValue / 100)), 0);
            }

            if (this.formData.discount_type === 'amount') {
                return Math.max(baseTotal - discountValue, 0);
            }

            return baseTotal;
        },

        get additionalCostTotal() {
            return (this.formData.costs || []).reduce((total, item) => {
                return total + (Number(item.qty || 0) * Number(item.price || 0));
            }, 0);
        },

        get grandTotal() {
            return this.subtotal + this.additionalCostTotal;
        },

        get paidTotal() {
            return (this.formData.payments || []).reduce((total, item) => {
                const amount = Number(item.amount || 0);
                return total + (item.payment_type === 'refund' ? (-1 * amount) : amount);
            }, 0);
        },

        get remainingTotal() {
            return Math.max(this.grandTotal - this.paidTotal, 0);
        },

        init() {
            this.resetForm();
        },

        defaultFormData() {
            return {
                id: '',
                branch_id: this.defaultBranchId,
                order_number: '',
                order_date: '{{ date('Y-m-d') }}',
                customer_id: '',
                sales_id: '',
                machine_id: '',
                qty: 1,
                base_price: 0,
                discount_type: '',
                discount_value: 0,
                estimated_start_date: '',
                estimated_finish_date: '',
                actual_finish_date: '',
                notes: '',
                internal_notes: '',
                status: 'draft',
                costs: [],
                components: [],
                payments: [],
            };
        },

        snapshotState() {
            return JSON.stringify(this.formData);
        },

        isFormDirty() {
            return this.snapshotState() !== this.originalSnapshot;
        },

        resetForm() {
            this.formData = this.defaultFormData();
            this.originalSnapshot = this.snapshotState();
        },

        async loadMasterData(branchId = '') {
            const normalizedBranchId = String(branchId || '');

            if (this.masterDataLoading) {
                return;
            }

            this.masterDataLoading = true;

            try {
                const response = await axios.get(`{{ route('machine-order.master-data') }}`, {
                    params: normalizedBranchId ? { branch_id: normalizedBranchId } : {},
                });

                const payload = response.data || {};
                this.customers = (payload.customers || []).map((customer) => ({
                    id: customer.id,
                    name: customer.full_name || 'Pelanggan',
                    branch_id: customer.branch_id || null,
                }));
                this.sales = (payload.sales || []).map((item) => ({
                    id: item.id,
                    name: item.name || 'Sales',
                    branch_id: item.branch_id || null,
                }));
                this.machines = (payload.machines || []).map((machine) => ({
                    id: machine.id,
                    machine_number: machine.machine_number || 'Mesin',
                    branch_id: machine.branch_id || null,
                    base_price: Number(machine.base_price || 0),
                    machine_type_name: machine.type?.name || machine.machine_type_name || '',
                    machine_components: (machine.machine_components || []).map((component) => ({
                        component_id: component.component_id || component.component?.id || null,
                        component_name: component.component?.name || '-',
                        qty: Number(component.qty || 1),
                    })),
                }));
                this.components = (payload.components || []).map((component) => ({
                    id: component.id,
                    name: component.name || 'Komponen',
                    type_size: component.type_size || '',
                    label: (component.name || 'Komponen') + (component.type_size ? ' - ' + component.type_size : ''),
                    qty: Number(component.qty || 0),
                    branch_id: component.branch_id || null,
                    branch_name: component.branch?.name || component.branch_name || '',
                    category_name: component.component_category?.name || component.component_category_name || '',
                }));
                this.masterDataLoaded = true;
                this.masterDataBranchId = normalizedBranchId;
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Gagal memuat data master order.');
            } finally {
                this.masterDataLoading = false;
            }
        },

        async ensureMasterDataLoaded(branchId = '') {
            const normalizedBranchId = String(branchId || '');

            if (this.masterDataLoaded && this.masterDataBranchId === normalizedBranchId) {
                return;
            }

            await this.loadMasterData(normalizedBranchId);
        },

        async handleBranchChanged() {
            if (!this.canEditCoreHeader) {
                return;
            }

            if (this.formData.branch_id) {
                await this.ensureMasterDataLoaded(this.formData.branch_id);
            } else {
                this.customers = [];
                this.sales = [];
                this.machines = [];
                this.components = [];
                this.masterDataLoaded = false;
                this.masterDataBranchId = '';
            }

            this.formData.customer_id = '';
            this.formData.sales_id = '';
            this.formData.machine_id = '';
            this.formData.base_price = 0;
            this.formData.components = [];
        },

        attemptClose() {
            if (!this.detailMode && this.isFormDirty()) {
                const confirmed = window.confirm('Perubahan belum disimpan. Yakin tutup modal?');
                if (!confirmed) {
                    return;
                }
            }

            this.showModal = false;
        },

        isFieldLocked(condition = false) {
            return this.detailMode || condition;
        },

        sanitizeCurrency(value) {
            return String(value ?? '').replace(/[^0-9]/g, '');
        },

        normalizeBoolean(value) {
            if (value === true || value === false) {
                return value;
            }

            if (typeof value === 'number') {
                return value === 1;
            }

            const normalized = String(value ?? '').toLowerCase().trim();
            return ['1', 'true', 'yes', 'ya'].includes(normalized);
        },

        formatCurrency(value) {
            const sanitized = this.sanitizeCurrency(value);
            if (!sanitized) {
                return '';
            }

            const number = Number(sanitized);
            return new Intl.NumberFormat('id-ID').format(Number.isNaN(number) ? 0 : number);
        },

        currency(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        formatStock(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value || 0));
        },

        componentStock(component) {
            const selected = this.componentById(component?.component_id);
            return Number(selected?.qty || 0);
        },

        handleCurrencyInput(event, field) {
            const sanitized = this.sanitizeCurrency(event.target.value);
            this.formData[field] = sanitized;
            event.target.value = this.formatCurrency(sanitized);
        },

        handleNestedCurrencyInput(event, group, index, field) {
            const sanitized = this.sanitizeCurrency(event.target.value);
            this.formData[group][index][field] = sanitized;
            event.target.value = this.formatCurrency(sanitized);
        },

        handleDiscountInput(event) {
            if (this.formData.discount_type === 'percent') {
                const sanitized = String(event.target.value || '').replace(/[^0-9.]/g, '');
                this.formData.discount_value = sanitized;
                event.target.value = sanitized;
                return;
            }

            const sanitized = this.sanitizeCurrency(event.target.value);
            this.formData.discount_value = sanitized;
            event.target.value = this.formatCurrency(sanitized);
        },

        machineById(id) {
            return this.machines.find(item => String(item.id) === String(id));
        },

        componentById(id) {
            return this.components.find(item => String(item.id) === String(id));
        },

        branchById(id) {
            return this.branches.find(item => String(item.id) === String(id));
        },

        customerById(id) {
            return this.customers.find(item => String(item.id) === String(id));
        },

        salesById(id) {
            return this.sales.find(item => String(item.id) === String(id));
        },

        statusLabel(value) {
            return this.statusOptions.find(item => item.value === value)?.label || value || '-';
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        printOrder() {
            const branchDetail = this.branchById(this.formData.branch_id) || {};
            const branch = branchDetail?.name || '-';
            const customer = this.customerById(this.formData.customer_id)?.name || '-';
            const machine = this.machineById(this.formData.machine_id);
            const machineLabel = machine
                ? `${machine.machine_number || '-'}${machine.machine_type_name ? ' - ' + machine.machine_type_name : ''}`
                : '-';

            const itemRows = [
                {
                    no: 1,
                    description: `Order Mesin ${machineLabel}`,
                    price: Number(this.formData.base_price || 0),
                    qty: Number(this.formData.qty || 0),
                    subtotal: Number(this.formData.base_price || 0) * Number(this.formData.qty || 0),
                },
                ...(this.formData.costs || [])
                    .filter(item => item.cost_name || Number(item.price || 0) > 0)
                    .map((cost, index) => ({
                        no: index + 2,
                        description: cost.cost_name || this.costTypeById(cost.cost_type_id)?.name || 'Biaya Tambahan',
                        price: Number(cost.price || 0),
                        qty: Number(cost.qty || 0),
                        subtotal: Number(cost.price || 0) * Number(cost.qty || 0),
                    })),
            ];

            const itemRowsHtml = itemRows.map((item) => `
                    <tr>
                        <td class="text-right">${item.no}</td>
                        <td>${this.escapeHtml(item.description)}</td>
                        <td class="text-right">${this.escapeHtml(this.currency(item.price))}</td>
                        <td class="text-right">0</td>
                        <td class="text-right">${this.escapeHtml(item.qty)}</td>
                        <td class="text-right">${this.escapeHtml(this.currency(item.subtotal))}</td>
                    </tr>
            `).join('');

            const issuedDate = this.formData.order_date || '-';
            const branchCity = branchDetail?.city || branch;
            const printTitle = this.formData.order_number || 'Order Mesin';

            const printWindow = window.open('', '_blank', 'width=1024,height=768');
            if (!printWindow) {
                window.toast.error('Popup diblokir browser. Izinkan popup untuk mencetak pesanan.');
                return;
            }

            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Pesanan ${this.escapeHtml(printTitle)}</title>
                    <style>
                        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; background: #f3f4f6; }
                        .page { width: 960px; margin: 24px auto; background: #fff; border: 1px solid #d1d5db; padding: 18px; }
                        .toolbar { width: 960px; margin: 24px auto 0; display: flex; justify-content: flex-end; gap: 12px; }
                        .btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; border: 1px solid #d1d5db; color: #111827; background: #fff; }
                        .btn-primary { background: #f97316; color: #fff; border-color: #f97316; }
                        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; }
                        .brand { font-size: 24px; font-weight: 700; letter-spacing: 0.6px; margin-bottom: 10px; color: #111827; }
                        .subtle { color: #4b5563; line-height: 1.45; }
                        .branch-meta { font-size: 13px; }
                        .branch-meta strong { font-size: 17px; color: #111827; }
                        .brand-logo { max-width: 220px; max-height: 80px; width: auto; height: auto; display: block; }
                        .divider { margin: 14px 0 18px; border-top: 2px solid #f97316; }
                        .title { text-align: center; margin: 6px 0 18px; }
                        .title h1 { margin: 0; letter-spacing: 6px; font-size: 28px; }
                        .title p { margin: 4px 0 0; font-weight: 700; }
                        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px; }
                        .callout { text-align: center; color: #f97316; font-weight: 700; font-size: 18px; }
                        table { width: 100%; border-collapse: collapse; font-size: 14px; }
                        th, td { border: 1px solid #d1d5db; padding: 8px 10px; }
                        th { background: #f9fafb; text-align: left; }
                        .text-right { text-align: right; }
                        .summary td { border-top: none; }
                        .notes { margin-top: 22px; display: flex; justify-content: space-between; gap: 32px; }
                        .notes ol { margin: 6px 0 0 18px; padding: 0; }
                        .signature { text-align: center; min-width: 220px; }
                        .signature .space { height: 70px; }
                        @media print {
                            body { background: #fff; }
                            .toolbar { display: none; }
                            .page { width: auto; margin: 0; border: none; padding: 0; }
                        }
                    </style>
                </head>
                <body>
                    <div class="toolbar">
                        <button onclick="window.print()" class="btn btn-primary">Print / Save PDF</button>
                    </div>

                    <div class="page">
                        <div class="header">
                            <div>
                                <div class="brand">PIONEER CNC INDONESIA</div>
                                <div class="subtle branch-meta">
                                    <div><strong>${this.escapeHtml(branch)}</strong></div>
                                    <div>${this.escapeHtml(branchDetail?.address || '-')}</div>
                                    <div>HP. ${this.escapeHtml(branchDetail?.phone || '-')}</div>
                                    <div>${this.escapeHtml(branchDetail?.website || '-')}</div>
                                    <div>${this.escapeHtml(branchDetail?.email || '-')}</div>
                                </div>
                            </div>
                            <img src="${window.location.origin}/images/logo.png" alt="Logo Pioneer CNC" class="brand-logo" onerror="this.style.display='none'">
                        </div>

                        <div class="divider"></div>

                        <div class="title">
                            <h1>PESANAN</h1>
                            <p>No : ${this.escapeHtml(this.formData.order_number || '-')}</p>
                        </div>

                        <div class="meta-grid">
                            <div class="subtle">
                                <div><strong>Kepada Yth.</strong></div>
                                <div>${this.escapeHtml(customer)}</div>
                            </div>
                            <div class="callout">
                                KONFIRMASI PESANAN<br>
                                MESIN
                            </div>
                        </div>

                        <p class="subtle" style="font-style:italic;">Dengan ini kami sampaikan rincian pesanan mesin sebagai bahan konfirmasi kepada pelanggan, dengan detail sebagai berikut:</p>

                        <table>
                            <thead>
                                <tr>
                                    <th style="width:48px;">No</th>
                                    <th>Deskripsi</th>
                                    <th style="width:130px;">Harga</th>
                                    <th style="width:90px;">Disc(%)</th>
                                    <th style="width:90px;">Qty</th>
                                    <th style="width:150px;">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemRowsHtml}
                                <tr class="summary">
                                    <td colspan="5" class="text-right"><strong>Subtotal</strong></td>
                                    <td class="text-right">${this.escapeHtml(this.currency(this.subtotal))}</td>
                                </tr>
                                <tr class="summary">
                                    <td colspan="5" class="text-right">Biaya Tambahan</td>
                                    <td class="text-right">${this.escapeHtml(this.currency(this.additionalCostTotal))}</td>
                                </tr>
                                <tr class="summary">
                                    <td colspan="5" class="text-right">Disc.</td>
                                    <td class="text-right">${this.escapeHtml(this.currency(0))}</td>
                                </tr>
                                <tr class="summary">
                                    <td colspan="5" class="text-right"><strong>Grand Total</strong></td>
                                    <td class="text-right"><strong>${this.escapeHtml(this.currency(this.grandTotal))}</strong></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="notes">
                            <div style="flex:1;">
                                <div><strong>Catatan:</strong></div>
                                <ol class="subtle">
                                    <li>Spesifikasi utama pesanan: ${this.escapeHtml(machineLabel)}.</li>
                                    <li>Tanggal order: ${this.escapeHtml(this.formData.order_date || '-')}.</li>
                                    <li>Estimasi mulai pengerjaan: ${this.escapeHtml(this.formData.estimated_start_date || '-')}.</li>
                                    <li>Estimasi selesai pengerjaan: ${this.escapeHtml(this.formData.estimated_finish_date || '-')}.</li>
                                </ol>
                            </div>
                            <div class="signature">
                                <div>${this.escapeHtml(branchCity)}, ${this.escapeHtml(issuedDate)}</div>
                                <div class="space"></div>
                                <div><u>Admin</u></div>
                                <div>Pioneer CNC Indonesia</div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => printWindow.print(), 250);
        },

        normalizeOrderComponent(item, machineId = '') {
            const componentId = item?.component_id
                ?? item?.component?.id
                ?? item?.component?.component_id
                ?? item?.master_component_id
                ?? '';

            const componentName = item?.component_name
                ?? item?.component?.name
                ?? item?.master_component_name
                ?? '';

            const machine = this.machineById(machineId);
            const machineComponentIds = (machine?.machine_components || []).map(component => String(component.component_id || ''));
            const isMachineComponent = componentId ? machineComponentIds.includes(String(componentId)) : false;

            return {
                component_id: componentId ? String(componentId) : '',
                component_name: componentName || '',
                search: componentName || '',
                dropdownOpen: false,
                qty: Number(item?.qty || 1),
                notes: item?.notes || '',
                is_optional: this.normalizeBoolean(item?.is_optional),
                is_custom: !isMachineComponent,
            };
        },

        costTypeById(id) {
            return this.costTypes.find(item => String(item.id) === String(id));
        },

        normalizeOrderCost(item) {
            const rawCostTypeId = item?.cost_type_id
                ?? item?.cost_type?.id
                ?? item?.type?.id
                ?? '';

            const costName = item?.cost_name
                ?? item?.cost_type?.name
                ?? item?.cost_type_name
                ?? item?.type?.name
                ?? '';

            const matchedCostType = !rawCostTypeId && costName
                ? this.costTypes.find(type => String(type.name || '').toLowerCase().trim() === String(costName).toLowerCase().trim())
                : null;

            const costTypeId = rawCostTypeId || matchedCostType?.id || '';

            return {
                cost_type_id: costTypeId ? String(costTypeId) : '',
                cost_name: costName || '',
                description: item?.description || '',
                qty: Number(item?.qty || 1),
                price: this.sanitizeCurrency(item?.price),
            };
        },

        normalizeMachineComponents(machine, orderQty = 1) {
            return (machine?.machine_components || []).map(item => ({
                component_id: String(item.component_id || ''),
                component_name: item.component_name || '-',
                search: item.component_name || '-',
                dropdownOpen: false,
                qty: Number(item.qty || 1) * Number(orderQty || 1),
                notes: '',
                is_optional: false,
                is_custom: false,
            }));
        },

        handleMachineChanged() {
            if (!this.canEditCoreHeader) {
                return;
            }

            if (this.branchSelectionRequired) {
                window.toast.error('Pilih cabang terlebih dahulu.');
                return;
            }

            const machine = this.machineById(this.formData.machine_id);
            if (!machine) {
                this.formData.base_price = 0;
                this.formData.components = [];
                return;
            }

            this.formData.base_price = this.sanitizeCurrency(machine.base_price);
            if (!this.editMode || this.formData.components.length === 0) {
                this.formData.components = this.normalizeMachineComponents(machine, this.formData.qty);
            }
        },

        handleQtyChanged() {
            if (!this.canEditCoreHeader || !this.formData.machine_id || this.editMode) {
                return;
            }

            this.applyMachineComponents();
        },

        applyMachineComponents() {
            if (!this.canEditComponents) {
                return;
            }

            if (this.branchSelectionRequired) {
                window.toast.error('Pilih cabang terlebih dahulu.');
                return;
            }

            const machine = this.machineById(this.formData.machine_id);
            if (!machine) {
                window.toast.error('Pilih mesin terlebih dahulu.');
                return;
            }

            this.formData.components = this.normalizeMachineComponents(machine, this.formData.qty);
        },

        addCostRow() {
            if (!this.canEditCosts) {
                return;
            }

            this.formData.costs.push({
                cost_type_id: '',
                cost_name: '',
                description: '',
                qty: 1,
                price: 0,
            });
        },

        removeCostRow(index) {
            if (!this.canEditCosts) {
                return;
            }

            this.formData.costs.splice(index, 1);
        },

        syncCostType(index) {
            if (!this.canEditCosts) {
                return;
            }

            const current = this.formData.costs[index];
            const type = this.costTypeById(current.cost_type_id);
            if (type && !current.cost_name) {
                current.cost_name = type.name;
            }
        },

        addComponentRow() {
            if (!this.canEditComponents) {
                return;
            }

            this.formData.components.push({
                component_id: '',
                component_name: '',
                search: '',
                dropdownOpen: false,
                qty: 1,
                notes: '',
                is_optional: false,
                is_custom: true,
            });
        },

        removeComponentRow(index) {
            if (!this.canEditComponents) {
                return;
            }

            this.formData.components.splice(index, 1);
        },

        syncComponent(index) {
            if (!this.canEditComponents) {
                return;
            }

            const current = this.formData.components[index];
            const component = this.componentById(current.component_id);
            if (component) {
                current.component_name = component.name;
                current.search = component.label || component.name;
            }
        },

        openComponentDropdown(index) {
            const row = this.formData.components[index];
            if (this.detailMode || !this.canEditComponents || this.branchSelectionRequired || !row?.is_custom) {
                return;
            }

            this.formData.components = this.formData.components.map((item, itemIndex) => ({
                ...item,
                dropdownOpen: item.is_custom ? itemIndex === index : false,
            }));
        },

        closeComponentDropdown(index) {
            if (!this.formData.components[index]) {
                return;
            }

            this.formData.components[index].dropdownOpen = false;
        },

        isComponentUsedInAnotherRow(componentId, currentIndex) {
            return this.formData.components.some((item, itemIndex) => {
                return itemIndex !== currentIndex && String(item.component_id || '') === String(componentId || '');
            });
        },

        filteredComponentsForRow(index) {
            const row = this.formData.components[index];
            const keyword = String(row?.search || '').toLowerCase().trim();

            return this.filteredBranchComponents
                .filter((item) => {
                    if (this.isComponentUsedInAnotherRow(item.id, index)) {
                        return false;
                    }

                    if (!keyword) {
                        return true;
                    }

                    return [
                        item.name,
                        item.type_size,
                        item.category_name,
                        item.branch_name,
                        item.label,
                    ].filter(Boolean).some((value) => String(value).toLowerCase().includes(keyword));
                })
                .slice(0, 20);
        },

        selectComponent(index, option) {
            if (!this.canEditComponents) {
                return;
            }

            if (!this.formData.components[index]) {
                return;
            }

            if (this.isComponentUsedInAnotherRow(option.id, index)) {
                window.toast.error('Komponen ini sudah dipilih di baris lain.');
                return;
            }

            this.formData.components[index].component_id = String(option.id);
            this.formData.components[index].component_name = option.name || option.label || '';
            this.formData.components[index].search = option.label || option.name || '';
            this.formData.components[index].dropdownOpen = false;
        },

        syncComponentInput(index, value) {
            if (!this.canEditComponents) {
                return;
            }

            if (!this.formData.components[index]) {
                return;
            }

            const row = this.formData.components[index];
            row.search = value;
            row.dropdownOpen = !this.branchSelectionRequired;

            const matched = this.filteredBranchComponents.find((item) => item.label === value);
            if (matched && this.isComponentUsedInAnotherRow(matched.id, index)) {
                row.component_id = '';
                row.component_name = '';
                window.toast.error('Komponen ini sudah dipilih di baris lain.');
                return;
            }

            row.component_id = matched ? String(matched.id) : '';
            row.component_name = matched ? (matched.name || matched.label || '') : '';
        },

        handleComponentBlur(index) {
            setTimeout(() => this.closeComponentDropdown(index), 150);
        },

        addPaymentRow() {
            if (!this.canEditPayments) {
                return;
            }

            this.formData.payments.push({
                payment_date: this.formData.order_date || '{{ date('Y-m-d') }}',
                payment_type: 'dp',
                amount: 0,
                payment_method: '',
                reference_number: '',
                notes: '',
            });
        },

        removePaymentRow(index) {
            if (!this.canEditPayments) {
                return;
            }

            this.formData.payments.splice(index, 1);
        },

        async openCreate() {
            this.editMode = false;
            this.detailMode = false;
            this.resetForm();
            this.showModal = true;
            if (this.formData.branch_id || this.defaultBranchId) {
                await this.ensureMasterDataLoaded(this.formData.branch_id || this.defaultBranchId);
            }
        },

        async openEdit(id) {
            this.editMode = true;
            this.detailMode = false;
            await this.loadOrder(id);
        },

        async openDetail(id) {
            this.editMode = false;
            this.detailMode = true;
            await this.loadOrder(id);
        },

        async loadOrder(id) {
            this.loading = true;

            try {
                const response = await axios.get(`{{ route('machine-order.show', ['id' => '__ID__']) }}`.replace('__ID__', id));
                const data = response.data?.data ?? response.data;

                await this.ensureMasterDataLoaded(data.branch_id || this.defaultBranchId);

                this.formData = {
                    id: data.id,
                    branch_id: String(data.branch_id || this.defaultBranchId),
                    order_number: data.order_number || '',
                    order_date: data.order_date || '{{ date('Y-m-d') }}',
                    customer_id: String(data.customer_id || ''),
                    sales_id: data.sales_id ? String(data.sales_id) : '',
                    machine_id: String(data.machine_id || ''),
                    qty: Number(data.qty || 1),
                    base_price: this.sanitizeCurrency(data.base_price),
                    discount_type: data.discount_type || '',
                    discount_value: data.discount_type === 'percent'
                        ? String(data.discount_value || 0)
                        : this.sanitizeCurrency(data.discount_value),
                    estimated_start_date: data.estimated_start_date || '',
                    estimated_finish_date: data.estimated_finish_date || '',
                    actual_finish_date: data.actual_finish_date || '',
                    notes: data.notes || '',
                    internal_notes: data.internal_notes || '',
                    status: data.status || 'draft',
                    costs: (data.costs || []).map(item => this.normalizeOrderCost(item)),
                    components: (data.components || []).map(item => this.normalizeOrderComponent(item, data.machine_id)),
                    payments: (data.payments || []).map(item => ({
                        payment_date: item.payment_date || '',
                        payment_type: item.payment_type || 'dp',
                        amount: this.sanitizeCurrency(item.amount),
                        payment_method: item.payment_method || '',
                        reference_number: item.reference_number || '',
                        notes: item.notes || '',
                    })),
                };

                this.showModal = true;
                this.originalSnapshot = this.snapshotState();
            } catch (error) {
                const message = error.response?.data?.message || 'Gagal memuat detail order.';
                window.toast.error(message);
            } finally {
                this.loading = false;
            }
        },

        validateForm() {
            if (!this.formData.branch_id && this.isSuperAdmin) {
                window.toast.error('Cabang wajib dipilih.');
                return false;
            }
            if (!this.formData.customer_id) {
                window.toast.error('Pelanggan wajib dipilih.');
                return false;
            }
            if (!this.formData.machine_id) {
                window.toast.error('Mesin wajib dipilih.');
                return false;
            }
            if (!this.formData.order_date) {
                window.toast.error('Tanggal order wajib diisi.');
                return false;
            }
            if (!Number(this.formData.qty || 0)) {
                window.toast.error('Qty wajib diisi.');
                return false;
            }
            if (!Number(this.formData.base_price || 0)) {
                window.toast.error('Base price wajib diisi.');
                return false;
            }

            return true;
        },

        buildPayload() {
            return {
                branch_id: this.formData.branch_id || null,
                order_date: this.formData.order_date,
                customer_id: Number(this.formData.customer_id),
                sales_id: this.formData.sales_id ? Number(this.formData.sales_id) : null,
                machine_id: Number(this.formData.machine_id),
                qty: Number(this.formData.qty || 1),
                base_price: Number(this.formData.base_price || 0),
                discount_type: this.formData.discount_type || null,
                discount_value: Number(this.formData.discount_value || 0),
                estimated_start_date: this.formData.estimated_start_date || null,
                estimated_finish_date: this.formData.estimated_finish_date || null,
                actual_finish_date: this.formData.actual_finish_date || null,
                notes: this.formData.notes || null,
                internal_notes: this.formData.internal_notes || null,
                status: this.formData.status,
                costs: (this.formData.costs || [])
                    .filter(item => item.cost_name || Number(item.price || 0) > 0)
                    .map(item => ({
                        cost_type_id: item.cost_type_id ? Number(item.cost_type_id) : null,
                        cost_name: item.cost_name || null,
                        description: item.description || null,
                        qty: Number(item.qty || 1),
                        price: Number(item.price || 0),
                    })),
                components: (this.formData.components || [])
                    .filter(item => item.component_id || item.component_name)
                    .map(item => ({
                        component_id: item.component_id ? Number(item.component_id) : null,
                        component_name: item.component_name || null,
                        qty: Number(item.qty || 1),
                        notes: item.notes || null,
                        is_optional: false,
                    })),
                payments: (this.formData.payments || [])
                    .filter(item => item.payment_date && Number(item.amount || 0) > 0)
                    .map(item => ({
                        payment_date: item.payment_date,
                        payment_type: item.payment_type,
                        amount: Number(item.amount || 0),
                        payment_method: item.payment_method || null,
                        reference_number: item.reference_number || null,
                        notes: item.notes || null,
                    })),
            };
        },

        async submitForm() {
            if (this.detailMode || !this.canSubmitForm) {
                return;
            }

            if (!this.validateForm()) {
                return;
            }

            this.loading = true;

            try {
                const payload = this.buildPayload();
                const url = this.editMode
                    ? `{{ route('machine-order.update', ['id' => '__ID__']) }}`.replace('__ID__', this.formData.id)
                    : `{{ route('machine-order.store') }}`;
                const method = this.editMode ? 'put' : 'post';

                const response = await axios({
                    method,
                    url,
                    data: payload,
                });

                window.toast.success(response.data.message || 'Machine order berhasil disimpan.');
                this.showModal = false;
                setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                const message = error.response?.data?.message || 'Gagal menyimpan machine order.';
                window.toast.error(message);
            } finally {
                this.loading = false;
            }
        },

        async deleteItem(id) {
            if (await window.SwalConfirm()) {
                try {
                    const response = await axios.delete(`{{ route('machine-order.destroy', ['id' => '__ID__']) }}`.replace('__ID__', id));
                    window.toast.success(response.data.message || 'Machine order berhasil dihapus.');
                    setTimeout(() => window.location.reload(), 500);
                } catch (error) {
                    window.toast.error(error.response?.data?.message || 'Gagal menghapus machine order.');
                }
            }
        },
    };
}
</script>
@endsection
