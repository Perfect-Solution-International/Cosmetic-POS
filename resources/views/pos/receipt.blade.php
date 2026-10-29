<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $sale->invoice_no }} | Psi One Global POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    </noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #07090E;
            color: #111827;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* 80mm Thermal Receipt Width */
        @media screen {
            .receipt-container {
                max-width: 80mm;
                margin: 20px auto;
                background: white;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
                border-radius: 8px;
                padding: 16px;
            }
        }

        /* Print Specific Optimization */
        @media print {
            body {
                background: white !important;
                color: black !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .receipt-container {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 auto !important;
                padding: 4mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body class="min-h-screen py-6 px-3">

    <!-- Action Bar for Screen view -->
    <div class="no-print max-w-[80mm] mx-auto mb-4 space-y-2">
        <div class="flex items-center justify-between gap-1.5 flex-wrap">
            <a 
                href="{{ route('pos.terminal') }}" 
                class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition flex items-center gap-1"
            >
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span>POS</span>
            </a>
            <div class="flex items-center gap-1.5 flex-wrap">
                <a 
                    href="{{ route('pos.receipt.whatsapp', $sale->id) }}" 
                    target="_blank"
                    class="px-2.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition flex items-center gap-1"
                    title="Send E-Receipt via WhatsApp"
                >
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>WhatsApp</span>
                </a>
                <button 
                    type="button"
                    onclick="copySmsText()"
                    class="px-2.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1"
                    title="Copy SMS Receipt Text"
                >
                    <i class="fa-solid fa-comment-sms text-[11px]"></i>
                    <span id="smsBtnText">SMS</span>
                </button>
                <button 
                    onclick="window.print()" 
                    class="px-2.5 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-extrabold shadow-md shadow-red-600/30 transition flex items-center gap-1"
                >
                    <i class="fa-solid fa-print text-[11px]"></i>
                    <span>Print</span>
                </button>
                <a 
                    href="{{ route('pos.receipt.escpos', $sale->id) }}" 
                    class="px-2 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-1"
                    title="Thermal ESC/POS Byte Stream"
                >
                    <i class="fa-solid fa-bolt text-[10px]"></i>
                </a>
                <button 
                    onclick="triggerCashDrawer()" 
                    class="px-2 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-silver-300 hover:text-white text-xs transition"
                    title="Kick Cash Drawer"
                >
                    <i class="fa-solid fa-cash-register text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 80mm Receipt Body -->
    <div class="receipt-container text-xs">
        
        <!-- Header & Logo -->
        <div class="text-center pb-3 border-b border-dashed border-gray-400">
            <div class="flex justify-center mb-1">
                <img src="{{ asset('images/psi-logo.png') }}" alt="PSI" class="h-10 object-contain filter grayscale contrast-125">
            </div>
            <h1 class="font-black text-sm tracking-tight uppercase">Perfect Solution International</h1>
            <p class="text-[10px] text-gray-600 uppercase font-semibold">Psi One Global POS</p>
            <div class="text-[10px] text-gray-700 mt-1 space-y-0.5">
                <p class="font-bold">{{ $sale->branch->name ?? 'Colombo Flagship Store' }}</p>
                <p>{{ $sale->branch->address ?? 'Level 2, Colombo City Centre, Colombo 02' }}</p>
                <p>Tel: {{ $sale->branch->phone ?? '+94 11 234 5678' }}</p>
            </div>
        </div>

        <!-- Meta Details -->
        <div class="py-2.5 border-b border-dashed border-gray-400 text-[11px] space-y-1 font-mono">
            <div class="flex justify-between">
                <span class="text-gray-600">INVOICE:</span>
                <span class="font-bold text-gray-900">{{ $sale->invoice_no }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">DATE:</span>
                <span>{{ $sale->created_at->format('d/m/Y h:i A') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">CASHIER:</span>
                <span>{{ $sale->cashier->name ?? 'Staff' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">REGISTER:</span>
                <span>{{ $sale->register->name ?? 'Counter 1' }}</span>
            </div>
            @if($sale->customer)
                <div class="flex justify-between pt-1 border-t border-dotted border-gray-300">
                    <span class="text-gray-600">CUSTOMER:</span>
                    <span class="font-semibold">{{ $sale->customer->name }}</span>
                </div>
            @endif
            @if($sale->pricing_mode === 'wholesale')
                <div class="flex justify-between pt-1 border-t border-dotted border-gray-300 text-purple-700 font-bold">
                    <span>MODE:</span>
                    <span>WHOLESALE B2B TIER</span>
                </div>
            @endif
        </div>

        <!-- Items Table -->
        <div class="py-2.5 border-b border-dashed border-gray-400">
            <table class="w-full text-left font-mono text-[11px]">
                <thead>
                    <tr class="border-b border-gray-400 text-gray-700 font-bold">
                        <th class="pb-1">ITEM</th>
                        <th class="pb-1 text-center">QTY</th>
                        <th class="pb-1 text-right">PRICE</th>
                        <th class="pb-1 text-right">TOTAL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dotted divide-gray-200">
                    @foreach($sale->items as $item)
                        <tr>
                            <td class="py-1.5 pr-1 font-sans">
                                <div class="font-bold text-gray-900 leading-tight">
                                    {{ $item->productVariation->product->name ?? 'Product' }}
                                </div>
                                @if($item->is_exchange)
                                    <div class="inline-block px-1 py-0.2 rounded text-[8px] font-black uppercase bg-red-100 text-red-700 border border-red-300 mt-0.5">
                                        [Exchange / Return]
                                    </div>
                                    @if($item->exchange_reason)
                                        <div class="text-[8px] text-red-600 italic">"{{ $item->exchange_reason }}"</div>
                                    @endif
                                @endif
                                @if($item->productVariation && $item->productVariation->name !== 'Standard')
                                    <div class="text-[9px] text-gray-600 font-mono">
                                        {{ $item->productVariation->name }} ({{ $item->productVariation->sku }})
                                    </div>
                                @endif

                                @if(!empty($item->serial_numbers) && is_array($item->serial_numbers))
                                    <div class="mt-1 text-[9px] text-gray-800 font-mono bg-gray-100 p-1 rounded border border-gray-200">
                                        @foreach($item->serial_numbers as $sn)
                                            <div class="flex items-center justify-between">
                                                <span>IMEI: <strong>{{ $sn }}</strong></span>
                                                @if($item->productVariation->warranty_months > 0)
                                                    <span class="text-gray-600 text-[8px] uppercase font-bold">{{ $item->productVariation->warranty_months }}M Warranty</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="py-1.5 text-center font-bold text-gray-800 align-top">
                                {{ $item->quantity }}
                            </td>
                            <td class="py-1.5 text-right text-gray-700 align-top">
                                {{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="py-1.5 text-right font-bold {{ $item->is_exchange || $item->subtotal < 0 ? 'text-red-600' : 'text-gray-900' }} align-top">
                                {{ $item->subtotal < 0 ? '- ' . number_format(abs($item->subtotal), 2) : number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Calculations -->
        <div class="py-2.5 border-b border-dashed border-gray-400 text-[11px] font-mono space-y-1">
            <div class="flex justify-between text-gray-700">
                <span>SUBTOTAL:</span>
                <span>Rs. {{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if($sale->discount_amount > 0)
                <div class="flex justify-between text-gray-700">
                    <span>DISCOUNT:</span>
                    <span>- Rs. {{ number_format($sale->discount_amount, 2) }}</span>
                </div>
            @endif
            @if($sale->loyalty_discount_amount > 0)
                <div class="flex justify-between text-emerald-700 font-bold">
                    <span>LOYALTY REDEEMED ({{ $sale->loyalty_points_redeemed }} pts):</span>
                    <span>- Rs. {{ number_format($sale->loyalty_discount_amount, 2) }}</span>
                </div>
            @endif
            @if($sale->tax_amount > 0)
                <div class="flex justify-between text-gray-700">
                    <span>TAX ({{ $sale->tax_rate }}%):</span>
                    <span>Rs. {{ number_format($sale->tax_amount, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between text-base font-black text-gray-900 pt-1.5 border-t border-gray-400">
                <span>NET TOTAL:</span>
                <span>Rs. {{ number_format($sale->grand_total, 2) }}</span>
            </div>
        </div>

        <!-- Payments Tendered -->
        <div class="py-2.5 border-b border-dashed border-gray-400 text-[11px] font-mono space-y-1">
            @foreach($sale->payments as $payment)
                <div class="flex justify-between text-gray-700 uppercase">
                    <span>PAID ({{ $payment->payment_method === 'qr' ? 'LANKAQR CBSL' : $payment->payment_method }}):</span>
                    <span>Rs. {{ number_format($payment->amount, 2) }}</span>
                </div>
            @endforeach
            <div class="flex justify-between text-gray-700">
                <span>TENDERED:</span>
                <span>Rs. {{ number_format($sale->paid_amount + $sale->change_amount, 2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-gray-900">
                <span>CHANGE RETURNED:</span>
                <span>Rs. {{ number_format($sale->change_amount, 2) }}</span>
            </div>
        </div>

        <!-- Loyalty Points Summary (if customer exists) -->
        @if($sale->customer)
            <div class="py-2 border-b border-dashed border-gray-400 text-[10px] text-center font-mono">
                <p class="font-bold text-gray-800">
                    Loyalty Points Earned: +{{ floor($sale->grand_total / 100) }} pts
                </p>
                <p class="text-gray-600">
                    Current Points Balance: {{ $sale->customer->loyalty_points }} pts
                </p>
            </div>
        @endif

        <!-- Footer Policy & Barcode -->
        <div class="pt-3 text-center space-y-2">
            <p class="text-[10px] text-gray-600 font-medium leading-tight">
                {{ $sale->branch->receipt_footer ?? 'Thank you for shopping with PSI One Global! Please retain bill for exchange within 7 days in original condition with tags.' }}
            </p>
            <div class="font-mono text-[9px] text-gray-500 tracking-widest uppercase">
                *** PSI LUXURY RETAIL ***
            </div>
            
            <!-- Simulated Barcode Line -->
            <div class="pt-1 flex flex-col items-center">
                <div class="h-8 w-44 bg-slate-900 flex items-center justify-center text-white font-mono text-[9px] tracking-widest font-black">
                    *{{ $sale->invoice_no }}*
                </div>
            </div>
        </div>

    </div>

    <script>
        function copySmsText() {
            const text = `PSI ONE POS Receipt #{{ $sale->invoice_no }}\nTotal: Rs. {{ number_format($sale->grand_total, 2) }}\nDate: {{ $sale->created_at->format('Y-m-d H:i') }}\nView e-Receipt: {{ route('pos.receipt', $sale->id) }}`;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('smsBtnText');
                if (btn) btn.innerText = 'Copied!';
                setTimeout(() => { if (btn) btn.innerText = 'SMS'; }, 2000);
            }).catch(() => {
                prompt('Copy SMS receipt text:', text);
            });
        }

        function triggerCashDrawer() {
            fetch("{{ route('pos.open-drawer') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
            })
            .then(res => res.json())
            .then(data => {
                alert('Cash drawer pulse signal triggered successfully (ESC/POS: \\x1b\\x70\\x00\\x19\\xfa).');
            })
            .catch(err => {
                console.error(err);
            });
        }
    </script>
</body>
</html>

