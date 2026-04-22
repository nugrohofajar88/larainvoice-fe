@extends('layouts.app')
@section('title', 'Manajemen Role')
@section('page-title', 'Master Role')

@section('content')
@php
    $sortBy  = request('sort_by', 'name');
    $sortDir = request('sort_dir', 'asc');

    $sortUrl = function($column) use ($sortBy, $sortDir) {
        $dir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort_by' => $column, 'sort_dir' => $dir, 'page' => 1]);
    };

    $sortIcon = function($column) use ($sortBy, $sortDir) {
        $isAsc  = $sortBy === $column && $sortDir === 'asc';
        $isDesc = $sortBy === $column && $sortDir === 'desc';
        return '
            <div class="flex flex-col ml-1.5 opacity-40 group-hover:opacity-100 transition-opacity">
                <svg class="w-2 h-2 ' . ($isAsc ? 'text-brand opacity-100' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h16l-8-8z"/></svg>
                <svg class="w-2 h-2 ' . ($isDesc ? 'text-brand opacity-100' : '') . '" fill="currentColor" viewBox="0 0 24 24"><path d="M12 20l8-8H4l8 8z"/></svg>
            </div>
        ';
    };
@endphp

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Master Role</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola role & hak akses pengguna sistem</p>
    </div>
    @if(\App\Helpers\MenuHelper::hasPermission('role', 'create'))
    <a href="{{ route('master.role.create') }}" class="btn btn-primary whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Tambah Role</span>
    </a>
    @endif
</div>

<div class="card overflow-hidden">
    <form action="{{ route('master.role.index') }}" method="GET">
        <input type="hidden" name="sort_by"  value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th class="w-16">NO</th>
                        <th>
                            <a href="{{ $sortUrl('name') }}" class="flex items-center group">
                                <span>Nama Role</span>
                                {!! $sortIcon('name') !!}
                            </a>
                        </th>
                        <th class="w-36 text-center">Pengguna</th>
                        <th class="text-center">Hak Akses</th>
                        <th class="w-24 text-center">Aksi</th>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner">
                            <input type="text" name="name" value="{{ request('name') }}"
                                   class="w-full text-xs font-normal bg-white border border-slate-200 rounded px-2 py-1 focus:ring-1 focus:ring-brand focus:border-brand outline-none transition-all"
                                   placeholder="Cari nama role...">
                        </th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner"></th>
                        <th class="py-2 px-4 shadow-inner text-center">
                            <button type="submit" class="hidden"></button>
                            @if(request()->anyFilled(['name']))
                                <a href="{{ route('master.role.index') }}" class="text-[10px] text-red-500 hover:text-red-700 underline font-semibold">Reset</a>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $item)
                        @php
                            $permissions = collect($item['permissions'] ?? []);
                            $total       = $permissions->count();
                            $fullAccess  = $permissions->filter(fn($p) => $p['view'] && $p['create'] && $p['edit'] && $p['delete'] && $p['detail'])->count();
                            $anyAccess   = $permissions->filter(fn($p) => $p['view'] || $p['create'] || $p['edit'] || $p['delete'] || $p['detail'])->count();
                        @endphp
                        <tr>
                            <td><span class="text-slate-400 font-mono text-xs">{{ ($roles->currentPage() - 1) * $roles->perPage() + $loop->iteration }}</span></td>

                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-brand/10 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-slate-900 uppercase">{{ $item['name'] }}</span>
                                </div>
                            </td>

                            <td class="text-center">
                                <span class="inline-flex items-center gap-1.5 text-sm text-slate-600">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    {{ $item['users_count'] }} pengguna
                                </span>
                            </td>

                            <td class="text-center">
                                @if($total > 0)
                                    <div class="flex items-center justify-center gap-2">
                                        @if($fullAccess === $total)
                                            <span class="badge badge-brand">Full Access</span>
                                        @else
                                            <span class="text-xs text-slate-600">
                                                <span class="font-semibold text-slate-800">{{ $anyAccess }}</span>/{{ $total }} modul aktif
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Tidak ada</span>
                                @endif
                            </td>

                            <td>
                                <div class="flex items-center justify-center gap-2">
                                    @if(\App\Helpers\MenuHelper::hasPermission('role', 'detail'))
                                    <a href="{{ route('master.role.edit', $item['id']) }}?detail=1"
                                       class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all"
                                       title="Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @endif

                                    @if(\App\Helpers\MenuHelper::hasPermission('role', 'edit'))
                                    <a href="{{ route('master.role.edit', $item['id']) }}"
                                       class="p-2 text-slate-400 hover:text-brand hover:bg-brand/5 rounded-lg transition-all"
                                       title="Edit Hak Akses">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @endif

                                    @if(\App\Helpers\MenuHelper::hasPermission('role', 'delete'))
                                    <button type="button"
                                            @click="confirmModal.confirm('{{ route('master.role.destroy', $item['id']) }}')"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                            title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                Tidak ada data role ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    @if($roles->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $roles->links() }}
        </div>
    @endif
</div>
@endsection

