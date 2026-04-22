@extends('layouts.app')
@section('title', 'Komponen')
@section('page-title', 'Master Component')

@section('content')
    @php
        $sortBy = request('sort_by', 'id');
        $sortDir = request('sort_dir', 'desc');
        $isSuperAdmin = \App\Helpers\AuthHelper::isSuperAdmin();
        $userBranchId = session('branch_id');
    @endphp

    <div x-data="{ 
        existingComponents: @js($componentReferences ?? []),
        showModal: false,
        editMode: false,
        detailMode: false,
        loading: false,
        submitMode: 'close',
        pendingItems: [],
        selectedExistingComponent: null,
        componentDropdownOpen: false,
        defaultFormData() {
            return { id: '', existing_component_id: '', name: '', type_size: '', weight: '', supplier_id: '', component_category_id: '', branch_id: '{{ $isSuperAdmin ? "" : $userBranchId }}', qty: 0, price_buy: '', price_sell: '' };
        },
        sanitizeCurrency(value) {
            return String(value ?? '').replace(/[^0-9]/g, '');
        },
        formatCurrency(value) {
            const sanitized = this.sanitizeCurrency(value);
            if (!sanitized) {
                return '';
            }

            const number = Number(sanitized);
            return new Intl.NumberFormat('id-ID').format(Number.isNaN(number) ? 0 : number);
        },
        handleCurrencyInput(event, field) {
            const sanitized = this.sanitizeCurrency(event.target.value);
            this.formData[field] = sanitized;
            event.target.value = this.formatCurrency(sanitized);
        },
        normalizedName(value) {
            return String(value ?? '')
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase();
        },
        clearAutoFilledFields() {
            this.formData.type_size = '';
            this.formData.weight = '';
            this.formData.supplier_id = '';
            this.formData.component_category_id = '';
        },
        resetExistingMatch(resetFields = false) {
            if (resetFields) {
                this.clearAutoFilledFields();
            }
            this.selectedExistingComponent = null;
            this.formData.existing_component_id = '';
        },
        openComponentDropdown() {
            if (this.editMode || this.detailMode || !this.formData.branch_id) {
                this.componentDropdownOpen = false;
                return;
            }

            this.componentDropdownOpen = true;
        },
        closeComponentDropdown() {
            this.componentDropdownOpen = false;
        },
        handleNameBlur() {
            setTimeout(() => {
                this.syncExistingComponentFromName();
                this.closeComponentDropdown();
            }, 150);
        },
        getFilteredExistingComponents() {
            const branchId = String(this.formData.branch_id ?? '');
            const keyword = this.normalizedName(this.formData.name);

            return this.existingComponents
                .filter(item => !branchId || String(item.branch_id ?? '') === branchId)
                .filter(item => !keyword || this.normalizedName(item.name).includes(keyword))
                .slice(0, 20);
        },
        applyExistingComponent(component) {
            if (!component) {
                this.resetExistingMatch();
                return;
            }

            this.selectedExistingComponent = component;
            this.formData.existing_component_id = component.id;
            this.formData.name = component.name ?? '';
            this.formData.type_size = component.type_size ?? '';
            this.formData.weight = component.weight ?? '';
            this.formData.supplier_id = component.supplier_id ?? '';
            this.formData.component_category_id = component.component_category_id ?? '';
            this.formData.price_buy = component.price_buy ?? '';
            this.formData.price_sell = component.price_sell ?? '';
        },
        selectExistingComponent(component) {
            this.applyExistingComponent(component);
            this.closeComponentDropdown();
        },
        syncExistingComponentFromName() {
            if (this.editMode || this.detailMode) {
                return;
            }

            const branchId = String(this.formData.branch_id ?? '');
            const name = this.normalizedName(this.formData.name);

            if (!branchId || !name) {
                return;
            }

            const matched = this.existingComponents.find(item =>
                String(item.branch_id ?? '') === branchId &&
                this.normalizedName(item.name) === name
            );

            if (matched) {
                this.applyExistingComponent(matched);
            }
        },
        matchExistingComponent() {
            const currentName = this.normalizedName(this.formData.name);
            const selectedName = this.normalizedName(this.selectedExistingComponent?.name ?? '');

            if (!currentName) {
                this.resetExistingMatch(!!this.selectedExistingComponent);
                return;
            }

            if (this.selectedExistingComponent && currentName !== selectedName) {
                this.resetExistingMatch(true);
            }
        },
        handleNameInput(value) {
            this.formData.name = value;
            this.matchExistingComponent();
            this.openComponentDropdown();
        },
        handleBranchChange(value) {
            this.formData.branch_id = value;
            if (!this.editMode && !this.detailMode) {
                this.formData.name = '';
            }
            this.matchExistingComponent();
            this.closeComponentDropdown();
        },
        formData: { id: '', existing_component_id: '', name: '', type_size: '', weight: '', supplier_id: '', component_category_id: '', branch_id: '{{ $isSuperAdmin ? "" : $userBranchId }}', qty: 0, price_buy: '', price_sell: '' },
        isFormDirty() {
            return Boolean(
                (this.formData.name || '').trim() ||
                (this.formData.type_size || '').trim() ||
                String(this.formData.weight || '').trim() ||
                String(this.formData.supplier_id || '').trim() ||
                String(this.formData.component_category_id || '').trim() ||
                String(this.formData.branch_id || '').trim() ||
                String(this.formData.qty || '').trim() ||
                String(this.formData.price_buy || '').trim() ||
                String(this.formData.price_sell || '').trim()
            );
        },
        validateCurrentForm() {
            if (!String(this.formData.branch_id || '').trim()) {
                window.toast.error('Cabang wajib dipilih.');
                return false;
            }

            if (!(this.formData.name || '').trim()) {
                window.toast.error('Nama komponen wajib diisi.');
                return false;
            }

            if (String(this.formData.price_sell || '').trim() === '') {
                window.toast.error('Harga jual wajib diisi.');
                return false;
            }

            if (String(this.formData.qty ?? '').trim() === '') {
                window.toast.error('Qty wajib diisi.');
                return false;
            }

            return true;
        },
        buildPayload(source = null) {
            const item = source || this.formData;

            return {
                existing_component_id: item.existing_component_id || null,
                name: item.name || '',
                type_size: item.type_size || '',
                weight: item.weight === '' || item.weight === null ? null : item.weight,
                supplier_id: item.supplier_id || null,
                component_category_id: item.component_category_id || null,
                branch_id: item.branch_id,
                qty: Number(item.qty || 0),
                price_buy: item.price_buy === '' || item.price_buy === null ? null : Number(item.price_buy || 0),
                price_sell: Number(item.price_sell || 0),
            };
        },
        resetCreateForm() {
            this.resetExistingMatch();
            this.formData = this.defaultFormData();
        },
        attemptClose() {
            if (!this.editMode && !this.detailMode && (this.pendingItems.length > 0 || this.isFormDirty())) {
                const confirmed = window.confirm('Masih ada data yang belum disimpan. Yakin tutup modal?');
                if (!confirmed) {
                    return;
                }
            }

            this.showModal = false;
        },
        openCreate() {
            this.detailMode = false;
            this.editMode = false;
            this.submitMode = 'close';
            this.pendingItems = [];
            this.resetCreateForm();
            this.showModal = true;
        },
        openEdit(item) {
            this.detailMode = false;
            this.editMode = true;
            this.submitMode = 'close';
            this.pendingItems = [];
            this.selectedExistingComponent = item;
            this.formData = { 
                id: item.id,
                existing_component_id: item.id,
                name: item.name ?? '',
                type_size: item.type_size ?? '',
                weight: item.weight ?? '',
                supplier_id: item.supplier_id ?? '',
                component_category_id: item.component_category_id ?? '',
                branch_id: item.branch_id ?? '',
                qty: item.qty ?? 0,
                price_buy: item.price_buy ?? '',
                price_sell: item.price_sell ?? ''
            };
            this.showModal = true;
        },
        openDetail(item) {
            this.openEdit(item);
            this.detailMode = true;
            this.editMode = false;
        },
        queueCurrentItem() {
            if (!this.validateCurrentForm()) {
                return;
            }

            this.pendingItems.push({
                client_id: Date.now() + Math.random(),
                ...this.buildPayload(),
            });

            this.resetCreateForm();
            window.toast.success('Komponen dimasukkan ke antrean.');
        },
        async submitForm() {
            if (this.detailMode) {
                return;
            }

            if (this.editMode) {
                if (!this.validateCurrentForm()) {
                    return;
                }

                this.loading = true;
                const url = `{{ route('master.component.index') }}/${this.formData.id}`;

                try {
                    const response = await axios.put(url, this.buildPayload());
                    window.toast.success(response.data.message || 'Data berhasil disimpan');
                    this.showModal = false;
                    setTimeout(() => window.location.reload(), 500);
                } catch (error) {
                    const message = error.response?.data?.message || 'Gagal menyimpan data';
                    window.toast.error(message);
                } finally {
                    this.loading = false;
                }

                return;
            }

            if (this.submitMode === 'create_another') {
                this.queueCurrentItem();
                return;
            }

            let payloads = [...this.pendingItems];

            if (this.isFormDirty()) {
                if (!this.validateCurrentForm()) {
                    return;
                }

                payloads.push({
                    client_id: 'current',
                    ...this.buildPayload(),
                });
            }

            if (payloads.length === 0) {
                window.toast.error('Isi minimal satu data atau tambahkan ke antrean terlebih dahulu.');
                return;
            }

            this.loading = true;
            let successCount = 0;

            try {
                for (const item of payloads) {
                    await axios.post(`{{ route('master.component.store') }}`, this.buildPayload(item));
                    successCount += 1;
                }

                this.pendingItems = [];
                this.resetCreateForm();
                window.toast.success(`${successCount} komponen berhasil disimpan.`);
                this.showModal = false;
                setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                const message = error.response?.data?.message || 'Gagal menyimpan data';
                const failedIndex = successCount;
                const remainingItems = payloads.slice(failedIndex);
                this.pendingItems = remainingItems
                    .filter((item) => item.client_id !== 'current')
                    .map((item) => ({ ...item }));
                this.resetCreateForm();

                if (successCount > 0) {
                    window.toast.error(`${message} ${successCount} data sudah tersimpan, sisanya tetap di antrean.`);
                } else {
                    window.toast.error(message);
                }
            } finally {
                this.loading = false;
            }
        },
        async deleteItem(id) {
            if (await window.SwalConfirm('Konfirmasi', 'Apakah Anda yakin ingin menghapus komponen ini?')) {
                try {
                    const response = await axios.delete(`{{ route('master.component.index') }}/${id}`);
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
                <h1 class="page-title text-2xl font-bold text-slate-900">Master Komponen</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola item komponen, kategori, supplier, dan stok awal.</p>
            </div>

            @if(\App\Helpers\MenuHelper::hasPermission('component', 'create'))
                <button @click="openCreate()" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Komponen</span>
                </button>
            @endif
        </div>

        <div class="card overflow-hidden">
            <form action="{{ route('master.component.index') }}" method="GET" id="filterForm">
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th class="w-16 text-center">NO</th>
                                <th>NAMA</th>
                                <th>TIPE / SIZE</th>
                                <th>KATEGORI</th>
                                <th>SUPPLIER</th>
                                <th>CABANG</th>
                                <th class="text-right">HARGA BELI</th>
                                <th class="text-right">HARGA JUAL</th>
                                <th class="text-right">STOK</th>
                                <th class="w-24 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($components as $item)
                                <tr>
                                    <td class="text-center">
                                        <span
                                            class="text-slate-400 font-mono text-xs">{{ ($components->currentPage() - 1) * $components->perPage() + $loop->iteration }}</span>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800">{{ $item['name'] }}</span>
                                            <span class="text-[10px] text-slate-400 uppercase font-mono tracking-tighter">ID:
                                                {{ $item['id'] }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $item['type_size'] ?? '-' }}</td>
                                    <td>{{ $item['component_category']['name'] ?? ($item['component_category_name'] ?? '-') }}
                                    </td>
                                    <td>{{ $item['supplier']['name'] ?? ($item['supplier_name'] ?? '-') }}</td>
                                    <td>{{ $item['branch']['name'] ?? ($item['branch_name'] ?? '-') }}</td>
                                    <td class="text-right font-medium">Rp
                                        {{ number_format((float) ($item['price_buy'] ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-right font-medium text-brand">Rp
                                        {{ number_format((float) ($item['price_sell'] ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-right font-semibold">{{ $item['qty'] ?? 0 }}</td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            @if(\App\Helpers\MenuHelper::hasPermission('component', 'detail'))
                                                <button type="button" @click="openDetail({{ json_encode($item) }})"
                                                    class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all"
                                                    title="Detail">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                            @endif
                                            @if(\App\Helpers\MenuHelper::hasPermission('component', 'edit'))
                                                <button type="button" @click="openEdit({{ json_encode($item) }})"
                                                    class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all"
                                                    title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                            @endif
                                            @if(\App\Helpers\MenuHelper::hasPermission('component', 'delete'))
                                                <button type="button" @click="deleteItem({{ $item['id'] }})"
                                                    class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                                    title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-20 text-center text-slate-400 italic">Data komponen belum
                                        tersedia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @if($components->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $components->links() }}
                </div>
            @endif
        </div>

        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center text-sm md:text-base">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="attemptClose()">
                </div>
                <div
                    class="relative inline-block w-full max-w-3xl p-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900"
                                x-text="detailMode ? 'Detail Komponen' : (editMode ? 'Edit Komponen' : 'Tambah Komponen Baru')">
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Lengkapi identitas komponen, supplier, kategori, dan stok
                                awal.</p>
                        </div>
                        <button @click="attemptClose()"
                            class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm()" class="space-y-6">
                        <fieldset :disabled="detailMode" class="group">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Cabang <span
                                            class="text-red-500">*</span></label>
                                    <select x-model="formData.branch_id" @change="handleBranchChange($event.target.value)"
                                        class="form-input w-full {{ $isSuperAdmin ? '' : 'bg-slate-100 cursor-not-allowed opacity-75' }}"
                                        {{ $isSuperAdmin ? '' : 'disabled' }} required>
                                        <option value="">--- Pilih Cabang ---</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Nama Komponen <span
                                            class="text-red-500">*</span></label>
                                    <template x-if="!editMode && !detailMode">
                                        <div class="relative" @click.outside="closeComponentDropdown()">
                                            <input
                                                type="text"
                                                x-model="formData.name"
                                                @focus="openComponentDropdown()"
                                                @blur="handleNameBlur()"
                                                @input="handleNameInput($event.target.value)"
                                                class="form-input w-full"
                                                :class="!formData.branch_id ? 'bg-slate-100 cursor-not-allowed opacity-75' : ''"
                                                :disabled="!formData.branch_id"
                                                :placeholder="formData.branch_id ? 'Cari atau ketik nama komponen...' : 'Pilih cabang terlebih dahulu'"
                                                required
                                            >

                                            <div
                                                x-show="componentDropdownOpen"
                                                x-transition
                                                class="absolute z-40 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-2xl overflow-hidden"
                                                style="display: none;"
                                            >
                                                <div class="max-h-64 overflow-y-auto">
                                                    <template x-if="!formData.branch_id">
                                                        <div class="px-4 py-4 text-sm text-slate-400 text-center">
                                                            Pilih cabang terlebih dahulu untuk melihat daftar komponen.
                                                        </div>
                                                    </template>

                                                    <template x-if="formData.branch_id">
                                                        <div>
                                                            <template x-for="component in getFilteredExistingComponents()" :key="`component-option-${component.id}`">
                                                                <button
                                                                    type="button"
                                                                    @mousedown.prevent="selectExistingComponent(component)"
                                                                    class="w-full px-4 py-3 text-left hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0"
                                                                >
                                                                    <div class="font-medium text-slate-800" x-text="component.name"></div>
                                                                    <div class="text-xs text-slate-400 mt-0.5" x-text="[component.type_size || null, component.component_category_name || component.component_category?.name || null, component.supplier_name || component.supplier?.name || null].filter(Boolean).join(' • ') || 'Komponen existing'"></div>
                                                                </button>
                                                            </template>

                                                            <div x-show="getFilteredExistingComponents().length === 0" class="px-4 py-4 text-sm text-slate-400 text-center">
                                                                Nama komponen belum ada. Anda bisa lanjut input sebagai komponen baru.
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="editMode || detailMode">
                                        <input type="text" x-model="formData.name" class="form-input w-full" required>
                                    </template>

                                    <p class="mt-1 text-[11px] text-slate-500" x-show="!editMode && !detailMode && formData.branch_id">
                                        Sistem akan mencari nama komponen pada master component sesuai cabang yang dipilih.
                                    </p>
                                    <p class="mt-1 text-[11px] text-amber-600" x-show="!editMode && !detailMode && !formData.branch_id">
                                        Pilih cabang terlebih dahulu sebelum mencari atau memilih nama komponen.
                                    </p>
                                    <p class="mt-1 text-[11px] font-semibold text-emerald-600" x-show="!editMode && !detailMode && selectedExistingComponent">
                                        Komponen existing terdeteksi. Data master tidak akan dibuat ulang, sistem hanya memproses stock dan histori harga.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Tipe / Size</label>
                                    <input type="text" x-model="formData.type_size" class="form-input w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Berat</label>
                                    <input type="number" step="0.01" x-model="formData.weight" class="form-input w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Kategori</label>
                                    <select x-model="formData.component_category_id" class="form-input w-full">
                                        <option value="">--- Pilih Kategori ---</option>
                                        @foreach($componentCategories as $category)
                                            <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Supplier</label>
                                    <select x-model="formData.supplier_id" class="form-input w-full">
                                        <option value="">--- Pilih Supplier ---</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Harga Beli</label>
                                    <input type="text" :value="formatCurrency(formData.price_buy)"
                                        @input="handleCurrencyInput($event, 'price_buy')" inputmode="numeric"
                                        class="form-input w-full font-mono text-right" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Harga Jual <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" :value="formatCurrency(formData.price_sell)"
                                        @input="handleCurrencyInput($event, 'price_sell')" inputmode="numeric"
                                        class="form-input w-full font-mono text-right" placeholder="0" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Qty / Stock Saat Ini <span
                                            class="text-red-500">*</span></label>
                                    <input type="number" x-model="formData.qty" class="form-input w-full" required>
                                </div>
                                <div class="md:col-span-2" x-show="detailMode">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                                Harga Beli</p>
                                            <p class="mt-1 text-base font-bold text-slate-900"
                                                x-text="'Rp ' + (formatCurrency(formData.price_buy) || '0')"></p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                                Harga Jual</p>
                                            <p class="mt-1 text-base font-bold text-brand"
                                                x-text="'Rp ' + (formatCurrency(formData.price_sell) || '0')"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <div x-show="!detailMode && !editMode" class="space-y-3" style="display: none;">
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <p class="text-slate-500">
                                    <span class="font-semibold text-slate-700" x-text="pendingItems.length"></span>
                                    data menunggu disimpan
                                </p>
                                <button
                                    type="button"
                                    x-show="pendingItems.length > 0"
                                    @click="pendingItems = []"
                                    class="text-red-500 hover:text-red-600 font-semibold"
                                >Kosongkan</button>
                            </div>
                        </div>

                        <div x-show="detailMode" class="mt-10 flex justify-end gap-3">
                            <button type="button" @click="attemptClose()" x-show="detailMode"
                                class="btn btn-primary px-8 py-3 rounded-2xl min-w-[120px]">Tutup</button>
                        </div>

                        <div x-show="!detailMode && editMode" class="mt-10 flex justify-end gap-3" style="display: none;">
                            <button type="button" @click="attemptClose()" class="btn btn-outline px-8 py-3 rounded-2xl">Batal</button>
                            <button type="submit" @click="submitMode = 'close'"
                                class="btn btn-primary px-10 py-3 rounded-2xl shadow-xl shadow-brand/20 flex items-center justify-center min-w-[160px]"
                                :disabled="loading">
                                <template x-if="!loading">
                                    <span>Simpan</span>
                                </template>
                                <template x-if="loading">
                                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </template>
                            </button>
                        </div>

                        <div x-show="!detailMode && !editMode" class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-3" style="display: none;">
                            <button type="button" @click="attemptClose()" class="btn btn-outline w-full justify-center min-h-[44px]">Batal</button>
                            <button type="submit" @click="submitMode = 'close'"
                                class="btn btn-primary w-full justify-center min-h-[44px] shadow-xl shadow-brand/20"
                                :disabled="loading">
                                <template x-if="!loading">
                                    <span>Simpan</span>
                                </template>
                                <template x-if="loading">
                                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </template>
                            </button>
                            <button type="submit" @click="submitMode = 'create_another'" class="btn btn-outline w-full justify-center min-h-[44px]" :disabled="loading">Buat lagi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
