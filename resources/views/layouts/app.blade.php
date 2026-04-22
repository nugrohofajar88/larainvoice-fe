<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pioneer CNC.ID - Sistem Manajemen CNC & Laser Cutting">
    <title>@yield('title', 'Dashboard') — Pioneer CNC.ID</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Vite (Tailwind + JS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Extra head --}}
    @stack('head')

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="h-full" x-data="{ 
    sidebarOpen: true, 
    mobileOpen: false,
    confirmModal: {
        show: false,
        title: 'Konfirmasi Hapus',
        message: 'Apakah Anda yakin ingin menghapus data ini?',
        formAction: '',
        confirm(action) {
            this.formAction = action;
            this.show = true;
        }
    },
    // Global Toast Helper for Alpine components
    toast(message, type = 'success') {
        window.toast[type](message);
    }
}">

    @php
        $allMenus = session('menus', []);
        $settingKeys = ['role', 'machine-type', 'plat-size', 'plat-material', 'menu', 'menu-setting'];
        $shortLogoAsset = file_exists(public_path('images/logo-short.png'))
            ? asset('images/logo-short.png')
            : (file_exists(public_path('images/logo-min.png')) ? asset('images/logo-min.png') : null);

        $mainMenus = array_filter($allMenus, fn($m) => !in_array($m['key'], $settingKeys));
        $settingMenus = array_filter($allMenus, fn($m) => in_array($m['key'], $settingKeys));
    @endphp

    <div class="flex h-screen">

        <div
            x-show="mobileOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm md:hidden"
            @click="mobileOpen = false"
            style="display: none;"
        ></div>

        <aside
            x-show="mobileOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-full opacity-0"
            class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col bg-dark-900 text-white shadow-2xl md:hidden"
            style="display: none;"
        >
            <div class="flex items-center justify-between gap-3 px-5 h-16 shrink-0 border-b border-dark-700">
                <div class="flex items-center gap-3">
                    @if($shortLogoAsset)
                        <img src="{{ $shortLogoAsset }}" alt="Logo Pioneer CNC" class="h-10 w-auto shrink-0">
                    @else
                        <div class="w-9 h-9 rounded-xl bg-brand flex items-center justify-center shrink-0 shadow-lg shadow-brand/30">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    @endif
                    <div>
                        <p class="font-bold text-white text-sm leading-tight">Pioneer CNC</p>
                        <p class="text-xs text-slate-400">.ID</p>
                    </div>
                </div>
                <button @click="mobileOpen = false" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-dark-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto sidebar-nav py-4 px-3 space-y-1">
                @foreach($mainMenus as $menu)
                    @php
                        $hasChildren = isset($menu['children']) && count($menu['children']) > 0;
                        $parentHasPermission = \App\Helpers\MenuHelper::hasPermission($menu['key'], 'read');

                        $visibleChildren = [];
                        if ($hasChildren) {
                            foreach ($menu['children'] as $child) {
                                if (\App\Helpers\MenuHelper::hasPermission($child['key'], 'read')) {
                                    $visibleChildren[] = $child;
                                }
                            }
                        }

                        $hasVisibleChildren = count($visibleChildren) > 0;
                        $shouldShowMenu = $hasChildren ? ($hasVisibleChildren || $parentHasPermission) : $parentHasPermission;
                    @endphp

                    @if($shouldShowMenu)
                        @php
                            $isActive = \App\Helpers\MenuHelper::isActive($menu['key']);
                            $isParentActive = $hasVisibleChildren && \App\Helpers\MenuHelper::isParentActive($visibleChildren);
                            $parentUrl = \App\Helpers\MenuHelper::getUrl($menu['key']);
                            $firstChildUrl = $visibleChildren ? \App\Helpers\MenuHelper::getUrl($visibleChildren[0]['key']) : '#';
                            $parentTargetUrl = $parentUrl !== '#' ? $parentUrl : $firstChildUrl;
                        @endphp

                        @if($hasVisibleChildren)
                            <div x-data="{ open: {{ $isParentActive ? 'true' : 'false' }}, targetUrl: '{{ $parentTargetUrl }}' }">
                                <div class="sidebar-parent-row {{ $isParentActive ? 'active' : '' }}">
                                    <a href="{{ $parentTargetUrl }}"
                                        class="sidebar-link sidebar-parent-link {{ $isParentActive ? 'active' : '' }}">
                                        {!! \App\Helpers\MenuHelper::getIcon($menu['key']) !!}
                                        <span>{{ $menu['name'] }}</span>
                                    </a>
                                    <button type="button" @click.stop="open = !open"
                                        class="sidebar-group-toggle {{ $isParentActive ? 'active' : '' }}"
                                        :aria-expanded="open.toString()"
                                        aria-label="Toggle submenu {{ $menu['name'] }}">
                                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                                <div x-show="open" x-transition class="pl-8 mt-1 space-y-1">
                                    @foreach($visibleChildren as $child)
                                        <a href="{{ \App\Helpers\MenuHelper::getUrl($child['key']) }}"
                                            class="sidebar-link text-xs {{ \App\Helpers\MenuHelper::isActive($child['key']) ? 'active' : '' }}">
                                            {{ $child['name'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ \App\Helpers\MenuHelper::getUrl($menu['key']) }}"
                                class="sidebar-link {{ $isActive ? 'active' : '' }}">
                                {!! \App\Helpers\MenuHelper::getIcon($menu['key']) !!}
                                <span class="font-medium">{{ $menu['name'] }}</span>
                            </a>
                        @endif
                    @endif
                @endforeach

                @if(count($settingMenus) > 0)
                    <div class="mt-8 mb-2 px-3">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">Pengaturan</p>
                    </div>

                    @foreach($settingMenus as $menu)
                        @php
                            $isActive = \App\Helpers\MenuHelper::isActive($menu['key']);
                            $hasPermission = \App\Helpers\MenuHelper::hasPermission($menu['key'], 'read');
                        @endphp

                        @if($hasPermission)
                            <a href="{{ \App\Helpers\MenuHelper::getUrl($menu['key']) }}"
                                class="sidebar-link {{ $isActive ? 'active' : '' }}">
                                {!! \App\Helpers\MenuHelper::getIcon($menu['key']) !!}
                                <span class="font-medium">{{ $menu['name'] }}</span>
                            </a>
                        @endif
                    @endforeach
                @endif
            </nav>
        </aside>

        {{-- ================================
        SIDEBAR
        ================================ --}}
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="hidden md:flex flex-col bg-dark-900 text-white transition-all duration-300 shrink-0 h-screen overflow-hidden">
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-5 h-16 shrink-0 border-b border-dark-700">
                @if($shortLogoAsset)
                    <img src="{{ $shortLogoAsset }}" alt="Logo Pioneer CNC" class="h-10 w-auto shrink-0">
                @else
                    <div
                        class="w-9 h-9 rounded-xl bg-brand flex items-center justify-center shrink-0 shadow-lg shadow-brand/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                @endif
                <div x-show="sidebarOpen" x-transition.opacity>
                    <p class="font-bold text-white text-sm leading-tight">Pioneer CNC</p>
                    <p class="text-xs text-slate-400">.ID</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto sidebar-nav py-4 px-3 space-y-1">
                {{-- Main Menus --}}
                @foreach($mainMenus as $menu)
                    @php
                        $hasChildren = isset($menu['children']) && count($menu['children']) > 0;
                        $parentHasPermission = \App\Helpers\MenuHelper::hasPermission($menu['key'], 'read');

                        $visibleChildren = [];
                        if ($hasChildren) {
                            foreach ($menu['children'] as $child) {
                                if (\App\Helpers\MenuHelper::hasPermission($child['key'], 'read')) {
                                    $visibleChildren[] = $child;
                                }
                            }
                        }

                        $hasVisibleChildren = count($visibleChildren) > 0;
                        $shouldShowMenu = $hasChildren ? ($hasVisibleChildren || $parentHasPermission) : $parentHasPermission;
                    @endphp

                    @if($shouldShowMenu)
                        @php
                            $isActive = \App\Helpers\MenuHelper::isActive($menu['key']);
                            $isParentActive = $hasVisibleChildren && \App\Helpers\MenuHelper::isParentActive($visibleChildren);
                            $parentUrl = \App\Helpers\MenuHelper::getUrl($menu['key']);
                            $firstChildUrl = $visibleChildren ? \App\Helpers\MenuHelper::getUrl($visibleChildren[0]['key']) : '#';
                            $parentTargetUrl = $parentUrl !== '#' ? $parentUrl : $firstChildUrl;
                        @endphp

                        @if($hasVisibleChildren)
                            {{-- Parent with Children --}}
                            <div x-data="{ open: {{ $isParentActive ? 'true' : 'false' }}, targetUrl: '{{ $parentTargetUrl }}' }">
                                <div class="sidebar-parent-row {{ $isParentActive ? 'active' : '' }}">
                                    <a href="{{ $parentTargetUrl }}"
                                        class="sidebar-link sidebar-parent-link {{ $isParentActive ? 'active' : '' }}">
                                        {!! \App\Helpers\MenuHelper::getIcon($menu['key']) !!}
                                        <span x-show="sidebarOpen">{{ $menu['name'] }}</span>
                                    </a>
                                    <button type="button" @click.stop="open = !open"
                                        class="sidebar-group-toggle {{ $isParentActive ? 'active' : '' }}"
                                        :aria-expanded="open.toString()"
                                        aria-label="Toggle submenu {{ $menu['name'] }}">
                                        <svg x-show="sidebarOpen" :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                                <div x-show="open && sidebarOpen" x-transition class="pl-8 mt-1 space-y-1">
                                    @foreach($visibleChildren as $child)
                                        <a href="{{ \App\Helpers\MenuHelper::getUrl($child['key']) }}"
                                            class="sidebar-link text-xs {{ \App\Helpers\MenuHelper::isActive($child['key']) ? 'active' : '' }}">
                                            {{ $child['name'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Single Menu Item --}}
                            <a href="{{ \App\Helpers\MenuHelper::getUrl($menu['key']) }}"
                                class="sidebar-link {{ $isActive ? 'active' : '' }}">
                                {!! \App\Helpers\MenuHelper::getIcon($menu['key']) !!}
                                <span x-show="sidebarOpen" class="font-medium">{{ $menu['name'] }}</span>
                            </a>
                        @endif
                    @endif
                @endforeach

                {{-- Settings Section --}}
                @if(count($settingMenus) > 0)
                    <div class="mt-8 mb-2 px-3 transition-all duration-300" x-show="sidebarOpen">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">Pengaturan</p>
                    </div>
                    <div class="mt-2 h-px bg-dark-700/50 mx-3" x-show="!sidebarOpen"></div>

                    @foreach($settingMenus as $menu)
                        @php
                            $isActive = \App\Helpers\MenuHelper::isActive($menu['key']);
                            $hasPermission = \App\Helpers\MenuHelper::hasPermission($menu['key'], 'read');
                        @endphp

                        @if($hasPermission)
                            <a href="{{ \App\Helpers\MenuHelper::getUrl($menu['key']) }}"
                                class="sidebar-link {{ $isActive ? 'active' : '' }}">
                                {!! \App\Helpers\MenuHelper::getIcon($menu['key']) !!}
                                <span x-show="sidebarOpen" class="font-medium">{{ $menu['name'] }}</span>
                            </a>
                        @endif
                    @endforeach
                @endif
            </nav>

            {{-- Sidebar Toggle --}}
            <div class="p-3 border-t border-dark-700">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="w-full flex items-center justify-center gap-2 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-dark-700 transition-all text-sm">
                    <svg :class="sidebarOpen ? '' : 'rotate-180'" class="w-5 h-5 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                    <span x-show="sidebarOpen" class="text-xs">Tutup Sidebar</span>
                </button>
            </div>
        </aside>

        {{-- ================================
        MAIN CONTENT
        ================================ --}}
        <div class="flex-1 flex flex-col min-w-0 h-screen">

            {{-- NAVBAR --}}
            <header
                class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 sticky top-0 z-30">
                <div class="flex items-center gap-4">
                    {{-- Mobile menu button --}}
                    <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{-- Breadcrumb --}}
                    <nav class="hidden md:flex items-center text-sm text-slate-500">
                        <span>Pioneer CNC</span>
                        <svg class="w-4 h-4 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-slate-800 font-medium">@yield('page-title', 'Dashboard')</span>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Branch selector (Super Admin only) --}}
                    @if(session('role') === 'super-admin')
                        <div class="hidden md:flex items-center gap-2 bg-slate-100 rounded-lg px-3 py-1.5">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <select class="bg-transparent text-sm text-slate-700 focus:outline-none cursor-pointer">
                                <option value="">Semua Cabang</option>
                                <option value="jkt">Jakarta</option>
                                <option value="sby">Surabaya</option>
                                <option value="bdg">Bandung</option>
                            </select>
                        </div>
                    @endif

                    {{-- Notification --}}
                    <button class="relative p-2 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-brand rounded-full"></span>
                    </button>

                    {{-- User dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition-colors">
                            <div
                                class="w-8 h-8 rounded-lg bg-brand flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr(session('user_name', 'A'), 0, 1)) }}
                            </div>
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-semibold text-slate-800 leading-tight">
                                    {{ session('user_name', 'Admin') }}</p>
                                <p class="text-xs text-slate-500">{{ session('role_label', 'Super Admin') }}</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition
                            class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs text-slate-500">Login sebagai</p>
                                <p class="text-sm font-semibold text-slate-800">{{ session('user_name', 'Admin') }}</p>
                            </div>
                            <a href="#"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profil Saya
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- PAGE CONTENT --}}
            <main class="flex-1 overflow-y-auto bg-slate-100">
                <div class="p-6">
                    @if(session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition
                            class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl">
                            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm">{{ session('success') }}</span>
                            <button @click="show = false" class="ml-auto text-green-500 hover:text-green-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div x-data="{ show: true }" x-show="show" x-transition
                            class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">
                            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm">{{ session('error') }}</span>
                            <button @click="show = false" class="ml-auto text-red-500 hover:text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    {{-- Global Confirmation Modal --}}
    <div x-show="confirmModal.show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4 px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/50 backdrop-blur-sm"
                @click="confirmModal.show = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
            <div
                class="relative z-10 inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-6 pt-6 pb-4 bg-white">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg font-bold leading-6 text-slate-900" x-text="confirmModal.title"></h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500" x-text="confirmModal.message"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 flex flex-row-reverse gap-3">
                    <form :action="confirmModal.formAction" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger px-6 py-2.5">Hapus Sekarang</button>
                    </form>
                    <button type="button" class="btn btn-outline px-6 py-2.5"
                        @click="confirmModal.show = false">Batal</button>
                </div>
            </div>
        </div>
    </div>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global Toast Configuration
        window.toast = {
            success: function (message) {
                Swal.fire({
                    icon: 'success',
                    title: message || 'Berhasil!',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: '#ffffff',
                    color: '#0f172a',
                    iconColor: '#3b82f6',
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
            },
            error: function (message) {
                Swal.fire({
                    icon: 'error',
                    title: message || 'Terjadi kesalahan!',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: '#ffffff',
                    color: '#0f172a',
                    iconColor: '#ef4444',
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
            }
        };

        // Global SwalConfirm helper to replace old confirmModal in new code
        window.SwalConfirm = async function (title = 'Konfirmasi', message = 'Apakah Anda yakin ingin melanjutkan?') {
            const result = await Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    container: 'z-[1000]',
                    popup: 'rounded-2xl shadow-2xl',
                    confirmButton: 'btn btn-primary px-6 py-2.5',
                    cancelButton: 'btn btn-outline px-6 py-2.5'
                }
            });
            return result.isConfirmed;
        };
    </script>
    @stack('scripts')
    @stack('modals')

</body>

</html>
