@extends('layouts.pos')

@section('title', 'PO ' . $po->po_number)

@section('content')
<div class="h-full flex flex-col overflow-y-auto bg-carbon-950 p-4 sm:p-6 space-y-5">

    <!-- Top Breadcrumb & Status Action Bar -->
    <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 shadow-xl">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('procurement.po') }}" class="w-9 h-9 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left text-xs"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-lg sm:text-xl font-black text-white font-mono">{{ $po->po_number }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $po->status_badge_class }}">
                        {{ $po->status_label }}
                    </span>
                </div>
                <p class="text-xs text-silver-400 mt-0.5">Created on {{ $po->order_date ? $po->order_date->format('M d, Y') : '-' }} by {{ $po->creator->name ?? 'Staff' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Print Official PO -->
            <a 
                href="{{ route('procurement.po.print', $po->id) }}" 
                target="_blank" 
                class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white text-xs font-bold transition flex items-center gap-2"
            >
                <i class="fa-solid fa-print text-brand-400"></i>
                <span>Print Official PO</span>
            </a>

            <!-- Receive Goods via GRN -->
            @if(in_array($po->status, ['approved', 'partially_received']))
                <a 
                    href="{{ route('procurement.grn') }}?po_id={{ $po->id }}" 
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition flex items-center gap-2"
                >
                    <i class="fa-solid fa-boxes-packing"></i>
                    <span>Receive Stock (GRN)</span>
                </a>
            @endif

            <!-- Multi-Stage Workflow Actions -->
            @if($po->status === 'draft')
                <form action="{{ route('procurement.po.status', $po->id) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="submitted">
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Submit for Approval</span>
                    </button>
                </form>
            @endif

            @if(in_array($po->status, ['draft', 'submitted']) && (auth()->user()?->isManager() || auth()->user()?->isAdmin()))
                <form action="{{ route('procurement.po.status', $po->id) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Approve Order</span>
                    </button>
                </form>
            @endif

            @if(!in_array($po->status, ['completed', 'cancelled']) && (auth()->user()?->isManager() || auth()->user()?->isAdmin()))
                <form action="{{ route('procurement.po.status', $po->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this purchase order?');" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="px-3 py-2 rounded-xl bg-rose-950/60 hover:bg-rose-900 border border-rose-800 text-rose-300 hover:text-white text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-ban"></i>
                        <span>Cancel PO</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2 shadow-lg">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- 4 Key Info Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Supplier Card -->
        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-md">
            <span class="text-[10px] font-bold uppercase tracking-wider text-silver-400 flex items-center gap-1.5 mb-2">
                <i class="fa-solid fa-truck text-brand-400"></i>
                <span>Vendor / Supplier</span>
            </span>
            <div class="text-sm font-bold text-white">{{ $po->supplier->name }}</div>
            <div class="text-xs text-silver-400 mt-1 font-mono">{{ $po->supplier->code }} &bull; {{ $po->supplier->phone ?? 'No phone' }}</div>
            <div class="text-[11px] text-silver-500 mt-1 truncate">{{ $po->supplier->address ?? 'No address registered' }}</div>
        </div>

        <!-- Destination Branch -->
        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-md">
            <span class="text-[10px] font-bold uppercase tracking-wider text-silver-400 flex items-center gap-1.5 mb-2">
                <i class="fa-solid fa-store text-silver-300"></i>
                <span>Receiving Branch</span>
            </span>
            <div class="text-sm font-bold text-white">{{ $po->branch->name }}</div>
            <div class="text-xs text-silver-400 mt-1 font-mono">Code: {{ $po->branch->code }}</div>
            <div class="text-[11px] text-silver-500 mt-1">{{ $po->branch->address ?? 'Main Store Branch' }}</div>
        </div>

        <!-- Commercial Terms -->
        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-md">
            <span class="text-[10px] font-bold uppercase tracking-wider text-silver-400 flex items-center gap-1.5 mb-2">
                <i class="fa-solid fa-handshake text-emerald-400"></i>
                <span>Payment & Credit Terms</span>
            </span>
            <div class="text-sm font-bold text-white">{{ $po->payment_terms ?? 'Net 30 Days' }}</div>
            <div class="text-xs text-silver-400 mt-1">
                Due: <span class="text-white font-mono font-bold">{{ $po->payment_due_date ? $po->payment_due_date->format('d M Y') : 'As agreed' }}</span>
            </div>
            <div class="text-[11px] text-emerald-400 mt-1">
                @if($po->discount_percent > 0)
                    Trade Discount: {{ $po->discount_percent }}% Applied
                @else
                    Standard Commercial Pricing
                @endif
            </div>
        </div>

        <!-- Fulfillment & Backorder Progress -->
        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-md">
            <span class="text-[10px] font-bold uppercase tracking-wider text-silver-400 flex items-center gap-1.5 mb-2">
                <i class="fa-solid fa-boxes-stacked text-purple-400"></i>
                <span>Fulfillment Progress</span>
            </span>
            <div class="flex items-baseline justify-between mb-1.5">
                <span class="text-sm font-bold text-white font-mono">{{ $po->total_received_quantity }} / {{ $po->total_ordered_quantity }}</span>
                <span class="text-[10px] text-silver-400 font-mono">
                    {{ $po->total_ordered_quantity > 0 ? round(($po->total_received_quantity / $po->total_ordered_quantity) * 100) : 0 }}% Received
                </span>
            </div>
            @php
                $progressPercent = $po->total_ordered_quantity > 0 ? min(100, round(($po->total_received_quantity / $po->total_ordered_quantity) * 100)) : 0;
            @endphp
            <div class="w-full h-2 rounded-full bg-carbon-800 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-brand-600 to-emerald-500 transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
            </div>
            <div class="flex items-center justify-between text-[10px] mt-2 text-silver-400">
                <span>Free items: <strong class="text-purple-300 font-mono">+{{ $po->total_free_quantity }}</strong></span>
                <span>Backorder: <strong class="text-amber-400 font-mono">{{ $po->total_backorder_quantity }}</strong></span>
            </div>
        </div>

    </div>

    <!-- Line Items Table -->
    <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="px-5 py-3.5 border-b border-carbon-800 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-brand-500"></i>
                <span>Ordered Line Items & Backorders</span>
            </h3>
            <span class="text-[11px] text-silver-400 font-mono">{{ $po->items->count() }} items listed</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-silver-300">
                <thead class="bg-carbon-850 border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400">
                    <tr>
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Item Description</th>
                        <th class="py-3 px-4 text-center">SKU / Barcode</th>
                        <th class="py-3 px-4 text-right">Ordered Qty</th>
                        <th class="py-3 px-4 text-right">Free Issues</th>
                        <th class="py-3 px-4 text-right">Received</th>
                        <th class="py-3 px-4 text-right">Backorder</th>
                        <th class="py-3 px-4 text-right">Unit Cost</th>
                        <th class="py-3 px-4 text-right">Disc %</th>
                        <th class="py-3 px-4 text-right">Net Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon-800/60 font-sans">
                    @foreach($po->items as $idx => $item)
                        <tr class="hover:bg-carbon-850/50 transition">
                            <td class="py-3 px-4 font-mono text-silver-500">{{ $idx + 1 }}</td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-white">{{ $item->productVariation->product->name ?? 'Product' }}</div>
                                <div class="text-[10px] text-brand-400">{{ $item->productVariation->name }}</div>
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-silver-400">
                                {{ $item->productVariation->sku ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-white">
                                {{ $item->quantity_ordered }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono">
                                @if($item->free_quantity > 0)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                        +{{ $item->free_quantity }}
                                    </span>
                                @else
                                    <span class="text-silver-600">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400">
                                {{ $item->quantity_received }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono">
                                @if($item->backorder_quantity > 0)
                                    <span class="text-amber-400 font-bold">{{ $item->backorder_quantity }}</span>
                                @else
                                    <span class="text-emerald-500 font-bold">&check; Fully Received</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-silver-300">
                                Rs. {{ number_format($item->unit_cost, 2) }}
                                @if($item->free_quantity > 0)
                                    <span class="block text-[9px] text-purple-400" title="Effective cost with free items">Eff: Rs. {{ number_format($item->effective_unit_cost, 2) }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-silver-400">
                                {{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-white">
                                Rs. {{ number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Financial Summary Footer -->
        <div class="p-5 border-t border-carbon-800 bg-carbon-850/60 flex flex-col sm:flex-row items-start justify-between gap-6">
            <div class="space-y-2 max-w-lg">
                @if($po->delivery_instructions)
                    <div class="p-3 rounded-xl bg-carbon-900 border border-carbon-800 text-xs">
                        <span class="font-bold text-silver-400 uppercase text-[10px] block mb-0.5">Delivery Instructions:</span>
                        <p class="text-silver-200">{{ $po->delivery_instructions }}</p>
                    </div>
                @endif
                @if($po->notes)
                    <div class="p-3 rounded-xl bg-carbon-900 border border-carbon-800 text-xs">
                        <span class="font-bold text-silver-400 uppercase text-[10px] block mb-0.5">Internal Notes:</span>
                        <p class="text-silver-200">{{ $po->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="w-full sm:w-80 space-y-2 font-mono text-xs">
                <div class="flex justify-between text-silver-400">
                    <span>Items Subtotal:</span>
                    <span class="font-bold text-white">Rs. {{ number_format($po->subtotal, 2) }}</span>
                </div>
                @if($po->discount_amount > 0)
                    <div class="flex justify-between text-emerald-400">
                        <span>Trade Discount ({{ $po->discount_percent }}%):</span>
                        <span class="font-bold">- Rs. {{ number_format($po->discount_amount, 2) }}</span>
                    </div>
                @endif
                @if($po->tax_amount > 0)
                    <div class="flex justify-between text-silver-400">
                        <span>Tax / VAT:</span>
                        <span class="font-bold text-white">+ Rs. {{ number_format($po->tax_amount, 2) }}</span>
                    </div>
                @endif
                @if($po->shipping_cost > 0)
                    <div class="flex justify-between text-silver-400">
                        <span>Freight / Shipping:</span>
                        <span class="font-bold text-white">+ Rs. {{ number_format($po->shipping_cost, 2) }}</span>
                    </div>
                @endif
                <div class="pt-2 border-t border-carbon-700 flex justify-between text-base font-black text-brand-400">
                    <span>Net Total:</span>
                    <span>Rs. {{ number_format($po->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Associated Goods Received Notes (GRN) -->
    @if($po->grns->count() > 0)
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 space-y-3 shadow-xl">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white flex items-center gap-2">
                <i class="fa-solid fa-dolly text-emerald-400"></i>
                <span>Associated Stock Receipts (GRN History)</span>
            </h3>

            <div class="space-y-2">
                @foreach($po->grns as $grn)
                    <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between text-xs font-sans">
                        <div class="flex items-center gap-3 font-mono">
                            <span class="font-bold text-white">{{ $grn->grn_number }}</span>
                            <span class="text-silver-500">Invoice: {{ $grn->supplier_invoice_no ?? 'N/A' }}</span>
                            <span class="text-silver-400">&bull; {{ $grn->received_date ? $grn->received_date->format('d M Y') : '' }}</span>
                            <span class="text-silver-400">by {{ $grn->receiver->name ?? 'Staff' }}</span>
                        </div>
                        <div class="font-mono font-bold text-white">
                            Rs. {{ number_format($grn->total_amount, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection

