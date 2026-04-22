@extends('layouts.auth')
@section('title', 'Login')

@section('content')
@php
    $shortLogoAsset = file_exists(public_path('images/logo-short.png'))
        ? asset('images/logo-short.png')
        : (file_exists(public_path('images/logo-min.png')) ? asset('images/logo-min.png') : null);
@endphp
<div class="min-h-screen flex">

    {{-- Left: Branding --}}
    <div class="hidden lg:flex lg:w-1/2 flex-col justify-between bg-gradient-to-br from-dark-900 via-dark-800 to-slate-900 p-12 relative overflow-hidden">
        {{-- Background decoration --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-20 w-64 h-64 bg-brand rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-48 h-48 bg-sky-500 rounded-full blur-3xl"></div>
        </div>

        {{-- Logo --}}
        <div class="relative flex items-center gap-3">
            @if($shortLogoAsset)
                <img src="{{ $shortLogoAsset }}" alt="Logo Pioneer CNC" class="h-12 w-auto shrink-0">
            @else
                <div class="w-11 h-11 rounded-2xl bg-brand flex items-center justify-center shadow-xl shadow-brand/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            @endif
            <div>
                <p class="text-white font-bold text-xl">Pioneer CNC<span class="text-brand">.ID</span></p>
                <p class="text-slate-400 text-xs">Business Management System</p>
            </div>
        </div>

        {{-- Center content --}}
        <div class="relative">
            <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                Kelola Bisnis CNC<br>
                <span class="text-brand">Lebih Mudah & Efisien</span>
            </h1>
            <p class="text-slate-400 text-lg leading-relaxed mb-8">
                Sistem terpadu untuk manajemen penjualan, produksi, dan pelaporan bisnis CNC & Laser Cutting Anda.
            </p>
            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['icon' => '📊', 'label' => 'Dashboard Real-time'],
                    ['icon' => '🏭', 'label' => 'Workflow Produksi'],
                    ['icon' => '💳', 'label' => 'Multi Pembayaran'],
                    ['icon' => '📈', 'label' => 'Laporan Lengkap'],
                ] as $feat)
                <div class="flex items-center gap-2 bg-white/5 rounded-xl px-4 py-3 border border-white/10">
                    <span class="text-xl">{{ $feat['icon'] }}</span>
                    <span class="text-sm text-slate-300">{{ $feat['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Footer --}}
        <div class="relative text-slate-600 text-xs">
            © {{ date('Y') }} Pioneer CNC.ID — All rights reserved
        </div>
    </div>

    {{-- Right: Login Form --}}
    <div class="flex-1 flex items-center justify-center p-8 bg-slate-50">
        <div class="w-full max-w-md">
            {{-- Mobile logo --}}
            <div class="lg:hidden flex items-center gap-3 mb-8">
                @if($shortLogoAsset)
                    <img src="{{ $shortLogoAsset }}" alt="Logo Pioneer CNC" class="h-11 w-auto shrink-0">
                @else
                    <div class="w-10 h-10 rounded-xl bg-brand flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        </svg>
                    </div>
                @endif
                <p class="text-slate-900 font-bold text-xl">Pioneer CNC<span class="text-brand">.ID</span></p>
            </div>

            <h2 class="text-2xl font-bold text-slate-900 mb-1">Selamat Datang 👋</h2>
            <p class="text-slate-500 text-sm mb-8">Masuk ke akun Anda untuk melanjutkan</p>

            {{-- Error --}}
            @if($errors->any())
            <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="space-y-4">
                    {{-- Username --}}
                    <div>
                        <label for="username" class="form-label">Username</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <input id="username" type="text" name="username" value="{{ old('username') }}"
                                class="form-input has-icon"
                                placeholder="Masukkan username"
                                required autofocus>
                        </div>
                    </div>

                    {{-- Password --}}
                    <div x-data="{ show: false }">
                        <label for="password" class="form-label">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input id="password" :type="show ? 'text' : 'password'" name="password"
                                class="form-input has-icon pr-10"
                                placeholder="Masukkan password"
                                required>
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit"
                    :disabled="loading"
                    class="w-full mt-6 btn-primary flex items-center justify-center py-3 text-base"
                    :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                    <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Memproses...' : 'Masuk'"></span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
