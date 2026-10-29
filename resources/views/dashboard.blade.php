@extends('layouts.pos')

@section('title', 'Dashboard')

@section('content')
@php
    $userRole = strtolower($user->role ?? '');
    $isAdmin = in_array($userRole, ['admin', 'super_admin']);
    $isManager = in_array($userRole, ['manager', 'branch_manager']);
    $isHeadOffice = $userRole === 'head_office_manager';
    $isCashier = $userRole === 'cashier';
    $isWarehouse = in_array($userRole, ['warehouse_manager', 'warehouse']);
    $isInventory = in_array($userRole, ['inventory_staff', 'inventory']);
    $isAccountant = $userRole === 'accountant';

    $canAccessPos = $isAdmin || $isManager || $isHeadOffice || $isCashier;
    $canAccessInventory = $isAdmin || $isManager || $isHeadOffice || $isInventory || $isWarehouse || $isAccountant;
@endphp
<main class="max-w-7xl w-full mx-auto px-3.5 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8 flex-1">

    <!-- Status Toast Banner -->
    @if (session('status'))
        <div class="mb-5 p-3.5 sm:p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm flex items-center justify-between shadow-lg">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-400 text-sm sm:text-base shrink-0"></i>
                <span>{{ session('status') }}</span>
            </div>
            <span class="text-[10px] sm:text-xs text-emerald-400/80 font-mono hidden xs:inline">Session Active</span>
        </div>
    @endif

    <!-- Hero Banner -->
    <div class="rounded-3xl bg-carbon-900 border border-carbon-800 p-4 sm:p-6 lg:p-8 relative overflow-hidden mb-6 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6">
            <div class="flex items-start gap-3 sm:gap-4">
                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-carbon-800 p-2 border border-carbon-700 flex items-center justify-center shrink-0 shadow-lg">
                    <img src="{{ asset('images/psi-logo.png') }}" alt="PSI Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-carbon-800 border border-carbon-700 text-silver-300 text-[10px] sm:text-xs font-semibold mb-1 sm:mb-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>PSI ONE GLOBAL POS &bull; Terminal Online</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight">
                        Welcome, {{ $user->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-silver-400 mt-0.5">
                        Signed In: <span class="text-white font-medium">{{ $user->email }}</span> &bull; Role: <span class="uppercase font-bold text-brand-400">{{ $user->role }}</span>
                    </p>
                </div>
            </div>

            <!-- Primary Fast Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 pt-2 md:pt-0">
                @if($canAccessPos)
                <a
                    href="{{ route('pos.terminal') }}"
                    class="flex-1 sm:flex-initial min-h-[46px] sm:min-h-[50px] px-5 sm:px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-xs sm:text-sm shadow-lg flex items-center justify-center gap-2 transition-all touch-action-btn"
                >
                    <i class="fa-solid fa-cash-register text-base"></i>
                    <span>Open Cashier Register</span>
                </a>
                @elseif($canAccessInventory)
                <a
                    href="{{ route('inventory.index') }}"
                    class="flex-1 sm:flex-initial min-h-[46px] sm:min-h-[50px] px-5 sm:px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-xs sm:text-sm shadow-lg flex items-center justify-center gap-2 transition-all touch-action-btn"
                >
                    <i class="fa-solid fa-boxes-stacked text-base"></i>
                    <span>Stock & Inventory</span>
                </a>
                @endif
                <a
                    href="{{ route('products.index') }}"
                    class="min-h-[46px] sm:min-h-[50px] px-4 py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-200 text-xs sm:text-sm font-semibold flex items-center justify-center gap-2 transition-all touch-action-btn"
                >
                    <i class="fa-solid fa-magnifying-glass text-brand-400"></i>
                    <span>Search Products</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
        
        <!-- Revenue -->
        <div class="p-3.5 sm:p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-silver-400">Today's Sales</span>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-wallet text-sm sm:text-base"></i>
                </div>
            </div>
            <div class="text-lg sm:text-2xl font-black font-mono text-white">Rs. {{ number_format($metrics['today_sales'], 2) }}</div>
            <div class="mt-1 text-[10px] sm:text-xs text-silver-400">
                <span>Completed sales today</span>
            </div>
        </div>

        <!-- Orders -->
        <div class="p-3.5 sm:p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-silver-400">Total Bills</span>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-brand-600/10 text-brand-400 flex items-center justify-center">
                    <i class="fa-solid fa-receipt text-sm sm:text-base"></i>
                </div>
            </div>
            <div class="text-lg sm:text-2xl font-black font-mono text-white">{{ $metrics['today_orders'] }}</div>
            <div class="mt-1 text-[10px] sm:text-xs text-silver-400">
                <span>Invoices generated</span>
            </div>
        </div>

        <!-- Catalog SKUs -->
        <div class="p-3.5 sm:p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-silver-400">Total Products</span>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-carbon-800 text-silver-300 flex items-center justify-center">
                    <i class="fa-solid fa-boxes-stacked text-sm sm:text-base"></i>
                </div>
            </div>
            <div class="text-lg sm:text-2xl font-black font-mono text-white">{{ number_format($metrics['total_products']) }} Items</div>
            <div class="mt-1 text-[10px] sm:text-xs text-amber-400 flex items-center gap-1 font-medium">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ $metrics['low_stock_items'] }} low stock</span>
            </div>
        </div>

        <!-- Open Registers -->
        <div class="p-3.5 sm:p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-silver-400">Active Counters</span>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-carbon-800 text-silver-300 flex items-center justify-center">
                    <i class="fa-solid fa-desktop text-sm sm:text-base"></i>
                </div>
            </div>
            <div class="text-lg sm:text-2xl font-black font-mono text-white">{{ $metrics['open_registers'] }} Open</div>
            <div class="mt-1 text-[10px] sm:text-xs text-emerald-400 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Ready for billing</span>
            </div>
        </div>

    </div>

    <!-- POS Module Touch Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6 mb-6">
        
        <!-- Cashier / Operator Info -->
        <div class="p-4 sm:p-6 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-lg flex flex-col justify-between">
            <div>
                <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 sm:mb-4 flex items-center gap-2 border-b border-carbon-800 pb-2">
                    <i class="fa-solid fa-user text-brand-500"></i>
                    <span>Cashier & Shift Information</span>
                </h3>

                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between py-1 border-b border-carbon-800/60">
                        <span class="text-silver-400">Cashier Name</span>
                        <span class="font-bold text-white">{{ $user->name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-carbon-800/60">
                        <span class="text-silver-400">Username</span>
                        <span class="font-mono text-brand-400 font-bold">{{ $user->username }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-carbon-800/60">
                        <span class="text-silver-400">Role</span>
                        <span class="font-bold uppercase text-brand-400 bg-brand-600/10 px-2 py-0.5 rounded">{{ $user->role }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-carbon-800/60">
                        <span class="text-silver-400">Current Shift</span>
                        @if($activeShift)
                            <span class="text-emerald-400 font-bold flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Shift Open (Float: Rs. {{ number_format($activeShift->opening_float, 2) }})
                            </span>
                        @else
                            <span class="text-amber-400 font-bold">Shift Not Started</span>
                        @endif
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-silver-400">Login Time</span>
                        <span class="font-mono text-silver-300">
                            {{ $user->last_login_at ? $user->last_login_at->format('M d, h:i A') : 'Just now' }}
                        </span>
                    </div>
                </div>
            </div>

            @if($canAccessPos)
            <div class="mt-4 pt-3 border-t border-carbon-800">
                <a href="{{ route('pos.terminal') }}" class="w-full py-2.5 px-3 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white text-xs font-bold flex items-center justify-center gap-2 transition-all">
                    <i class="fa-solid fa-arrow-right text-brand-500"></i>
                    <span>Go to Cashier Register</span>
                </a>
            </div>
            @elseif($canAccessInventory)
            <div class="mt-4 pt-3 border-t border-carbon-800">
                <a href="{{ route('inventory.index') }}" class="w-full py-2.5 px-3 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white text-xs font-bold flex items-center justify-center gap-2 transition-all">
                    <i class="fa-solid fa-arrow-right text-brand-500"></i>
                    <span>Manage Stock & Inventory</span>
                </a>
            </div>
            @endif
        </div>

        <!-- Retail Quick Shortcuts -->
        <div class="lg:col-span-2 p-4 sm:p-6 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-lg">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 sm:mb-4 flex items-center gap-2 border-b border-carbon-800 pb-2">
                <i class="fa-solid fa-grid-2 text-silver-300"></i>
                <span>Quick Actions</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                
                @if($canAccessPos)
                <!-- Cashier Register -->
                <a href="{{ route('pos.terminal') }}" class="p-4 rounded-xl bg-carbon-800/80 hover:bg-carbon-800 border border-carbon-700/80 hover:border-brand-500/50 transition-all group touch-action-btn block">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-brand-600/10 group-hover:bg-brand-600/20 text-brand-400 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-cash-register text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-brand-300">Cashier Register</h4>
                            <p class="text-xs text-silver-400">Issue bills, scan barcodes & print receipts</p>
                        </div>
                    </div>
                </a>
                @elseif($canAccessInventory)
                <!-- Stock & Inventory -->
                <a href="{{ route('inventory.index') }}" class="p-4 rounded-xl bg-carbon-800/80 hover:bg-carbon-800 border border-carbon-700/80 hover:border-brand-500/50 transition-all group touch-action-btn block">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-brand-600/10 group-hover:bg-brand-600/20 text-brand-400 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-boxes-stacked text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-brand-300">Stock & Inventory</h4>
                            <p class="text-xs text-silver-400">View real-time branch stock, batches & alerts</p>
                        </div>
                    </div>
                </a>
                @endif

                <!-- Products & Inventory -->
                <a href="{{ route('products.index') }}" class="p-4 rounded-xl bg-carbon-800/80 hover:bg-carbon-800 border border-carbon-700/80 hover:border-silver-400/50 transition-all group touch-action-btn block">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-carbon-700/50 text-silver-300 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-boxes-stacked text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-silver-200">Products List</h4>
                            <p class="text-xs text-silver-400">Manage items, shades, prices & add new items</p>
                        </div>
                    </div>
                </a>

                <!-- Customers & Loyalty Points -->
                <a href="{{ route('customers.index') }}" class="p-4 rounded-xl bg-carbon-800/80 hover:bg-carbon-800 border border-carbon-700/80 hover:border-purple-500/50 transition-all group touch-action-btn block">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-user-group text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-purple-300">Customers</h4>
                            <p class="text-xs text-silver-400">Customer phone numbers, purchase history and points</p>
                        </div>
                    </div>
                </a>

                <!-- Sales Invoices & Returns -->
                <a href="{{ route('sales.index') }}" class="p-4 rounded-xl bg-carbon-800/80 hover:bg-carbon-800 border border-carbon-700/80 hover:border-brand-500/50 transition-all group touch-action-btn block">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-brand-600/10 text-brand-400 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-receipt text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-brand-300">Sales & Returns</h4>
                            <p class="text-xs text-silver-400">Lookup past receipts, process exchanges & refunds</p>
                        </div>
                    </div>
                </a>

                @if(auth()->user()?->isAdmin() || auth()->user()?->isManager())
                <!-- Current Stock & Expiry -->
                <a href="{{ route('inventory.index') }}" class="p-4 rounded-xl bg-carbon-800/80 hover:bg-carbon-800 border border-carbon-700/80 hover:border-amber-500/50 transition-all group touch-action-btn block">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-warehouse text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-amber-300">Stock & Inventory</h4>
                            <p class="text-xs text-silver-400">Check branch stock levels and expiry dates</p>
                        </div>
                    </div>
                </a>

                <!-- Sales History & Reports -->
                <a href="{{ route('reports.index') }}" class="p-4 rounded-xl bg-carbon-800/80 hover:bg-carbon-800 border border-carbon-700/80 hover:border-emerald-500/50 transition-all group touch-action-btn block">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-chart-column text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-emerald-300">Sales Reports</h4>
                            <p class="text-xs text-silver-400">View daily revenue, profit margin & analytics</p>
                        </div>
                    </div>
                </a>

                <!-- Daily Expenses -->
                <a href="{{ route('expenses.index') }}" class="p-4 rounded-xl bg-carbon-800/80 hover:bg-carbon-800 border border-carbon-700/80 hover:border-brand-500/50 transition-all group touch-action-btn block">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center transition-all shrink-0">
                            <i class="fa-solid fa-money-bill text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-rose-300">Daily Expenses</h4>
                            <p class="text-xs text-silver-400">Record petty cash, store bills and office expenses</p>
                        </div>
                    </div>
                </a>
                @endif

            </div>
        </div>

    </div>

    <!-- Recent Sales Invoices Section -->
    <div class="rounded-2xl bg-carbon-900 border border-carbon-800 p-4 sm:p-6 shadow-lg">
        <div class="flex items-center justify-between mb-4 border-b border-carbon-800 pb-3">
            <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-brand-500"></i>
                    <span>Recent Sales Invoices</span>
                </h3>
                <p class="text-xs text-silver-400">Latest completed customer checkout receipts</p>
            </div>
            <a href="{{ route('sales.index') }}" class="text-xs font-bold text-brand-400 hover:text-brand-300 flex items-center gap-1.5">
                <span>View All Invoices</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        @if($recentSales->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-carbon-800 text-silver-400 uppercase text-[10px] tracking-wider">
                            <th class="py-2.5 px-3">Invoice No</th>
                            <th class="py-2.5 px-3">Time</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3">Payment Method</th>
                            <th class="py-2.5 px-3 text-right">Total Amount</th>
                            <th class="py-2.5 px-3 text-center">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-sans">
                        @foreach($recentSales as $sale)
                            <tr class="hover:bg-carbon-850/60 transition-colors">
                                <td class="py-3 px-3 font-mono font-bold text-brand-400">
                                    <a href="{{ route('sales.show', $sale->id) }}" class="hover:underline">
                                        {{ $sale->invoice_no }}
                                    </a>
                                </td>
                                <td class="py-3 px-3 text-silver-300 font-mono">
                                    {{ $sale->created_at->format('h:i A') }}
                                </td>
                                <td class="py-3 px-3 text-white font-medium">
                                    {{ $sale->customer->name ?? 'Walk-in Customer' }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $sale->payment_method === 'cash' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-brand-600/10 text-brand-400' }}">
                                        {{ $sale->payment_method }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-white">
                                    Rs. {{ number_format($sale->grand_total, 2) }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="p-1.5 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white inline-flex items-center gap-1 text-[11px] font-semibold transition-all">
                                        <i class="fa-solid fa-print text-brand-400"></i>
                                        <span>Print</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-8 text-center text-silver-400 text-xs">
                <i class="fa-solid fa-receipt text-2xl text-silver-600 mb-2 block"></i>
                <p>No sales recorded yet today.</p>
                @if($canAccessPos)
                <a href="{{ route('pos.terminal') }}" class="mt-2 inline-block text-brand-400 hover:underline font-bold">Start Cashier Register &rarr;</a>
                @endif
            </div>
        @endif
    </div>

</main>
@endsection
