@extends('layouts.app')
@section('title', 'Mesin')
@section('page-title', 'Master Mesin')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<style>
    .quill-wrapper .ql-toolbar {
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        border-color: rgb(226 232 240);
        background: rgb(248 250 252);
    }

    .quill-wrapper .ql-container {
        min-height: 96px;
        border-bottom-left-radius: 1rem;
        border-bottom-right-radius: 1rem;
        border-color: rgb(226 232 240);
        font-size: 0.8125rem;
        color: rgb(15 23 42);
        background: white;
    }

    .quill-wrapper .ql-editor {
        min-height: 96px;
        padding: 0.5rem 0.625rem;
    }

    .rich-preview {
        color: rgb(51 65 85);
        line-height: 1.7;
    }

    .rich-preview h1,
    .rich-preview h2,
    .rich-preview h3 {
        color: rgb(15 23 42);
        font-weight: 700;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }

    .rich-preview p {
        margin-bottom: 0.75rem;
    }

    .rich-preview ul,
    .rich-preview ol {
        padding-left: 1.25rem;
        margin-bottom: 0.75rem;
    }

    .rich-preview ul {
        list-style: disc;
    }

    .rich-preview ol {
        list-style: decimal;
    }
</style>
@endpush

@section('content')
@php
    $sortBy = request('sort_by', 'id');
    $sortDir = request('sort_dir', 'desc');
    $isSuperAdmin = \App\Helpers\AuthHelper::isSuperAdmin();
    $userBranchId = session('branch_id');
    $defaultBranchId = $isSuperAdmin ? '' : (string) $userBranchId;
    $componentOptions = collect($components)->map(fn ($item) => [
        'id' => $item['id'],
        'name' => $item['name'] ?? 'Komponen',
        'type_size' => $item['type_size'] ?? '',
        'branch_id' => $item['branch_id'] ?? null,
        'branch_name' => $item['branch']['name'] ?? ($item['branch_name'] ?? ''),
        'category_name' => $item['component_category']['name'] ?? ($item['component_category_name'] ?? ''),
        'label' => trim(($item['name'] ?? 'Komponen') . (($item['type_size'] ?? '') ? ' - ' . $item['type_size'] : '')),
    ])->values();

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

