@extends('layouts.app')
@section('title', 'Daftar Plat')
@section('page-title', 'Master Data Plat (Varian)')

@section('content')
@php
    $sortBy      = request('sort_by', 'id');
    $sortDir     = request('sort_dir', 'desc');
    $isSuperAdmin = \App\Helpers\AuthHelper::isSuperAdmin();
    $userBranchId = session('branch_id');

    $sortUrl = function($column) use ($sortBy, $sortDir) {
        $dir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_dir' => $dir, 'page' => 1]);
    };

    $sortIcon = function($column) use ($sortBy, $sortDir) {
        $isCurrent = $sortBy === $column;
        $isAsc     = $isCurrent && $sortDir === 'asc';
        $isDesc    = $isCurrent && $sortDir === 'desc';
        $ascClass  = $isAsc  ? 'text-brand opacity-100' : '';
        $descClass = $isDesc ? 'text-brand opacity-100' : '';
        
        return "
            <div class='flex flex-col ml-1.5 opacity-40 group-hover:opacity-100 transition-opacity'>
                <svg class='w-2 h-2 $ascClass' fill='currentColor' viewBox='0 0 24 24'><path d='M12 4l-8 8h16l-8-8z'/></svg>
                <svg class='w-2 h-2 $descClass' fill='currentColor' viewBox='0 0 24 24'><path d='M12 20l8-8H4l8 8z'/></svg>
            </div>
        ";
    };
@endphp

