@extends('layouts.pos')

@section('title', 'Stock Take ' . $stockTake->reference_no)

@section('content')
<div class="h-full flex flex-col justify-between overflow-hidden bg-carbon-950">
    <!-- Header -->
    <header class="p-4 sm:p-5 border-b border-carbon-800 bg-carbon-900/40 backdrop-blur-md flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.stock_takes.index') }}" class="w-9 h-9 rounded-xl bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-base sm:text-lg font-black text-white tracking-tight flex items-center gap-2">
                    {{ $stockTake->reference_no }}
                    @if($stockTake->status === 'in_progress')
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                            Scanning Active
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                            Reconciled &amp; Closed
                        </span>
                    @endif
                </h1>
                <p class="text-xs text-silver-400">Branch: {{ $stockTake->branch->name }} &bull; Started: {{ $stockTake->started_at->format('M d, Y H:i') }} &bull; Lead: {{ $stockTake->user->name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('inventory.stock_takes.print', $stockTake->id) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                <span>Discrepancy Report</span>
            </a>

            @if($stockTake->status === 'in_progress')
                <form method="POST" action="{{ route('inventory.stock_takes.complete', $stockTake->id) }}" onsubmit="return confirm('Are you sure you want to finalize this stock take? Current physical counts will immediately overwrite the actual branch inventory in the system!');" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black transition flex items-center gap-2 shadow-lg shadow-emerald-600/30">
                        <i class="fa-solid fa-check-double"></i>
                        <span>Reconcile &amp; Update Inventory</span>
                    </button>
                </form>
            @endif
        </div>
    </header>

    <!-- Barcode Scanner Bar (Active when in_progress) -->
    @if($stockTake->status === 'in_progress')
        <div class="p-4 bg-carbon-900 border-b border-carbon-800 shrink-0">
            <div class="max-w-4xl mx-auto flex flex-wrap sm:flex-nowrap items-center gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-barcode absolute left-3.5 top-1/2 -translate-y-1/2 text-amber-400 text-base"></i>
                    <input 
                        type="text" 
                        id="barcodeScannerInput" 
                        placeholder="Scan item Barcode or enter SKU and press Enter..." 
                        autofocus
                        autocomplete="off"
                        class="w-full pl-11 pr-24 py-3 rounded-2xl bg-carbon-800 border-2 border-amber-500/50 text-white font-mono text-sm placeholder:text-silver-500 focus:outline-none focus:border-amber-400 focus:ring-4 focus:ring-amber-500/10 shadow-lg shadow-black/40"
                    >
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                        <span class="px-2 py-1 rounded-lg bg-carbon-700 text-[10px] font-mono text-silver-300">Scanner Ready</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center bg-carbon-800 rounded-xl p-1 border border-carbon-700">
                        <button type="button" onclick="setCountStep(1)" id="btnStep1" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 text-black">
                            +1 Mode
                        </button>
                        <button type="button" onclick="setCountStep(10)" id="btnStep10" class="px-3 py-1.5 rounded-lg text-xs font-bold text-silver-400 hover:text-white">
                            +10 Box
                        </button>
                    </div>

                    <button type="button" onclick="processScanInput()" class="px-5 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-black font-black text-xs transition flex items-center gap-1.5 shrink-0 shadow-lg shadow-amber-500/20">
                        <i class="fa-solid fa-plus"></i>
                        <span>Count</span>
                    </button>
                </div>
            </div>

            <!-- Live scan feedback pill -->
            <div id="scanFeedback" class="max-w-4xl mx-auto mt-2 hidden text-xs font-bold px-3 py-1.5 rounded-xl border transition-all"></div>
        </div>
    @endif

    <!-- Metrics Row -->
    <div class="p-4 sm:p-5 pb-0 grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0">
        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-silver-400 uppercase tracking-wider">System Target Qty</div>
                <div class="text-xl font-black text-white mt-1">{{ number_format($stats['system_total_qty']) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-carbon-800 text-silver-300 flex items-center justify-center text-sm">
                <i class="fa-solid fa-cubes"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Physical Verified Qty</div>
                <div class="text-xl font-black text-amber-300 mt-1" id="statPhysicalQty">{{ number_format($stats['physical_total_qty']) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider {{ $stats['variance_total_qty'] < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                    Variance (Discrepancy)
                </div>
                <div class="text-xl font-black mt-1 {{ $stats['variance_total_qty'] < 0 ? 'text-rose-400' : 'text-emerald-400' }}" id="statVarianceQty">
                    {{ $stats['variance_total_qty'] > 0 ? '+' : '' }}{{ number_format($stats['variance_total_qty']) }}
                </div>
            </div>
            <div class="w-9 h-9 rounded-xl {{ $stats['variance_total_qty'] < 0 ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' }} flex items-center justify-center text-sm">
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider {{ $stats['variance_total_cost'] < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                    Variance Valuation (Rs.)
                </div>
                <div class="text-xl font-black mt-1 {{ $stats['variance_total_cost'] < 0 ? 'text-rose-400' : 'text-emerald-400' }}" id="statVarianceCost">
                    Rs. {{ number_format($stats['variance_total_cost'], 2) }}
                </div>
            </div>
            <div class="w-9 h-9 rounded-xl {{ $stats['variance_total_cost'] < 0 ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' }} flex items-center justify-center text-sm">
                <i class="fa-solid fa-coins"></i>
            </div>
        </div>
    </div>

    <!-- Items Table & Filters -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-5">
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <!-- Filter Bar -->
            <form method="GET" action="{{ route('inventory.stock_takes.show', $stockTake->id) }}" class="p-3.5 border-b border-carbon-800 flex flex-wrap items-center justify-between gap-3 bg-carbon-900/60">
                <div class="relative w-full max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-silver-500 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter by Product, SKU, or Barcode..." class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-carbon-800 border border-carbon-700/80 text-white text-xs placeholder:text-silver-500 focus:outline-none focus:border-amber-500">
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('inventory.stock_takes.show', [$stockTake->id, 'filter' => '']) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ !request('filter') ? 'bg-amber-500 text-black' : 'bg-carbon-800 text-silver-300 hover:text-white' }}">
                        All Items ({{ $stats['items_count'] }})
                    </a>
                    <a href="{{ route('inventory.stock_takes.show', [$stockTake->id, 'filter' => 'variance_only']) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ request('filter') === 'variance_only' ? 'bg-rose-600 text-white' : 'bg-carbon-800 text-rose-400 hover:text-white' }}">
                        Discrepancies Only ({{ $stats['deficit_items_count'] + $stats['surplus_items_count'] }})
                    </a>
                    <a href="{{ route('inventory.stock_takes.show', [$stockTake->id, 'filter' => 'counted_only']) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ request('filter') === 'counted_only' ? 'bg-emerald-600 text-white' : 'bg-carbon-800 text-emerald-400 hover:text-white' }}">
                        Counted (>0)
                    </a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-carbon-800 bg-carbon-850/50 text-silver-400 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Item &amp; Barcode</th>
                            <th class="py-3 px-4 text-center">Unit Cost</th>
                            <th class="py-3 px-4 text-center">System Target</th>
                            <th class="py-3 px-4 text-center">Physical Count</th>
                            <th class="py-3 px-4 text-center">Variance</th>
                            <th class="py-3 px-4 text-right">Variance Value</th>
                            @if($stockTake->status === 'in_progress')
                                <th class="py-3 px-4 text-center">Adjust Count</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60" id="stockTakeItemsTbody">
                        @forelse($items as $item)
                            <tr id="row_{{ $item->product_variation_id }}" class="hover:bg-carbon-800/40 transition {{ $item->variance_qty !== 0 ? 'bg-amber-500/[0.02]' : '' }}">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white text-sm">{{ $item->productVariation->product->name }}</div>
                                    <div class="text-[11px] text-silver-400 font-mono">
                                        {{ $item->productVariation->name }} &bull; SKU: <span class="text-white">{{ $item->productVariation->sku }}</span>
                                        @if($item->productVariation->barcode)
                                            &bull; Barcode: <span class="text-amber-400">{{ $item->productVariation->barcode }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-silver-400">Rs. {{ number_format($item->unit_cost, 2) }}</td>
                                <td class="py-3 px-4 text-center font-mono text-silver-300 font-bold">{{ number_format($item->system_qty) }}</td>
                                <td class="py-3 px-4 text-center font-mono font-black text-white text-sm row-physical-qty">
                                    {{ number_format($item->physical_qty) }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-black row-variance-qty {{ $item->variance_qty < 0 ? 'text-rose-400' : ($item->variance_qty > 0 ? 'text-emerald-400' : 'text-silver-500') }}">
                                    {{ $item->variance_qty > 0 ? '+' : '' }}{{ number_format($item->variance_qty) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold row-variance-cost {{ $item->variance_cost < 0 ? 'text-rose-400' : ($item->variance_cost > 0 ? 'text-emerald-400' : 'text-silver-400') }}">
                                    Rs. {{ number_format($item->variance_cost, 2) }}
                                </td>
                                @if($stockTake->status === 'in_progress')
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" onclick="quickAdjust('{{ $item->productVariation->sku }}', 1)" class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-emerald-600 hover:text-white text-emerald-400 font-bold flex items-center justify-center transition" title="+1">
                                                +1
                                            </button>
                                            <button type="button" onclick="quickAdjust('{{ $item->productVariation->sku }}', -1)" class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-rose-600 hover:text-white text-rose-400 font-bold flex items-center justify-center transition" title="-1">
                                                -1
                                            </button>
                                            <button type="button" onclick="promptExactCount('{{ $item->productVariation->sku }}', {{ $item->physical_qty }})" class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-amber-600 hover:text-black text-amber-400 font-bold flex items-center justify-center transition" title="Set Exact Qty">
                                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-silver-500">
                                    No items match the selected filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="p-3.5 border-t border-carbon-800">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    let currentStep = 1;

    function setCountStep(step) {
        currentStep = step;
        const btn1 = document.getElementById('btnStep1');
        const btn10 = document.getElementById('btnStep10');
        if (step === 1) {
            btn1.className = 'px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 text-black';
            btn10.className = 'px-3 py-1.5 rounded-lg text-xs font-bold text-silver-400 hover:text-white';
        } else {
            btn10.className = 'px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 text-black';
            btn1.className = 'px-3 py-1.5 rounded-lg text-xs font-bold text-silver-400 hover:text-white';
        }
    }

    const scannerInput = document.getElementById('barcodeScannerInput');
    if (scannerInput) {
        scannerInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                processScanInput();
            }
        });
    }

    function processScanInput() {
        if (!scannerInput) return;
        const val = scannerInput.value.trim();
        if (!val) return;

        quickAdjust(val, currentStep);
        scannerInput.value = '';
        scannerInput.focus();
    }

    function quickAdjust(barcodeOrSku, qty, setExact = false) {
        const feedback = document.getElementById('scanFeedback');

        fetch("{{ route('inventory.stock_takes.scan', $stockTake->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                barcode: barcodeOrSku,
                quantity: qty,
                set_exact: setExact
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update feedback
                if (feedback) {
                    feedback.className = 'max-w-4xl mx-auto mt-2 text-xs font-bold px-3 py-1.5 rounded-xl border bg-emerald-500/10 border-emerald-500/30 text-emerald-300 block';
                    feedback.textContent = `✓ Counted ${data.item.name} (${data.item.sku}): Physical = ${data.item.physical_qty} (Variance: ${data.item.variance_qty})`;
                }

                // Update Table Row if present
                const row = document.getElementById('row_' + data.item.id);
                if (row) {
                    row.querySelector('.row-physical-qty').textContent = data.item.physical_qty;
                    const varEl = row.querySelector('.row-variance-qty');
                    varEl.textContent = (data.item.variance_qty > 0 ? '+' : '') + data.item.variance_qty;
                    varEl.className = 'py-3 px-4 text-center font-mono font-black row-variance-qty ' + 
                        (data.item.variance_qty < 0 ? 'text-rose-400' : (data.item.variance_qty > 0 ? 'text-emerald-400' : 'text-silver-500'));
                    
                    const costEl = row.querySelector('.row-variance-cost');
                    costEl.textContent = 'Rs. ' + data.item.variance_cost;
                    costEl.className = 'py-3 px-4 text-right font-mono font-bold row-variance-cost ' + 
                        (parseFloat(data.item.variance_cost) < 0 ? 'text-rose-400' : (parseFloat(data.item.variance_cost) > 0 ? 'text-emerald-400' : 'text-silver-400'));
                    
                    // Flash highlight
                    row.classList.add('bg-amber-500/20');
                    setTimeout(() => row.classList.remove('bg-amber-500/20'), 800);
                }

                // Update Totals
                if (data.session_totals) {
                    document.getElementById('statPhysicalQty').textContent = data.session_totals.physical_qty;
                    const statVar = document.getElementById('statVarianceQty');
                    statVar.textContent = (data.session_totals.variance_qty > 0 ? '+' : '') + data.session_totals.variance_qty;
                    statVar.className = 'text-xl font-black mt-1 ' + (data.session_totals.variance_qty < 0 ? 'text-rose-400' : 'text-emerald-400');

                    const statCost = document.getElementById('statVarianceCost');
                    statCost.textContent = 'Rs. ' + data.session_totals.variance_cost;
                    statCost.className = 'text-xl font-black mt-1 ' + (parseFloat(data.session_totals.variance_cost) < 0 ? 'text-rose-400' : 'text-emerald-400');
                }
            } else {
                if (feedback) {
                    feedback.className = 'max-w-4xl mx-auto mt-2 text-xs font-bold px-3 py-1.5 rounded-xl border bg-rose-500/10 border-rose-500/30 text-rose-300 block';
                    feedback.textContent = '✕ ' + (data.message || 'Item scan failed.');
                }
            }
        })
        .catch(err => {
            if (feedback) {
                feedback.className = 'max-w-4xl mx-auto mt-2 text-xs font-bold px-3 py-1.5 rounded-xl border bg-rose-500/10 border-rose-500/30 text-rose-300 block';
                feedback.textContent = '✕ Network error while updating count.';
            }
        });
    }

    function promptExactCount(sku, currentVal) {
        const val = prompt('Enter exact physical verified count for ' + sku + ':', currentVal);
        if (val !== null && val !== '' && !isNaN(val)) {
            quickAdjust(sku, parseInt(val), true);
        }
    }
</script>
@endsection

