@extends('layouts.pos')

@section('title', 'Serial & IMEI Registry')

@section('content')
<main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1">
    
    <!-- Top Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-carbon-800 border border-carbon-700 text-silver-300 text-xs font-semibold mb-1">
                <i class="fa-solid fa-barcode text-brand-500"></i>
                <span>Hardware & Electronics Tracking</span>
            </div>
            <h2 class="text-2xl font-black text-white tracking-tight">Unique Serial Numbers & IMEI Registry</h2>
            <p class="text-xs sm:text-sm text-silver-400 mt-0.5">
                Individual unit tracking, stock allocation, and customer warranty claim verification.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a 
                href="{{ route('warranty.lookup') }}" 
                class="px-4 py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-200 text-xs font-bold flex items-center gap-2 transition-all"
            >
                <i class="fa-solid fa-shield-halved text-brand-400"></i>
                <span>Check Warranty</span>
            </a>
            <button 
                onclick="openAddSerialsModal()" 
                class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-extrabold shadow-lg flex items-center gap-2 transition-all touch-action-btn"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Add / Ingest IMEIs</span>
            </button>
        </div>
    </div>

    <!-- Status Alerts -->
    @if(session('status'))
        <div class="mb-5 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm flex items-center gap-3 shadow-lg">
            <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm shadow-lg">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Please review the errors below:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-md">
            <div class="text-xs text-silver-400 font-bold uppercase mb-1">Total Tracked Units</div>
            <div class="text-2xl font-black font-mono text-white">{{ number_format($totalCount) }}</div>
            <div class="text-[11px] text-silver-500 mt-0.5">In database registry</div>
        </div>

        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-md">
            <div class="text-xs text-emerald-400 font-bold uppercase mb-1">In Stock (Available)</div>
            <div class="text-2xl font-black font-mono text-emerald-400">{{ number_format($inStockCount) }}</div>
            <div class="text-[11px] text-silver-500 mt-0.5">Ready for checkout</div>
        </div>

        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-md">
            <div class="text-xs text-sky-400 font-bold uppercase mb-1">Sold to Customers</div>
            <div class="text-2xl font-black font-mono text-sky-400">{{ number_format($soldCount) }}</div>
            <div class="text-[11px] text-silver-500 mt-0.5">Dispatched on POS bills</div>
        </div>

        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-md">
            <div class="text-xs text-brand-400 font-bold uppercase mb-1">Active Warranties</div>
            <div class="text-2xl font-black font-mono text-brand-400">{{ number_format($underWarrantyCount) }}</div>
            <div class="text-[11px] text-silver-500 mt-0.5">Coverage currently active</div>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-4 mb-6 shadow-md">
        <form method="GET" action="{{ route('inventory.serials') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            
            <div class="sm:col-span-4">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-silver-500 text-xs"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search Serial #, IMEI, SKU or Invoice..." 
                        class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <div class="sm:col-span-3">
                <select name="branch_id" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-silver-200 text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Shop Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3">
                <select name="status" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-silver-200 text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Statuses</option>
                    <option value="in_stock" {{ $status === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                    <option value="sold" {{ $status === 'sold' ? 'selected' : '' }}>Sold</option>
                    <option value="returned" {{ $status === 'returned' ? 'selected' : '' }}>Returned</option>
                    <option value="defective" {{ $status === 'defective' ? 'selected' : '' }}>Defective</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition-all">
                    Filter
                </button>
                <a href="{{ route('inventory.serials') }}" class="py-2 px-3 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-400 hover:text-white text-xs transition-all">
                    <i class="fa-solid fa-rotate"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Serials Table -->
    <div class="bg-carbon-900 border border-carbon-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-silver-300">
                <thead class="bg-carbon-850/80 border-b border-carbon-800 text-silver-400 uppercase text-[10px] font-bold tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Serial / IMEI #</th>
                        <th class="px-4 py-3.5">Product & SKU</th>
                        <th class="px-4 py-3.5">Branch Location</th>
                        <th class="px-4 py-3.5">Unit Status</th>
                        <th class="px-4 py-3.5">Sale & Customer</th>
                        <th class="px-4 py-3.5">Warranty Duration</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon-800/80 font-mono text-[11px]">
                    @forelse($serials as $serial)
                    <tr class="hover:bg-carbon-850/50 transition-colors">
                        <td class="px-4 py-3.5 font-bold text-white tracking-wide">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-carbon-800 border border-carbon-700 text-brand-400">
                                <i class="fa-solid fa-microchip text-[10px]"></i>
                                <span>{{ $serial->serial_number }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3.5 font-sans">
                            <div class="font-bold text-white text-xs">
                                {{ $serial->productVariation->product->name ?? 'N/A' }}
                            </div>
                            <div class="text-[10px] text-silver-500 font-mono">
                                {{ $serial->productVariation->name }} &bull; SKU: {{ $serial->productVariation->sku }}
                            </div>
                        </td>
                        <td class="px-4 py-3.5 font-sans">
                            <span class="text-silver-300 font-medium">{{ $serial->branch->name ?? 'Default Branch' }}</span>
                        </td>
                        <td class="px-4 py-3.5">
                            @if($serial->status === 'in_stock')
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">
                                    IN STOCK
                                </span>
                            @elseif($serial->status === 'sold')
                                <span class="px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/30 text-[10px] font-bold">
                                    SOLD
                                </span>
                            @elseif($serial->status === 'defective')
                                <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30 text-[10px] font-bold">
                                    DEFECTIVE
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] font-bold">
                                    {{ strtoupper($serial->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 font-sans">
                            @if($serial->sale)
                                <a href="{{ route('sales.show', $serial->sale->id) }}" class="text-brand-400 hover:underline font-bold font-mono">
                                    {{ $serial->sale->invoice_no }}
                                </a>
                                <div class="text-[10px] text-silver-400">
                                    {{ $serial->sale->customer->name ?? 'Walk-in' }} &bull; {{ $serial->sold_at ? $serial->sold_at->format('d/m/Y') : '' }}
                                </div>
                            @else
                                <span class="text-silver-600 italic">Not sold yet</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 font-sans">
                            @if($serial->warranty_months > 0)
                                <div class="font-bold text-xs text-white">
                                    {{ $serial->warranty_months }} Months
                                </div>
                                @if($serial->status === 'sold' && $serial->warranty_expires_at)
                                    @if($serial->is_under_warranty)
                                        <span class="text-[10px] text-emerald-400 flex items-center gap-1 font-mono">
                                            <i class="fa-solid fa-circle-check text-[9px]"></i>
                                            <span>Active till {{ $serial->warranty_expires_at->format('d/m/Y') }}</span>
                                        </span>
                                    @else
                                        <span class="text-[10px] text-rose-400 flex items-center gap-1 font-mono">
                                            <i class="fa-solid fa-circle-xmark text-[9px]"></i>
                                            <span>Expired {{ $serial->warranty_expires_at->format('d/m/Y') }}</span>
                                        </span>
                                    @endif
                                @else
                                    <span class="text-[10px] text-silver-500">Starts on purchase</span>
                                @endif
                            @else
                                <span class="text-silver-600 text-[10px]">No Warranty</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right font-sans">
                            <a 
                                href="{{ route('warranty.lookup', ['serial' => $serial->serial_number]) }}" 
                                class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white text-[11px] font-bold border border-carbon-700 transition-all inline-flex items-center gap-1"
                            >
                                <i class="fa-solid fa-magnifying-glass text-[9px] text-brand-400"></i>
                                <span>Lookup</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-silver-500 font-sans">
                            <i class="fa-solid fa-barcode text-2xl text-silver-600 mb-2 block"></i>
                            No serial or IMEI numbers found matching the filter criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($serials->hasPages())
        <div class="p-4 border-t border-carbon-800">
            {{ $serials->links() }}
        </div>
        @endif
    </div>

</main>

<!-- Ingest / Add Serial Numbers Modal -->
<div id="addSerialsModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-carbon-900 border border-carbon-750 rounded-3xl p-6 shadow-2xl overflow-hidden relative">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 mb-4">
            <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-brand-500"></i>
                <span>Ingest Serial / IMEI Numbers into Stock</span>
            </h3>
            <button onclick="closeAddSerialsModal()" class="text-silver-500 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('inventory.serials.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Branch Location *</label>
                <select name="branch_id" required class="w-full px-3 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ (auth()->user()->branch_id == $b->id) ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Product Variation *</label>
                <select name="product_variation_id" required class="w-full px-3 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="">Select Product Variation</option>
                    @foreach($variations as $var)
                        <option value="{{ $var->id }}">
                            {{ $var->product->name ?? '' }} - {{ $var->name }} (SKU: {{ $var->sku }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">
                    Serial / IMEI Numbers * (Paste multiple, 1 per line)
                </label>
                <textarea 
                    name="serials_text" 
                    rows="6" 
                    required 
                    placeholder="358941092145892&#10;358941092145893&#10;358941092145894" 
                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono text-xs focus:outline-none focus:border-brand-500 placeholder-silver-600"
                ></textarea>
                <p class="text-[10px] text-silver-500 mt-1">
                    Tip: You can use a barcode/IMEI scanner to directly scan units sequentially into this box.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Unit Cost (Rs.)</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="purchase_cost" 
                        placeholder="Leave blank for default" 
                        class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Internal Notes</label>
                    <input 
                        type="text" 
                        name="notes" 
                        placeholder="e.g. Batch from Abans" 
                        class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-carbon-800">
                <button type="button" onclick="closeAddSerialsModal()" class="px-4 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-extrabold shadow-lg">
                    Add Units to Stock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddSerialsModal() {
        document.getElementById('addSerialsModal').classList.remove('hidden');
    }
    function closeAddSerialsModal() {
        document.getElementById('addSerialsModal').classList.add('hidden');
    }
</script>
@endsection

