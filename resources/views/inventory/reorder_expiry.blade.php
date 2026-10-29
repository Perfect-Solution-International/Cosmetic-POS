@extends('layouts.pos')

@section('title', 'Weekly Stock Reorder & Batch Expiry Audit')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400">
                <i class="fa-solid fa-boxes-packing text-base"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base sm:text-lg font-black text-white">Weekly Stock Reorder & Batch Expiry Audit</h1>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        WEEKLY CYCLE
                    </span>
                </div>
                <p class="text-xs text-silver-400">{{ $branchName }} &bull; Prevents stock-outs & expired inventory write-offs</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Print A4 -->
            <a
                href="{{ route('inventory.reorder_expiry.print', request()->query()) }}"
                target="_blank"
                class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold transition flex items-center gap-1.5 touch-action-btn"
                title="Print Official A4 Reorder & Expiry Audit Sheet"
            >
                <i class="fa-solid fa-print"></i>
                <span class="hidden sm:inline">Print (A4)</span>
            </a>

            <!-- Download Excel Sheet (.xlsx) -->
            <a
                href="{{ route('inventory.reorder_expiry.export-excel', request()->query()) }}"
                class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition touch-action-btn"
                title="Download Weekly Reorder & Expiry Audit as Microsoft Excel (.xlsx) Sheet"
            >
                <i class="fa-solid fa-file-excel"></i>
                <span>Download Excel Sheet</span>
            </a>

            <a
                href="{{ route('inventory.index') }}"
                class="px-3 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold transition flex items-center gap-1.5"
            >
                <i class="fa-solid fa-arrow-left"></i>
                <span>Stock Index</span>
            </a>
        </div>
    </div>

    <!-- Branch Selector Bar -->
    <div class="bg-carbon-900/60 border-b border-carbon-800/80 px-4 sm:px-6 py-2.5 shrink-0">
        <form action="{{ route('inventory.reorder_expiry') }}" method="GET" class="flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="text-silver-400 font-medium">Filter Location:</span>
                <select name="branch_id" onchange="this.form.submit()" class="px-3.5 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500">
                    <option value="">All Branches & Warehouses Combined</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
                @if(request()->filled('branch_id'))
                    <a href="{{ route('inventory.reorder_expiry') }}" class="text-xs text-rose-400 hover:underline ml-2">Reset</a>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('procurement.po') }}" class="px-3 py-1.5 rounded-xl bg-brand-600/20 hover:bg-brand-600 text-brand-300 hover:text-white border border-brand-500/30 text-xs font-bold transition flex items-center gap-1">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Purchase Orders</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

        <!-- 4 Top KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Items Needing Reorder -->
            <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-xs text-silver-400 font-bold uppercase">
                    <span>Reorder Alerts</span>
                    <i class="fa-solid fa-bell text-amber-400"></i>
                </div>
                <div class="text-2xl font-black text-amber-300 font-mono">
                    {{ $reorderItems->count() }} Items
                </div>
                <p class="text-[10px] text-silver-500">{{ $outOfStockCount }} out of stock &bull; {{ $criticalReorderCount }} critical</p>
            </div>

            <!-- Estimated Reorder Purchase Cost -->
            <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-xs text-silver-400 font-bold uppercase">
                    <span>Est. Replenish Cost</span>
                    <i class="fa-solid fa-wallet text-emerald-400"></i>
                </div>
                <div class="text-2xl font-black text-emerald-400 font-mono">
                    Rs. {{ number_format($totalReorderEstCost, 2) }}
                </div>
                <p class="text-[10px] text-silver-500">To restock all shortage items</p>
            </div>

            <!-- Expired Stock (Do Not Sell) -->
            <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-xs text-silver-400 font-bold uppercase">
                    <span>Expired Stock</span>
                    <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
                </div>
                <div class="text-2xl font-black text-rose-400 font-mono">
                    Rs. {{ number_format($totalExpiredCost, 2) }}
                </div>
                <p class="text-[10px] text-rose-400/80">{{ $expiringBatches->where('is_expired', true)->count() }} batch(es) must be quarantined</p>
            </div>

            <!-- Near Expiry Within 90 Days -->
            <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-xs text-silver-400 font-bold uppercase">
                    <span>Expiry Risk (90 Days)</span>
                    <i class="fa-solid fa-hourglass-half text-amber-400"></i>
                </div>
                <div class="text-2xl font-black text-amber-300 font-mono">
                    Rs. {{ number_format($totalNearExpiryCost, 2) }}
                </div>
                <p class="text-[10px] text-silver-500">{{ $expiringBatches->where('is_expired', false)->count() }} batch(es) eligible for promo sale</p>
            </div>

        </div>

        <!-- Section 1: Low Stock Replenishment & Reorder Procurement Schedule -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-3xl overflow-hidden shadow-xl p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-carbon-800 pb-3 flex-wrap gap-2">
                <div>
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-dolly text-amber-400"></i>
                        <span>Weekly Stock Replenishment & Reorder Procurement Schedule</span>
                    </h3>
                    <p class="text-[11px] text-silver-400 mt-0.5">Inventory items below safety reorder threshold requiring replenishment</p>
                </div>
                <span class="text-xs font-mono font-bold text-amber-400">
                    Est. Total Cost: Rs. {{ number_format($totalReorderEstCost, 2) }}
                </span>
            </div>

            @if($reorderItems->isEmpty())
                <div class="py-10 text-center text-xs text-silver-400 space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-carbon-800 text-emerald-400 mx-auto flex items-center justify-center text-lg">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <p class="font-bold text-white">All product inventory levels are healthy!</p>
                    <p class="text-[11px] text-silver-500">No stock lines are currently below their safety reorder thresholds.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400 bg-carbon-850/60">
                                <th class="p-3">Product Name & Variation</th>
                                <th class="p-3 text-center">SKU</th>
                                <th class="p-3">Branch Location</th>
                                <th class="p-3 text-right">Current Stock</th>
                                <th class="p-3 text-right">Threshold</th>
                                <th class="p-3 text-right">Suggested Order</th>
                                <th class="p-3 text-right">Unit Cost</th>
                                <th class="p-3 text-right">Est. Order Cost</th>
                                <th class="p-3 text-center">Urgency Status</th>
                                <th class="p-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon-800 font-mono">
                            @foreach($reorderItems as $item)
                                <tr class="hover:bg-carbon-850/50 transition">
                                    <td class="p-3 font-sans font-bold text-white">
                                        <div>{{ $item->product_name }}</div>
                                        @if($item->variation_name)
                                            <span class="text-[10px] text-silver-400 font-normal">{{ $item->variation_name }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center text-silver-400 text-[11px]">{{ $item->sku }}</td>
                                    <td class="p-3 font-sans text-silver-300 text-[11px]">{{ $item->branch_name }}</td>
                                    <td class="p-3 text-right font-black {{ $item->current_stock <= 0 ? 'text-rose-400' : 'text-amber-400' }}">
                                        {{ number_format($item->current_stock) }} pcs
                                    </td>
                                    <td class="p-3 text-right text-silver-400">{{ number_format($item->reorder_threshold) }} pcs</td>
                                    <td class="p-3 text-right font-black text-emerald-400">
                                        {{ number_format($item->suggested_order) }} pcs
                                    </td>
                                    <td class="p-3 text-right text-silver-300">Rs. {{ number_format($item->purchase_cost, 2) }}</td>
                                    <td class="p-3 text-right font-bold text-white">
                                        Rs. {{ number_format($item->est_total_cost, 2) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($item->urgency === 'OUT_OF_STOCK')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-black bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                                OUT OF STOCK
                                            </span>
                                        @elseif($item->urgency === 'CRITICAL')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                                CRITICAL (&lt;50%)
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                                REORDER NOW
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <a
                                            href="{{ route('procurement.po') }}"
                                            class="px-2 py-1 rounded bg-carbon-800 hover:bg-brand-600 text-silver-300 hover:text-white border border-carbon-700 text-[10px] font-bold font-sans transition inline-flex items-center gap-1"
                                            title="Create Purchase Order"
                                        >
                                            <i class="fa-solid fa-plus text-[9px]"></i>
                                            <span>Order</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Section 2: Batch Expiry Risk Schedule (Within 90 Days & Expired) -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-3xl overflow-hidden shadow-xl p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-carbon-800 pb-3 flex-wrap gap-2">
                <div>
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-calendar-xmark text-rose-400"></i>
                        <span>Batch Expiry Risk & Quarantine Schedule (FEFO Analysis)</span>
                    </h3>
                    <p class="text-[11px] text-silver-400 mt-0.5">Expiring cosmetics, skincare, and perishable items within 90 days</p>
                </div>
                <span class="text-xs font-mono font-bold text-rose-400">
                    At-Risk Valuation: Rs. {{ number_format($totalExpiredCost + $totalNearExpiryCost, 2) }}
                </span>
            </div>

            @if($expiringBatches->isEmpty())
                <div class="py-10 text-center text-xs text-silver-400 space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-carbon-800 text-emerald-400 mx-auto flex items-center justify-center text-lg">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <p class="font-bold text-white">No batches expiring within the next 90 days!</p>
                    <p class="text-[11px] text-silver-500">All current batch stock has safe shelf-life.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400 bg-carbon-850/60">
                                <th class="p-3">Product Item</th>
                                <th class="p-3 text-center">Batch Number</th>
                                <th class="p-3">Branch</th>
                                <th class="p-3 text-center">Expiry Date</th>
                                <th class="p-3 text-center">Shelf Life Left</th>
                                <th class="p-3 text-right">Quantity</th>
                                <th class="p-3 text-right">Unit Cost</th>
                                <th class="p-3 text-right">Cost At Risk</th>
                                <th class="p-3 text-center">Risk Classification</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon-800 font-mono">
                            @foreach($expiringBatches as $batch)
                                <tr class="hover:bg-carbon-850/50 transition">
                                    <td class="p-3 font-sans font-bold text-white">
                                        <div>{{ $batch->product_name }}</div>
                                        @if($batch->variation_name)
                                            <span class="text-[10px] text-silver-400 font-normal">{{ $batch->variation_name }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center text-amber-400 text-[11px] font-bold">
                                        {{ $batch->batch_number }}
                                    </td>
                                    <td class="p-3 font-sans text-silver-300 text-[11px]">{{ $batch->branch_name }}</td>
                                    <td class="p-3 text-center text-silver-300">{{ $batch->exp_date }}</td>
                                    <td class="p-3 text-center">
                                        @if($batch->is_expired)
                                            <span class="text-rose-400 font-bold text-xs">EXPIRED</span>
                                        @else
                                            <span class="font-bold text-white">{{ $batch->days_left }} Days</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right font-bold text-white">{{ number_format($batch->quantity) }} pcs</td>
                                    <td class="p-3 text-right text-silver-300">Rs. {{ number_format($batch->cost_price, 2) }}</td>
                                    <td class="p-3 text-right font-black {{ $batch->is_expired ? 'text-rose-400' : 'text-amber-400' }}">
                                        Rs. {{ number_format($batch->cost_at_risk, 2) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($batch->is_expired)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-black bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                                QUARANTINE / DO NOT SELL
                                            </span>
                                        @elseif($batch->days_left <= 30)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-rose-500/10 text-rose-300 border border-rose-500/30">
                                                URGENT PROMO (&lt;30d)
                                            </span>
                                        @elseif($batch->days_left <= 60)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                HIGH RISK (60d)
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                                MEDIUM (90d)
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