<div x-data="machinePage()" x-init="init()" x-cloak>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="page-title text-2xl font-bold text-slate-900">Master Mesin</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola mesin operasional per cabang beserta komponen penyusun dan lampirannya.</p>
        </div>

        @if(\App\Helpers\MenuHelper::hasPermission('machine', 'create'))
        <button @click="openCreate()" class="btn btn-primary px-5 shadow-lg shadow-brand/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Mesin</span>
        </button>
        @endif
    </div>

    <div class="card overflow-hidden">
        <form action="{{ route('master.mesin.index') }}" method="GET" id="filterForm">
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">NO</th>
                            <th>
                                <a href="{{ $sortUrl('machine_number') }}" class="flex items-center group">
                                    <span>NO. MESIN</span>
                                    {!! $sortIcon('machine_number') !!}
                                </a>
                            </th>
                            <th>JENIS MESIN</th>
                            <th>CABANG</th>
                            <th class="text-right">
                                <a href="{{ $sortUrl('base_price') }}" class="inline-flex items-center group">
                                    <span>HARGA DASAR</span>
                                    {!! $sortIcon('base_price') !!}
                                </a>
                            </th>
                            <th>KOMPONEN</th>
                            <th>STATUS</th>
                            <th class="w-24 text-center">AKSI</th>
                        </tr>
                        <tr class="bg-slate-50/50">
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner">
                                <input type="text" name="search" value="{{ request('search') }}"
                                    class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all placeholder:text-slate-300"
                                    placeholder="Cari no. mesin..." onchange="document.getElementById('filterForm').submit()">
                            </th>
                            <th class="py-2 px-4 shadow-inner">
                                <select name="machine_type_id" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand outline-none transition-all">
                                    <option value="">Semua jenis</option>
                                    @foreach($machineTypes as $machineType)
                                        <option value="{{ $machineType['id'] }}" {{ (string) request('machine_type_id') === (string) $machineType['id'] ? 'selected' : '' }}>
                                            {{ $machineType['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="py-2 px-4 shadow-inner">
                                @if($isSuperAdmin)
                                <select name="branch_id" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand outline-none transition-all">
                                    <option value="">Semua cabang</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch['id'] }}" {{ (string) request('branch_id') === (string) $branch['id'] ? 'selected' : '' }}>
                                            {{ $branch['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @endif
                            </th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner"></th>
                            <th class="py-2 px-4 shadow-inner">
                                <select name="is_active" onchange="document.getElementById('filterForm').submit()" class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1.5 focus:ring-1 focus:ring-brand outline-none transition-all">
                                    <option value="">Semua status</option>
                                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Non-Aktif</option>
                                </select>
                            </th>
                            <th class="py-2 px-4 shadow-inner text-center">
                                @if(request()->anyFilled(['search', 'machine_type_id', 'branch_id', 'is_active']))
                                    <a href="{{ route('master.mesin.index') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($machines as $item)
                        <tr>
                            <td class="text-center">
                                <span class="text-slate-400 font-mono text-xs">{{ ($machines->currentPage() - 1) * $machines->perPage() + $loop->iteration }}</span>
                            </td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ $item['machine_number'] }}</span>
                                    <span class="text-[10px] text-slate-400 uppercase font-mono tracking-tighter">ID: {{ $item['id'] }}</span>
                                </div>
                            </td>
                            <td>{{ $item['type']['name'] ?? ($item['machine_type_name'] ?? '-') }}</td>
                            <td>{{ $item['branch']['name'] ?? ($item['branch_name'] ?? '-') }}</td>
                            <td class="text-right font-semibold text-slate-700">Rp {{ number_format((float) ($item['base_price'] ?? 0), 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $componentCount = count($item['machine_components'] ?? []);
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 text-xs font-semibold text-slate-600">
                                    {{ $componentCount }} item
                                </span>
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
                                    @if(\App\Helpers\MenuHelper::hasPermission('machine', 'detail'))
                                    <button type="button" @click='openDetail(@json($item))' class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all" title="Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    @endif
                                    @if(\App\Helpers\MenuHelper::hasPermission('machine', 'edit'))
                                    <button type="button" @click='openEdit(@json($item))' class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    @endif
                                    @if(\App\Helpers\MenuHelper::hasPermission('machine', 'delete'))
                                    <button type="button" @click="deleteItem({{ $item['id'] }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-20 text-center text-slate-400 italic">Data mesin belum tersedia.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($machines->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/30">
            {{ $machines->links() }}
        </div>
        @endif
    </div>

    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center text-sm md:text-base">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>

            <div class="relative inline-block w-full max-w-5xl p-8 overflow-visible text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl">
                <div class="flex items-start justify-between gap-4 mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900" x-text="detailMode ? 'Detail Mesin' : (editMode ? 'Edit Mesin' : 'Tambah Mesin')"></h3>
                        <p class="text-xs text-slate-500 mt-1">Lengkapi informasi mesin, deskripsi, file lampiran, dan komponen penyusunnya.</p>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm()" class="space-y-4">
                    <fieldset :disabled="detailMode">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cabang <span class="text-red-500">*</span></label>
                                <select x-model="formData.branch_id" @change="handleBranchChanged()" class="form-input w-full {{ $isSuperAdmin ? '' : 'bg-slate-100 cursor-not-allowed opacity-75' }}" {{ $isSuperAdmin ? '' : 'disabled' }} required>
                                    <option value="">--- Pilih Cabang ---</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">No. Mesin <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.machine_number" class="form-input w-full font-mono uppercase" placeholder="Contoh: L-12 atau M-01" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Mesin <span class="text-red-500">*</span></label>
                                <select x-model="formData.machine_type_id" class="form-input w-full" required>
                                    <option value="">--- Pilih Jenis ---</option>
                                    @foreach($machineTypes as $machineType)
                                        <option value="{{ $machineType['id'] }}">{{ $machineType['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg) <span class="text-red-500">*</span></label>
                                <input type="number" x-model="formData.weight" min="0" step="0.01" class="form-input w-full" placeholder="0" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Base Price <span class="text-red-500">*</span></label>
                                <input type="text" :value="formatCurrency(formData.base_price)" @input="handleCurrencyInput($event, 'base_price')" inputmode="numeric" class="form-input w-full font-mono text-right" placeholder="0" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2.5">
                                    <div class="pr-3">
                                        <p class="text-sm font-semibold" :class="Number(formData.is_active) === 1 ? 'text-emerald-600' : 'text-red-500'" x-text="Number(formData.is_active) === 1 ? 'Aktif' : 'Tidak Aktif'"></p>
                                        <p class="text-xs text-slate-500">Gunakan switch untuk mengubah status.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="formData.is_active = Number(formData.is_active) === 1 ? 0 : 1"
                                        class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
                                        :class="Number(formData.is_active) === 1 ? 'bg-emerald-500' : 'bg-slate-300'"
                                        :aria-pressed="Number(formData.is_active) === 1 ? 'true' : 'false'"
                                    >
                                        <span
                                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"
                                            :class="Number(formData.is_active) === 1 ? 'translate-x-6' : 'translate-x-1'"
                                        ></span>
                                    </button>
                                </div>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <div class="quill-wrapper">
                                    <div x-ref="descriptionEditor"></div>
                                </div>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Lampiran File</label>
                                <input type="file" x-ref="files" class="form-input w-full" multiple accept=".pdf,.dxf,.dwg,.ai,.cdr,.svg,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx">
                                <p class="mt-1 text-xs text-slate-500">Maksimal 10 file, masing-masing maksimal 20MB.</p>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between mb-3 gap-3">
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900">Komponen Penyusun</h4>
                                    <p class="text-xs text-slate-500">Pilih cabang terlebih dahulu, lalu cari komponen berdasarkan nama atau tipe.</p>
                                </div>
                                <button type="button" @click="addComponentRow()" class="btn btn-outline px-3 py-2 rounded-xl text-sm">
                                    Tambah Komponen
                                </button>
                            </div>

                            <div class="overflow-visible rounded-xl border border-slate-200">
                                <template x-if="formData.components.length === 0">
                                    <div class="bg-slate-50 px-4 py-5 text-sm text-slate-500 text-center">
                                        Belum ada komponen yang ditambahkan.
                                    </div>
                                </template>

                                <template x-if="formData.components.length > 0">
                                    <div class="bg-slate-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 grid grid-cols-[minmax(0,1fr)_88px_44px] gap-2">
                                        <div>Nama Komponen</div>
                                        <div class="text-center">Qty</div>
                                        <div></div>
                                    </div>
                                </template>

                                <template x-for="(component, index) in formData.components" :key="`component-${index}`">
                                    <div class="overflow-visible border-t border-slate-200 first:border-t-0 bg-white px-3 py-2.5">
                                        <div class="grid grid-cols-[minmax(0,1fr)_88px_44px] gap-2 items-start">
                                            <div class="overflow-visible">
                                                <div class="relative" @click.outside="closeComponentDropdown(index)">
                                                    <input
                                                        type="text"
                                                        x-model="component.search"
                                                        @focus="openComponentDropdown(index)"
                                                        @input="syncComponentInput(index, $event.target.value)"
                                                        @blur="handleComponentBlur(index)"
                                                        class="form-input w-full h-10"
                                                        :class="!formData.branch_id ? 'bg-slate-100 cursor-not-allowed opacity-75' : ''"
                                                        :disabled="!formData.branch_id"
                                                        :placeholder="formData.branch_id ? 'Cari komponen...' : 'Pilih cabang terlebih dahulu'"
                                                    >

                                                    <div
                                                        x-show="component.dropdownOpen"
                                                        x-transition
                                                        class="absolute left-0 right-0 z-40 mt-1 w-full min-w-[24rem] rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden"
                                                        style="display: none;"
                                                    >
                                                        <div class="max-h-64 overflow-y-auto">
                                                            <template x-if="!formData.branch_id">
                                                                <div class="px-4 py-4 text-sm text-slate-400 text-center">
                                                                    Pilih cabang terlebih dahulu.
                                                                </div>
                                                            </template>

                                                            <template x-if="formData.branch_id">
                                                                <div>
                                                                    <template x-for="option in filteredComponentsForRow(index)" :key="`machine-component-${index}-${option.id}`">
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
                                            </div>
                                            <div>
                                                <input type="number" min="1" x-model="component.qty" class="form-input w-full h-10 text-center" placeholder="1">
                                            </div>
                                            <div class="flex justify-center">
                                                <button type="button" @click="removeComponentRow(index)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 text-red-500 hover:bg-red-50 transition-all" title="Hapus komponen">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </fieldset>

                    <template x-if="detailMode && selectedItem">
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <h4 class="text-sm font-bold text-slate-800 mb-3">Ringkasan Komponen</h4>
                            <div class="rounded-xl border border-slate-200 overflow-hidden">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Komponen</th>
                                            <th class="px-3 py-2 text-left">Kategori</th>
                                            <th class="px-3 py-2 text-right">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="item in selectedItem.machine_components || []" :key="item.id">
                                            <tr class="border-t border-slate-100">
                                                <td class="px-3 py-2" x-text="item.component?.name || '-'"></td>
                                                <td class="px-3 py-2" x-text="item.component?.component_category?.name || '-'"></td>
                                                <td class="px-3 py-2 text-right font-semibold" x-text="item.qty"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <template x-if="detailMode">
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <h4 class="text-sm font-bold text-slate-800 mb-3">Deskripsi</h4>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="rich-preview" x-show="selectedItem?.description" x-html="selectedItem?.description || ''"></div>
                                <p class="text-sm text-slate-500 italic" x-show="!selectedItem?.description">Belum ada deskripsi.</p>
                            </div>
                        </div>
                    </template>

                    <template x-if="selectedItem && (selectedItem.files || []).length">
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <h4 class="text-sm font-bold text-slate-800 mb-3">Lampiran Tersimpan</h4>
                            <div class="space-y-1.5">
                                <template x-for="file in selectedItem.files || []" :key="file.id">
                                    <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-slate-800 truncate" x-text="file.file_name || ('File #' + file.id)"></p>
                                            <p class="text-[11px] text-slate-500">
                                                <span x-text="(file.file_extension || '-').toUpperCase()"></span>
                                                <span> - </span>
                                                <span x-text="formatFileSize(file.file_size || 0)"></span>
                                            </p>
                                        </div>
                                        <a :href="`{{ route('master.mesin.files.download', ['id' => '__ID__', 'fileId' => '__FILE__']) }}`.replace('__ID__', selectedItem.id).replace('__FILE__', file.id)"
                                            class="btn btn-outline px-3 py-1.5 rounded-lg text-xs whitespace-nowrap">
                                            Download
                                        </a>
                                        <button type="button"
                                            x-show="{{ \App\Helpers\MenuHelper::hasPermission('machine', 'edit') ? 'true' : 'false' }}"
                                            @click="deleteFile(selectedItem.id, file.id)"
                                            class="btn btn-outline px-3 py-1.5 rounded-lg text-xs whitespace-nowrap border-red-200 text-red-500 hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="mt-6 flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" @click="showModal = false" x-show="detailMode" class="btn btn-primary px-4 py-2 rounded-xl min-w-[100px] text-sm">Tutup</button>
                        <button type="button" @click="showModal = false" x-show="!detailMode" class="btn btn-outline px-4 py-2 rounded-xl text-sm">Batal</button>
                        <button type="submit" x-show="!detailMode" class="btn btn-primary px-5 py-2 rounded-xl flex items-center justify-center min-w-[160px] text-sm" :disabled="loading">
                            <template x-if="!loading">
                                <span x-text="editMode ? 'Simpan Perubahan' : 'Simpan Mesin'"></span>
                            </template>
                            <template x-if="loading">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function machinePage() {
        return {
            showModal: false,
            editMode: false,
            detailMode: false,
            loading: false,
            selectedItem: null,
            formData: {},
            quill: null,
            componentOptions: @js($componentOptions),
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
            init() {
                this.resetForm();
            },
            get filteredBranchComponents() {
                const branchId = String(this.formData.branch_id || '');

                if (!branchId) {
                    return this.componentOptions;
                }

                return this.componentOptions.filter((item) => String(item.branch_id || '') === branchId);
            },
            initEditor() {
                if (this.quill || !this.$refs.descriptionEditor || typeof Quill === 'undefined') {
                    return;
                }

                this.quill = new Quill(this.$refs.descriptionEditor, {
                    theme: 'snow',
                    placeholder: 'Tulis deskripsi mesin di sini...',
                    modules: {
                        toolbar: [
                            [{ header: [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline'],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            ['link', 'blockquote'],
                            ['clean']
                        ]
                    }
                });

                this.quill.on('text-change', () => {
                    this.formData.description = this.quill.root.innerHTML === '<p><br></p>'
                        ? ''
                        : this.quill.root.innerHTML;
                });
            },
            syncEditorContent() {
                this.$nextTick(() => {
                    this.initEditor();

                    if (!this.quill) {
                        return;
                    }

                    const html = this.formData.description || '';
                    this.quill.root.innerHTML = html || '<p><br></p>';
                });
            },
            resetForm() {
                this.formData = {
                    id: '',
                    machine_number: '',
                    machine_type_id: '',
                    branch_id: @js($defaultBranchId),
                    base_price: 0,
                    weight: 0,
                    description: '',
                    is_active: 1,
                    components: [],
                };
                this.syncEditorContent();
            },
            normalizeComponents(items = []) {
                return items.map(item => ({
                    component_id: String(item.component_id ?? item.component?.id ?? ''),
                    search: (item.component?.name || '') + ((item.component?.type_size || '') ? ` - ${item.component.type_size}` : ''),
                    dropdownOpen: false,
                    qty: Number(item.qty ?? item.pivot?.qty ?? 1),
                }));
            },
            handleBranchChanged() {
                this.formData.components = this.formData.components.map(() => ({
                    component_id: '',
                    search: '',
                    dropdownOpen: false,
                    qty: 1,
                }));
            },
            openCreate() {
                this.editMode = false;
                this.detailMode = false;
                this.selectedItem = null;
                this.resetForm();
                this.showModal = true;
                this.syncEditorContent();
                if (this.$refs.files) {
                    this.$refs.files.value = '';
                }
            },
            openEdit(item) {
                this.editMode = true;
                this.detailMode = false;
                this.selectedItem = item;
                this.formData = {
                    id: item.id,
                    machine_number: item.machine_number ?? '',
                    machine_type_id: String(item.machine_type_id ?? ''),
                    branch_id: String(item.branch_id ?? @js($defaultBranchId)),
                    base_price: item.base_price ?? 0,
                    weight: item.weight ?? 0,
                    description: item.description ?? '',
                    is_active: item.is_active ? 1 : 0,
                    components: this.normalizeComponents(item.machine_components || []),
                };
                this.showModal = true;
                this.syncEditorContent();
                if (this.$refs.files) {
                    this.$refs.files.value = '';
                }
            },
            openDetail(item) {
                this.openEdit(item);
                this.detailMode = true;
                this.editMode = false;
            },
            addComponentRow() {
                this.formData.components.push({ component_id: '', search: '', dropdownOpen: false, qty: 1 });
            },
            removeComponentRow(index) {
                this.formData.components.splice(index, 1);
            },
            openComponentDropdown(index) {
                if (this.detailMode || !this.formData.branch_id) {
                    return;
                }

                this.formData.components = this.formData.components.map((item, itemIndex) => ({
                    ...item,
                    dropdownOpen: itemIndex === index,
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
                if (!this.formData.components[index]) {
                    return;
                }

                if (this.isComponentUsedInAnotherRow(option.id, index)) {
                    window.toast.error('Komponen ini sudah dipilih di baris lain.');
                    return;
                }

                this.formData.components[index].component_id = String(option.id);
                this.formData.components[index].search = option.label;
                this.formData.components[index].dropdownOpen = false;
            },
            syncComponentInput(index, value) {
                if (!this.formData.components[index]) {
                    return;
                }

                const row = this.formData.components[index];
                row.search = value;
                row.dropdownOpen = !!this.formData.branch_id;

                const matched = this.filteredBranchComponents.find((item) => item.label === value);
                if (matched && this.isComponentUsedInAnotherRow(matched.id, index)) {
                    row.component_id = '';
                    window.toast.error('Komponen ini sudah dipilih di baris lain.');
                    return;
                }

                row.component_id = matched ? String(matched.id) : '';
            },
            handleComponentBlur(index) {
                setTimeout(() => this.closeComponentDropdown(index), 150);
            },
            buildPayload() {
                return {
                    machine_number: this.formData.machine_number,
                    machine_type_id: this.formData.machine_type_id,
                    branch_id: this.formData.branch_id,
                    base_price: this.formData.base_price,
                    weight: this.formData.weight,
                    description: this.formData.description,
                    is_active: Number(this.formData.is_active),
                    components: this.formData.components
                        .filter(item => item.component_id && Number(item.qty) > 0)
                        .map(item => ({
                            component_id: Number(item.component_id),
                            qty: Number(item.qty),
                        })),
                };
            },
            buildFormData() {
                const payload = this.buildPayload();
                const formData = new FormData();

                Object.entries(payload).forEach(([key, value]) => {
                    if (key === 'components') {
                        value.forEach((component, index) => {
                            formData.append(`components[${index}][component_id]`, component.component_id);
                            formData.append(`components[${index}][qty]`, component.qty);
                        });
                        return;
                    }

                    formData.append(key, value ?? '');
                });

                const files = this.$refs.files?.files ? Array.from(this.$refs.files.files) : [];
                files.forEach((file, index) => {
                    formData.append(`files[${index}]`, file);
                });

                return formData;
            },
            formatFileSize(size) {
                const value = Number(size || 0);
                if (value < 1024) return `${value} B`;
                if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
                return `${(value / (1024 * 1024)).toFixed(1)} MB`;
            },
            async submitForm() {
                this.loading = true;
                const url = this.editMode ? `{{ route('master.mesin.index') }}/${this.formData.id}` : `{{ route('master.mesin.store') }}`;

                try {
                    const formData = this.buildFormData();
                    if (this.editMode) {
                        formData.append('_method', 'PUT');
                    }

                    const response = await axios.post(url, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
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
                        const response = await axios.delete(`{{ route('master.mesin.index') }}/${id}`);
                        window.toast.success(response.data.message || 'Berhasil dihapus');
                        setTimeout(() => window.location.reload(), 500);
                    } catch (error) {
                        window.toast.error(error.response?.data?.message || 'Gagal menghapus data');
                    }
                }
            },
            async deleteFile(machineId, fileId) {
                if (await window.SwalConfirm()) {
                    try {
                        const response = await axios.delete(`{{ route('master.mesin.files.destroy', ['id' => '__ID__', 'fileId' => '__FILE__']) }}`.replace('__ID__', machineId).replace('__FILE__', fileId));
                        window.toast.success(response.data.message || 'File berhasil dihapus');

                        if (this.selectedItem?.files) {
                            this.selectedItem.files = this.selectedItem.files.filter(file => Number(file.id) !== Number(fileId));
                        }
                    } catch (error) {
                        window.toast.error(error.response?.data?.message || 'Gagal menghapus file');
                    }
                }
            }
        }
    }
</script>
@endsection
