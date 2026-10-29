@extends('layouts.pos')

@section('title', 'Goods Received Notes (GRN)')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-box-open text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Goods Received Notes (GRN)</h2>
                <p class="text-xs text-silver-400">Receive stock, input cosmetics batch numbers & settle supplier payments</p>
            </div>
        </div>

        <button 
            type="button" 
            onclick="openNewGrnModal()"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
        >
            <i class="fa-solid fa-plus"></i>
            <span>Receive New Goods (GRN)</span>
        </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">

        @if(session('status'))
            <div class="p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-3.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">GRN Number</th>
                            <th class="py-3 px-4">Received Date</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-4">Receiving Branch</th>
                            <th class="py-3 px-4">Received Items & Batches</th>
                            <th class="py-3 px-4 text-right">Total GRN Value</th>
                            <th class="py-3 px-4 text-center">Payment Status</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($grns as $grn)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <!-- GRN Number -->
                                <td class="py-3.5 px-4 font-mono">
                                    <span class="text-white font-bold">{{ $grn->grn_number }}</span>
                                    <span class="block text-[10px] text-silver-500">By {{ $grn->receiver->name ?? 'Receiver' }}</span>
                                    @if($grn->purchase_order_id)
                                        <a href="{{ route('procurement.po.show', $grn->purchase_order_id) }}" class="text-[10px] text-brand-400 hover:underline block mt-0.5">
                                            PO: {{ $grn->purchaseOrder->po_number ?? 'PO #' . $grn->purchase_order_id }}
                                        </a>
                                    @endif
                                </td>

                                <!-- Received Date -->
                                <td class="py-3.5 px-4 text-silver-400">
                                    {{ \Carbon\Carbon::parse($grn->received_date)->format('d M Y') }}
                                </td>

                                <!-- Supplier -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white">{{ $grn->supplier->name ?? 'Supplier' }}</div>
                                    @if($grn->supplier_invoice_no)
                                        <div class="text-[10px] text-silver-500 font-mono">Inv: {{ $grn->supplier_invoice_no }}</div>
                                    @endif
                                </td>

                                <!-- Branch -->
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                        {{ $grn->branch->name ?? 'Main' }}
                                    </span>
                                </td>

                                <!-- Items & Batches -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        @foreach($grn->items as $item)
                                            <div class="text-[11px] text-white flex items-center gap-1.5">
                                                <span class="font-bold">{{ $item->quantity }}x</span>
                                                <span>{{ $item->productVariation->product->name ?? '' }} ({{ $item->productVariation->name }})</span>
                                                @if($item->batch_number)
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-carbon-800 text-brand-400 border border-brand-500/30">
                                                        Batch: {{ $item->batch_number }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Total GRN Value -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-300">
                                    Rs. {{ number_format($grn->total_amount, 2) }}
                                </td>

                                <!-- Payment Status & Method -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $grn->payment_status_badge_class }}">
                                        {{ $grn->payment_status === 'paid' ? 'Paid' : ($grn->payment_status === 'partially_paid' ? 'Partial' : 'Unpaid') }}
                                    </span>
                                    
                                    @if($grn->payment_status === 'paid')
                                        <div class="text-[10px] text-silver-400 mt-1 font-mono flex items-center justify-center gap-1">
                                            <i class="fa-solid fa-check text-emerald-400 text-[9px]"></i>
                                            <span>{{ $grn->payment_method_label }}</span>
                                        </div>
                                    @elseif($grn->payment_status === 'partially_paid')
                                        <div class="text-[10px] text-amber-400 mt-1 font-mono">
                                            Paid: Rs. {{ number_format($grn->paid_amount, 2) }}
                                        </div>
                                    @else
                                        <div class="text-[10px] text-rose-400/80 mt-1 font-mono">
                                            Due: Rs. {{ number_format($grn->remaining_amount, 2) }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <button 
                                        type="button" 
                                        onclick='openPaymentModal(@json($grn))'
                                        class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 hover:border-emerald-500/50 text-white text-xs font-semibold flex items-center justify-center gap-1.5 transition shadow-sm mx-auto"
                                        title="Edit Payment Method or Settle Bill"
                                    >
                                        <i class="fa-solid fa-money-check-dollar text-emerald-400 text-xs"></i>
                                        <span>{{ $grn->payment_status === 'paid' ? 'Edit Payment' : 'Pay / Settle' }}</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-box-open text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No goods received notes recorded</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($grns->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $grns->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Modal: Settle / Edit Payment for GRN -->
<div id="grnPaymentModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4 max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-money-check-dollar text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-white">Settle / Edit GRN Payment</h3>
                    <p class="text-xs text-silver-400" id="payModalGrnSubtitle">Update payment status and method</p>
                </div>
            </div>
            <button type="button" onclick="closePaymentModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="grnPaymentForm" method="POST" action="" class="space-y-4 overflow-y-auto flex-1 pr-1 text-xs">
            @csrf

            <!-- Payment Overview Card -->
            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 space-y-2 font-mono">
                <div class="flex justify-between text-silver-400">
                    <span>Supplier / Vendor:</span>
                    <span id="payModalSupplierName" class="font-bold text-white font-sans text-xs">Supplier</span>
                </div>
                <div class="flex justify-between text-silver-400">
                    <span>Total Bill Value:</span>
                    <span id="payModalTotalAmount" class="font-bold text-white">Rs. 0.00</span>
                </div>
                <div class="flex justify-between text-silver-400">
                    <span>Already Paid:</span>
                    <span id="payModalAlreadyPaid" class="font-bold text-emerald-400">Rs. 0.00</span>
                </div>
                <div class="pt-1.5 border-t border-carbon-750 flex justify-between text-sm font-black text-amber-400">
                    <span>Outstanding Due:</span>
                    <span id="payModalDueBalance">Rs. 0.00</span>
                </div>
            </div>

            <!-- Form Controls -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Status *</label>
                    <select name="payment_status" id="pay_status_select" onchange="onPaymentStatusChange()" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        <option value="paid">Paid (Full Settlement)</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="unpaid">Unpaid / On Credit</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Method *</label>
                    <select name="payment_method" id="pay_method_select" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        <option value="bank_transfer">Bank Transfer / Online</option>
                        <option value="cash">Cash in Hand</option>
                        <option value="cheque">Cheque</option>
                        <option value="credit_card">Credit / Debit Card</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div id="paymentAmountGroup">
                    <label class="font-bold text-silver-300 uppercase block mb-1">Amount to Settle (Rs) *</label>
                    <input type="number" step="any" min="0" name="amount" id="pay_amount_input" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono font-bold focus:outline-none focus:border-brand-500 text-right">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Date *</label>
                    <input type="date" name="payment_date" id="pay_date_input" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Cheque / Reference #</label>
                    <input type="text" name="reference_no" id="pay_reference_input" placeholder="e.g. CHQ-99120 or TXN-4481" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Notes / Memo</label>
                    <input type="text" name="notes" id="pay_notes_input" placeholder="e.g. Settled upon invoice receipt" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2 mt-2">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save Payment & Update Status</span>
            </button>
        </form>
    </div>
</div>

<!-- Receive Goods (GRN) Modal with Initial Payment Option -->
<div id="newGrnModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-3xl overflow-hidden shadow-2xl p-6 space-y-4 max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-boxes-packing text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Receive Goods (GRN Entry)</h3>
                    <p class="text-xs text-silver-400">Auto-increments branch stock, tracks batches and sets payment status</p>
                </div>
            </div>
            <button type="button" onclick="closeNewGrnModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('procurement.grn.store') }}" method="POST" class="space-y-4 overflow-y-auto flex-1 pr-1 text-xs">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Supplier / Vendor *</label>
                    <select name="supplier_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Against PO (Optional)</label>
                    <select name="purchase_order_id" id="grnPoSelect" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                        <option value="">Direct Receiving (No PO)</option>
                        @foreach($pendingPos as $po)
                            <option value="{{ $po->id }}" {{ request('po_id') == $po->id ? 'selected' : '' }}>
                                {{ $po->po_number }} ({{ $po->status_label }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Branch *</label>
                    <select name="branch_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Received Date *</label>
                    <input type="date" name="received_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Supplier Invoice #</label>
                    <input type="text" name="supplier_invoice_no" placeholder="INV-VENDOR-99" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Initial Settlement / Payment Terms Upon Delivery -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 rounded-2xl bg-carbon-850/80 border border-carbon-800">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Status Upon Receipt</label>
                    <select name="payment_status" class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        <option value="unpaid" selected>Unpaid (Credit / Pending Payment)</option>
                        <option value="paid">Paid (Cash / Bank Transfer on Delivery)</option>
                        <option value="partially_paid">Partially Paid</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                        <option value="bank_transfer" selected>Bank Transfer</option>
                        <option value="cash">Cash in Hand</option>
                        <option value="cheque">Cheque</option>
                        <option value="credit_card">Credit Card</option>
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Reference / Cheque #</label>
                    <input type="text" name="payment_reference" placeholder="e.g. TXN-1209 / CHQ-33" class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- GRN Items Entry Matrix -->
            <div class="space-y-2 pt-2 border-t border-carbon-800">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-silver-300 uppercase block">Items, Batches & Unit Costs</label>
                    <button type="button" onclick="addGrnItemRow()" class="text-xs text-brand-400 font-bold hover:underline">+ Add Another Item</button>
                </div>

                <div id="grnItemsContainer" class="space-y-2.5">
                    <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                            <div class="sm:col-span-6">
                                <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Product Item *</label>
                                <select name="items[0][product_variation_id]" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                                    @foreach($variations as $v)
                                        <option value="{{ $v->id }}">{{ $v->product->name ?? '' }} - {{ $v->name }} ({{ $v->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-3">
                                <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Quantity *</label>
                                <input type="number" name="items[0][quantity]" min="1" value="20" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono font-bold text-right focus:outline-none focus:border-brand-500">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Unit Cost (Rs) *</label>
                                <input type="number" name="items[0][unit_cost]" step="any" min="0" value="1800.00" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500">
                            </div>
                        </div>

                        <!-- Cosmetics Batch & Expiry Row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-carbon-800/60">
                            <div>
                                <label class="text-[10px] text-brand-400 uppercase font-bold block mb-1">Batch Number (e.g. BATCH-2026-A)</label>
                                <input type="text" name="items[0][batch_number]" value="BATCH-{{ date('Y') }}-{{ rand(10,99) }}" class="w-full px-2.5 py-1 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500">
                            </div>
                            <div>
                                <label class="text-[10px] text-brand-400 uppercase font-bold block mb-1">Expiry Date (Cosmetics / Skincare)</label>
                                <input type="date" name="items[0][exp_date]" value="{{ date('Y-m-d', strtotime('+2 years')) }}" class="w-full px-2.5 py-1 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition">
                Record GRN & Increase Stock
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let grnRowIndex = 1;
    let currentSelectedGrn = null;

    function openNewGrnModal() {
        const modal = document.getElementById('newGrnModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeNewGrnModal() {
        const modal = document.getElementById('newGrnModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openPaymentModal(grn) {
        currentSelectedGrn = grn;
        const modal = document.getElementById('grnPaymentModal');
        const form = document.getElementById('grnPaymentForm');

        if (modal && form) {
            form.action = `/procurement/grn/${grn.id}/payment`;

            const subtitle = document.getElementById('payModalGrnSubtitle');
            if (subtitle) subtitle.textContent = `GRN #${grn.grn_number} • Issued ${grn.received_date ? new Date(grn.received_date).toLocaleDateString() : ''}`;

            const supplierEl = document.getElementById('payModalSupplierName');
            if (supplierEl) supplierEl.textContent = (grn.supplier ? grn.supplier.name : 'Supplier');

            const total = parseFloat(grn.total_amount) || 0;
            const paid = parseFloat(grn.paid_amount) || 0;
            const due = Math.max(0, total - paid);

            const totalEl = document.getElementById('payModalTotalAmount');
            if (totalEl) totalEl.textContent = 'Rs. ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const paidEl = document.getElementById('payModalAlreadyPaid');
            if (paidEl) paidEl.textContent = 'Rs. ' + paid.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const dueEl = document.getElementById('payModalDueBalance');
            if (dueEl) dueEl.textContent = 'Rs. ' + due.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Set Form defaults
            const statusSelect = document.getElementById('pay_status_select');
            if (statusSelect) {
                statusSelect.value = grn.payment_status || 'paid';
            }

            const methodSelect = document.getElementById('pay_method_select');
            if (methodSelect) {
                methodSelect.value = grn.payment_method || 'bank_transfer';
            }

            const amountInput = document.getElementById('pay_amount_input');
            if (amountInput) {
                amountInput.value = due > 0 ? due.toFixed(2) : total.toFixed(2);
            }

            const refInput = document.getElementById('pay_reference_input');
            if (refInput) {
                refInput.value = grn.payment_reference || '';
            }

            onPaymentStatusChange();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closePaymentModal() {
        const modal = document.getElementById('grnPaymentModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function onPaymentStatusChange() {
        const status = document.getElementById('pay_status_select')?.value;
        const amountGroup = document.getElementById('paymentAmountGroup');
        const amountInput = document.getElementById('pay_amount_input');

        if (currentSelectedGrn) {
            const total = parseFloat(currentSelectedGrn.total_amount) || 0;
            const paid = parseFloat(currentSelectedGrn.paid_amount) || 0;
            const due = Math.max(0, total - paid);

            if (status === 'paid') {
                if (amountInput) amountInput.value = due > 0 ? due.toFixed(2) : total.toFixed(2);
            } else if (status === 'unpaid') {
                if (amountInput) amountInput.value = '0.00';
            }
        }
    }

    function addGrnItemRow() {
        const container = document.getElementById('grnItemsContainer');
        const i = grnRowIndex++;
        const div = document.createElement('div');
        div.className = "p-3 rounded-xl bg-carbon-850 border border-carbon-800 space-y-2";
        
        div.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                <div class="sm:col-span-6">
                    <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Product Item *</label>
                    <select name="items[${i}][product_variation_id]" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                        @foreach($variations as $v)
                            <option value="{{ $v->id }}">{{ $v->product->name ?? '' }} - {{ $v->name }} ({{ $v->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Quantity *</label>
                    <input type="number" name="items[${i}][quantity]" min="1" value="10" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono font-bold text-right focus:outline-none focus:border-brand-500">
                </div>
                <div class="sm:col-span-3 flex items-end gap-2">
                    <div class="flex-1">
                        <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Unit Cost (Rs) *</label>
                        <input type="number" name="items[${i}][unit_cost]" step="any" min="0" value="0.00" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500">
                    </div>
                    <button type="button" onclick="this.parentElement.parentElement.parentElement.remove()" class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-400 hover:text-white hover:bg-rose-600 flex items-center justify-center text-xs mb-0.5">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-carbon-800/60">
                <div>
                    <label class="text-[10px] text-brand-400 uppercase font-bold block mb-1">Batch Number</label>
                    <input type="text" name="items[${i}][batch_number]" placeholder="Batch #" class="w-full px-2.5 py-1 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="text-[10px] text-brand-400 uppercase font-bold block mb-1">Expiry Date</label>
                    <input type="date" name="items[${i}][exp_date]" class="w-full px-2.5 py-1 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>
        `;

        container.appendChild(div);
    }

    @if(request('po_id'))
    document.addEventListener('DOMContentLoaded', function() {
        openNewGrnModal();
    });
    @endif

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNewGrnModal();
            closePaymentModal();
        }
    });
</script>
@endsection
