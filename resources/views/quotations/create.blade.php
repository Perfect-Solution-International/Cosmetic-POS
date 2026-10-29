@extends('layouts.pos')

@section('title', 'Create Commercial Quotation')

@section('content')
<div class="h-full flex flex-col justify-between overflow-hidden bg-carbon-950">
    <!-- Header -->
    <header class="p-4 sm:p-5 border-b border-carbon-800 bg-carbon-900/40 backdrop-blur-md flex items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('quotations.index') }}" class="w-9 h-9 rounded-xl bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-base sm:text-lg font-black text-white tracking-tight flex items-center gap-2">
                    Create New Quotation / Proforma Invoice
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-brand-500/10 text-brand-400 border border-brand-500/20">{{ $nextNumber }}</span>
                </h1>
                <p class="text-xs text-silver-400">Prepare professional commercial quote with custom pricing and terms</p>
            </div>
        </div>
    </header>

    <!-- Main Form -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6">
        <form method="POST" action="{{ route('quotations.store') }}" id="quotationForm" class="max-w-6xl mx-auto space-y-6">
            @csrf

            @if($errors->any())
                <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
                    <div class="font-bold mb-1">Please fix the following validation errors:</div>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Metadata Card -->
            <div class="p-5 sm:p-6 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-4">
                <h2 class="text-xs font-black text-silver-400 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-building text-brand-500"></i>
                    <span>Quotation Details &amp; Commercial Scope</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <div>
                        <label class="block text-silver-400 font-bold mb-1.5">Issuing Branch *</label>
                        <select name="branch_id" class="w-full px-3.5 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $selectedBranch->id == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-silver-400 font-bold mb-1.5">Customer (Optional)</label>
                        <select name="customer_id" id="customerSelect" onchange="handleCustomerSelect()" class="w-full px-3.5 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-white font-medium focus:outline-none focus:border-brand-500">
                            <option value="">-- Generic / Walk-in Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-phone="{{ $c->phone }}" data-email="{{ $c->email }}" data-tier="{{ $c->pricing_tier }}">
                                    {{ $c->name }} ({{ $c->phone }}) [{{ ucfirst($c->pricing_tier) }}]
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-silver-400 font-bold mb-1.5">Issue Date *</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full px-3.5 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-silver-400 font-bold mb-1.5">Validity Expiry</label>
                        <input type="date" name="valid_until" value="{{ date('Y-m-d', strtotime('+14 days')) }}" class="w-full px-3.5 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-silver-400 font-bold mb-1.5">Customer Name</label>
                        <input type="text" name="customer_name" id="customerName" placeholder="Client or Company Name" class="w-full px-3.5 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-silver-400 font-bold mb-1.5">Customer Phone</label>
                        <input type="text" name="customer_phone" id="customerPhone" placeholder="07XXXXXXXX" class="w-full px-3.5 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-silver-400 font-bold mb-1.5">Customer Email</label>
                        <input type="email" name="customer_email" id="customerEmail" placeholder="client@company.com" class="w-full px-3.5 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-silver-400 font-bold mb-1.5">Pricing Mode *</label>
                        <select name="pricing_mode" id="pricingMode" onchange="recalculateAllLines()" class="w-full px-3.5 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-white font-bold focus:outline-none focus:border-brand-500">
                            <option value="retail">Retail Pricing</option>
                            <option value="wholesale">Wholesale Pricing (B2B)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Line Items Card -->
            <div class="p-5 sm:p-6 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black text-silver-400 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-brand-500"></i>
                        <span>Quotation Line Items</span>
                    </h2>

                    <button type="button" onclick="addLineItem()" class="px-3.5 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-plus text-brand-500"></i>
                        <span>Add Item</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse" id="itemsTable">
                        <thead>
                            <tr class="border-b border-carbon-800 bg-carbon-850/50 text-silver-400 font-bold uppercase tracking-wider">
                                <th class="py-2.5 px-3 w-5/12">Product / SKU</th>
                                <th class="py-2.5 px-3 w-2/12 text-center">Quantity</th>
                                <th class="py-2.5 px-3 w-2/12 text-right">Unit Price (Rs.)</th>
                                <th class="py-2.5 px-3 w-2/12 text-right">Line Total (Rs.)</th>
                                <th class="py-2.5 px-3 w-1/12 text-center">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTbody" class="divide-y divide-carbon-800/60">
                            <!-- Injected dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals & Commercial Notes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: Terms & Notes -->
                <div class="p-5 sm:p-6 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-4">
                    <h2 class="text-xs font-black text-silver-400 uppercase tracking-wider">Terms &amp; Special Conditions</h2>
                    <div>
                        <label class="block text-silver-400 text-xs font-bold mb-1.5">Payment Terms &amp; Scope Notes</label>
                        <textarea name="terms" rows="3" class="w-full p-3 rounded-2xl bg-carbon-800 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">1. Quotation valid for 14 days from issue date.
