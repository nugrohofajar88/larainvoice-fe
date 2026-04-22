@extends('layouts.app')
@section('title', 'Cabang')
@section('page-title', 'Master Cabang')

@section('content')
    @php
        $sortBy = request('sort_by', 'id');
        $sortDir = request('sort_dir', 'desc');
        $isSuperAdmin = \App\Helpers\AuthHelper::isSuperAdmin();

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
    @endphp

    <div x-data="{ 
            showModal: false,
            editMode: false,
            detailMode: false,
            loading: false,
            submitMode: 'close',
            pendingItems: [],
            closeModal() {
                @if(!$isSuperAdmin)
                    window.location.href = '{{ route('dashboard') }}';
                @else
                    this.showModal = false;
                @endif
            },
            attemptClose() {
                if (!this.editMode && !this.detailMode && (this.pendingItems.length > 0 || this.isFormDirty())) {
                    const confirmed = window.confirm('Masih ada data yang belum disimpan. Yakin tutup modal?');
                    if (!confirmed) {
                        return;
                    }
                }

                this.closeModal();
            },
            formData: { 
                id: '', 
                name: '', 
                city: '', 
                phone: '', 
                email: '', 
                website: '', 
                address: '',
                bank_accounts: [],
                setting: {
                    minimum_stock: 0,
                    sales_commission_percentage: 0,
                    invoice_header_name: '',
                    invoice_header_position: '',
                    invoice_footer_note: ''
                }
            },
            defaultFormData() {
                return { 
                    id: '', name: '', city: '', phone: '', email: '', website: '', address: '',
                    bank_accounts: [],
                    setting: { minimum_stock: 0, sales_commission_percentage: 0, invoice_header_name: '', invoice_header_position: '', invoice_footer_note: '' }
                };
            },
            isFormDirty() {
                return Boolean(
                    (this.formData.name || '').trim() ||
                    (this.formData.city || '').trim() ||
                    (this.formData.phone || '').trim() ||
                    (this.formData.email || '').trim() ||
                    (this.formData.website || '').trim() ||
                    (this.formData.address || '').trim() ||
                    (Array.isArray(this.formData.bank_accounts) && this.formData.bank_accounts.length > 0) ||
                    (this.formData.setting?.minimum_stock ?? 0) !== 0 ||
                    (this.formData.setting?.sales_commission_percentage ?? 0) !== 0 ||
                    (this.formData.setting?.invoice_header_name || '').trim() ||
                    (this.formData.setting?.invoice_header_position || '').trim() ||
                    (this.formData.setting?.invoice_footer_note || '').trim()
                );
            },
            validateCurrentForm() {
                if (!(this.formData.name || '').trim()) {
                    window.toast.error('Nama cabang wajib diisi.');
                    return false;
                }

                if (!(this.formData.city || '').trim()) {
                    window.toast.error('Kota wajib diisi.');
                    return false;
                }

                return true;
            },
            openCreate() {
                this.detailMode = false;
                this.editMode = false;
                this.submitMode = 'close';
                this.pendingItems = [];
                this.formData = this.defaultFormData();
                this.showModal = true;
            },
            openEdit(item) {
                this.detailMode = false;
                this.editMode = true;
                this.submitMode = 'close';
                this.pendingItems = [];
                this.formData = { 
                    id: item.id, 
                    name: item.name, 
                    city: item.city, 
                    phone: item.phone || '', 
                    email: item.email || '', 
                    website: item.website || '', 
                    address: item.address || '',
                    bank_accounts: Array.isArray(item.bank_accounts) ? JSON.parse(JSON.stringify(item.bank_accounts)) : [],
                    setting: {
                        minimum_stock: item.setting?.minimum_stock ?? 0,
                        sales_commission_percentage: item.setting?.sales_commission_percentage ?? 0,
                        invoice_header_name: item.setting?.invoice_header_name ?? '',
                        invoice_header_position: item.setting?.invoice_header_position ?? '',
                        invoice_footer_note: item.setting?.invoice_footer_note ?? ''
                    }
                };
                this.showModal = true;
            },
            resetCreateForm() {
                this.formData = this.defaultFormData();
            },
            openDetail(item) {
                this.openEdit(item);
                this.detailMode = true;
                this.editMode = false;
            },
            addBank() {
                this.formData.bank_accounts.push({ bank_name: '', account_number: '', account_holder: '', is_default: false });
            },
            removeBank(index) {
                this.formData.bank_accounts.splice(index, 1);
            },
            setBankDefault(index) {
                this.formData.bank_accounts.forEach((b, i) => b.is_default = (i === index));
            },
            queueCurrentItem() {
                if (!this.validateCurrentForm()) {
                    return;
                }

                this.pendingItems.push({
                    client_id: Date.now() + Math.random(),
                    name: this.formData.name.trim(),
                    city: this.formData.city.trim(),
                    phone: (this.formData.phone || '').trim(),
                    email: (this.formData.email || '').trim(),
                    website: (this.formData.website || '').trim(),
                    address: (this.formData.address || '').trim(),
                    bank_accounts: JSON.parse(JSON.stringify(this.formData.bank_accounts || [])),
                    setting: JSON.parse(JSON.stringify(this.formData.setting || {})),
                });
                this.resetCreateForm();
                window.toast.success('Data dimasukkan ke antrean.');
            },
            async submitForm() {
                if (this.detailMode) {
                    return;
                }

                if (!this.editMode && this.submitMode === 'create_another') {
                    this.queueCurrentItem();
                    return;
                }

                if (!this.validateCurrentForm() && (this.editMode || this.isFormDirty())) {
                    return;
                }

                if (!this.editMode) {
                    let payloads = [...this.pendingItems];

                    if (this.isFormDirty()) {
                        payloads.push({
                            client_id: 'current',
                            name: this.formData.name.trim(),
                            city: this.formData.city.trim(),
                            phone: (this.formData.phone || '').trim(),
                            email: (this.formData.email || '').trim(),
                            website: (this.formData.website || '').trim(),
                            address: (this.formData.address || '').trim(),
                            bank_accounts: JSON.parse(JSON.stringify(this.formData.bank_accounts || [])),
                            setting: JSON.parse(JSON.stringify(this.formData.setting || {})),
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
                            await axios.post(`{{ route('master.cabang.store') }}`, {
                                name: item.name,
                                city: item.city,
                                phone: item.phone,
                                email: item.email,
                                website: item.website,
                                address: item.address,
                                bank_accounts: item.bank_accounts,
                                setting: item.setting,
                            });
                            successCount += 1;
                        }

                        this.pendingItems = [];
                        this.resetCreateForm();
                        window.toast.success(`${successCount} cabang berhasil disimpan.`);
                        this.showModal = false;
                        setTimeout(() => window.location.reload(), 500);
                    } catch (error) {
                        const failedIndex = successCount;
                        const remainingItems = payloads.slice(failedIndex);
                        this.pendingItems = remainingItems
                            .filter((item) => item.client_id !== 'current')
                            .map((item) => ({ ...item }));
                        this.resetCreateForm();

                        const message = error.response?.data?.message || 'Gagal menyimpan sebagian data.';
                        if (successCount > 0) {
                            window.toast.error(`${message} ${successCount} data sudah tersimpan, sisanya tetap di antrean.`);
                        } else {
                            window.toast.error(message);
                        }
                    } finally {
                        this.loading = false;
                    }

                    return;
                }

                this.loading = true;
                const url = this.editMode ? `{{ route('master.cabang.index') }}/${this.formData.id}` : `{{ route('master.cabang.store') }}`;
                const method = this.editMode ? 'put' : 'post';

                try {
                    const response = await axios({
                        method: method,
                        url: url,
                        data: this.formData
                    });

                    window.toast.success(response.data.message || 'Data berhasil disimpan');
                    setTimeout(() => window.location.reload(), 500);
                } catch (error) {
                    const message = error.response?.data?.message || 'Gagal menyimpan data';
                    window.toast.error(message);
                } finally {
                    this.loading = false;
                }
            },
            async deleteItem(id) {
                if (await window.SwalConfirm()) {
                    try {
                        const response = await axios.delete(`{{ route('master.cabang.index') }}/${id}`);
                        window.toast.success(response.data.message || 'Berhasil dihapus');
                        setTimeout(() => window.location.reload(), 500);
                    } catch (error) {
                        window.toast.error(error.response?.data?.message || 'Gagal menghapus data');
                    }
                }
            }
        }" x-init="() => {
            @if(!$isSuperAdmin && $branches->total() > 0)
                openEdit({{ json_encode($branches->items()[0]) }});
            @endif
        }">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="page-title text-2xl font-bold text-slate-900">Master Cabang</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola data cabang perusahaan dan pengaturan operasional.</p>
            </div>

            @if($isSuperAdmin || \App\Helpers\MenuHelper::hasPermission('branch', 'create'))
                <button @click="openCreate()" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Cabang</span>
                </button>
            @endif
        </div>

        <div class="card overflow-hidden">
            <form action="{{ route('master.cabang.index') }}" method="GET" id="filterForm">
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th class="w-16 text-center">NO</th>
                                <th>
                                    <a href="{{ $sortUrl('name') }}" class="flex items-center group">
                                        <span>NAMA CABANG</span>
                                        {!! $sortIcon('name') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('city') }}" class="flex items-center group">
                                        <span>KOTA</span>
                                        {!! $sortIcon('city') !!}
                                    </a>
                                </th>
                                <th class="hidden lg:table-cell">
                                    <a href="{{ $sortUrl('phone') }}" class="flex items-center group">
                                        <span>TELEPON</span>
                                        {!! $sortIcon('phone') !!}
                                    </a>
                                </th>
                                <th class="w-24 text-center">AKSI</th>
                            </tr>
                            <tr class="bg-slate-50/50">
                                <th class="py-2 px-4 shadow-inner"></th>
                                <th class="py-2 px-4 shadow-inner">
                                    <input type="text" name="name" value="{{ request('name') }}"
                                        class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                        placeholder="Cari nama..."
                                        onchange="document.getElementById('filterForm').submit()">
                                </th>
                                <th class="py-2 px-4 shadow-inner">
                                    <input type="text" name="city" value="{{ request('city') }}"
                                        class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                        placeholder="Cari kota..."
                                        onchange="document.getElementById('filterForm').submit()">
                                </th>
                                <th class="py-2 px-4 shadow-inner hidden lg:table-cell">
                                    <input type="text" name="phone" value="{{ request('phone') }}"
                                        class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                        placeholder="Cari telp..."
                                        onchange="document.getElementById('filterForm').submit()">
                                </th>
                                <th class="py-2 px-4 shadow-inner text-center">
                                    @if(request()->anyFilled(['name', 'city', 'phone']))
                                        <a href="{{ route('master.cabang.index') }}"
                                            class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branches as $item)
                                <tr>
                                    <td class="text-center">
                                        <span
                                            class="text-slate-400 font-mono text-xs">{{ ($branches->currentPage() - 1) * $branches->perPage() + $loop->iteration }}</span>
                                    </td>
                                    <td class="font-bold text-slate-800">{{ $item['name'] }}</td>
                                    <td>{{ $item['city'] }}</td>
                                    <td class="hidden lg:table-cell text-slate-600 font-mono text-xs">{{ $item['phone'] }}</td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            @if(\App\Helpers\MenuHelper::hasPermission('branch', 'detail'))
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

                                            @if(\App\Helpers\MenuHelper::hasPermission('branch', 'edit'))
                                                <button type="button" @click="openEdit({{ json_encode($item) }})"
                                                    class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all"
                                                    title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                            @endif

                                            @if(\App\Helpers\MenuHelper::hasPermission('branch', 'delete'))
                                                <button type="button" @click="deleteItem({{ $item['id'] }})"
                                                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
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
                                    <td colspan="5" class="py-20 text-center text-slate-400 italic">Data cabang belum tersedia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @if($branches->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $branches->links() }}
                </div>
            @endif
        </div>

        {{-- Modal Form --}}
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center text-sm md:text-base">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="attemptClose()">
                </div>

                <div
                    class="relative inline-block w-full max-w-4xl p-0 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                    <div class="flex items-center justify-between p-8 border-b border-slate-100 pb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900"
                                x-text="detailMode ? 'Detail Cabang' : (editMode ? 'Konfigurasi Cabang' : 'Tambah Cabang Baru')">
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Lengkapi data profil, rekening bank, dan pengaturan
                                invoice.</p>
                        </div>
                        <button @click="attemptClose()"
                            class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm()" class="overflow-y-auto max-h-[75vh]" novalidate>
                        <fieldset :disabled="detailMode" class="group">
                            <div class="p-8 space-y-10">
                                {{-- Section 1: Informasi Utama --}}
                                <section>
                                    <h4
                                        class="text-sm font-bold text-brand uppercase tracking-wider mb-4 flex items-center gap-2">
                                        <span
                                            class="w-8 h-8 rounded-full bg-brand/10 text-brand flex items-center justify-center text-xs">01</span>
                                        Profil Cabang
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Cabang <span
                                                    class="text-red-500">*</span></label>
                                            <input type="text" x-model="formData.name" class="form-input w-full"
                                                placeholder="Contoh: Kantor Cabang Malang" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Kota <span
                                                    class="text-red-500">*</span></label>
                                            <input type="text" x-model="formData.city" class="form-input w-full"
                                                placeholder="Kota" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Telepon</label>
                                            <input type="text" x-model="formData.phone" class="form-input w-full"
                                                placeholder="0812...">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Email</label>
                                            <input type="email" x-model="formData.email" class="form-input w-full"
                                                placeholder="cabang@example.com">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Website</label>
                                            <input type="text" x-model="formData.website" class="form-input w-full"
                                                placeholder="www.website.com">
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat
                                                Lengkap</label>
                                            <textarea x-model="formData.address" class="form-input w-full" rows="2"
                                                placeholder="Alamat lengkap cabang..."></textarea>
                                        </div>
                                    </div>
                                </section>

                                <hr class="border-slate-100">

                                {{-- Section 2: Rekening Bank --}}
                                <section>
                                    <div class="flex items-center justify-between mb-4">
                                        <h4
                                            class="text-sm font-bold text-brand uppercase tracking-wider flex items-center gap-2">
                                            <span
                                                class="w-8 h-8 rounded-full bg-brand/10 text-brand flex items-center justify-center text-xs">02</span>
                                            Daftar Rekening Bank
                                        </h4>
                                        <button type="button" @click="addBank()"
                                            class="text-xs font-bold text-brand hover:text-brand-dark bg-brand/5 px-3 py-1.5 rounded-lg border border-brand/20 transition-all">+
                                            Tambah Rekening</button>
                                    </div>

                                    <div class="overflow-hidden border border-slate-200 rounded-2xl">
                                        <table class="w-full text-left bg-slate-50/50">
                                            <thead
                                                class="bg-white border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase">
                                                <tr>
                                                    <th class="px-4 py-3">Bank</th>
                                                    <th class="px-4 py-3">No. Rekening</th>
                                                    <th class="px-4 py-3">Atas Nama</th>
                                                    <th class="px-4 py-3 w-20 text-center">Default</th>
                                                    <th class="px-4 py-3 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-xs">
                                                <template x-for="(bank, index) in formData.bank_accounts" :key="index">
                                                    <tr class="border-b border-slate-100 bg-white">
                                                        <td class="p-2">
                                                            <input type="text" x-model="bank.bank_name"
                                                                class="w-full bg-transparent border-0 focus:ring-0 p-1 font-bold text-slate-700"
                                                                placeholder="BCA/Mandiri" required>
                                                        </td>
                                                        <td class="p-2">
                                                            <input type="text" x-model="bank.account_number"
                                                                class="w-full bg-transparent border-0 focus:ring-0 p-1 font-mono"
                                                                placeholder="No. Rekening" required>
                                                        </td>
                                                        <td class="p-2">
                                                            <input type="text" x-model="bank.account_holder"
                                                                class="w-full bg-transparent border-0 focus:ring-0 p-1"
                                                                placeholder="Pemilik" required>
                                                        </td>
                                                        <td class="p-2 text-center">
                                                            <input type="checkbox" @change="setBankDefault(index)"
                                                                :checked="bank.is_default"
                                                                class="w-4 h-4 text-brand border-slate-300 rounded focus:ring-brand">
                                                        </td>
                                                        <td class="p-2">
                                                            <button type="button" @click="removeBank(index)"
                                                                class="text-slate-300 hover:text-red-500 transition-colors">
                                                                <svg class="w-4 h-4" fill="currentColor"
                                                                    viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd"
                                                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                        clip-rule="evenodd"></path>
                                                                </svg>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                        <template x-if="formData.bank_accounts.length === 0">
                                            <div class="p-8 text-center bg-white italic text-slate-400 text-xs">Belum ada
                                                rekening terdaftar. Tentukan rekening untuk pembayaran invoice.</div>
                                        </template>
                                    </div>
                                </section>

                                <hr class="border-slate-100">

                                {{-- Section 3: Settings --}}
                                <section>
                                    <h4
                                        class="text-sm font-bold text-brand uppercase tracking-wider mb-4 flex items-center gap-2">
                                        <span
                                            class="w-8 h-8 rounded-full bg-brand/10 text-brand flex items-center justify-center text-xs">03</span>
                                        Pengaturan Cabang & Invoice
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="space-y-4">
                                            <p
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50 pb-1">
                                                Operasional</p>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Min. Stok
                                                    Notifikasi</label>
                                                <input type="number" x-model="formData.setting.minimum_stock"
                                                    class="form-input w-full">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Komisi Sales
                                                    (%)</label>
                                                <input type="number" step="0.01"
                                                    x-model="formData.setting.sales_commission_percentage"
                                                    class="form-input w-full">
                                            </div>
                                        </div>
                                        <div class="space-y-4">
                                            <p
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50 pb-1">
                                                Legalitas Invoice</p>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Penanda Tangan
                                                    Invoice</label>
                                                <input type="text" x-model="formData.setting.invoice_header_name"
                                                    class="form-input w-full" placeholder="Nama Lengkap">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Jabatan</label>
                                                <input type="text" x-model="formData.setting.invoice_header_position"
                                                    class="form-input w-full" placeholder="Contoh: Manager Operasional">
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Note / Footer
                                                Invoice</label>
                                            <textarea x-model="formData.setting.invoice_footer_note"
                                                class="form-input w-full" rows="2"
                                                placeholder="Syarat & ketentuan pembayaran, info retur, dll."></textarea>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </fieldset>

                        <div x-show="!detailMode && !editMode" class="px-8 pt-5" style="display: none;">
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

                        <div class="bg-slate-50 p-8 sticky bottom-0 border-t border-slate-100">
                            <div x-show="detailMode" class="flex justify-end">
                                <button type="button" @click="attemptClose()"
                                    class="btn btn-primary px-8 py-3 rounded-2xl min-w-[120px] justify-center">Tutup</button>
                            </div>

                            <div x-show="!detailMode && editMode" class="flex flex-col sm:flex-row sm:justify-end gap-3">
                                <button type="button" @click="attemptClose()"
                                    class="btn btn-outline px-8 py-3 rounded-2xl justify-center sm:min-w-[120px]">Batal</button>
                                <button type="submit" @click="submitMode = 'close'"
                                    class="btn btn-primary px-10 py-3 rounded-2xl shadow-xl shadow-brand/20 flex items-center justify-center sm:min-w-[180px]"
                                    :disabled="loading">
                                    <template x-if="!loading"><span>Simpan</span></template>
                                    <template x-if="loading">
                                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>
                                </button>
                            </div>

                            <div x-show="!detailMode && !editMode" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <button type="button" @click="attemptClose()" class="btn btn-outline w-full justify-center min-h-[44px]">Batal</button>
                                <button type="submit" @click="submitMode = 'close'"
                                    class="btn btn-primary w-full justify-center min-h-[44px] shadow-xl shadow-brand/20" :disabled="loading">
                                    <template x-if="!loading"><span>Simpan</span></template>
                                    <template x-if="loading">
                                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>
                                </button>
                                <button type="submit" @click="submitMode = 'create_another'" class="btn btn-outline w-full justify-center min-h-[44px]" :disabled="loading">Buat lagi</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
