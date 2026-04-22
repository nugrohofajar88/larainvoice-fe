@extends('layouts.app')
@section('title', 'Sales')
@section('page-title', 'Master Sales')

@section('content')
    @php
        $sortBy = request('sort_by', 'name');
        $sortDir = request('sort_dir', 'asc');
        $isSuperAdmin = \App\Helpers\AuthHelper::isSuperAdmin();
        $userBranchId = session('branch_id');

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
        formData: { 
            id: '', 
            name: '', 
            email: '', 
            username: '', 
            password: '', 
            nik: '', 
            branch_id: '{{ $isSuperAdmin ? "" : $userBranchId }}' 
        },
        defaultFormData() {
            return {
                id: '',
                name: '',
                email: '',
                username: '',
                password: '',
                nik: '',
                branch_id: '{{ $isSuperAdmin ? "" : $userBranchId }}'
            };
        },
        isFormDirty() {
            return Boolean(
                (this.formData.name || '').trim() ||
                (this.formData.email || '').trim() ||
                (this.formData.username || '').trim() ||
                (this.formData.password || '').trim() ||
                (this.formData.nik || '').trim() ||
                (this.formData.branch_id || '').toString().trim()
            );
        },
        validateCurrentForm() {
            if (!(this.formData.name || '').trim()) {
                window.toast.error('Nama sales wajib diisi.');
                return false;
            }

            if (!(this.formData.username || '').trim()) {
                window.toast.error('Username wajib diisi.');
                return false;
            }

            if (!this.editMode && !(this.formData.password || '').trim()) {
                window.toast.error('Password wajib diisi.');
                return false;
            }

            if (!this.editMode && (this.formData.password || '').length < 6) {
                window.toast.error('Password minimal 6 karakter.');
                return false;
            }

            if (this.editMode && (this.formData.password || '').trim() && (this.formData.password || '').length < 6) {
                window.toast.error('Password minimal 6 karakter.');
                return false;
            }

            if (!(this.formData.branch_id || '').toString().trim()) {
                window.toast.error('Cabang wajib dipilih.');
                return false;
            }

            return true;
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
                email: item.email || '', 
                username: item.username, 
                password: '', 
                nik: item.nik || (item.sales_profile ? item.sales_profile.nik : (item.salesProfile ? item.salesProfile.nik : '')), 
                branch_id: item.branch_id 
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
        queueCurrentItem() {
            if (!this.validateCurrentForm()) {
                return;
            }

            this.pendingItems.push({
                client_id: Date.now() + Math.random(),
                name: this.formData.name.trim(),
                email: (this.formData.email || '').trim(),
                username: this.formData.username.trim(),
                password: this.formData.password,
                nik: (this.formData.nik || '').trim(),
                branch_id: this.formData.branch_id,
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
                        email: (this.formData.email || '').trim(),
                        username: this.formData.username.trim(),
                        password: this.formData.password,
                        nik: (this.formData.nik || '').trim(),
                        branch_id: this.formData.branch_id,
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
                        await axios.post(`{{ route('master.sales.store') }}`, {
                            name: item.name,
                            email: item.email,
                            username: item.username,
                            password: item.password,
                            nik: item.nik,
                            branch_id: item.branch_id,
                        });
                        successCount += 1;
                    }

                    this.pendingItems = [];
                    this.resetCreateForm();
                    window.toast.success(`${successCount} sales berhasil disimpan.`);
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
            const url = this.editMode ? `{{ route('master.sales.index') }}/${this.formData.id}` : `{{ route('master.sales.store') }}`;
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
                if (error.response?.status === 422) {
                    console.error('Validation errors:', error.response.data.errors);
                }
            } finally {
                this.loading = false;
            }
        },
        async deleteItem(id) {
            if (await window.SwalConfirm()) {
                try {
                    const response = await axios.delete(`{{ route('master.sales.index') }}/${id}`);
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
                <h1 class="page-title text-2xl font-bold text-slate-900">Master Sales</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola data tenaga penjualan per cabang.</p>
            </div>

            @if(\App\Helpers\MenuHelper::hasPermission('sales', 'create'))
            <button @click="openCreate()" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Tambah Sales</span>
            </button>
            @endif
        </div>

        <div class="card overflow-hidden">
            <form action="{{ route('master.sales.index') }}" method="GET" id="filterForm">
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th class="w-16 text-center">NO</th>
                                <th>
                                    <a href="{{ $sortUrl('name') }}" class="flex items-center group">
                                        <span>NAMA LENGKAP</span>
                                        {!! $sortIcon('name') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('nik') }}" class="flex items-center group">
                                        <span>NIK</span>
                                        {!! $sortIcon('nik') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('username') }}" class="flex items-center group">
                                        <span>USERNAME</span>
                                        {!! $sortIcon('username') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('branch') }}" class="flex items-center group">
                                        <span>CABANG</span>
                                        {!! $sortIcon('branch') !!}
                                    </a>
                                </th>
                                <th class="w-24 text-center">AKSI</th>
                            </tr>
                            <tr class="bg-slate-50/50">
                                <th class="py-2 px-4 shadow-inner"></th>
                                <th class="py-2 px-4 shadow-inner">
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                        placeholder="Cari nama..." onchange="document.getElementById('filterForm').submit()">
                                </th>
                                <th class="py-2 px-4 shadow-inner">
                                    <input type="text" name="nik" value="{{ request('nik') }}"
                                        class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                        placeholder="Cari NIK..." onchange="document.getElementById('filterForm').submit()">
                                </th>
                                <th class="py-2 px-4 shadow-inner">
                                    <input type="text" name="username" value="{{ request('username') }}"
                                        class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                        placeholder="Cari username..." onchange="document.getElementById('filterForm').submit()">
                                </th>
                                <th class="py-2 px-4 shadow-inner">
                                    <input type="text" name="branch" value="{{ request('branch') }}"
                                        class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                        placeholder="Cari cabang..." onchange="document.getElementById('filterForm').submit()">
                                </th>
                                <th class="py-2 px-4 shadow-inner text-center">
                                    @if(request()->anyFilled(['search', 'nik', 'username', 'branch']))
                                        <a href="{{ route('master.sales.index') }}"
                                            class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $item)
                                <tr>
                                    <td class="text-center">
                                        <span
                                            class="text-slate-400 font-mono text-xs">{{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}</span>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800">{{ $item['name'] }}</span>
                                            <span
                                                class="text-[10px] text-slate-400 font-mono">{{ $item['email'] ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span>{{ $item['nik'] ?: ($item['sales_profile']['nik'] ?? ($item['salesProfile']['nik'] ?? '-')) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-slate-700 font-medium">{{ $item['username'] }}</span>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span
                                                class="text-slate-700 text-sm font-medium">{{ $item['branch']['name'] ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            @if(\App\Helpers\MenuHelper::hasPermission('sales', 'detail'))
                                            <button type="button" @click="openDetail({{ json_encode($item) }})"
                                                class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all" title="Detail">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            @endif

                                            @if(\App\Helpers\MenuHelper::hasPermission('sales', 'edit'))
                                            <button type="button" @click="openEdit({{ json_encode($item) }})"
                                                class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            @endif

                                            @if(\App\Helpers\MenuHelper::hasPermission('sales', 'delete'))
                                            <button type="button"
                                                @click="deleteItem({{ $item['id'] }})"
                                                class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
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
                                    <td colspan="6" class="py-20 text-center text-slate-400 italic">Data sales belum tersedia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @if($sales->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>

        {{-- Modal Form --}}
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center text-sm md:text-base">
                <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="attemptClose()">
                </div>

                <div
                    class="relative inline-block w-full max-w-2xl p-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900"
                                x-text="detailMode ? 'Detail Sales' : (editMode ? 'Edit Data Sales' : 'Tambah Sales Baru')"></h3>
                            <p class="text-xs text-slate-500 mt-1">Lengkapi informasi biodata dan akses login sales.</p>
                        </div>
                        <button @click="attemptClose()"
                            class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm()" class="space-y-6" novalidate>
                        <fieldset :disabled="detailMode" class="group">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Nama Lengkap --}}
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Nama Lengkap <span
                                        class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.name"
                                    class="form-input w-full" placeholder="Nama sales" required>
                            </div>

                            {{-- NIK --}}
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">NIK (Nomor Induk Karyawan)</label>
                                <input type="text" x-model="formData.nik"
                                    class="form-input w-full font-mono uppercase"
                                    placeholder="Kosongkan jika tidak ada">
                            </div>

                            {{-- Username --}}
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Username Login <span
                                        class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.username"
                                    class="form-input w-full font-mono" placeholder="username" required>
                            </div>

                            {{-- Email --}}
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Email</label>
                                <input type="email" x-model="formData.email"
                                    class="form-input w-full" placeholder="email@contoh.com">
                            </div>

                            {{-- Password --}}
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Password <span x-show="!editMode"
                                        class="text-red-500">*</span></label>
                                <input type="password" x-model="formData.password"
                                    class="form-input w-full" placeholder="Minimal 6 karakter"
                                    :required="!editMode">
                                <p x-show="editMode" class="text-[10px] text-slate-400 mt-1 italic">* Kosongkan jika tidak
                                    ingin mengubah password.</p>
                            </div>

                            {{-- Cabang --}}
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Cabang Penempatan <span
                                        class="text-red-500">*</span></label>
                                <select x-model="formData.branch_id" class="form-input w-full {{ $isSuperAdmin ? '' : 'bg-slate-100 cursor-not-allowed opacity-75' }}" {{ $isSuperAdmin ? '' : 'disabled' }} required>
                                    <option value="">--- Pilih Cabang ---</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                    @endforeach
                                </select>
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

                        <div class="mt-8 pt-2">
                            <div x-show="detailMode" class="flex justify-end">
                                <button type="button" @click="attemptClose()"
                                    class="btn btn-primary px-8 py-3 rounded-2xl min-w-[120px] justify-center">Tutup</button>
                            </div>

                            <div x-show="!detailMode && editMode" class="flex flex-col sm:flex-row sm:justify-end gap-3">
                                <button type="button" @click="attemptClose()"
                                    class="btn btn-outline px-8 py-3 rounded-2xl justify-center sm:min-w-[120px]">Batal</button>
                                <button type="submit" @click="submitMode = 'close'"
                                    class="btn btn-primary px-10 py-3 rounded-2xl shadow-xl shadow-brand/20 flex items-center justify-center sm:min-w-[160px]" :disabled="loading">
                                    <template x-if="!loading">
                                        <span>Simpan</span>
                                    </template>
                                    <template x-if="loading">
                                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </template>
                                </button>
                            </div>

                            <div x-show="!detailMode && !editMode" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <button type="button" @click="attemptClose()"
                                    class="btn btn-outline w-full justify-center min-h-[44px]">Batal</button>
                                <button type="submit" @click="submitMode = 'close'"
                                    class="btn btn-primary w-full justify-center min-h-[44px] shadow-xl shadow-brand/20" :disabled="loading">
                                    <template x-if="!loading"><span>Simpan</span></template>
                                    <template x-if="loading">
                                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </template>
                                </button>
                                <button type="submit" @click="submitMode = 'create_another'"
                                    class="btn btn-outline w-full justify-center min-h-[44px]" :disabled="loading">Buat lagi</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