2. 50% advance upon confirmation, balance upon delivery.
3. Cheques written in favor of PSI ONE GLOBAL (PVT) LTD.</textarea>
                    </div>
                    <div>
                        <label class="block text-silver-400 text-xs font-bold mb-1.5">Internal Remarks (Optional)</label>
                        <textarea name="notes" rows="2" placeholder="Notes for sales team / management" class="w-full p-3 rounded-2xl bg-carbon-800 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"></textarea>
                    </div>
                </div>

                <!-- Right: Totals Calculation Card -->
                <div class="p-5 sm:p-6 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-3.5 text-xs">
                    <h2 class="text-xs font-black text-silver-400 uppercase tracking-wider border-b border-carbon-800 pb-2">Commercial Summary</h2>

                    <div class="flex items-center justify-between">
                        <span class="text-silver-400 font-bold">Items Subtotal</span>
                        <span class="font-mono font-bold text-white text-sm" id="summarySubtotal">Rs. 0.00</span>
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-1">
                        <div class="flex items-center gap-2">
                            <span class="text-silver-400 font-bold">Commercial Discount</span>
                            <select name="discount_type" id="discountType" onchange="calculateGrandTotal()" class="px-2 py-1 rounded-lg bg-carbon-800 border border-carbon-700 text-silver-300 text-[11px]">
                                <option value="fixed">Fixed (Rs.)</option>
                                <option value="percentage">Percent (%)</option>
                            </select>
                        </div>
                        <input type="number" step="0.01" min="0" name="discount_amount" id="discountAmount" value="0.00" oninput="calculateGrandTotal()" class="w-28 text-right px-2.5 py-1 rounded-lg bg-carbon-800 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-1">
                        <div class="flex items-center gap-2">
                            <span class="text-silver-400 font-bold">Tax Rate (%)</span>
                        </div>
                        <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="taxRate" value="0.00" oninput="calculateGrandTotal()" class="w-28 text-right px-2.5 py-1 rounded-lg bg-carbon-800 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
                    </div>

                    <div class="pt-3 border-t border-carbon-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-sm font-black text-white uppercase tracking-wider">Total Quoted Value</span>
                            <div class="text-[10px] text-silver-400">Net payable if accepted by client</div>
                        </div>
                        <span class="font-mono font-black text-emerald-400 text-xl" id="summaryGrandTotal">Rs. 0.00</span>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3">
                        <a href="{{ route('quotations.index') }}" class="px-4 py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-400 text-xs font-bold transition">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs shadow-lg shadow-brand-600/30 transition flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save &amp; Generate Quotation</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const variationsCatalog = @json($variations);
    let lineIndex = 0;

    function handleCustomerSelect() {
        const select = document.getElementById('customerSelect');
        const opt = select.options[select.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('customerName').value = opt.text.split(' (')[0];
            document.getElementById('customerPhone').value = opt.dataset.phone || '';
            document.getElementById('customerEmail').value = opt.dataset.email || '';
            if (opt.dataset.tier === 'wholesale') {
                document.getElementById('pricingMode').value = 'wholesale';
                recalculateAllLines();
            }
        }
    }

    function addLineItem(variationId = null, qty = 1) {
        const tbody = document.getElementById('itemsTbody');
        const rowId = 'line_' + lineIndex;

        const tr = document.createElement('tr');
        tr.id = rowId;
        tr.className = 'hover:bg-carbon-800/40 transition';

        let optionsHtml = '<option value="">-- Choose Product / SKU --</option>';
        variationsCatalog.forEach(v => {
            const isSelected = variationId && variationId == v.id ? 'selected' : '';
            optionsHtml += `<option value="${v.id}" data-retail="${v.selling_price}" data-wholesale="${v.wholesale_price || v.selling_price}" ${isSelected}>${v.product.name} - ${v.name} (${v.sku})</option>`;
        });

        tr.innerHTML = `
            <td class="py-2 px-3">
                <select name="items[${lineIndex}][product_variation_id]" onchange="updateLinePrice('${rowId}')" required class="w-full px-2.5 py-1.5 rounded-xl bg-carbon-800 border border-carbon-700 text-white focus:outline-none focus:border-brand-500">
                    ${optionsHtml}
                </select>
            </td>
            <td class="py-2 px-3 text-center">
                <input type="number" min="1" name="items[${lineIndex}][quantity]" value="${qty}" oninput="updateLineTotal('${rowId}')" class="w-20 text-center px-2 py-1.5 rounded-xl bg-carbon-800 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
            </td>
            <td class="py-2 px-3 text-right">
                <input type="number" step="0.01" min="0" name="items[${lineIndex}][unit_price]" value="0.00" oninput="updateLineTotal('${rowId}')" class="w-28 text-right px-2 py-1.5 rounded-xl bg-carbon-800 border border-carbon-700 text-white font-mono focus:outline-none focus:border-brand-500">
            </td>
            <td class="py-2 px-3 text-right font-mono font-bold text-white line-subtotal">
                Rs. 0.00
            </td>
            <td class="py-2 px-3 text-center">
                <button type="button" onclick="removeLineItem('${rowId}')" class="p-1.5 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white transition">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        if (variationId) {
            updateLinePrice(rowId);
        }
        lineIndex++;
    }

    function updateLinePrice(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;
        const select = row.querySelector('select');
        const priceInput = row.querySelector('input[name*="unit_price"]');
        const opt = select.options[select.selectedIndex];
        const isWholesale = document.getElementById('pricingMode').value === 'wholesale';

        if (opt && opt.value) {
            const price = isWholesale ? (parseFloat(opt.dataset.wholesale) || parseFloat(opt.dataset.retail)) : parseFloat(opt.dataset.retail);
            priceInput.value = price.toFixed(2);
        }
        updateLineTotal(rowId);
    }

    function recalculateAllLines() {
        document.querySelectorAll('#itemsTbody tr').forEach(row => {
            updateLinePrice(row.id);
        });
    }

    function updateLineTotal(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;
        const qty = parseFloat(row.querySelector('input[name*="quantity"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name*="unit_price"]').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.line-subtotal').textContent = 'Rs. ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        calculateGrandTotal();
    }

    function removeLineItem(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            calculateGrandTotal();
        }
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        document.querySelectorAll('#itemsTbody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name*="quantity"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name*="unit_price"]').value) || 0;
            subtotal += (qty * price);
        });

        document.getElementById('summarySubtotal').textContent = 'Rs. ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        const discountType = document.getElementById('discountType').value;
        const discountVal = parseFloat(document.getElementById('discountAmount').value) || 0;
        let discount = discountVal;
        if (discountType === 'percentage') {
            discount = (subtotal * discountVal) / 100;
        }

        const taxRate = parseFloat(document.getElementById('taxRate').value) || 0;
        const taxable = Math.max(0, subtotal - discount);
        const tax = (taxable * taxRate) / 100;
        const grandTotal = taxable + tax;

        document.getElementById('summaryGrandTotal').textContent = 'Rs. ' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Initialize with 1 default line
    window.addEventListener('DOMContentLoaded', () => {
        if (variationsCatalog.length > 0) {
            addLineItem(variationsCatalog[0].id, 1);
        } else {
            addLineItem();
        }
    });
</script>
@endsection

