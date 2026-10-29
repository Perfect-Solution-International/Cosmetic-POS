<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Psi POS">
    <meta name="theme-color" content="#07090E">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') | Psi One Global POS</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    <!-- Google Fonts (Non-blocking Asynchronous Load with System UI Fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap">
    </noscript>

    <!-- Local Precompiled High-Speed Tailwind CSS, FontAwesome 6 & Utilities -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .custom-sidebar-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-sidebar-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-sidebar-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 9999px;
        }
        .custom-sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="h-full bg-carbon-950 text-slate-100 antialiased selection:bg-brand-600 selection:text-white flex overflow-hidden">

    <!-- Backdrop for Mobile Slide-Out Drawer -->
    <div
        id="drawerBackdrop"
        onclick="togglePosDrawer()"
        class="fixed inset-0 bg-black/75 backdrop-blur-sm z-40 transition-opacity opacity-0 pointer-events-none duration-300 lg:hidden"
    ></div>

    <!-- LEFT SIDEBAR: Persistent Docked on Desktop/Tablet, Off-Canvas on Mobile -->
    <aside
        id="posSidebar"
        class="fixed lg:static top-0 bottom-0 left-0 w-64 xl:w-72 bg-carbon-900 border-r border-carbon-800/90 z-50 flex flex-col shrink-0 shadow-2xl lg:shadow-none -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-out safe-top safe-bottom h-screen max-h-screen overflow-hidden select-none"
    >
        <!-- 1. Modern SaaS Header (Brand & Terminal Switcher) -->
        <div class="p-3.5 sm:p-4 border-b border-carbon-800/80 flex items-center justify-between shrink-0 bg-carbon-900/60 backdrop-blur-md">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-carbon-800 p-1 psi-silver-border flex items-center justify-center shadow-lg shadow-black/60 shrink-0 group-hover:border-brand-500/40 transition">
                    <img src="{{ asset('images/psi-logo.png') }}" alt="PSI" class="w-full h-full object-contain filter drop-shadow(0 2px 4px rgba(229,26,46,0.35))">
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 leading-none">
                        <h1 class="font-black text-xs tracking-tight text-white">
                            <span class="tracking-wider">PSI ONE</span>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-500 via-rose-400 to-silver-200">GLOBAL POS</span>
                        </h1>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1">
                        <p class="text-[8.5px] text-silver-400 font-bold tracking-widest uppercase">RETAIL TERMINAL</p>
                        <span class="px-1 py-0.2 rounded text-[7.5px] font-mono font-bold bg-brand-500/15 border border-brand-500/30 text-brand-400 uppercase tracking-tighter">SaaS Cloud</span>
                    </div>
                </div>
            </a>

            <!-- Mobile Drawer Close Button -->
            <button
                type="button"
                onclick="togglePosDrawer()"
                class="lg:hidden w-7 h-7 rounded-lg bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 flex items-center justify-center text-silver-400 hover:text-white transition-all touch-action-btn"
                aria-label="Close Sidebar"
            >
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <!-- 2. SaaS Quick Jump & Collapse/Expand All -->
        <div class="px-3 pt-2.5 pb-2 shrink-0 border-b border-carbon-800/40 bg-carbon-900/40">
            <div class="flex items-center gap-1.5">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass text-[11px] text-silver-500 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        type="text"
                        id="sidebarQuickSearch"
                        onkeyup="filterSidebarMenu(this.value)"
                        placeholder="Quick search... (Ctrl+K)"
                        class="w-full bg-carbon-850/90 border border-carbon-800 rounded-xl pl-7 pr-6 py-1.5 text-[11px] text-silver-200 placeholder-silver-500/80 focus:outline-none focus:border-brand-500/70 focus:bg-carbon-800 transition shadow-inner"
                    >
                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[8px] font-mono font-bold text-silver-500 border border-carbon-700/60 rounded px-1 hidden sm:inline bg-carbon-900/60">/</span>
                </div>
                <button
                    type="button"
                    id="toggleAllSectionsBtn"
                    onclick="toggleAllNavSections()"
                    title="Expand / Collapse all categories"
                    class="w-7 h-7 rounded-xl bg-carbon-850/90 border border-carbon-800 hover:border-carbon-700 hover:bg-carbon-800 flex items-center justify-center text-silver-500 hover:text-silver-200 transition touch-action-btn shrink-0"
                >
                    <i class="fa-solid fa-arrows-up-down text-[10px]"></i>
                </button>
            </div>
        </div>

        @php
            $userRole = strtolower(auth()->user()?->role ?? '');
            $isAdmin = in_array($userRole, ['admin', 'super_admin']);
            $isManager = in_array($userRole, ['manager', 'branch_manager']);
            $isHeadOffice = $userRole === 'head_office_manager';
            $isCashier = $userRole === 'cashier';
            $isInventory = in_array($userRole, ['inventory_staff', 'inventory']);
            $isWarehouse = in_array($userRole, ['warehouse_manager', 'warehouse']);
            $isAccountant = $userRole === 'accountant';

            // Feature permissions:
            $canAccessPos = $isAdmin || $isManager || $isHeadOffice || $isCashier;
            $canManageAttributes = $isAdmin || $isManager || $isHeadOffice;
            $canAccessInventory = $isAdmin || $isManager || $isHeadOffice || $isInventory || $isWarehouse || $isAccountant;
            $canAccessPurchasing = $isAdmin || $isManager || $isHeadOffice || $isWarehouse || $isInventory || $isAccountant;
            $canAccessSalesRecords = $isAdmin || $isManager || $isHeadOffice || $isCashier || $isAccountant;
            $canAccessFinance = $isAdmin || $isManager || $isHeadOffice || $isAccountant;

            // Category active state detections:
            $isProductsActive = request()->routeIs('products.*') || request()->routeIs('inventory.barcodes');
            $isInventoryActive = request()->routeIs('inventory.index') || request()->routeIs('inventory.transfers') || request()->routeIs('inventory.reorder_expiry*') || request()->routeIs('inventory.batch_expiry') || request()->routeIs('inventory.stock_takes.*') || request()->routeIs('inventory.serials') || request()->routeIs('warranty.*');
            $isPurchasingActive = request()->routeIs('procurement.*');
            $isSalesActive = request()->routeIs('sales.*') || request()->routeIs('quotations.*') || request()->routeIs('customers.*') || request()->routeIs('subscriptions.*');
            $isFinanceActive = request()->routeIs('reports.*') || request()->routeIs('expenses.*');
            $isAdminActive = request()->routeIs('hq.*') || request()->routeIs('branches.*') || request()->routeIs('users.*');

            $hasAnyActiveCategory = $isProductsActive || $isInventoryActive || $isPurchasingActive || $isSalesActive || $isFinanceActive || $isAdminActive;
        @endphp

        <!-- 3. Scrollable SaaS Navigation Menu -->
        <nav class="flex-1 min-h-0 overflow-y-auto px-2.5 py-2.5 space-y-2 pb-6 custom-sidebar-scrollbar" id="sidebarNavContainer">

            @if($canAccessPos)
            <!-- Primary Action: POS Terminal Card -->
            <div class="px-0.5 pb-1">
                <a
                    href="{{ route('pos.terminal') }}"
                    class="sidebar-nav-item group relative overflow-hidden flex items-center justify-between px-3 py-2.5 rounded-xl transition-all touch-action-btn {{ request()->routeIs('pos.terminal') ? 'bg-gradient-to-r from-brand-600 via-rose-600 to-brand-700 text-white font-bold shadow-lg shadow-brand-900/50 ring-1 ring-white/20' : 'bg-gradient-to-r from-brand-600/15 via-rose-600/10 to-transparent border border-brand-500/30 text-white hover:border-brand-500/60 hover:bg-brand-600/25 shadow-sm' }}"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ request()->routeIs('pos.terminal') ? 'bg-black/25 text-white' : 'bg-brand-500/20 text-brand-400 group-hover:bg-brand-500 group-hover:text-white transition-all' }}">
                            <i class="fa-solid fa-cash-register text-xs"></i>
                        </div>
                        <div>
                            <span class="block text-xs font-bold leading-tight text-white">Cashier Register</span>
                            <span class="block text-[9px] text-silver-400 group-hover:text-silver-300 font-mono">Terminal Billing</span>
                        </div>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-[8.5px] font-mono font-bold tracking-tight {{ request()->routeIs('pos.terminal') ? 'bg-white/20 text-white' : 'bg-brand-500/20 border border-brand-500/40 text-brand-300' }}">
                        POS
                    </span>
                </a>
            </div>
            @endif

            <!-- Primary Action 2: Dashboard -->
            <a
                href="{{ route('dashboard') }}"
                class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs transition-all touch-action-btn {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
            >
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-gauge-high text-xs w-4 text-center {{ request()->routeIs('dashboard') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                    <span>Dashboard</span>
                </div>
                @if(request()->routeIs('dashboard'))
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                @endif
            </a>

            <!-- Category 1: Products & Catalog -->
            <div class="sidebar-nav-section space-y-1" data-section-id="products" data-has-active="{{ $isProductsActive ? '1' : '0' }}">
                <button
                    type="button"
                    onclick="toggleNavSection('products')"
                    class="nav-section-header w-full px-2.5 py-1.5 flex items-center justify-between text-[10.5px] font-bold uppercase tracking-wider text-silver-400 hover:text-white rounded-lg hover:bg-carbon-800/40 transition group"
                >
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-tags text-[10px] {{ $isProductsActive ? 'text-brand-400' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                        <span>Products & Catalog</span>
                        @if($isProductsActive)
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-chevron-down text-[9px] text-silver-500 transition-transform duration-200 nav-chevron {{ $isProductsActive ? 'rotate-180' : '' }}"></i>
                    </div>
                </button>
                
                <div class="nav-section-body space-y-0.5 pl-2 border-l border-carbon-800/70 ml-3 {{ $isProductsActive ? '' : 'hidden' }}">
                    <a
                        href="{{ route('products.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('products.index') || request()->routeIs('products.create') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-box-open text-xs w-4 text-center {{ request()->routeIs('products.index') || request()->routeIs('products.create') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Products List</span>
                        </div>
                        @if(request()->routeIs('products.index') || request()->routeIs('products.create'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    @if($canManageAttributes)
                    <a
                        href="{{ route('products.attributes') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('products.attributes') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-swatchbook text-xs w-4 text-center {{ request()->routeIs('products.attributes') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Shades & Sizes</span>
                        </div>
                        @if(request()->routeIs('products.attributes'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                    @endif

                    @if($canAccessInventory)
                    <a
                        href="{{ route('inventory.barcodes') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('inventory.barcodes') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-barcode text-xs w-4 text-center {{ request()->routeIs('inventory.barcodes') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Barcode Generator</span>
                        </div>
                        @if(request()->routeIs('inventory.barcodes'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                    @endif
                </div>
            </div>

            <!-- Category 2: Inventory & Stocks -->
            @if($canAccessInventory)
            <div class="sidebar-nav-section space-y-1" data-section-id="inventory" data-has-active="{{ $isInventoryActive ? '1' : '0' }}">
                <button
                    type="button"
                    onclick="toggleNavSection('inventory')"
                    class="nav-section-header w-full px-2.5 py-1.5 flex items-center justify-between text-[10.5px] font-bold uppercase tracking-wider text-silver-400 hover:text-white rounded-lg hover:bg-carbon-800/40 transition group"
                >
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-warehouse text-[10px] {{ $isInventoryActive ? 'text-brand-400' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                        <span>Inventory & Stocks</span>
                        @if($isInventoryActive)
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-chevron-down text-[9px] text-silver-500 transition-transform duration-200 nav-chevron {{ $isInventoryActive ? 'rotate-180' : '' }}"></i>
                    </div>
                </button>
                
                <div class="nav-section-body space-y-0.5 pl-2 border-l border-carbon-800/70 ml-3 {{ $isInventoryActive ? '' : 'hidden' }}">
                    <a
                        href="{{ route('inventory.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('inventory.index') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-boxes-stacked text-xs w-4 text-center {{ request()->routeIs('inventory.index') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Stock & Inventory</span>
                        </div>
                        @if(request()->routeIs('inventory.index'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    <a
                        href="{{ route('inventory.transfers') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('inventory.transfers') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-dolly text-xs w-4 text-center {{ request()->routeIs('inventory.transfers') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Stock Transfers</span>
                        </div>
                        @if(request()->routeIs('inventory.transfers'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    <a
                        href="{{ route('inventory.reorder_expiry') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('inventory.reorder_expiry*') ? 'bg-gradient-to-r from-amber-600/25 via-amber-600/10 to-transparent text-white font-bold border-l-2 border-amber-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                        title="Weekly Stock Reorder & Batch Expiry Audit"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-boxes-packing text-xs w-4 text-center {{ request()->routeIs('inventory.reorder_expiry*') ? 'text-amber-400 filter drop-shadow-[0_0_6px_rgba(245,158,11,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Reorder & Expiry Audit</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[8.5px] font-mono font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                            Weekly
                        </span>
                    </a>

                    @if(!$isAccountant)
                    <a
                        href="{{ route('inventory.batch_expiry') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('inventory.batch_expiry') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-calendar-days text-xs w-4 text-center {{ request()->routeIs('inventory.batch_expiry') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Expiry Date Alerts</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[8.5px] font-mono font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                            Batch
                        </span>
                    </a>

                    <a
                        href="{{ route('inventory.stock_takes.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('inventory.stock_takes.*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-clipboard-check text-xs w-4 text-center {{ request()->routeIs('inventory.stock_takes.*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Physical Stock Audit</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[8.5px] font-mono font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                            Audit
                        </span>
                    </a>

                    <a
                        href="{{ route('inventory.serials') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('inventory.serials') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-microchip text-xs w-4 text-center {{ request()->routeIs('inventory.serials') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Serial & IMEI Tracking</span>
                        </div>
                        @if(request()->routeIs('inventory.serials'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    <a
                        href="{{ route('warranty.lookup') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('warranty.lookup') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-shield-halved text-xs w-4 text-center {{ request()->routeIs('warranty.lookup') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Warranty Lookup</span>
                        </div>
                        @if(request()->routeIs('warranty.lookup'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Category 3: Purchasing & Procurement -->
            @if($canAccessPurchasing)
            <div class="sidebar-nav-section space-y-1" data-section-id="purchasing" data-has-active="{{ $isPurchasingActive ? '1' : '0' }}">
                <button
                    type="button"
                    onclick="toggleNavSection('purchasing')"
                    class="nav-section-header w-full px-2.5 py-1.5 flex items-center justify-between text-[10.5px] font-bold uppercase tracking-wider text-silver-400 hover:text-white rounded-lg hover:bg-carbon-800/40 transition group"
                >
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-cart-shopping text-[10px] {{ $isPurchasingActive ? 'text-brand-400' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                        <span>Purchasing & GRN</span>
                        @if($isPurchasingActive)
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-chevron-down text-[9px] text-silver-500 transition-transform duration-200 nav-chevron {{ $isPurchasingActive ? 'rotate-180' : '' }}"></i>
                    </div>
                </button>
                
                <div class="nav-section-body space-y-0.5 pl-2 border-l border-carbon-800/70 ml-3 {{ $isPurchasingActive ? '' : 'hidden' }}">
                    @if(!$isInventory)
                    <a
                        href="{{ route('procurement.po') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('procurement.po*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-clipboard-list text-xs w-4 text-center {{ request()->routeIs('procurement.po*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Purchase Orders</span>
                        </div>
                        @if(request()->routeIs('procurement.po*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                    @endif

                    @if(!$isAccountant)
                    <a
                        href="{{ route('procurement.grn') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('procurement.grn') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-dolly-flatbed text-xs w-4 text-center {{ request()->routeIs('procurement.grn') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Receive Stock (GRN)</span>
                        </div>
                        @if(request()->routeIs('procurement.grn'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                    @endif

                    @if(!$isInventory)
                    <a
                        href="{{ route('procurement.suppliers') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('procurement.suppliers') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-truck text-xs w-4 text-center {{ request()->routeIs('procurement.suppliers') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Suppliers</span>
                        </div>
                        @if(request()->routeIs('procurement.suppliers'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                    @endif

                    @if(!$isAccountant)
                    <a
                        href="{{ route('procurement.returns') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('procurement.returns*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-arrow-rotate-left text-xs w-4 text-center {{ request()->routeIs('procurement.returns*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Supplier Returns</span>
                        </div>
                        @if(request()->routeIs('procurement.returns*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Category 4: Sales & Customers -->
            @if($canAccessSalesRecords)
            <div class="sidebar-nav-section space-y-1" data-section-id="sales" data-has-active="{{ $isSalesActive ? '1' : '0' }}">
                <button
                    type="button"
                    onclick="toggleNavSection('sales')"
                    class="nav-section-header w-full px-2.5 py-1.5 flex items-center justify-between text-[10.5px] font-bold uppercase tracking-wider text-silver-400 hover:text-white rounded-lg hover:bg-carbon-800/40 transition group"
                >
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-[10px] {{ $isSalesActive ? 'text-brand-400' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                        <span>Sales & Customers</span>
                        @if($isSalesActive)
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-chevron-down text-[9px] text-silver-500 transition-transform duration-200 nav-chevron {{ $isSalesActive ? 'rotate-180' : '' }}"></i>
                    </div>
                </button>
                
                <div class="nav-section-body space-y-0.5 pl-2 border-l border-carbon-800/70 ml-3 {{ $isSalesActive ? '' : 'hidden' }}">
                    <a
                        href="{{ route('sales.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('sales.*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-receipt text-xs w-4 text-center {{ request()->routeIs('sales.*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Sales & Returns</span>
                        </div>
                        @if(request()->routeIs('sales.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    <a
                        href="{{ route('quotations.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('quotations.*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-file-invoice-dollar text-xs w-4 text-center {{ request()->routeIs('quotations.*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Quotations & Estimates</span>
                        </div>
                        @if(request()->routeIs('quotations.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    <a
                        href="{{ route('customers.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('customers.*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-user-group text-xs w-4 text-center {{ request()->routeIs('customers.*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Customers</span>
                        </div>
                        @if(request()->routeIs('customers.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    @if($isAdmin || $isHeadOffice)
                    <a
                        href="{{ route('subscriptions.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('subscriptions.*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-clock-rotate-left text-xs w-4 text-center {{ request()->routeIs('subscriptions.*') ? 'text-purple-400 filter drop-shadow-[0_0_6px_rgba(168,85,247,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Subscriptions & Trials</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[8.5px] font-mono font-bold bg-purple-500/15 text-purple-400 border border-purple-500/30">
                            SaaS
                        </span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Category 5: Finance & Reports -->
            @if($canAccessFinance)
            <div class="sidebar-nav-section space-y-1" data-section-id="finance" data-has-active="{{ $isFinanceActive ? '1' : '0' }}">
                <button
                    type="button"
                    onclick="toggleNavSection('finance')"
                    class="nav-section-header w-full px-2.5 py-1.5 flex items-center justify-between text-[10.5px] font-bold uppercase tracking-wider text-silver-400 hover:text-white rounded-lg hover:bg-carbon-800/40 transition group"
                >
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-[10px] {{ $isFinanceActive ? 'text-brand-400' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                        <span>Finance & Reports</span>
                        @if($isFinanceActive)
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-chevron-down text-[9px] text-silver-500 transition-transform duration-200 nav-chevron {{ $isFinanceActive ? 'rotate-180' : '' }}"></i>
                    </div>
                </button>
                
                <div class="nav-section-body space-y-0.5 pl-2 border-l border-carbon-800/70 ml-3 {{ $isFinanceActive ? '' : 'hidden' }}">
                    <a
                        href="{{ route('reports.pnl') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('reports.pnl*') ? 'bg-gradient-to-r from-emerald-600/25 via-emerald-600/10 to-transparent text-white font-bold border-l-2 border-emerald-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                        title="Profit & Loss Income Statement Analytics"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-file-invoice-dollar text-xs w-4 text-center {{ request()->routeIs('reports.pnl*') ? 'text-emerald-400 filter drop-shadow-[0_0_6px_rgba(16_185_129,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Profit & Loss (P&L)</span>
                        </div>
                        <span class="text-[8.5px] font-mono font-bold px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                            P&L
                        </span>
                    </a>

                    <a
                        href="{{ route('reports.shifts') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('reports.shifts') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-calculator text-xs w-4 text-center {{ request()->routeIs('reports.shifts') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Shift Z-Reports</span>
                        </div>
                        @if(request()->routeIs('reports.shifts'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    @if(!$isCashier)
                    <a
                        href="{{ route('expenses.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('expenses.*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-money-bill-wave text-xs w-4 text-center {{ request()->routeIs('expenses.*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Daily Expenses</span>
                        </div>
                        @if(request()->routeIs('expenses.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                    @endif

                    <a
                        href="{{ route('reports.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('reports.index') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-chart-column text-xs w-4 text-center {{ request()->routeIs('reports.index') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Reports & Analytics</span>
                        </div>
                        @if(request()->routeIs('reports.index'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>
                </div>
            </div>
            @endif

            <!-- Category 6: System Administration & HQ -->
            @if($isAdmin || $isHeadOffice)
            <div class="sidebar-nav-section space-y-1" data-section-id="admin" data-has-active="{{ $isAdminActive ? '1' : '0' }}">
                <button
                    type="button"
                    onclick="toggleNavSection('admin')"
                    class="nav-section-header w-full px-2.5 py-1.5 flex items-center justify-between text-[10.5px] font-bold uppercase tracking-wider text-silver-400 hover:text-white rounded-lg hover:bg-carbon-800/40 transition group"
                >
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-gears text-[10px] {{ $isAdminActive ? 'text-brand-400' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                        <span>System & Settings</span>
                        @if($isAdminActive)
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-chevron-down text-[9px] text-silver-500 transition-transform duration-200 nav-chevron {{ $isAdminActive ? 'rotate-180' : '' }}"></i>
                    </div>
                </button>
                
                <div class="nav-section-body space-y-0.5 pl-2 border-l border-carbon-800/70 ml-3 {{ $isAdminActive ? '' : 'hidden' }}">
                    <a
                        href="{{ route('hq.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('hq.*') ? 'bg-gradient-to-r from-purple-600/25 via-purple-600/10 to-transparent text-white font-bold border-l-2 border-purple-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-network-wired text-xs w-4 text-center {{ request()->routeIs('hq.*') ? 'text-purple-400 filter drop-shadow-[0_0_6px_rgba(168,85,247,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Multi-Outlet Master HQ</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[8.5px] font-mono font-bold bg-purple-500/15 border border-purple-500/30 text-purple-400">
                            HQ
                        </span>
                    </a>

                    @if($isAdmin)
                    <a
                        href="{{ route('branches.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('branches.*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-shop text-xs w-4 text-center {{ request()->routeIs('branches.*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Branches & Counters</span>
                        </div>
                        @if(request()->routeIs('branches.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    <a
                        href="{{ route('users.index') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn {{ request()->routeIs('users.*') ? 'bg-gradient-to-r from-brand-600/25 via-brand-600/10 to-transparent text-white font-bold border-l-2 border-brand-500 shadow-sm' : 'text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group' }}"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-user-shield text-xs w-4 text-center {{ request()->routeIs('users.*') ? 'text-brand-400 filter drop-shadow-[0_0_6px_rgba(229,26,46,0.6)]' : 'text-silver-500 group-hover:text-silver-300' }}"></i>
                            <span>Staff & Roles</span>
                        </div>
                        @if(request()->routeIs('users.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_6px_#E51A2E]"></span>
                        @endif
                    </a>

                    <a
                        href="{{ route('admin.backup.download') }}"
                        class="sidebar-nav-item flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-all touch-action-btn text-silver-400 hover:text-white hover:bg-carbon-800/60 font-medium group"
                        title="Download Database Backup (JSON)"
                    >
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-database text-xs w-4 text-center text-silver-500 group-hover:text-emerald-400"></i>
                            <span>Backup Database</span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[8.5px] font-mono font-bold bg-carbon-800 text-silver-400 border border-carbon-700">
                            JSON
                        </span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

        </nav>

        <!-- 4. Modern SaaS Footer (Operator Profile, Status & Terminal Control) -->
        <div class="p-2.5 sm:p-3 border-t border-carbon-800/80 bg-carbon-900/95 shrink-0 space-y-2">
            <!-- Operator Profile Card -->
            <div class="p-2 rounded-xl bg-carbon-850/80 border border-carbon-800/80 flex items-center justify-between gap-2 shadow-sm">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="relative w-7 h-7 rounded-lg bg-gradient-to-tr from-brand-600/30 to-carbon-700 border border-brand-500/40 flex items-center justify-center text-brand-300 font-extrabold text-xs shrink-0 shadow-inner">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 ring-2 ring-carbon-900 absolute -bottom-0.5 -right-0.5 animate-pulse"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[11.5px] font-bold text-white truncate leading-tight">{{ auth()->user()?->name ?? 'Operator' }}</div>
                        <div class="flex items-center gap-1 mt-0.5">
                            <span class="px-1.5 py-0.2 rounded text-[7.5px] font-mono font-bold uppercase tracking-wider bg-brand-500/15 border border-brand-500/30 text-brand-400">
                                {{ auth()->user()?->role ?? 'cashier' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- One-click Terminal Logout Form Button -->
                <form method="POST" action="{{ route('logout') }}" class="shrink-0" title="Log Out Terminal">
                    @csrf
                    <button
                        type="submit"
                        class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-rose-950/80 border border-carbon-700 hover:border-brand-500/50 text-silver-400 hover:text-brand-400 flex items-center justify-center transition-all touch-action-btn"
                        aria-label="Log Out Terminal"
                    >
                        <i class="fa-solid fa-power-off text-[11px]"></i>
                    </button>
                </form>
            </div>

            <!-- Terminal Online Status Strip -->
            <div class="flex items-center justify-between text-[9.5px] text-silver-400/90 font-mono px-1">
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>MySQL Active</span>
                </span>
                <span class="text-silver-500 text-[9px]">Station #01 &bull; Terminal Online</span>
            </div>
        </div>
    </aside>

    <!-- RIGHT MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        
        <!-- Top Sticky Header Navigation Bar -->
        <header class="border-b border-carbon-800 bg-carbon-900/90 backdrop-blur-xl shrink-0 safe-top z-30">
            <div class="px-3.5 sm:px-6 h-14 sm:h-16 flex items-center justify-between">
                
                <!-- Left: Hamburger Button (for Mobile/Tablet) & Current Page Breadcrumb -->
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        onclick="togglePosDrawer()"
                        class="lg:hidden w-9 h-9 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 flex items-center justify-center text-silver-300 hover:text-white transition-all touch-action-btn"
                        aria-label="Open Navigation Sidebar"
                    >
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>

                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-white flex items-center gap-2">
                            <span>@yield('title', 'Dashboard')</span>
                        </h2>
                        <span class="text-[10px] text-silver-400 hidden xs:inline">Psi One Global POS &bull; Terminal Online</span>
                    </div>
                </div>

                <!-- Right: Quick Station Info, Live Clock & Fast Actions -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Quick POS Button -->
                    <a
                        href="{{ route('pos.terminal') }}"
                        class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 text-white font-bold text-xs shadow-md touch-action-btn"
                    >
                        <i class="fa-solid fa-cash-register"></i>
                        <span>POS Billing</span>
                    </a>

                    <!-- Station Identifier Pill -->
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-carbon-800/90 border border-carbon-700/80 text-xs font-mono">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-silver-400 hidden xs:inline">Terminal:</span>
                        <span class="font-bold text-silver-200">#01</span>
                    </div>

                    <!-- Role Badge -->
                    <span class="px-2.5 py-1 rounded-full text-[10px] sm:text-[11px] font-bold uppercase tracking-wider
                        @if(auth()->user()?->role === 'admin') bg-brand-600/20 text-brand-400 border border-brand-500/40
                        @elseif(auth()->user()?->role === 'manager') bg-silver-400/20 text-silver-200 border border-silver-300/30
                        @else bg-rose-500/20 text-rose-300 border border-rose-500/30 @endif">
                        {{ auth()->user()?->role ?? 'cashier' }}
                    </span>

                    <!-- Quick Logout Button for Mobile/Tablet Top Bar -->
                    <form method="POST" action="{{ route('logout') }}" class="inline lg:hidden">
                        @csrf
                        <button
                            type="submit"
                            title="Sign Out"
                            class="p-2 rounded-lg bg-carbon-800 hover:bg-brand-950 border border-carbon-700 text-rose-400 hover:text-white text-xs touch-action-btn"
                        >
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </form>
                </div>

            </div>
        </header>

        <!-- Main Scrollable Content Area -->
        <div class="flex-1 overflow-y-auto pb-20 sm:pb-8">
            @yield('content')
        </div>

    </div>

    <!-- Mobile Bottom App Bar (Always accessible on Mobile devices < 640px) -->
    <nav class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-carbon-900/95 backdrop-blur-xl border-t border-carbon-800 px-3 py-1 safe-bottom flex items-center justify-around shadow-2xl">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center py-1.5 px-3 {{ request()->routeIs('dashboard') ? 'text-brand-400 font-bold' : 'text-silver-400' }} touch-action-btn">
            <i class="fa-solid fa-house text-base"></i>
            <span class="text-[10px] mt-1">Home</span>
        </a>

        <a href="{{ route('products.index') }}" class="flex flex-col items-center py-1.5 px-3 {{ request()->routeIs('products.*') ? 'text-brand-400 font-bold' : 'text-silver-400' }} touch-action-btn">
            <i class="fa-solid fa-boxes-stacked text-base"></i>
            <span class="text-[10px] mt-1">Catalog</span>
        </a>

        <!-- Center Quick POS Register Floating Button -->
        <a href="{{ route('pos.terminal') }}" class="flex flex-col items-center -mt-4 bg-gradient-to-r from-brand-600 to-rose-700 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg shadow-brand-600/50 border-2 border-carbon-900 touch-action-btn">
            <i class="fa-solid fa-cash-register text-base"></i>
        </a>

        <a href="{{ route('sales.index') }}" class="flex flex-col items-center py-1.5 px-3 {{ request()->routeIs('sales.*') ? 'text-brand-400 font-bold' : 'text-silver-400' }} touch-action-btn">
            <i class="fa-solid fa-receipt text-base"></i>
            <span class="text-[10px] mt-1">Invoices</span>
        </a>

        <button onclick="togglePosDrawer()" class="flex flex-col items-center py-1.5 px-3 text-silver-400 hover:text-white touch-action-btn">
            <i class="fa-solid fa-bars text-base text-brand-500"></i>
            <span class="text-[10px] mt-1 font-bold text-white">Menu</span>
        </button>
    </nav>

    <!-- Global Drawer / Sidebar Toggle & Search Script -->
    <script>
        function togglePosDrawer() {
            const sidebar = document.getElementById('posSidebar');
            const backdrop = document.getElementById('drawerBackdrop');
            const isOpen = !sidebar.classList.contains('-translate-x-full');

            if (isOpen) {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('opacity-0', 'pointer-events-none');
                backdrop.classList.remove('opacity-100', 'pointer-events-auto');
            } else {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                backdrop.classList.add('opacity-100', 'pointer-events-auto');
            }
        }

        function toggleNavSection(sectionId) {
            const section = document.querySelector(`.sidebar-nav-section[data-section-id="${sectionId}"]`);
            if (!section) return;
            const body = section.querySelector('.nav-section-body');
            const chevron = section.querySelector('.nav-chevron');
            if (!body) return;

            const isCurrentlyHidden = body.classList.contains('hidden');
            if (isCurrentlyHidden) {
                body.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
                localStorage.setItem('psi_nav_' + sectionId, 'true');
            } else {
                body.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
                localStorage.setItem('psi_nav_' + sectionId, 'false');
            }
        }

        function toggleAllNavSections() {
            const sections = document.querySelectorAll('.sidebar-nav-section[data-section-id]');
            let anyClosed = false;
            sections.forEach(section => {
                const body = section.querySelector('.nav-section-body');
                if (body && body.classList.contains('hidden')) {
                    anyClosed = true;
                }
            });

            const openAll = anyClosed;
            sections.forEach(section => {
                const body = section.querySelector('.nav-section-body');
                const chevron = section.querySelector('.nav-chevron');
                const sectionId = section.dataset.sectionId;
                if (body) {
                    if (openAll) {
                        body.classList.remove('hidden');
                        if (chevron) chevron.classList.add('rotate-180');
                        if (sectionId) localStorage.setItem('psi_nav_' + sectionId, 'true');
                    } else {
                        body.classList.add('hidden');
                        if (chevron) chevron.classList.remove('rotate-180');
                        if (sectionId) localStorage.setItem('psi_nav_' + sectionId, 'false');
                    }
                }
            });
        }

        function filterSidebarMenu(query) {
            query = (query || '').toLowerCase().trim();
            const navItems = document.querySelectorAll('.sidebar-nav-item');
            const sections = document.querySelectorAll('.sidebar-nav-section');

            if (query) {
                // Auto-expand all category bodies to reveal matches
                sections.forEach(section => {
                    const body = section.querySelector('.nav-section-body');
                    const chevron = section.querySelector('.nav-chevron');
                    if (body) body.classList.remove('hidden');
                    if (chevron) chevron.classList.add('rotate-180');
                });

                navItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(query)) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });

                sections.forEach(section => {
                    const visibleItems = section.querySelectorAll('.sidebar-nav-item:not(.hidden)');
                    if (visibleItems.length === 0) {
                        section.classList.add('hidden');
                    } else {
                        section.classList.remove('hidden');
                    }
                });
            } else {
                // Restore regular visibility and section collapse states
                navItems.forEach(item => item.classList.remove('hidden'));
                sections.forEach(section => {
                    section.classList.remove('hidden');
                    const sectionId = section.dataset.sectionId;
                    const body = section.querySelector('.nav-section-body');
                    const chevron = section.querySelector('.nav-chevron');
                    const hasActive = section.dataset.hasActive === '1';

                    if (sectionId && body) {
                        const saved = localStorage.getItem('psi_nav_' + sectionId);
                        if (hasActive) {
                            body.classList.remove('hidden');
                            if (chevron) chevron.classList.add('rotate-180');
                        } else if (saved === 'true') {
                            body.classList.remove('hidden');
                            if (chevron) chevron.classList.add('rotate-180');
                        } else if (saved === 'false') {
                            body.classList.add('hidden');
                            if (chevron) chevron.classList.remove('rotate-180');
                        }
                    }
                });
            }
        }

        // Restore saved sidebar category collapse states
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.sidebar-nav-section[data-section-id]');
            sections.forEach(section => {
                const sectionId = section.dataset.sectionId;
                const hasActive = section.dataset.hasActive === '1';
                const body = section.querySelector('.nav-section-body');
                const chevron = section.querySelector('.nav-chevron');
                if (!body) return;

                if (hasActive) {
                    body.classList.remove('hidden');
                    if (chevron) chevron.classList.add('rotate-180');
                } else {
                    const saved = localStorage.getItem('psi_nav_' + sectionId);
                    if (saved === 'false') {
                        body.classList.add('hidden');
                        if (chevron) chevron.classList.remove('rotate-180');
                    } else if (saved === 'true') {
                        body.classList.remove('hidden');
                        if (chevron) chevron.classList.add('rotate-180');
                    }
                }
            });
        });

        // Global Keyboard Shortcut: Press "Ctrl+K", "Cmd+K", or "/" to focus quick jump
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('sidebarQuickSearch');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            } else if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const searchInput = document.getElementById('sidebarQuickSearch');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        // Instant Page Hover-Prefetcher: Prefetches menu routes on hover/touch for instant page navigation
        (function() {
            const prefetchedUrls = new Set();
            function prefetchUrl(url) {
                if (!url || prefetchedUrls.has(url) || url.includes('/logout') || url.includes('#') || url.startsWith('javascript:')) return;
                prefetchedUrls.add(url);
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.as = 'document';
                link.href = url;
                document.head.appendChild(link);
            }
            document.addEventListener('mouseover', function(e) {
                const a = e.target.closest('a');
                if (a && a.href && a.origin === window.location.origin) {
                    prefetchUrl(a.href);
                }
            }, { passive: true });
            document.addEventListener('touchstart', function(e) {
                const a = e.target.closest('a');
                if (a && a.href && a.origin === window.location.origin) {
                    prefetchUrl(a.href);
                }
            }, { passive: true });
        })();
    </script>

    @stack('scripts')
    @yield('scripts')
</body>
</html>
