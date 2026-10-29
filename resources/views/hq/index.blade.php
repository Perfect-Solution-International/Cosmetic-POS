@extends('layouts.pos')

@section('title', 'Multi-Outlet Master HQ')

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Header & Business Overview -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-gradient-to-r from-carbon-900 via-carbon-900 to-purple-950/40 p-6 rounded-3xl border border-carbon-800 shadow-2xl">
        <div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                    MASTER TENANT HQ
                </span>
                <span class="text-silver-500">•</span>
                <span class="text-silver-400 font-semibold">{{ $activeSub->business_name ?? 'Multi-Outlet Retail Group' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white mt-1.5 flex items-center gap-3">
                <i class="fa-solid fa-network-wired text-purple-400 text-xl sm:text-2xl"></i>
                <span>Multi-Outlet Master Command</span>
            </h1>
            <p class="text-xs text-silver-400 mt-1">
                Real-time centralized control across all licensed retail outlets, inventory distribution, cashier shifts, and revenue.
            </p>
        </div>

        <!-- Subscription Quota & Quick Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Quota Badge -->
            <div class="px-4 py-2.5 rounded-2xl bg-carbon-850 border border-carbon-700/80 shadow-md">
                <div class="flex items-center justify-between gap-4 text-[11px] mb-1">
                    <span class="text-silver-400 font-medium">Branch License Quota:</span>
                    <span class="font-mono font-black {{ $consolidatedKPIs['used_branches'] >= $consolidatedKPIs['allocated_branches'] ? 'text-amber-400' : 'text-emerald-400' }}">
                        {{ $consolidatedKPIs['used_branches'] }} / {{ $consolidatedKPIs['allocated_branches'] }} Outlets Active
                    </span>
                </div>
                <div class="w-48 h-1.5 bg-carbon-800 rounded-full overflow-hidden">
                    @php
                        $pct = min(100, round(($consolidatedKPIs['used_branches'] / max(1, $consolidatedKPIs['allocated_branches'])) * 100));
                    @endphp
                    <div class="h-full bg-gradient-to-r from-purple-500 to-emerald-400 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
            </div>

            <!-- Manage Branches -->
            <a 
                href="{{ route('branches.index') }}" 
                class="px-4 py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white font-bold text-xs flex items-center gap-2 transition"
            >
                <i class="fa-solid fa-code-branch text-brand-400"></i>
                <span>Manage Branches</span>
            </a>

            <!-- Subscriptions & Plans -->
            <a 
                href="{{ route('subscriptions.index') }}" 
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-brand-600 hover:from-purple-500 hover:to-brand-500 text-white font-black text-xs shadow-lg shadow-purple-600/30 flex items-center gap-2 transition touch-action-btn"
            >
                <i class="fa-solid fa-layer-group"></i>
                <span>Plan & Licenses</span>
            </a>
        </div>
    </div>

    <!-- Active Subscription Banner (Plan Type, Trial Remaining & Add-ons) -->
    @if($activeSub)
    <div class="p-4 rounded-2xl bg-carbon-900/90 border border-purple-500/30 flex flex-wrap items-center justify-between gap-3 text-xs shadow-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-crown"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-white text-sm">{{ $activeSub->package->name ?? 'Standard SaaS Package' }}</span>
                    <span class="px-2 py-0.5 rounded-md text-[9px] font-mono font-black uppercase bg-purple-500/20 text-purple-300 border border-purple-500/40">
                        {{ strtoupper($activeSub->billing_cycle ?? 'Monthly') }}
                    </span>
                    @if($activeSub->isTrialActive())
                        <span class="px-2 py-0.5 rounded-md text-[9px] font-mono font-black uppercase bg-amber-500/20 text-amber-300 border border-amber-500/40">
                            {{ $activeSub->days_remaining }} Days Free Trial Remaining
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-md text-[9px] font-mono font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            Active Paid Subscription
                        </span>
                    @endif
                </div>
                <p class="text-[11px] text-silver-400 mt-0.5">
                    Includes <strong>{{ $activeSub->package->included_branches ?? 1 }} base outlet</strong> + 
                    <strong>{{ max(0, $activeSub->allocated_branches - ($activeSub->package->included_branches ?? 1)) }} add-on outlet licenses</strong> 
                    (@ Rs. {{ number_format($activeSub->additional_branch_price, 2) }}/branch).
                </p>
            </div>
        </div>

        <div class="flex items-center gap-4 font-mono text-right">
            <div>
                <span class="text-[10px] text-silver-500 block uppercase font-bold">Total Subscription</span>
                <span class="text-base font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-purple-300">
                    Rs. {{ number_format($activeSub->total_price ?: $activeSub->price, 2) }}
                </span>
                <span class="text-[9px] text-silver-500 uppercase block">/ {{ $activeSub->billing_cycle }}</span>
            </div>

            @if($activeSub->isTrialActive())
                <form method="POST" action="{{ route('subscriptions.convert', $activeSub->id) }}">
                    @csrf
                    <input type="hidden" name="payment_method" value="cash">
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-[11px] shadow-lg shadow-emerald-600/30 transition">
                        Convert to Paid Plan
                    </button>
                </form>
            @endif
        </div>
    </div>
    @endif

    <!-- Top Consolidated KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Consolidated Sales -->
        <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl flex items-center justify-between">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-silver-500">Today's Combined Revenue</span>
                <div class="mt-1 flex items-baseline gap-1">
                    <span class="text-xs text-silver-400 font-mono">Rs.</span>
                    <span class="text-2xl font-black text-white font-mono">{{ number_format($consolidatedKPIs['total_sales_today'], 2) }}</span>
                </div>
                <span class="text-[10px] text-emerald-400 font-semibold mt-1 flex items-center gap-1">
                    <i class="fa-solid fa-arrow-trend-up"></i> Across all active outlets
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-brand-600/20 border border-brand-500/30 text-brand-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-cash-register"></i>
            </div>
        </div>

        <!-- 2. Consolidated Orders -->
        <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl flex items-center justify-between">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-silver-500">Total Transactions Today</span>
                <div class="mt-1">
                    <span class="text-2xl font-black text-white font-mono">{{ number_format($consolidatedKPIs['total_orders_today']) }}</span>
                    <span class="text-xs text-silver-400 font-bold ml-1">Receipts</span>
                </div>
                <span class="text-[10px] text-silver-400 font-semibold mt-1 block">Live synced bills</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-500/20 border border-purple-500/30 text-purple-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <!-- 3. Active Stores Operating -->
        <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl flex items-center justify-between">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-silver-500">Retail Outlets Active</span>
                <div class="mt-1 flex items-baseline gap-1">
                    <span class="text-2xl font-black text-white font-mono">{{ $consolidatedKPIs['used_branches'] }}</span>
                    <span class="text-xs text-silver-400 font-bold">/ {{ $consolidatedKPIs['allocated_branches'] }} Licensed</span>
                </div>
                <span class="text-[10px] text-emerald-400 font-semibold mt-1 block">
                    {{ max(0, $consolidatedKPIs['allocated_branches'] - $consolidatedKPIs['used_branches']) }} Licenses available
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-shop"></i>
            </div>
        </div>

        <!-- 4. Active Cashier Shifts -->
        <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl flex items-center justify-between">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-silver-500">Open Cashier Shifts</span>
                <div class="mt-1">
                    <span class="text-2xl font-black text-white font-mono">{{ $consolidatedKPIs['open_shifts'] }}</span>
                    <span class="text-xs text-silver-400 font-bold ml-1">Live Shifts</span>
                </div>
                <span class="text-[10px] text-silver-400 font-semibold mt-1 block">Counters taking payments</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-user-clock"></i>
            </div>
        </div>
    </div>

    <!-- SECTION 1: Side-by-Side Outlet Performance Cards -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                    <i class="fa-solid fa-store text-purple-400"></i>
                    <span>Branch & Outlet Performance Matrix</span>
                </h3>
                <p class="text-xs text-silver-400">Live operational status, today's sales, and active cashier shifts per retail location</p>
            </div>

            <a href="{{ route('branches.index') }}" class="text-xs text-brand-400 hover:underline font-bold flex items-center gap-1">
                <span>View Full Branch Setup</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($branchMetrics as $bm)
                @php $b = $bm['branch']; @endphp
                <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 hover:border-purple-500/50 transition-all shadow-xl space-y-4 flex flex-col justify-between">
                    <div>
                        <!-- Branch Top Bar -->
                        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 mb-3">
                            <div>
                                <h4 class="text-sm font-extrabold text-white">{{ $b->name }}</h4>
                                <div class="flex items-center gap-2 text-[10px] text-silver-400 mt-0.5">
                                    <span class="font-mono text-brand-400 font-bold">{{ $b->code }}</span>
                                    <span>•</span>
                                    <span>{{ $b->city ?? 'Central' }}</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $bm['active_shift'] ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-carbon-800 text-silver-500' }}">
                                {{ $bm['active_shift'] ? 'Shift Open' : 'Closed' }}
                            </span>
                        </div>

                        <!-- Outlet Stats -->
                        <div class="grid grid-cols-2 gap-3 py-2">
                            <div class="p-3 rounded-2xl bg-carbon-850 border border-carbon-750">
                                <span class="text-[10px] text-silver-400 uppercase font-semibold block">Today's Sales</span>
                                <span class="text-base font-black text-white font-mono block mt-0.5">
                                    Rs. {{ number_format($bm['sales_today'], 2) }}
                                </span>
                                <span class="text-[9px] text-silver-500 font-mono">{{ $bm['orders_today'] }} orders</span>
                            </div>

                            <div class="p-3 rounded-2xl bg-carbon-850 border border-carbon-750">
                                <span class="text-[10px] text-silver-400 uppercase font-semibold block">Stock on Hand</span>
                                <span class="text-base font-black text-white font-mono block mt-0.5">
                                    {{ number_format($bm['total_units']) }}
                                </span>
                                <span class="text-[9px] {{ $bm['low_stock_count'] > 0 ? 'text-amber-400 font-bold' : 'text-silver-500' }}">
                                    {{ $bm['low_stock_count'] }} low stock
                                </span>
                            </div>
                        </div>

                        <!-- Active Shift Cashier Info -->
                        <div class="mt-3 p-2.5 rounded-xl bg-carbon-850/60 border border-carbon-800 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-user text-silver-400"></i>
                                <div>
                                    <span class="text-[10px] text-silver-500 block">Active Cashier:</span>
                                    <span class="text-white font-bold text-[11px]">
                                        {{ $bm['active_shift']->cashier->name ?? 'None on duty' }}
                                    </span>
                                </div>
                            </div>
                            <span class="font-mono text-[10px] text-silver-400">
                                {{ $bm['open_registers'] }} / {{ $bm['total_registers'] }} Regs Open
                            </span>
                        </div>
                    </div>

                    <!-- Outlet Quick Actions -->
                    <div class="pt-3 border-t border-carbon-800 flex items-center justify-between gap-2">
                        <a 
                            href="{{ route('dashboard', ['branch_id' => $b->id]) }}" 
                            class="flex-1 py-2 text-center rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 font-bold text-xs transition"
                        >
                            View Store
                        </a>
                        <a 
                            href="{{ route('pos.terminal', ['branch_id' => $b->id]) }}" 
                            class="flex-1 py-2 text-center rounded-xl bg-brand-600/20 hover:bg-brand-600 text-brand-300 hover:text-white font-bold text-xs border border-brand-500/30 transition"
                        >
                            POS Counter
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 p-8 rounded-3xl bg-carbon-900 border border-carbon-800 text-center text-silver-400">
                    <p>No active branches found. Add branches to begin managing your multi-outlet network.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- SECTION 2: Inter-Branch Stock Comparison & Transfer Matrix -->
    <div class="p-6 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-carbon-800 pb-4">
            <div>
                <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-brand-400"></i>
                    <span>Central Inventory & Multi-Branch Stock Matrix</span>
                </h3>
                <p class="text-xs text-silver-400">Compare inventory quantities across each branch to identify low stock and initiate stock transfers</p>
            </div>

            <a 
                href="{{ route('inventory.transfers') }}" 
                class="px-4 py-2 rounded-xl bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 text-xs font-bold transition flex items-center gap-2 self-start sm:self-auto"
            >
                <i class="fa-solid fa-dolly"></i>
                <span>Transfer Stock Between Branches</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-carbon-800 text-[10px] text-silver-400 font-extrabold uppercase tracking-wider">
                        <th class="py-3 px-3">Product / Variation</th>
                        <th class="py-3 px-3 font-mono">SKU</th>
                        <th class="py-3 px-3 text-center font-mono">Combined Total</th>
                        @foreach($branches as $b)
                            <th class="py-3 px-3 text-center font-mono text-purple-300">{{ $b->name }}</th>
                        @endforeach
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon-800/60 font-medium">
                    @forelse($stockMatrix as $row)
                        <tr class="hover:bg-carbon-850/50 transition">
                            <td class="py-3 px-3">
                                <span class="font-bold text-white block">{{ $row['product_name'] }}</span>
                                <span class="text-[10px] text-silver-500">{{ $row['variation']->name ?? '' }}</span>
                            </td>
                            <td class="py-3 px-3 font-mono text-silver-400 text-[11px]">{{ $row['sku'] }}</td>
                            <td class="py-3 px-3 text-center font-mono font-bold text-white text-sm">
                                {{ number_format($row['total_stock']) }}
                            </td>
                            @foreach($branches as $b)
                                @php $bQty = $row['branches'][$b->id] ?? 0; @endphp
                                <td class="py-3 px-3 text-center font-mono">
                                    <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ $bQty <= 5 ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' : 'bg-carbon-800 text-silver-200' }}">
                                        {{ $bQty }}
                                    </span>
                                </td>
                            @endforeach
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('inventory.transfers') }}" class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-silver-300 text-[10px] font-bold inline-flex items-center gap-1 transition">
                                    <i class="fa-solid fa-arrow-right-arrow-left text-[9px] text-brand-400"></i>
                                    <span>Move</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + $branches->count() }}" class="py-6 text-center text-silver-500">
                                No product inventory recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 3: Live Consolidated Sales Feed -->
    <div class="p-6 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div>
                <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-emerald-400"></i>
                    <span>Live Multi-Outlet Sales Feed</span>
                </h3>
                <p class="text-xs text-silver-400">Real-time transactions from across all outlet counters</p>
            </div>

            <a href="{{ route('sales.index') }}" class="text-xs text-silver-400 hover:text-white transition font-bold">
                View All Invoices →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-carbon-800 text-[10px] text-silver-400 font-extrabold uppercase tracking-wider">
                        <th class="py-2.5 px-3">Time</th>
                        <th class="py-2.5 px-3">Invoice No</th>
                        <th class="py-2.5 px-3">Origin Branch</th>
                        <th class="py-2.5 px-3">Cashier</th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3 text-right font-mono">Grand Total</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon-800/60 font-medium">
                    @forelse($recentSales as $sale)
                        <tr class="hover:bg-carbon-850/50 transition">
                            <td class="py-2.5 px-3 text-silver-400 font-mono text-[11px]">{{ $sale->created_at->format('H:i') }}</td>
                            <td class="py-2.5 px-3 font-mono font-bold text-white text-xs">
                                <a href="{{ route('pos.receipt', $sale->id) }}" class="hover:text-purple-400 transition">
                                    {{ $sale->invoice_no }}
                                </a>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-carbon-800 text-silver-300 border border-carbon-700">
                                    {{ $sale->branch->name ?? 'Main Outlet' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-silver-300 text-xs">{{ $sale->cashier->name ?? 'Cashier' }}</td>
                            <td class="py-2.5 px-3 text-silver-400 text-xs">{{ $sale->customer->name ?? 'Walk-in Customer' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-black text-white text-xs">
                                Rs. {{ number_format($sale->grand_total, 2) }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    {{ $sale->payment_status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-silver-500">
                                No sales registered today yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

