@extends('layouts.app')
@section('title', 'Tipe Biaya')
@section('page-title', 'Cost Type')

@section('content')
<div x-data="{ 
    showModal: false,
    editMode: false,
    detailMode: false,
    loading: false,
    submitMode: 'close',
    pendingItems: [],
    formData: { id: '', name: '', description: '' },
    defaultFormData() {
        return { id: '', name: '', description: '' };
    },
    isFormDirty() {
        return Boolean((this.formData.name || '').trim() || (this.formData.description || '').trim());
    },
    validateCurrentForm() {
        if (!(this.formData.name || '').trim()) {
            window.toast.error('Nama tipe biaya wajib diisi.');
            return false;
        }

        return true;
    },
    resetCreateForm() {
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
        this.formData = this.defaultFormData();
        this.showModal = true;
    },
    openEdit(item) {
        this.detailMode = false;
        this.editMode = true;
        this.submitMode = 'close';
        this.pendingItems = [];
        this.formData = { id: item.id, name: item.name, description: item.description || '' };
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
            name: this.formData.name.trim(),
            description: (this.formData.description || '').trim(),
        });
        this.resetCreateForm();
        window.toast.success('Data dimasukkan ke antrean.');
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

            try {
                const response = await axios.put(`{{ route('master.cost-type.index') }}/${this.formData.id}`, {
                    name: this.formData.name,
                    description: this.formData.description,
                });

                window.toast.success(response.data.message || 'Tipe biaya berhasil diperbarui.');
                this.showModal = false;
                setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                const message = error.response?.data?.message || 'Gagal memperbarui data.';
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
                name: this.formData.name.trim(),
                description: (this.formData.description || '').trim(),
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
                await axios.post(`{{ route('master.cost-type.store') }}`, {
                    name: item.name,
                    description: item.description,
                });
                successCount += 1;
            }

            this.pendingItems = [];
            this.resetCreateForm();
            window.toast.success(`${successCount} tipe biaya berhasil disimpan.`);
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
    }
}" x-init="@if(session('reopen_cost_type_create')) openCreate() @endif">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">Tipe Biaya</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola master jenis biaya tambahan yang bisa dipakai langsung di invoice.</p>
        </div>
        
        @if(\App\Helpers\MenuHelper::hasPermission('cost-type', 'create'))
        <button @click="openCreate()" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Tipe Biaya</span>
        </button>
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th>Nama Tipe</th>
                        <th>Deskripsi</th>
                        <th class="w-24 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costTypes as $item)
                    <tr>
                        <td><span class="text-slate-400 font-mono text-xs">{{ ($costTypes->currentPage() - 1) * $costTypes->perPage() + $loop->iteration }}</span></td>
                        <td class="font-bold text-slate-700">{{ $item['name'] }}</td>
                        <td class="text-slate-500">{{ $item['description'] ?: '-' }}</td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                @if(\App\Helpers\MenuHelper::hasPermission('cost-type', 'detail'))
                                <button @click="openDetail({{ json_encode($item) }})" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all" title="Detail"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                                @endif

                                @if(\App\Helpers\MenuHelper::hasPermission('cost-type', 'edit'))
                                <button @click="openEdit({{ json_encode($item) }})" class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                @endif

                                @if(\App\Helpers\MenuHelper::hasPermission('cost-type', 'delete'))
                                <button @click="confirmModal.confirm('{{ route('master.cost-type.destroy', $item['id']) }}')" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Hapus"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-20 text-center text-slate-400 italic">Data belum tersedia.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($costTypes->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/30">
            {{ $costTypes->links() }}
        </div>
        @endif
    </div>

    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="attemptClose()"></div>
            <div class="relative inline-block w-full max-w-md p-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900" x-text="detailMode ? 'Detail Tipe Biaya' : (editMode ? 'Edit Tipe Biaya' : 'Tambah Tipe Biaya')"></h3>
                    <button @click="attemptClose()" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm()" novalidate>
                    @csrf

                    <fieldset :disabled="detailMode" class="group">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Nama Tipe</label>
                                <input type="text" name="name" x-model="formData.name" class="form-input w-full" placeholder="Contoh: Ongkir, Instalasi, Biaya Lain-lain" required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Deskripsi</label>
                                <textarea name="description" x-model="formData.description" class="form-input w-full min-h-28" placeholder="Opsional, untuk membantu admin saat memilih item biaya."></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <div x-show="!detailMode && !editMode" class="mt-4 space-y-3" style="display: none;">
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
                            <button type="button" @click="attemptClose()" class="btn btn-primary px-8 py-3 rounded-2xl min-w-[120px] justify-center">Tutup</button>
                        </div>

                        <div x-show="!detailMode && editMode" class="flex flex-col sm:flex-row sm:justify-end gap-3">
                            <button type="button" @click="attemptClose()" class="btn btn-outline px-6 justify-center sm:min-w-[120px]">Batal</button>
                            <button type="submit" @click="submitMode = 'close'" class="btn btn-primary px-8 justify-center sm:min-w-[150px]" :disabled="loading">
                                <span x-show="!loading">Simpan</span>
                                <svg x-show="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" style="display: none;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </button>
                        </div>

                        <div x-show="!detailMode && !editMode" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button type="button" @click="attemptClose()" class="btn btn-outline w-full justify-center min-h-[44px]">Batal</button>
                            <button type="submit" @click="submitMode = 'close'" class="btn btn-primary w-full justify-center min-h-[44px]" :disabled="loading">
                                <span x-show="!loading">Simpan</span>
                                <svg x-show="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" style="display: none;"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
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
