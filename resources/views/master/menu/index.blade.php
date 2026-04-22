@extends('layouts.app')
@section('title', 'Manajemen Menu')
@section('page-title', 'Master Menu')

@section('content')
<div x-data="{ 
    showModal: false,
    editMode: false,
    detailMode: false,
    formData: { 
        id: '', 
        name: '', 
        key: '', 
        parent_id: '', 
        icon: '', 
        route: '', 
        sort_order: 0, 
        is_active: 1 
    },
    openCreate() {
        this.detailMode = false;
        this.editMode = false;
        this.formData = { id: '', name: '', key: '', parent_id: '', icon: '', route: '', sort_order: 0, is_active: 1 };
        this.showModal = true;
    },
    openEdit(item) {
        this.editMode = true;
        this.formData = { 
            id: item.id, 
            name: item.name, 
            key: item.key, 
            parent_id: item.parent_id || '', 
            icon: item.icon || '', 
            route: item.route || '', 
            sort_order: item.sort_order, 
            is_active: item.is_active ? 1 : 0 
        };
        this.showModal = true;
    },
    openDetail(item) {
        this.openEdit(item);
        this.detailMode = true;
        this.editMode = false;
    }
}">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">Manajemen Menu Navigasi</h1>
            <p class="text-sm text-slate-500 mt-1">Konfigurasi struktur menu, hierarki, dan urutan tampilan sidebar.</p>
        </div>
        
        @if(\App\Helpers\MenuHelper::hasPermission('menu-setting', 'create'))
        <button @click="openCreate()" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Menu</span>
        </button>
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">ORDER</th>
                        <th>Nama Menu</th>
                        <th>Key Identifier</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th class="w-24 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($menus as $item)
                    <tr class="{{ $item['level'] > 0 ? 'bg-slate-50/20' : 'bg-white font-semibold' }}">
                        <td>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-500 font-mono text-xs ring-1 ring-slate-200">
                                {{ $item['sort_order'] }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center" style="margin-left: {{ $item['level'] * 1.5 }}rem">
                                @if($item['level'] > 0)
                                    <div class="w-4 border-b-2 border-l-2 border-slate-300 h-4 -mt-3 mr-2 rounded-bl-lg"></div>
                                @endif
                                <div class="flex flex-col">
                                    <span class="text-slate-800">{{ $item['name'] }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono italic uppercase tracking-tighter">{{ $item['route'] ?? 'No Route' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code class="text-xs px-2 py-1 bg-slate-100 rounded-md text-slate-600 ring-1 ring-slate-200">{{ $item['key'] }}</code>
                        </td>
                        <td>
                            @if($item['parent_id'])
                                <span class="badge badge-outline text-[10px] px-2 py-0.5 border-slate-200 text-slate-500">ID: {{ $item['parent_id'] }}</span>
                            @else
                                <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">Root</span>
                            @endif
                        </td>
                        <td>
                            @if($item['is_active'])
                                <span class="badge badge-success text-[10px] px-2 py-0.5">Aktif</span>
                            @else
                                <span class="badge badge-danger text-[10px] px-2 py-0.5">Non-Aktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                @if(\App\Helpers\MenuHelper::hasPermission('menu-setting', 'detail'))
                                <button @click="openDetail({{ json_encode($item) }})" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all" title="Detail"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                                @endif

                                @if(\App\Helpers\MenuHelper::hasPermission('menu-setting', 'edit'))
                                <button @click="openEdit({{ json_encode($item) }})" class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                @endif

                                @if(\App\Helpers\MenuHelper::hasPermission('menu-setting', 'delete'))
                                <button @click="confirmModal.confirm('{{ route('master.menu.destroy', $item['id']) }}')" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Hapus"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-20 text-center text-slate-400 italic">Data menu belum tersedia.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Form --}}
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center text-sm md:text-base">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
            
            <div class="relative inline-block w-full max-w-2xl p-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900" x-text="detailMode ? 'Detail Menu' : (editMode ? 'Edit Menu' : 'Tambah Menu Baru')"></h3>
                        <p class="text-xs text-slate-500 mt-1">Konfigurasikan properti menu dan hierarki navigasi.</p>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="editMode ? '{{ route('master.menu.index') }}/' + formData.id : '{{ route('master.menu.store') }}'" method="POST">
                    @csrf
                    <template x-if="editMode">
                        @method('PUT')
                    </template>

                    <fieldset :disabled="detailMode" class="group">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Nama Menu --}}
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Menu <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="formData.name" class="form-input w-full" placeholder="Contoh: Laporan Penjualan" required>
                        </div>

                        {{-- Key Identifier --}}
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Key Identifier <span class="text-red-500">*</span></label>
                            <input type="text" name="key" x-model="formData.key" class="form-input w-full font-mono text-xs uppercase" placeholder="CONTOH: SALES-REPORT" required>
                            <p class="text-[10px] text-slate-400 mt-1">Harus unik, digunakan untuk icon & permission sistem.</p>
                        </div>

                        {{-- Parent Menu --}}
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Menu Induk (Parent)</label>
                            <select name="parent_id" x-model="formData.parent_id" class="form-input w-full">
                                <option value="">--- Jadikan Root Menu ---</option>
                                @foreach($menuTree as $root)
                                    <option value="{{ $root['id'] }}">{{ $root['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Order --}}
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Urutan (Sort Order)</label>
                            <input type="number" name="sort_order" x-model="formData.sort_order" class="form-input w-full" placeholder="0" required>
                        </div>

                        {{-- Icon --}}
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Icon (SVG/Class)</label>
                            <input type="text" name="icon" x-model="formData.icon" class="form-input w-full" placeholder="Kosongkan jika ingin default">
                        </div>

                        {{-- Status --}}
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Status Aktif</label>
                            <div class="flex items-center gap-4 mt-2">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="is_active" value="1" x-model="formData.is_active" class="hidden peer">
                                    <div class="w-5 h-5 rounded-full border-2 border-slate-200 peer-checked:border-brand peer-checked:bg-brand flex items-center justify-center transition-all group-hover:border-brand/40">
                                        <div class="w-1.5 h-1.5 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-600 peer-checked:text-brand transition-colors">Aktif</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="is_active" value="0" x-model="formData.is_active" class="hidden peer">
                                    <div class="w-5 h-5 rounded-full border-2 border-slate-200 peer-checked:border-red-500 peer-checked:bg-red-500 flex items-center justify-center transition-all group-hover:border-red-400">
                                        <div class="w-1.5 h-1.5 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-600 peer-checked:text-red-500 transition-colors">Non-Aktif</span>
                                </label>
                            </div>
                        </div>

                        {{-- Route --}}
                        <div class="col-span-full">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Rute Web (Route Path)</label>
                            <input type="text" name="route" x-model="formData.route" class="form-input w-full font-mono text-xs" placeholder="/master/menu-custom">
                        </div>
                    </div>
                    </fieldset>

                    <div class="mt-10 flex justify-end gap-3">
                        <button type="button" @click="showModal = false" x-show="detailMode" class="btn btn-primary px-8 py-3 rounded-2xl min-w-[120px]">Tutup</button>

                        <button type="button" @click="showModal = false" x-show="!detailMode" class="btn btn-outline px-8 py-3 rounded-2xl">Batal</button>
                        <button type="submit" x-show="!detailMode" class="btn btn-primary px-10 py-3 rounded-2xl shadow-xl shadow-brand/20 flex items-center justify-center min-w-[160px]">
                            <span x-text="editMode ? 'Simpan Perubahan' : 'Tambah Menu'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
