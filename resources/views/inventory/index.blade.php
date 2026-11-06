@extends('layouts.pos')

@section('title', 'Branch Stock & Valuation')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Top Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-warehouse text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Branch Inventory & Stock Valuation</h2>
                <p class="text-xs text-silver-400">Real-time multi-location stocks, purchase cost vs retail valuation</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button 
                type="button" 
                onclick="openAdjustmentModal()"
                class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-scale-balanced text-amber-400"></i>
                <span>Stock Adjustment</span>
            </button>
            <a 
                href="{{ route('inventory.transfers') }}" 
                class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-bold shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-truck-arrow-right"></i>
                <span>Inter-Branch Transfers</span>
            </a>
        </div>
    </div>

    <!-- Valuation Metrics Cards -->
    <div class="bg-carbon-900/60 border-b border-carbon-800 px-4 sm:px-6 py-4 shrink-0">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            
            <!-- 1. Total Units in Branch -->
            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div>
                    <span class="text-[10px] text-silver-400 font-bold uppercase block">Total Units In Stock</span>
                    <span class="text-base sm:text-lg font-black text-white font-mono">{{ number_format($totalItemsCount) }}</span>
                </div>
            </div>

            <!-- 2. Total Cost Valuation (COGS) -->
            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div>
                    <span class="text-[10px] text-silver-400 font-bold uppercase block">Cost Valuation (Purchase)</span>
                    <span class="text-base sm:text-lg font-black text-amber-300 font-mono">Rs. {{ number_format($totalCostValuation, 2) }}</span>
                </div>
            </div>

            <!-- 3. Total Retail Valuation -->
            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <span class="text-[10px] text-silver-400 font-bold uppercase block">Retail Valuation (Selling)</span>
                    <span class="text-base sm:text-lg font-black text-emerald-400 font-mono">Rs. {{ number_format($totalRetailValuation, 2) }}</span>
                </div>
            </div>

            <!-- 4. Potential Gross Margin -->
            @php
                $potentialGrossProfit = $totalRetailValuation - $totalCostValuation;
                $potentialMarginPct = $totalRetailValuation > 0 ? ($potentialGrossProfit / $totalRetailValuation) * 100 : 0;
            @endphp
            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-400">
                    <i class="fa-solid fa-percent"></i>
                </div>
                <div>
                    <span class="text-[10px] text-silver-400 font-bold uppercase block">Projected Margin</span>
                    <span class="text-base sm:text-lg font-black text-brand-400 font-mono">{{ number_format($potentialMarginPct, 1) }}%</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-carbon-900/40 border-b border-carbon-800 px-4 sm:px-6 py-3 shrink-0">
        <form action="{{ route('inventory.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <!-- Branch Filter -->
            <div class="w-48 sm:w-56">
                <select name="branch_id" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search input -->
            <div class="flex-1 min-w-[180px]">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-silver-400 text-xs"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search SKU, barcode, or product name..."
                        class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <!-- Low stock checkbox -->
            <label class="flex items-center gap-2 cursor-pointer bg-carbon-850 px-3 py-1.5 rounded-xl border border-carbon-700 text-xs">
                <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()" class="rounded text-brand-600 bg-carbon-800">
                <span class="text-silver-300 font-medium">Reorder Alerts Only</span>
            </label>

            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-white text-xs font-semibold border border-carbon-700">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'low_stock']))
                <a href="{{ route('inventory.index', ['branch_id' => $selectedBranchId]) }}" class="text-xs text-rose-400 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Stock Ledger Table -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">

        @if(session('status'))
            <div class="p-3 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Item & Variation</th>
                            <th class="py-3 px-4">SKU / Barcode</th>
                            <th class="py-3 px-4">Batch & Expiry</th>
                            <th class="py-3 px-4 text-center">Unit Cost</th>
                            <th class="py-3 px-4 text-center">Selling Price</th>
                            <th class="py-3 px-4 text-center">In Stock Qty</th>
                            <th class="py-3 px-4 text-right">Cost Value</th>
                            <th class="py-3 px-4 text-right">Retail Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($stocks as $stock)
                            @php
                                $var = $stock->productVariation;
                                $prod = $var?->product;
                                $cost = $var?->purchase_cost ?? 0;
                                $price = $var?->selling_price ?? 0;
                                $costVal = $stock->quantity * $cost;
                                $retailVal = $stock->quantity * $price;
                                $isLow = $stock->quantity <= ($stock->reorder_level ?? 5);
                            @endphp
                            <tr class="hover:bg-carbon-850/60 transition-colors">
                                <!-- Item & Variation -->
                                <td class="py-3.5 px-4 font-sans">
                                    <div class="font-bold text-white text-xs">{{ $prod->name ?? 'Product' }}</div>
                                    <div class="text-[10px] text-silver-400 flex items-center gap-1.5 mt-0.5">
                                        <span class="text-brand-400 font-semibold">{{ $var->name ?? 'Standard' }}</span>
                                        <span>•</span>
                                        <span class="text-silver-500">{{ $prod->category->name ?? 'General' }}</span>
                                    </div>
                                </td>

                                <!-- SKU & Barcode -->
                                <td class="py-3.5 px-4">
                                    <div class="text-white font-bold">{{ $var->sku ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-silver-500">{{ $var->barcode ?? 'No Barcode' }}</div>
                                </td>

                                <!-- Batch & Expiry -->
                                <td class="py-3.5 px-4 font-sans">
                                    @if($stock->batch_number)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-carbon-800 text-silver-300 border border-carbon-700">
                                            {{ $stock->batch_number }}
                                        </span>
                                        @if($stock->exp_date)
                                            <span class="block text-[10px] text-silver-400 mt-1">Exp: {{ \Carbon\Carbon::parse($stock->exp_date)->format('M Y') }}</span>
                                        @endif
                                    @else
                                        <span class="text-silver-600 text-[10px]">-</span>
                                    @endif
                                </td>

                                <!-- Unit Cost -->
                                <td class="py-3.5 px-4 text-center text-silver-400">
                                    Rs. {{ number_format($cost, 2) }}
                                </td>

                                <!-- Selling Price -->
                                <td class="py-3.5 px-4 text-center text-white font-bold">
                                    Rs. {{ number_format($price, 2) }}
                                </td>

                                <!-- Quantity & Reorder Status -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $stock->quantity <= 0 ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : ($isLow ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30') }}">
                                        {{ $stock->quantity }}
                                    </span>
                                </td>

                                <!-- Cost Value -->
                                <td class="py-3.5 px-4 text-right text-amber-300 font-semibold">
                                    Rs. {{ number_format($costVal, 2) }}
                                </td>

                                <!-- Retail Value -->
                                <td class="py-3.5 px-4 text-right text-emerald-400 font-bold">
                                    Rs. {{ number_format($retailVal, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-boxes-stacked text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No stock entries found for this branch</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($stocks->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $stocks->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Stock Adjustment Modal -->
<div id="adjustmentModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-600/20 border border-amber-500/30 flex items-center justify-center text-amber-500">
                    <i class="fa-solid fa-scale-balanced text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Stock Adjustment</h3>
                    <p class="text-xs text-silver-400">Record stock addition, damage, loss or audit correction</p>
                </div>
            </div>
            <button type="button" onclick="closeAdjustmentModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('inventory.adjustments.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Product Variation *</label>
                <select name="product_variation_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                    <option value="">Select Item to adjust</option>
                    @php
                        $variationList = (isset($allVariations) && $allVariations->isNotEmpty()) 
                            ? $allVariations 
                            : $stocks->pluck('productVariation')->filter();
                    @endphp
                    @foreach($variationList as $var)
                        @php
                            $curStock = $var->stocks ? $var->stocks->where('branch_id', $selectedBranchId)->sum('quantity') : 0;
                        @endphp
                        <option value="{{ $var->id }}">
                            {{ $var->product->name ?? '' }} - {{ $var->name }} (SKU: {{ $var->sku }} | Current: {{ $curStock }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Adjustment Type *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                        <option value="addition">Addition (+) Found Stock</option>
                        <option value="subtraction">Subtraction (-) Damaged / Expired</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Adjustment Quantity *</label>
                    <input 
                        type="number" 
                        name="quantity" 
                        min="1" 
                        value="1" 
                        required 
                        class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Reason / Justification *</label>
                <input 
                    type="text" 
                    name="reason" 
                    placeholder="e.g. Broken in transit, tester usage, inventory count correction" 
                    required 
                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                >
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-amber-600/30 transition">
                Apply Stock Adjustment
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openAdjustmentModal() {
        const modal = document.getElementById('adjustmentModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAdjustmentModal() {
        const modal = document.getElementById('adjustmentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