<div x-data="{
    showModal: false,
    editMode:   false,
    detailMode: false,
    loading:    false,
    multiItems: [],
    selectedBranch: '',
    selectedType: '',
    parseCurrency(value) {
        if (value === null || value === undefined || value === '') {
            return 0;
        }

        if (typeof value === 'number') {
            return Number.isNaN(value) ? 0 : Math.round(value);
        }

        let normalized = String(value).trim().replace(/\s/g, '');

        if (!normalized) {
            return 0;
        }

        const lastComma = normalized.lastIndexOf(',');
        const lastDot = normalized.lastIndexOf('.');

        if (lastComma !== -1 && lastDot !== -1 && lastComma > lastDot) {
            normalized = normalized.replace(/\./g, '').replace(/,/g, '.');
        } else if (lastComma !== -1 && lastDot !== -1 && lastDot > lastComma) {
            normalized = normalized.replace(/,/g, '');
        } else if (lastComma !== -1) {
            const decimalDigits = normalized.length - lastComma - 1;
            normalized = decimalDigits === 3
                ? normalized.replace(/,/g, '')
                : normalized.replace(/,/g, '.');
        } else if (lastDot !== -1) {
            const decimalDigits = normalized.length - lastDot - 1;
            normalized = decimalDigits === 3
                ? normalized.replace(/\./g, '')
                : normalized;
        } else {
            normalized = normalized.replace(/,/g, '');
        }

        normalized = normalized.replace(/[^0-9.-]/g, '');

        const parsed = Number.parseFloat(normalized);
        return Number.isNaN(parsed) ? 0 : Math.round(parsed);
    },
    formatCurrency(value) {
        const amount = this.parseCurrency(value);
        if (!amount) {
            return '';
        }

        return new Intl.NumberFormat('id-ID').format(amount);
    },
    handleCurrencyInput(event, target, field = null) {
        const amount = this.parseCurrency(event.target.value);

        if (field) {
            target[field] = amount;
        }

        event.target.value = this.formatCurrency(amount);
    },
    normalizePriceFields(item) {
        return {
            ...item,
            price_buy: this.parseCurrency(item.price_buy),
            price_sell: this.parseCurrency(item.price_sell),
        };
    },
    openCreate() {
        this.detailMode = false;
        this.editMode   = false;
        this.formData   = {
            id: '', plate_type_id: '', size_id: '',
            branch_id: '{{ $isSuperAdmin ? '' : $userBranchId }}',
            qty: 0, price_buy: 0, price_sell: 0, is_active: true
        };
        this.showModal = true;
    },
    async openEdit(item) {
        this.detailMode = false;
        this.editMode   = true;
        this.loading    = true;
        this.showModal  = true;
        
        // Keep single formData populated for Detail mode safety
        this.formData   = {
            id:            item.id,
            plate_type_id: item.plate_type_id,
            size_id:       item.size_id,
            branch_id:     item.branch_id,
            qty:           item.qty,
            price_buy:     item.price_buy  || 0,
            price_sell:    item.price_sell || 0,
            is_active:     !!item.is_active
        };

        // Track which branch and type we are editing
        this.selectedBranch = item.branch_name;
        this.selectedType   = item.plate_type_name;

        try {
            const response = await axios.get('{{ route('master.plat.multi') }}', {
                params: { branch_id: item.branch_id, plate_type_id: item.plate_type_id }
            });
            this.multiItems = (response.data || []).map((entry) => this.normalizePriceFields(entry));
        } catch (error) {
            window.toast.error('Gagal memuat daftar ukuran');
            this.showModal = false;
        } finally {
            this.loading = false;
        }
    },
    openDetail(item) {
        this.openEdit(item);
        this.detailMode = true;
        this.editMode   = false;
    },
    async submitForm() {
        this.loading = true;
        try {
            if (this.editMode) {
                // Batch Update
                const response = await axios.put('{{ route('master.plat.batch') }}', {
                    items: this.multiItems.map((entry) => this.normalizePriceFields(entry))
                });
                window.toast.success(response.data.message || 'Batch update berhasil');
            } else {
                // Single Store
                const response = await axios.post('{{ route('master.plat.store') }}', this.normalizePriceFields(this.formData));
                window.toast.success(response.data.message || 'Data berhasil disimpan');
            }
            this.showModal = false;
            setTimeout(() => window.location.reload(), 500);
        } catch (error) {
            window.toast.error(error.response?.data?.message || 'Gagal menyimpan data');
        } finally {
            this.loading = false;
        }
    },
    async deleteItem(id) {
        if (await window.SwalConfirm()) {
            try {
                const response = await axios.delete('{{ route('master.plat.index') }}/' + id);
                window.toast.success(response.data.message || 'Berhasil dihapus');
                setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                window.toast.error(error.response?.data?.message || 'Gagal menghapus data');
            }
        }
    }
}">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">Varian Plat</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola stok dan harga berbagai varian plat per cabang.</p>
        </div>
        @if(\App\Helpers\MenuHelper::hasPermission('product', 'create'))
        <button @click="openCreate()" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Varian Plat</span>
        </button>
        @endif
    </div>

    <div class="card overflow-hidden">
        <form action="{{ route('master.plat.index') }}" method="GET" id="filterForm">
            <input type="hidden" name="sort_by"  value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">NO</th>
                            <th>
                                <a href="{{ $sortUrl('plate_type') }}" class="flex items-center group">
                                    <span>MATERIAL</span>
                                    {!! $sortIcon('plate_type') !!}
                                </a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('size') }}" class="flex items-center group">
                                    <span>UKURAN</span>
                                    {!! $sortIcon('size') !!}
                                </a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('branch_id') }}" class="flex items-center group">
                                    <span>CABANG</span>
                                    {!! $sortIcon('branch_id') !!}
                                </a>
                            </th>
                            <th class="w-32 text-center">HARGA JUAL</th>
                            <th class="w-24 text-center">QTY STOK</th>
                            <th class="w-24 text-center">AKSI</th>
                        </tr>
                        <tr class="bg-slate-50/50">
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner">
                                <select name="plate_type_id" onchange="this.form.submit()" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all">
                                    <option value="">— Cari Material —</option>
                                    @foreach($plateTypes as $type)
                                        <option value="{{ $type['id'] }}" {{ request('plate_type_id') == $type['id'] ? 'selected' : '' }}>{{ $type['name'] }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="py-2 px-4 shadow-inner">
                                <select name="size_id" onchange="this.form.submit()" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all">
                                    <option value="">— Cari Ukuran —</option>
                                    @foreach($sizes as $size)
                                        <option value="{{ $size['id'] }}" {{ request('size_id') == $size['id'] ? 'selected' : '' }}>{{ $size['name'] ?? $size['value'] }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="py-2 px-4 shadow-inner">
                                @if($isSuperAdmin)
                                <select name="branch_id" onchange="this.form.submit()" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all">
                                    <option value="">— Cari Cabang —</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch['id'] }}" {{ request('branch_id') == $branch['id'] ? 'selected' : '' }}>{{ $branch['name'] }}</option>
                                    @endforeach
                                </select>
                                @endif
                            </th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner text-center">
                                @if(request()->anyFilled(['plate_type_id', 'size_id', 'branch_id']))
                                    <a href="{{ route('master.plat.index') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plates as $item)
                        <tr>
                            <td class="text-center">
                                <span class="text-slate-400 font-mono text-xs">{{ ($plates->currentPage() - 1) * $plates->perPage() + $loop->iteration }}</span>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ $item['plate_type_name'] ?? ($item['plateType']['name'] ?? 'N/A') }}</span>
                                    <span class="text-[10px] text-slate-400 uppercase font-mono tracking-tighter">ID: {{ $item['id'] }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-outline text-xs px-2 py-0.5">{{ $item['size_value'] ?? ($item['size']['name'] ?? ($item['size']['value'] ?? 'N/A')) }}</span>
                            </td>
                            <td>
                                <span class="text-slate-700 text-sm font-medium">{{ $item['branch_name'] ?? ($item['branch']['name'] ?? 'Pusat') }}</span>
                            </td>
                            <td class="text-center font-mono text-sm uppercase">Rp {{ number_format($item['price_sell'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="font-bold {{ ($item['qty'] ?? 0) < ($item['minimum_stock'] ?? 0) ? 'text-red-600' : 'text-slate-700' }}">{{ number_format($item['qty'] ?? 0, 0) }}</span>
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-1">
                                    @if(\App\Helpers\MenuHelper::hasPermission('product', 'detail'))
                                    <button type="button" @click="openDetail({{ json_encode($item) }})" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                                    @endif
                                    @if(\App\Helpers\MenuHelper::hasPermission('product', 'edit'))
                                    <button type="button" @click="openEdit({{ json_encode($item) }})" class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                    @endif
                                    @if(\App\Helpers\MenuHelper::hasPermission('product', 'delete'))
                                    <button type="button" @click="deleteItem({{ $item['id'] }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="py-20 text-center text-slate-400 italic">Data varian plat belum tersedia.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
        @if($plates->hasPages()) <div class="p-4 border-t border-slate-100 bg-slate-50/30">{{ $plates->links() }}</div> @endif
    </div>

    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative inline-block w-full max-w-2xl p-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900" x-text="detailMode ? 'Detail Varian Plat' : (editMode ? 'Edit Varian Plat' : 'Tambah Varian Baru')"></h3>
                        <p class="text-xs text-slate-500 mt-1">Konfigurasikan bahan, letak cabang, serta harga jual.</p>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-xl transition-all"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <form @submit.prevent="submitForm()">
                    <!-- Mode EDIT: Multi-Variant Table -->
                    <template x-if="editMode">
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Kantor Cabang</label>
                                    <p class="text-slate-800 font-semibold" x-text="selectedBranch"></p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jenis Plat</label>
                                    <p class="text-slate-800 font-semibold" x-text="selectedType"></p>
                                </div>
                            </div>

                            <div class="overflow-hidden border border-slate-200 rounded-2xl shadow-sm">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Size</th>
                                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-center w-48">Harga Jual</th>
                                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-center w-24">Qty</th>
                                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-center w-20">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 italic-last-row">
                                        <template x-for="(v, index) in multiItems" :key="v.id">
                                            <tr class="hover:bg-slate-50/50 transition-colors" :class="!v.is_active ? 'bg-slate-50/50 grayscale-[0.5]' : ''">
                                                <td class="px-4 py-3">
                                                    <span class="badge badge-outline text-xs" :class="v.is_active ? 'border-brand text-brand' : 'border-slate-300 text-slate-400'" x-text="v.size_name"></span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="relative group">
                                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp.</span>
                                                        <input type="text" :value="formatCurrency(v.price_sell) || '0'"
                                                            @input="handleCurrencyInput($event, v, 'price_sell')"
                                                            inputmode="numeric"
                                                            class="w-full pl-10 pr-3 py-2 text-right font-mono text-sm border-0 focus:ring-2 focus:ring-brand rounded-lg bg-transparent hover:bg-white transition-all shadow-sm group-hover:shadow-md" :disabled="!v.is_active">
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <input type="number" x-model="v.qty" class="w-full px-2 py-2 text-center font-mono text-sm border-0 focus:ring-2 focus:ring-brand rounded-lg bg-transparent hover:bg-white transition-all shadow-sm group-hover:shadow-md" :disabled="!v.is_active">
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" @click="v.is_active = !v.is_active" 
                                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none"
                                                        :class="v.is_active ? 'bg-brand' : 'bg-slate-300'">
                                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                                                            :class="v.is_active ? 'translate-x-5' : 'translate-x-0.5'"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <!-- Mode NEW / DETAIL: Single Form -->
                    <template x-if="!editMode">
                        <fieldset :disabled="detailMode" class="group">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-2">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Cabang Penyimpanan <span class="text-red-500">*</span></label>
                                    <select x-model="formData.branch_id" class="form-select w-full {{ $isSuperAdmin ? '' : 'bg-slate-100 cursor-not-allowed border-0' }}" {{ $isSuperAdmin ? '' : 'disabled' }} :required="!editMode">
                                        <option value="">--- Pilih Cabang ---</option>
                                        @foreach($branches as $branch) <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Material / Tipe Plat <span class="text-red-500">*</span></label>
                                    <select x-model="formData.plate_type_id" class="form-select w-full" :required="!editMode">
                                        <option value="">--- Pilih Material ---</option>
                                        @foreach($plateTypes as $type) <option value="{{ $type['id'] }}">{{ $type['name'] }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Ukuran <span class="text-red-500">*</span></label>
                                    <select x-model="formData.size_id" class="form-select w-full" :required="!editMode">
                                        <option value="">--- Pilih Ukuran ---</option>
                                        @foreach($sizes as $size) <option value="{{ $size['id'] }}">{{ $size['name'] ?? $size['value'] }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Harga Beli (Rp)</label>
                                    <input type="text" :value="formatCurrency(formData.price_buy)" @input="handleCurrencyInput($event, formData, 'price_buy')" inputmode="numeric" class="form-input w-full font-mono text-right" placeholder="0">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Harga Jual (Rp) <span class="text-red-500">*</span></label>
                                    <input type="text" :value="formatCurrency(formData.price_sell)" @input="handleCurrencyInput($event, formData, 'price_sell')" inputmode="numeric" class="form-input w-full font-mono text-right" placeholder="0" :required="!editMode">
                                </div>
                                <div class="col-span-2 md:col-span-1" x-show="!detailMode">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Qty Stok Awal <span class="text-red-500">*</span></label>
                                    <input type="number" x-model="formData.qty" class="form-input w-full font-mono text-center" placeholder="0" min="0" :required="!editMode">
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Status Penjualan</label>
                                    <div class="flex items-center gap-4 mt-3">
                                        <label class="flex items-center gap-2 cursor-pointer outline-none"><input type="radio" :value="true" x-model="formData.is_active" class="form-radio text-brand focus:ring-brand"><span class="text-sm font-semibold text-slate-600">Terintegrasi / Aktif</span></label>
                                        <label class="flex items-center gap-2 cursor-pointer outline-none"><input type="radio" :value="false" x-model="formData.is_active" class="form-radio text-red-500 focus:ring-red-500"><span class="text-sm font-semibold text-slate-600">Non-Aktif</span></label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </template>

                    <div class="mt-10 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" x-show="detailMode" class="btn btn-primary px-8 py-3 rounded-2xl min-w-[120px]">Tutup</button>
                        <button type="button" @click="showModal = false" x-show="!detailMode" class="btn btn-outline px-8 py-3 rounded-2xl">Batal</button>
                        <button type="submit" x-show="!detailMode" class="btn btn-primary px-10 py-3 rounded-2xl shadow-xl shadow-brand/20 flex items-center justify-center min-w-[160px]" :disabled="loading">
                            <span x-show="!loading" x-text="editMode ? 'Simpan Perubahan' : 'Tambah Plat'"></span>
                            <svg x-show="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
