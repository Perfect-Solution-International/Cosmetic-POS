@extends('layouts.pos')

@section('title', 'Serial & Warranty Lookup')

@section('content')
<main class="max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-carbon-800 border border-carbon-700 text-silver-300 text-xs font-semibold mb-1">
                <i class="fa-solid fa-shield-halved text-brand-500"></i>
                <span>Customer Care & Warranty Service</span>
            </div>
            <h2 class="text-2xl font-black text-white tracking-tight">IMEI & Warranty Lookup</h2>
            <p class="text-xs sm:text-sm text-silver-400 mt-0.5">
                Verify customer purchase date, warranty expiration, and validity for RMA repairs.
            </p>
        </div>

        <a href="{{ route('inventory.serials') }}" class="px-4 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 text-xs font-bold border border-carbon-700 transition-all">
            &larr; Back to Registry
        </a>
    </div>

    <!-- Search Input Box -->
    <div class="bg-carbon-900 border border-carbon-800 rounded-3xl p-6 shadow-2xl mb-8">
        <form method="GET" action="{{ route('warranty.lookup') }}">
            <label class="text-xs font-bold text-silver-300 uppercase block mb-2">Scan or Enter Serial Number / IMEI</label>
            <div class="flex gap-2.5">
                <div class="relative flex-1">
                    <i class="fa-solid fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-silver-500 text-base"></i>
                    <input 
                        type="text" 
                        name="serial" 
                        value="{{ $serialNumber }}" 
                        autofocus
                        required
                        placeholder="e.g. 358941092145892 or SN-LAP-0921" 
                        class="w-full pl-11 pr-4 py-3.5 rounded-2xl bg-carbon-850 border-2 border-carbon-700 text-white font-mono text-base font-bold placeholder-silver-600 focus:outline-none focus:border-brand-500 shadow-inner"
                    >
                </div>
                <button type="submit" class="px-7 py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-sm shadow-lg flex items-center gap-2 transition-all touch-action-btn shrink-0">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Verify</span>
                </button>
            </div>
        </form>
    </div>

    @if($serialNumber !== '')
        @if($record)
            <!-- Warranty Details Card -->
            <div class="bg-carbon-900 border border-carbon-800 rounded-3xl p-6 sm:p-8 shadow-2xl overflow-hidden relative">
                
                <!-- Status Banner -->
                @if($record->status === 'sold')
                    @if($record->is_under_warranty)
                        <div class="p-4 rounded-2xl bg-emerald-500/10 border-2 border-emerald-500/30 text-emerald-300 flex items-center justify-between mb-6 shadow-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-shield-check text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-extrabold text-white uppercase tracking-wider">Active Warranty Valid</div>
                                    <div class="text-xs text-emerald-300">
                                        Coverage is active until <strong class="text-white">{{ $record->warranty_expires_at->format('F d, Y') }}</strong> ({{ now()->diffInDays($record->warranty_expires_at) }} days remaining)
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-emerald-500 text-black text-xs font-black uppercase tracking-wider shadow">
                                APPROVED
                            </span>
                        </div>
                    @else
                        <div class="p-4 rounded-2xl bg-rose-500/10 border-2 border-rose-500/30 text-rose-300 flex items-center justify-between mb-6 shadow-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-shield-xmark text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-extrabold text-white uppercase tracking-wider">Warranty Expired</div>
                                    <div class="text-xs text-rose-300">
                                        Expired on <strong class="text-white">{{ $record->warranty_expires_at ? $record->warranty_expires_at->format('F d, Y') : 'N/A' }}</strong>
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-rose-500 text-white text-xs font-black uppercase tracking-wider shadow">
                                EXPIRED
                            </span>
                        </div>
                    @endif
                @else
                    <div class="p-4 rounded-2xl bg-amber-500/10 border-2 border-amber-500/30 text-amber-300 flex items-center justify-between mb-6 shadow-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-box text-xl"></i>
                            </div>
                            <div>
                                <div class="text-sm font-extrabold text-white uppercase tracking-wider">Unit In Store Stock</div>
                                <div class="text-xs text-amber-300">
                                    This unit has not been sold yet (Branch: {{ $record->branch->name ?? 'N/A' }}). Warranty will activate upon customer checkout.
                                </div>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-amber-500 text-black text-xs font-black uppercase tracking-wider">
                            IN STOCK
                        </span>
                    </div>
                @endif

                <!-- Device & Purchase Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                    
                    <!-- Left: Device Details -->
                    <div class="space-y-3.5 bg-carbon-850 p-5 rounded-2xl border border-carbon-800">
                        <h4 class="text-xs font-extrabold text-silver-300 uppercase tracking-wider border-b border-carbon-700/60 pb-2">
                            Product Specifications
                        </h4>
                        
                        <div class="flex justify-between">
                            <span class="text-silver-400">Product Name:</span>
                            <span class="font-bold text-white text-right">{{ $record->productVariation->product->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-silver-400">Variation / Model:</span>
                            <span class="font-bold text-white">{{ $record->productVariation->name ?? 'Standard' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-silver-400">Product SKU:</span>
                            <span class="font-mono text-brand-400 font-bold">{{ $record->productVariation->sku }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-silver-400">Serial / IMEI:</span>
                            <span class="font-mono text-white font-black">{{ $record->serial_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-silver-400">Warranty Term:</span>
                            <span class="font-bold text-white">{{ $record->warranty_months }} Months Standard</span>
                        </div>
                    </div>

                    <!-- Right: Sale & Customer Details -->
                    <div class="space-y-3.5 bg-carbon-850 p-5 rounded-2xl border border-carbon-800">
                        <h4 class="text-xs font-extrabold text-silver-300 uppercase tracking-wider border-b border-carbon-700/60 pb-2">
                            Sales & Customer Record
                        </h4>

                        @if($record->sale)
                        <div class="flex justify-between">
                            <span class="text-silver-400">Invoice Number:</span>
                            <a href="{{ route('sales.show', $record->sale->id) }}" class="font-mono text-brand-400 hover:underline font-bold">
                                {{ $record->sale->invoice_no }}
                            </a>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-silver-400">Purchase Date:</span>
                            <span class="font-mono text-white font-bold">{{ $record->sold_at ? $record->sold_at->format('Y-m-d H:i') : 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-silver-400">Customer Name:</span>
                            <span class="font-bold text-white">{{ $record->sale->customer->name ?? 'Walk-in Customer' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-silver-400">Customer Mobile:</span>
                            <span class="font-mono text-white">{{ $record->sale->customer->phone ?? 'Not recorded' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-silver-400">Issuing Branch:</span>
                            <span class="font-medium text-white">{{ $record->branch->name ?? 'N/A' }}</span>
                        </div>
                        @else
                        <div class="py-6 text-center text-silver-500 italic">
                            No customer sale record associated with this serial number yet.
                        </div>
                        @endif

                    </div>

                </div>

            </div>
        @else
            <!-- Not Found Card -->
            <div class="bg-carbon-900 border border-carbon-800 rounded-3xl p-8 text-center shadow-xl">
                <div class="w-16 h-16 rounded-full bg-rose-500/10 text-rose-500 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                </div>
                <h3 class="text-lg font-black text-white">Serial / IMEI Not Found</h3>
                <p class="text-xs text-silver-400 mt-1 max-w-md mx-auto">
                    The identifier <code class="text-brand-400 font-mono font-bold">{{ $serialNumber }}</code> is not registered in the system database. Please verify the number on the device label or receipt.
                </p>
            </div>
        @endif
    @endif

</main>
@endsection

