@extends('layouts.pos')

@section('title', 'Invoice Details - ' . $sale->invoice_no)

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.index') }}" class="w-9 h-9 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-400 hover:text-white flex items-center justify-center border border-carbon-700">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-extrabold text-white font-mono">{{ $sale->invoice_no }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $sale->payment_status === 'paid' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }}">
                        {{ $sale->payment_status }}
                    </span>
                </div>
                <p class="text-xs text-silver-400">Created on {{ $sale->created_at->format('d M Y, h:i A') }} • Cashier: {{ $sale->cashier->name ?? 'Staff' }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($sale->payment_status !== 'refunded')
                <button 
                    type="button" 
                    onclick="openReturnModal()"
                    class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-red-500/30 text-rose-400 hover:text-white text-xs font-bold transition flex items-center gap-2 touch-action-btn"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Process Return / Exchange</span>
                </button>
            @endif

            <a 
                href="{{ route('pos.receipt', $sale->id) }}" 
                target="_blank"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-print"></i>
                <span>Print Receipt</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

        @if(session('status'))
            <div class="p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: Items & Calculation Breakdown (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Items Table -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
                    <div class="p-4 border-b border-carbon-800 flex items-center justify-between">
                        <h3 class="text-xs font-extrabold text-white uppercase tracking-wider">Purchased Items</h3>
                        <span class="text-xs text-silver-400 font-mono">{{ $sale->items->count() }} line items</span>
                    </div>

                    <table class="w-full text-left text-xs text-silver-300">
                        <thead class="bg-carbon-850 border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400 font-mono">
                            <tr>
                                <th class="py-2.5 px-4">Item & Variation</th>
                                <th class="py-2.5 px-4 text-center">Unit Price</th>
                                <th class="py-2.5 px-4 text-center">Quantity</th>
                                <th class="py-2.5 px-4 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon-800/60 font-mono">
                            @foreach($sale->items as $item)
                                <tr class="hover:bg-carbon-850/60 transition-colors">
                                    <td class="py-3 px-4 font-sans">
                                        <div class="font-bold text-white text-xs">{{ $item->productVariation->product->name ?? 'Product' }}</div>
                                        <div class="text-[10px] text-silver-400 mt-0.5">
                                            {{ $item->productVariation->name ?? 'Standard' }} • 
                                            <span class="font-mono text-brand-400">SKU: {{ $item->productVariation->sku ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center text-silver-300">
                                        Rs. {{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-white">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-white">
                                        Rs. {{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Calculation Footer -->
                    <div class="p-4 border-t border-carbon-800 bg-carbon-850/40 space-y-2 text-xs font-mono">
                        <div class="flex justify-between text-silver-400">
                            <span>Subtotal:</span>
                            <span class="text-white">Rs. {{ number_format($sale->subtotal, 2) }}</span>
                        </div>
                        @if($sale->discount_amount > 0)
                            <div class="flex justify-between text-rose-400">
                                <span>Discount:</span>
                                <span>- Rs. {{ number_format($sale->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        @if($sale->tax_amount > 0)
                            <div class="flex justify-between text-silver-400">
                                <span>Tax ({{ $sale->tax_rate }}%):</span>
                                <span class="text-white">Rs. {{ number_format($sale->tax_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="pt-2 border-t border-carbon-800 flex justify-between font-bold text-sm">
                            <span class="text-white">Grand Total:</span>
                            <span class="text-brand-400 font-black text-base">Rs. {{ number_format($sale->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Past Returns / Exchanges Ledger if exists -->
                @if($sale->returns && $sale->returns->count() > 0)
                    <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl p-4 space-y-3">
                        <h3 class="text-xs font-extrabold text-rose-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Return & Refund Audit History</span>
                        </h3>
                        @foreach($sale->returns as $ret)
                            <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-white">{{ $ret->return_no }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $ret->action === 'restock' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }}">
                                        Action: {{ $ret->action }}
                                    </span>
                                </div>
                                <p class="text-silver-400">Reason: {{ $ret->reason }}</p>
                                <div class="font-mono text-rose-400 font-bold">
                                    Refund Amount: Rs. {{ number_format($ret->total_refund_amount, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Right: Metadata & Payments (1 col) -->
            <div class="space-y-6">
                <!-- Customer & Store Info Card -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 shadow-xl space-y-4">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider border-b border-carbon-800 pb-3">
                        Transaction Metadata
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-[10px] text-silver-500 uppercase font-bold block">Sales Origin (Channel)</span>
                            @if($sale->sales_channel === 'online')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-blue-500/20 text-blue-300 border border-blue-500/40 mt-1">
                                    <i class="fa-solid fa-globe text-blue-400"></i> Online Self-Service
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 mt-1">
                                    <i class="fa-solid fa-cash-register text-emerald-400"></i> In-Store Register (Counter)
                                </span>
                            @endif
                        </div>

                        <div>
                            <span class="text-[10px] text-silver-500 uppercase font-bold block">Branch & Counter</span>
                            <p class="text-white font-semibold">{{ $sale->branch->name ?? 'Store' }}</p>
                            <p class="text-silver-400 font-mono text-[11px]">{{ $sale->register->name ?? 'Counter 1' }}</p>
                        </div>

                        <div>
                            <span class="text-[10px] text-silver-500 uppercase font-bold block">Customer Information</span>
                            @if($sale->customer)
                                <p class="text-white font-semibold">{{ $sale->customer->name }}</p>
                                <p class="text-silver-400 font-mono text-[11px]">{{ $sale->customer->phone }}</p>
                                <p class="text-emerald-400 font-bold text-[10px] mt-0.5">Points: {{ $sale->customer->loyalty_points }}</p>
                            @else
                                <p class="text-silver-400 italic">Walk-in Retail Customer</p>
                            @endif
                        </div>

                        <div>
                            <span class="text-[10px] text-silver-500 uppercase font-bold block">Shift & Cashier</span>
                            <p class="text-white font-semibold">{{ $sale->cashier->name ?? 'Cashier' }}</p>
                            <p class="text-silver-400 text-[11px]">Shift #{{ $sale->shift_id ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payments Tendered Card -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 shadow-xl space-y-4">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider border-b border-carbon-800 pb-3">
                        Payments Tendered
                    </h3>

                    <div class="space-y-2 font-mono text-xs">
                        @foreach($sale->payments as $pay)
                            <div class="p-2.5 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between">
                                <span class="capitalize text-silver-300 font-bold">{{ $pay->payment_method }}</span>
                                <span class="text-white font-bold">Rs. {{ number_format($pay->amount, 2) }}</span>
                            </div>
                        @endforeach

                        <div class="pt-2 flex justify-between text-silver-400">
                            <span>Change Returned:</span>
                            <span class="text-emerald-400 font-bold">Rs. {{ number_format($sale->change_amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Return / Exchange Modal -->
<div id="returnModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600/20 border border-rose-500/30 flex items-center justify-center text-rose-500">
                    <i class="fa-solid fa-rotate-left text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Process Return / Exchange</h3>
                    <p class="text-xs text-silver-400">Classify return action & refund customer</p>
                </div>
            </div>
            <button type="button" onclick="closeReturnModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('sales.return', $sale->id) }}" method="POST" class="space-y-4">
            @csrf

            <!-- Return Action (Restock vs Damaged) -->
            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Return Handling Action *</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3 rounded-xl bg-carbon-850 border border-carbon-700 cursor-pointer flex items-center gap-2">
                        <input type="radio" name="action" value="restock" checked class="text-brand-600">
                        <div>
                            <span class="text-xs font-bold text-white block">Restock Item</span>
                            <span class="text-[10px] text-silver-400">Return to active inventory</span>
                        </div>
                    </label>
                    <label class="p-3 rounded-xl bg-carbon-850 border border-carbon-700 cursor-pointer flex items-center gap-2">
                        <input type="radio" name="action" value="damaged" class="text-brand-600">
                        <div>
                            <span class="text-xs font-bold text-white block">Damaged / Defective</span>
                            <span class="text-[10px] text-silver-400">Do not return to stock</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Items Selector -->
            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Select Items and Quantity to Return *</label>
                <div class="space-y-2">
                    @foreach($sale->items as $i => $item)
                        <div class="p-2.5 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between gap-3 text-xs">
                            <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $item->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-white truncate">{{ $item->productVariation->product->name ?? '' }}</div>
                                <div class="text-[10px] text-silver-400 font-mono">Bought: {{ $item->quantity }}x @ Rs. {{ number_format($item->unit_price, 2) }}</div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <label class="text-[10px] text-silver-400 font-bold">Qty:</label>
                                <input 
                                    type="number" 
                                    name="items[{{ $i }}][quantity]" 
                                    min="1" 
                                    max="{{ $item->quantity }}" 
                                    value="1" 
                                    class="w-16 px-2 py-1 rounded bg-carbon-900 border border-carbon-700 text-white font-mono text-center font-bold"
                                >
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Reason for Return *</label>
                <input 
                    type="text" 
                    name="reason" 
                    required 
                    placeholder="e.g. Wrong shade requested, defect on zipper, size exchange" 
                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                >
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-rose-600/30 transition">
                Confirm Return & Issue Refund
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openReturnModal() {
        const modal = document.getElementById('returnModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeReturnModal() {
        const modal = document.getElementById('returnModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

