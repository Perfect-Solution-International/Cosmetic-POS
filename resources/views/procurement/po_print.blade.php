<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $po->po_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
            }
            .print-page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 text-gray-800 font-sans antialiased">

    <!-- Top floating action bar -->
    <div class="no-print max-w-4xl mx-auto mb-4 px-4 flex items-center justify-between">
        <a href="{{ route('procurement.po.show', $po->id) }}" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-xs font-bold hover:bg-gray-700 flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Order</span>
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-5 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-xs font-bold shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Purchase Order (A4)</span>
            </button>
        </div>
    </div>

    <!-- Official A4 Document Paper -->
    <div class="print-page max-w-4xl mx-auto bg-white border border-gray-300 shadow-xl rounded-xl p-8 sm:p-12">
        
        <!-- Document Header -->
        <div class="flex items-start justify-between border-b-2 border-gray-900 pb-6 mb-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/psi-logo.png') }}" alt="PSI Logo" class="w-14 h-14 object-contain">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900">PSI ONE GLOBAL POS</h1>
                    <p class="text-xs font-semibold text-gray-600 tracking-wider">OFFICIAL RETAIL TERMINAL &bull; PROCUREMENT DIVISION</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $po->branch->name }} &bull; {{ $po->branch->address ?? 'Sri Lanka' }}</p>
                    <p class="text-xs text-gray-500">Phone: {{ $po->branch->phone ?? '+94 11 234 5678' }} &bull; Web: psipos.com</p>
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-gray-900 text-white text-xs font-black uppercase tracking-widest rounded mb-2">
                    PURCHASE ORDER
                </span>
                <div class="font-mono text-xl font-black text-gray-900">{{ $po->po_number }}</div>
                <div class="text-xs text-gray-600 mt-1">Status: <strong class="uppercase text-gray-900">{{ $po->status_label }}</strong></div>
            </div>
        </div>

        <!-- Order Meta Grid: Vendor Box vs Destination Box -->
        <div class="grid grid-cols-2 gap-6 mb-6 text-xs">
            <!-- Vendor Info -->
            <div class="border border-gray-300 rounded-lg p-4 bg-gray-50/50">
                <span class="font-black text-[10px] uppercase tracking-wider text-gray-500 block mb-1">VENDOR / SUPPLIER:</span>
                <div class="text-sm font-bold text-gray-900">{{ $po->supplier->name }}</div>
                <div class="text-gray-600 mt-0.5">Code: <span class="font-mono font-bold">{{ $po->supplier->code }}</span></div>
                @if($po->supplier->contact_person)
                    <div class="text-gray-600">Attn: {{ $po->supplier->contact_person }}</div>
                @endif
                <div class="text-gray-600">Phone: {{ $po->supplier->phone ?? 'N/A' }}</div>
                <div class="text-gray-600">Email: {{ $po->supplier->email ?? 'N/A' }}</div>
                <div class="text-gray-600 mt-1 text-[11px]">{{ $po->supplier->address }}</div>
            </div>

            <!-- Ship To & Commercial Terms -->
            <div class="border border-gray-300 rounded-lg p-4 bg-gray-50/50">
                <span class="font-black text-[10px] uppercase tracking-wider text-gray-500 block mb-1">ORDER & PAYMENT DETAILS:</span>
                <div class="space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Order Date:</span>
                        <span class="font-bold text-gray-900">{{ $po->order_date ? $po->order_date->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Expected Delivery:</span>
                        <span class="font-bold text-gray-900">{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('d M Y') : 'Immediate' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Payment Terms:</span>
                        <span class="font-bold text-gray-900">{{ $po->payment_terms ?? 'Net 30 Days' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Payment Due Date:</span>
                        <span class="font-bold text-gray-900">{{ $po->payment_due_date ? $po->payment_due_date->format('d M Y') : 'As agreed' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Deliver To:</span>
                        <span class="font-bold text-gray-900">{{ $po->branch->name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="border border-gray-300 rounded-lg overflow-hidden mb-6">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-100 border-b border-gray-300 text-[10px] font-black uppercase text-gray-700 tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 w-8 text-center">#</th>
                        <th class="py-2.5 px-3">Item Description</th>
                        <th class="py-2.5 px-3 text-center">SKU / Code</th>
                        <th class="py-2.5 px-3 text-right">Qty</th>
                        <th class="py-2.5 px-3 text-right">Free Issues</th>
                        <th class="py-2.5 px-3 text-right">Unit Price (Rs)</th>
                        <th class="py-2.5 px-3 text-right">Disc %</th>
                        <th class="py-2.5 px-3 text-right">Amount (Rs)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($po->items as $idx => $item)
                        <tr>
                            <td class="py-2.5 px-3 text-center font-mono text-gray-500">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-gray-900">{{ $item->productVariation->product->name ?? 'Item' }}</div>
                                <div class="text-[10px] text-gray-600">{{ $item->productVariation->name }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono text-gray-600">{{ $item->productVariation->sku ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-900">{{ $item->quantity_ordered }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-purple-700 font-bold">
                                {{ $item->free_quantity > 0 ? '+' . $item->free_quantity : '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono">{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono">{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '-' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-900">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary & Instructions Grid -->
        <div class="grid grid-cols-2 gap-6 items-start mb-8 text-xs">
            <!-- Instructions & Terms -->
            <div class="space-y-3">
                @if($po->delivery_instructions)
                    <div class="border border-gray-200 rounded p-2.5 bg-gray-50">
                        <strong class="text-[10px] uppercase font-bold text-gray-700 block mb-0.5">Delivery Instructions:</strong>
                        <p class="text-gray-600">{{ $po->delivery_instructions }}</p>
                    </div>
                @endif
                <div class="text-[10px] text-gray-500 leading-relaxed">
                    <strong>Terms & Conditions:</strong><br>
                    1. Goods must be delivered in proper packaging with standard retail barcodes.<br>
                    2. Invoices must quote this Purchase Order Number ({{ $po->po_number }}).<br>
                    3. Expiry dates must be valid for at least 12 months from the delivery date.
                </div>
            </div>

            <!-- Financial Subtotals -->
            <div class="border border-gray-300 rounded-lg p-4 space-y-1.5 font-mono bg-gray-50">
                <div class="flex justify-between text-gray-600">
                    <span>Items Subtotal:</span>
                    <span class="font-bold text-gray-900">Rs. {{ number_format($po->subtotal, 2) }}</span>
                </div>
                @if($po->discount_amount > 0)
                    <div class="flex justify-between text-green-700 font-bold">
                        <span>Trade Discount ({{ $po->discount_percent }}%):</span>
                        <span>- Rs. {{ number_format($po->discount_amount, 2) }}</span>
                    </div>
                @endif
                @if($po->tax_amount > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>VAT / Tax:</span>
                        <span>+ Rs. {{ number_format($po->tax_amount, 2) }}</span>
                    </div>
                @endif
                @if($po->shipping_cost > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>Freight / Shipping:</span>
                        <span>+ Rs. {{ number_format($po->shipping_cost, 2) }}</span>
                    </div>
                @endif
                <div class="pt-2 border-t-2 border-gray-900 flex justify-between text-base font-black text-gray-900">
                    <span>Net Order Total:</span>
                    <span>Rs. {{ number_format($po->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- 3 Authorized Signatures Block -->
        <div class="grid grid-cols-3 gap-6 pt-12 border-t border-gray-300 text-center text-xs">
            <div>
                <div class="border-b border-gray-400 pb-1 mb-1 font-bold text-gray-900">
                    {{ $po->creator->name ?? 'Store Keeper' }}
                </div>
                <span class="text-[10px] uppercase tracking-wider text-gray-500">Prepared By</span>
            </div>
            <div>
                <div class="border-b border-gray-400 pb-1 mb-1 font-bold text-gray-900">
                    Authorized Manager
                </div>
                <span class="text-[10px] uppercase tracking-wider text-gray-500">Approved By</span>
            </div>
            <div>
                <div class="border-b border-gray-400 pb-1 mb-1 text-gray-400">
                    &nbsp;
                </div>
                <span class="text-[10px] uppercase tracking-wider text-gray-500">Vendor Acceptance (Stamp/Sign)</span>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="mt-8 text-center text-[10px] text-gray-400">
            This is a computer-generated purchase order issued via PSI ONE Global POS. Generated on {{ date('Y-m-d H:i') }}.
        </div>

    </div>

</body>
</html>

