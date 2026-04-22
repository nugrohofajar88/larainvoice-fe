@extends('layouts.app')
@section('title', $role ? 'Edit Role' : 'Tambah Role')
@section('page-title', $role ? 'Edit Role' : 'Tambah Role')

@section('content')
@php
    /**
     * Membangun peta relasi & daftar modul rata (flattened) secara rekursif.
     */
    $childToParentIdMap = [];
    $flattenModules = [];

    $processModules = function($items, $level = 0) use (&$processModules, &$childToParentIdMap, &$flattenModules) {
        foreach ($items as $item) {
            // Peta untuk JS logic
            if (!empty($item['parent_id'])) {
                $childToParentIdMap[(int) $item['id']] = (int) $item['parent_id'];
            }
            
            // Tambahkan info level untuk identasi di UI
            $item['level'] = $level;
            $flattenModules[] = $item;

            // Proses children jika ada
            if (!empty($item['children'])) {
                $processModules($item['children'], $level + 1);
            }
        }
    };

    $processModules($modules);
@endphp

<div class="max-w-5xl mx-auto">
    <form method="POST"
          action="{{ $role ? route('master.role.update', $role['id']) : route('master.role.store') }}"
          id="role-form">
        @csrf
        @if($role) @method('PUT') @endif

        {{-- Error Messages --}}
        @if($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-5 py-4 text-sm space-y-1 mb-6">
                <p class="font-semibold">Terdapat kesalahan:</p>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif
        @if(session('api_errors') && count(session('api_errors')))
            <div class="rounded-xl bg-orange-50 border border-orange-200 text-orange-700 px-5 py-4 text-sm space-y-1 mb-6">
                <p class="font-semibold">Detail validasi dari server:</p>
                @foreach(session('api_errors') as $field => $messages)
                    @foreach((array) $messages as $msg)
                        <p>• <span class="font-medium capitalize">{{ str_replace('_', ' ', $field) }}</span>: {{ $msg }}</p>
                    @endforeach
                @endforeach
            </div>
        @endif

        @php
            $isDetail = request('detail') == 1;
        @endphp

        <div class="space-y-6">
            <fieldset {{ $isDetail ? 'disabled' : '' }} class="group space-y-6">

            {{-- ═══ BAGIAN 1: INFO ROLE ═══ --}}
            <div class="card">
                <div class="card-header border-b border-slate-100 bg-slate-50/50">
                    <h2 class="font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        {{ $role ? 'Edit Role' : 'Role Baru' }}
                    </h2>
                </div>
                <div class="card-body">
                    <div class="max-w-sm">
                        <label class="form-label text-slate-700">Nama Role</label>
                        <input type="text" name="name" class="form-input uppercase"
                               value="{{ old('name', $role['name'] ?? '') }}"
                               required placeholder="Contoh: admin-cabang, kasir, sales...">
                        <p class="text-[10px] text-slate-400 mt-1">Nama role akan disimpan dalam huruf kapital.</p>
                    </div>
                </div>
            </div>

            {{-- ═══ BAGIAN 2: MATRIKS PERMISSION ═══ --}}
            <div class="card">
                <div class="card-header border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-4">
                    <h2 class="font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Matriks Hak Akses
                    </h2>
                    @if(count($modules) > 0)
                        <button type="button" onclick="toggleAll()"
                                class="text-xs text-slate-500 hover:text-brand border border-slate-200 hover:border-brand rounded-lg px-3 py-1.5 transition-all">
                            Centang / Hapus Semua
                        </button>
                    @endif
                </div>

                @if(count($modules) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm table-fixed">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-5 py-3 w-64">
                                        Modul
                                    </th>
                                    @foreach(['view' => 'Lihat', 'create' => 'Tambah', 'edit' => 'Edit', 'delete' => 'Hapus', 'detail' => 'Detail'] as $action => $label)
                                        <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">
                                            <button type="button"
                                                    onclick="toggleColumn('{{ $action }}')"
                                                    class="flex flex-col items-center gap-1 mx-auto group cursor-pointer">
                                                @if($action === 'view')
                                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                @elseif($action === 'create')
                                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                @elseif($action === 'edit')
                                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                @elseif($action === 'delete')
                                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                @elseif($action === 'detail')
                                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @endif
                                                <span class="group-hover:text-brand transition-colors">{{ $label }}</span>
                                            </button>
                                        </th>
                                    @endforeach
                                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3 w-24">
                                        Semua
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($flattenModules as $perm)
                                    @php
                                        $module   = $perm['module'];
                                        $label    = !empty($perm['name'])
                                                      ? $perm['name']
                                                      : ucwords(str_replace('-', ' ', $module));
                                        
                                        $level    = $perm['level'] ?? 0;
                                        $isParent = ($level === 0);
                                    @endphp
                                    <tr class="{{ $isParent ? 'bg-slate-50/40' : 'bg-transparent' }} border-b border-slate-100 hover:bg-slate-50/30 transition-colors">
                                        <td class="px-5 py-4 relative">
                                            {{-- Indikator Hirarki (Garis Tree) --}}
                                            @if(!$isParent)
                                                <div class="absolute left-7 top-0 bottom-0 w-px bg-slate-200"></div>
                                                <div class="absolute left-7 top-1/2 w-4 h-px bg-slate-200"></div>
                                            @endif

                                            {{-- Hidden inputs --}}
                                            <input type="hidden" name="modules[]" value="{{ $module }}">
                                            <input type="hidden" name="permission_ids[{{ $module }}]" value="{{ $perm['id'] ?? '' }}">

                                            <div class="relative flex items-center gap-3" style="padding-left: {{ $level === 0 ? 0 : ($level * 1.5 + 0.5) }}rem;">
                                                @if($isParent)
                                                    <div class="w-2 h-2 rounded-full border-2 border-brand bg-white"></div>
                                                @endif
                                                <div class="flex flex-col">
                                                    <span class="{{ $isParent ? 'font-bold text-slate-800' : 'text-slate-600' }} text-sm leading-none">
                                                        {{ $label }}
                                                    </span>
                                                    <span class="text-[10px] text-slate-400 font-mono mt-1">{{ $module }}</span>
                                                </div>
                                            </div>
                                        </td>

                                        @foreach(['view', 'create', 'edit', 'delete', 'detail'] as $action)
                                            <td class="text-center px-4 py-4">
                                                <div class="relative inline-flex items-center justify-center w-6 h-6 group">
                                                    <input type="checkbox"
                                                           name="permissions[{{ $module }}][{{ $action }}]"
                                                           value="1"
                                                           data-module="{{ $module }}"
                                                           data-perm-id="{{ $perm['id'] }}"
                                                           data-action="{{ $action }}"
                                                           class="absolute inset-0 opacity-0 z-10 cursor-pointer peer"
                                                           onchange="onPermissionChange(this)"
                                                           {{ $perm[$action] ? 'checked' : '' }}>
                                                    
                                                    {{-- Premium Checkbox Design --}}
                                                    <div class="w-5 h-5 rounded border-2 border-slate-200 peer-checked:border-brand peer-checked:bg-brand flex items-center justify-center transition-all bg-white">
                                                        <svg class="w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transform scale-50 peer-checked:scale-100 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </td>
                                        @endforeach

                                        {{-- Toggle seluruh baris --}}
                                        <td class="text-center px-4 py-2.5">
                                            <button type="button"
                                                    onclick="toggleRow('{{ $module }}')"
                                                    class="text-xs text-slate-400 hover:text-brand border border-slate-200 hover:border-brand/50 rounded-lg px-2.5 py-1.5 transition-all">
                                                Semua
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center text-slate-400">
                        <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm">Daftar modul tidak dapat dimuat dari server.</p>
                        <p class="text-xs mt-1">Pastikan terdapat minimal satu role di sistem.</p>
                    </div>
                @endif
            </div>

            {{-- ═══ BAGIAN 3: DAFTAR PENGGUNA (Edit only, readonly) ═══ --}}
            @if($role && !empty($role['users']))
                <div class="card">
                    <div class="card-header border-b border-slate-100 bg-slate-50/50">
                        <h2 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Pengguna dengan Role Ini
                            <span class="ml-1 bg-slate-200 text-slate-600 text-xs rounded-full px-2 py-0.5">{{ count($role['users']) }}</span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="flex flex-wrap gap-2">
                            @foreach($role['users'] as $u)
                                <div class="flex items-center gap-2 bg-slate-100 rounded-lg px-3 py-2">
                                    <div class="w-6 h-6 rounded-md bg-brand flex items-center justify-center text-white font-bold text-[10px]">
                                        {{ strtoupper(substr($u['name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-700 leading-tight">{{ $u['name'] }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $u['username'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>{{-- end space-y-6 --}}

        {{-- ═══ STABILITY FOOTER (GLASSMORPHISM) ═══ --}}
        <div class="mt-12 sticky bottom-6 z-30">
            <div class="bg-white/80 backdrop-blur-md border border-white p-4 rounded-2xl shadow-2xl flex items-center justify-between gap-4 max-w-4xl mx-auto ring-1 ring-slate-900/5">
                <div class="hidden md:flex items-center gap-3 px-2">
                    <div class="w-10 h-10 rounded-xl bg-brand/10 flex items-center justify-center text-brand">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800">Konfigurasi Role</p>
                        <p class="text-[10px] text-slate-500">Pastikan hak akses sudah sesuai kebijakan</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    @if($isDetail)
                        <a href="{{ route('master.role.index') }}" class="btn btn-primary px-10 rounded-xl shadow-lg shadow-brand/25 flex items-center gap-2">
                            Tutup
                        </a>
                    @else
                        <a href="{{ route('master.role.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 px-4 py-2 transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="bg-brand text-white font-bold py-2.5 px-10 rounded-xl shadow-lg shadow-brand/25 hover:bg-brand/90 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Simpan Perubahan
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    /**
     * Peta child permission id → parent permission id (dari API via parent_id).
     * Tidak ada hardcode — semua relasi dinamis dari response API.
     */
    const childToParentIdMap = @json($childToParentIdMap);

    /**
     * Dipanggil setiap kali satu checkbox berubah.
     * Jika sub-menu (parent_id != null) dicentang → auto-centang 'view' pada parent.
     */
    function onPermissionChange(checkbox) {
        if (!checkbox.checked) return;

        const permId   = parseInt(checkbox.dataset.permId);
        const parentId = childToParentIdMap[permId];

        if (parentId) {
            // Auto-centang 'view' pada parent agar akses menu induk tersedia
            const parentView = document.querySelector(
                `[data-perm-id="${parentId}"][data-action="view"]`
            );
            if (parentView && !parentView.checked) {
                parentView.checked = true;
            }
        }
    }

    /**
     * Toggle semua checkbox pada satu kolom aksi (e.g., semua "view")
     */
    function toggleColumn(action) {
        const boxes = [...document.querySelectorAll(`[data-action="${action}"]`)];
        const allChecked = boxes.every(cb => cb.checked);
        boxes.forEach(cb => {
            cb.checked = !allChecked;
            onPermissionChange(cb);
        });
    }

    /**
     * Toggle semua aksi (view/create/edit/delete) pada satu baris modul
     */
    function toggleRow(moduleKey) {
        const boxes = [...document.querySelectorAll(`[data-module="${moduleKey}"]`)];
        const allChecked = boxes.every(cb => cb.checked);
        boxes.forEach(cb => {
            cb.checked = !allChecked;
            onPermissionChange(cb);
        });
    }

    /**
     * Toggle SEMUA checkbox di seluruh matriks
     */
    function toggleAll() {
        const boxes = [...document.querySelectorAll('[data-action]')];
        const allChecked = boxes.every(cb => cb.checked);
        boxes.forEach(cb => {
            cb.checked = !allChecked;
            onPermissionChange(cb);
        });
    }
</script>
@endpush

@endsection
