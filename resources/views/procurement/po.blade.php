@extends('layouts.pos')

@section('title', 'Purchase Orders (PO)')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-file-invoice text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Purchase Orders (PO)</h2>
                <p class="text-xs text-silver-400">Commercial purchasing, payment terms, trade discounts & backorders</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('procurement.grn') }}" class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-box-open text-brand-400"></i>
                <span>Goods Received (GRN)</span>
            </a>
            <button 
                type="button" 
                onclick="openNewPoModal()"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Create Purchase Order</span>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">

        @if(session('status'))
            <div class="p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">PO Number</th>
                            <th class="py-3 px-4">Order Date</th>
                            <th class="py-3 px-4">Supplier & Terms</th>
                            <th class="py-3 px-4">Destination</th>
                            <th class="py-3 px-4">Fulfillment Status</th>
                            <th class="py-3 px-4 text-right">Total Amount</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($orders as $po)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <!-- PO Number -->
                                <td class="py-3.5 px-4 font-mono">
                                    <a href="{{ route('procurement.po.show', $po->id) }}" class="text-brand-400 hover:text-brand-300 font-bold block hover:underline">
                                        {{ $po->po_number }}
                                    </a>
                                    <span class="text-[10px] text-silver-500">By {{ $po->creator->name ?? 'Staff' }}</span>
                                </td>

                                <!-- Order Date -->
                                <td class="py-3.5 px-4 text-silver-400">
                                    <div class="font-medium text-white">{{ $po->order_date ? $po->order_date->format('d M Y') : '-' }}</div>
                                    @if($po->expected_delivery_date)
                                        <div class="text-[10px] text-amber-400 flex items-center gap-1 mt-0.5">
                                            <i class="fa-regular fa-clock"></i>
                                            <span>Exp: {{ $po->expected_delivery_date->format('d M') }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Supplier & Terms -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white">{{ $po->supplier->name ?? 'Supplier' }}</div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[10px] text-silver-400 font-mono bg-carbon-800 px-1.5 py-0.2 rounded border border-carbon-700">
                                            {{ $po->payment_terms ?? 'Net 30' }}
                                        </span>
                                        @if($po->discount_percent > 0)
                                            <span class="text-[9px] text-emerald-400 font-bold bg-emerald-500/10 px-1 py-0.2 rounded">
                                                -{{ $po->discount_percent }}% Disc
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Destination Branch -->
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                        {{ $po->branch->name ?? 'Main' }}
                                    </span>
                                </td>

                                <!-- Fulfillment / Backorder Progress -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5 text-[11px]">
                                            <span class="font-bold text-white">{{ $po->total_received_quantity }}/{{ $po->total_ordered_quantity }}</span>
                                            <span class="text-silver-400">Received</span>
                                            @if($po->total_free_quantity > 0)
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                                    +{{ $po->total_free_quantity }} Free
                                                </span>
                                            @endif
                                        </div>
                                        @if($po->total_backorder_quantity > 0 && $po->total_received_quantity > 0)
                                            <div class="text-[10px] text-amber-400 font-medium">
                                                {{ $po->total_backorder_quantity }} on backorder
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Total Amount -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-white">
                                    <div>Rs. {{ number_format($po->total_amount, 2) }}</div>
                                    @if($po->discount_amount > 0)
                                        <div class="text-[10px] text-silver-500 line-through">Rs. {{ number_format($po->subtotal, 2) }}</div>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $po->status_badge_class }}">
                                        {{ $po->status_label }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a 
                                            href="{{ route('procurement.po.show', $po->id) }}" 
                                            class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-carbon-700 border border-carbon-700 text-silver-300 hover:text-white flex items-center justify-center transition"
                                            title="View Details"
                                        >
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>
                                        <a 
                                            href="{{ route('procurement.po.print', $po->id) }}" 
                                            target="_blank" 
                                            class="w-7 h-7 rounded-lg bg-carbon-800 hover:bg-carbon-700 border border-carbon-700 text-silver-300 hover:text-white flex items-center justify-center transition"
                                            title="Print Official A4 PO"
                                        >
                                            <i class="fa-solid fa-print text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-file-invoice text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No purchase orders created yet</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Advanced Create Purchase Order Modal -->
<div id="newPoModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-3 sm:p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-4xl overflow-hidden shadow-2xl p-5 sm:p-6 space-y-4 max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-file-circle-plus text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-white">New Purchase Order</h3>
                    <p class="text-[11px] text-silver-400">Commercial terms, item trade deals, free issues & credit period</p>
                </div>
            </div>
            <button type="button" onclick="closeNewPoModal()" class="w-8 h-8 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('procurement.po.store') }}" method="POST" id="poForm" class="space-y-4 overflow-y-auto flex-1 pr-1 text-xs">
            @csrf

            <!-- 1. Header Information -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-carbon-850/70 p-3.5 rounded-2xl border border-carbon-800">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Supplier / Vendor *</label>
                    <select name="supplier_id" id="poSupplierSelect" required class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Receiving Branch *</label>
                    <select name="branch_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Order Date *</label>
                    <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Expected Delivery</label>
                    <input type="date" name="expected_delivery_date" value="{{ date('Y-m-d', strtotime('+3 days')) }}" class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- 2. Commercial Terms (Payment Terms, Credit Days, Initial Status) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-carbon-850/70 p-3.5 rounded-2xl border border-carbon-800">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Terms</label>
                    <select name="payment_terms" class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                        <option value="Net 30 Days" selected>Net 30 Days (Standard Credit)</option>
                        <option value="Net 15 Days">Net 15 Days</option>
                        <option value="Net 60 Days">Net 60 Days (Extended Credit)</option>
                        <option value="Cash on Delivery (COD)">Cash on Delivery (COD)</option>
                        <option value="Immediate / Cash">Immediate / Cash</option>
                        <option value="Advance 50%">Advance 50% / Balance on Delivery</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Due Date</label>
                    <input type="date" name="payment_due_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Initial Order Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                        <option value="approved" selected>Approved (Ready to Dispatch to Supplier)</option>
                        <option value="submitted">Submit for Manager Approval</option>
                        <option value="draft">Save as Draft</option>
                    </select>
                </div>
            </div>

            <!-- 3. Items & Free Issues Matrix -->
            <div class="space-y-2 pt-2 border-t border-carbon-800">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-silver-300 uppercase">Line Items & Free Issues</span>
                        <p class="text-[10px] text-silver-500">Include quantity ordered, promotional free items (e.g. Buy 10 Get 1 Free) & discounts</p>
                    </div>
                    <button type="button" onclick="addPoItemRow()" class="text-xs text-brand-400 font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Add Item</span>
                    </button>
                </div>

                <!-- Table Header for items -->
                <div class="grid grid-cols-12 gap-2 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-silver-500">
                    <div class="col-span-5">Product Variation</div>
                    <div class="col-span-2 text-right">Order Qty</div>
                    <div class="col-span-1 text-right">Free Qty</div>
                    <div class="col-span-2 text-right">Unit Cost (Rs)</div>
                    <div class="col-span-1 text-right">Disc %</div>
                    <div class="col-span-1 text-center">Action</div>
                </div>

                <div id="poItemsContainer" class="space-y-2">
                    <div class="grid grid-cols-12 gap-2 p-2 rounded-xl bg-carbon-850 border border-carbon-800 items-center po-item-row">
                        <div class="col-span-5">
                            <select name="items[0][product_variation_id]" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                                @foreach($variations as $v)
                                    <option value="{{ $v->id }}">{{ $v->product->name ?? '' }} - {{ $v->name }} ({{ $v->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="items[0][quantity]" min="1" value="10" required placeholder="Qty" oninput="calculatePoTotals()" class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono font-bold text-right focus:outline-none focus:border-brand-500 po-qty">
                        </div>
                        <div class="col-span-1">
                            <input type="number" name="items[0][free_quantity]" min="0" value="0" placeholder="Free" oninput="calculatePoTotals()" class="w-full px-2 py-1.5 rounded-lg bg-purple-950/40 border border-purple-800/60 text-purple-300 text-xs font-mono font-bold text-right focus:outline-none focus:border-purple-500 po-free" title="Free issues from supplier (Buy X Get Y Free)">
                        </div>
                        <div class="col-span-2">
                            <input type="number" name="items[0][unit_cost]" step="any" min="0" value="1500.00" required placeholder="Cost" oninput="calculatePoTotals()" class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500 po-cost">
                        </div>
                        <div class="col-span-1">
                            <input type="number" name="items[0][discount_percent]" step="any" min="0" max="100" value="0" placeholder="%" oninput="calculatePoTotals()" class="w-full px-1.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500 po-disc">
                        </div>
                        <div class="col-span-1 text-center">
                            <button type="button" onclick="removePoRow(this)" class="text-rose-400 hover:text-white text-xs p-1">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Commercial Summary (Trade Discount, Tax, Shipping, Net Total) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-carbon-800">
                <div class="space-y-2">
                    <div>
                        <label class="font-bold text-silver-400 uppercase block mb-1">Delivery Instructions</label>
                        <textarea name="delivery_instructions" rows="2" placeholder="e.g. Deliver to rear loading bay between 9 AM - 2 PM..." class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"></textarea>
                    </div>
                    <div>
                        <label class="font-bold text-silver-400 uppercase block mb-1">Internal Notes</label>
                        <input type="text" name="notes" placeholder="e.g. Promotional seasonal stock order" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 space-y-2 font-mono">
                    <div class="flex justify-between text-silver-400">
                        <span>Items Subtotal:</span>
                        <span id="summarySubtotal" class="font-bold text-white">Rs. 15,000.00</span>
                    </div>

                    <div class="flex items-center justify-between text-silver-400">
                        <span class="flex items-center gap-1">
                            <span>Trade Discount:</span>
                            <input type="number" name="discount_percent" id="poTradeDiscInput" min="0" max="100" step="any" value="0" oninput="calculatePoTotals()" class="w-14 px-1.5 py-0.5 rounded bg-carbon-900 border border-carbon-700 text-emerald-400 font-bold text-xs text-right">
                            <span>%</span>
                        </span>
                        <span id="summaryTradeDiscount" class="text-emerald-400 font-bold">- Rs. 0.00</span>
                    </div>

                    <div class="flex items-center justify-between text-silver-400">
                        <span>Tax / VAT Amount:</span>
                        <input type="number" name="tax_amount" id="poTaxInput" min="0" step="any" value="0" oninput="calculatePoTotals()" class="w-24 px-1.5 py-0.5 rounded bg-carbon-900 border border-carbon-700 text-white font-bold text-xs text-right">
                    </div>

                    <div class="flex items-center justify-between text-silver-400">
                        <span>Shipping / Freight:</span>
                        <input type="number" name="shipping_cost" id="poShippingInput" min="0" step="any" value="0" oninput="calculatePoTotals()" class="w-24 px-1.5 py-0.5 rounded bg-carbon-900 border border-carbon-700 text-white font-bold text-xs text-right">
                    </div>

                    <div class="pt-2 border-t border-carbon-700 flex justify-between text-sm font-black text-white">
                        <span class="text-brand-400">Net Grand Total:</span>
                        <span id="summaryGrandTotal" class="text-brand-400">Rs. 15,000.00</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Save & Issue Purchase Order</span>
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let poRowIndex = 1;

    function openNewPoModal() {
        const modal = document.getElementById('newPoModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        calculatePoTotals();
    }

    function closeNewPoModal() {
        const modal = document.getElementById('newPoModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function addPoItemRow() {
        const container = document.getElementById('poItemsContainer');
        const i = poRowIndex++;
        const div = document.createElement('div');
        div.className = "grid grid-cols-12 gap-2 p-2 rounded-xl bg-carbon-850 border border-carbon-800 items-center po-item-row";
        
        div.innerHTML = `
            <div class="col-span-5">
                <select name="items[${i}][product_variation_id]" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    @foreach($variations as $v)
                        <option value="{{ $v->id }}">{{ $v->product->name ?? '' }} - {{ $v->name }} ({{ $v->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <input type="number" name="items[${i}][quantity]" min="1" value="1" required placeholder="Qty" oninput="calculatePoTotals()" class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono font-bold text-right focus:outline-none focus:border-brand-500 po-qty">
            </div>
            <div class="col-span-1">
                <input type="number" name="items[${i}][free_quantity]" min="0" value="0" placeholder="Free" oninput="calculatePoTotals()" class="w-full px-2 py-1.5 rounded-lg bg-purple-950/40 border border-purple-800/60 text-purple-300 text-xs font-mono font-bold text-right focus:outline-none focus:border-purple-500 po-free" title="Free issues (Buy X Get Y Free)">
            </div>
            <div class="col-span-2">
                <input type="number" name="items[${i}][unit_cost]" step="any" min="0" value="0.00" required placeholder="Cost" oninput="calculatePoTotals()" class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500 po-cost">
            </div>
            <div class="col-span-1">
                <input type="number" name="items[${i}][discount_percent]" step="any" min="0" max="100" value="0" placeholder="%" oninput="calculatePoTotals()" class="w-full px-1.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500 po-disc">
            </div>
            <div class="col-span-1 text-center">
                <button type="button" onclick="removePoRow(this)" class="text-rose-400 hover:text-white text-xs p-1">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;

        container.appendChild(div);
        calculatePoTotals();
    }

    function removePoRow(btn) {
        const rows = document.querySelectorAll('.po-item-row');
        if (rows.length <= 1) {
            alert('A purchase order requires at least one item.');
            return;
        }
        btn.closest('.po-item-row').remove();
        calculatePoTotals();
    }

    function calculatePoTotals() {
        let subtotal = 0;
        const rows = document.querySelectorAll('.po-item-row');

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.po-qty')?.value) || 0;
            const cost = parseFloat(row.querySelector('.po-cost')?.value) || 0;
            const disc = parseFloat(row.querySelector('.po-disc')?.value) || 0;
            const lineTotal = qty * cost * (1 - (disc / 100));
            subtotal += lineTotal;
        });

        const tradeDiscPercent = parseFloat(document.getElementById('poTradeDiscInput')?.value) || 0;
        const tradeDiscAmount = subtotal * (tradeDiscPercent / 100);
        const taxAmount = parseFloat(document.getElementById('poTaxInput')?.value) || 0;
        const shippingCost = parseFloat(document.getElementById('poShippingInput')?.value) || 0;

        const grandTotal = Math.max(0, (subtotal - tradeDiscAmount) + taxAmount + shippingCost);

        const subtotalEl = document.getElementById('summarySubtotal');
        const tradeDiscEl = document.getElementById('summaryTradeDiscount');
        const grandTotalEl = document.getElementById('summaryGrandTotal');

        if (subtotalEl) subtotalEl.textContent = 'Rs. ' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (tradeDiscEl) tradeDiscEl.textContent = '- Rs. ' + tradeDiscAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (grandTotalEl) grandTotalEl.textContent = 'Rs. ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNewPoModal();
        }
    });
</script>
@endsection
