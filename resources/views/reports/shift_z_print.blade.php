<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Shift Z-Report #{{ str_pad($shift->id, 5, '0', STR_PAD_LEFT) }} - {{ $shift->branch->name ?? 'POS' }}</title>
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
                max-width: 100% !important;
            }
            .thermal-mode {
                width: 80mm !important;
                max-width: 80mm !important;
                padding: 4mm !important;
                font-size: 11px !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 text-gray-900 font-sans antialiased">

    <!-- Top floating action bar (hidden during print) -->
    <div class="no-print max-w-4xl mx-auto mb-4 px-4 flex items-center justify-between">
        <a href="{{ route('reports.shifts') }}" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-xs font-bold hover:bg-gray-700 flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Shifts List</span>
        </a>
        <div class="flex items-center gap-2">
            <button onclick="toggleThermalMode()" id="layoutToggleBtn" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-bold border border-gray-400 flex items-center gap-1.5">
                <i class="fa-solid fa-receipt"></i>
                <span id="layoutToggleText">Switch to 80mm Thermal Slip</span>
            </button>
            <button onclick="window.print()" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Official Z-Report</span>
            </button>
        </div>
    </div>

    <!-- Official Document Container (A4 / 80mm Switchable) -->
    <div id="printContainer" class="print-page max-w-3xl mx-auto bg-white border border-gray-300 shadow-xl rounded-xl p-8 sm:p-10 transition-all">
        
        <!-- Document Header -->
        <div class="flex items-start justify-between border-b-2 border-gray-900 pb-5 mb-5">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/psi-logo.png') }}" alt="PSI Logo" class="w-12 h-12 object-contain">
                <div>
                    <h1 class="text-xl font-black tracking-tight text-gray-900">PSI ONE GLOBAL POS</h1>
                    <p class="text-[11px] font-bold text-gray-600 tracking-wider">OFFICIAL CASHIER SHIFT Z-REPORT &bull; CASH RECONCILIATION</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $shift->branch->name ?? 'Main Retail Store' }} &bull; {{ $shift->register->name ?? 'Register Terminal #1' }}</p>
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-gray-900 text-white text-xs font-black uppercase tracking-widest rounded mb-1.5">
                    Z-REPORT #{{ str_pad($shift->id, 5, '0', STR_PAD_LEFT) }}
                </span>
                <div class="text-xs text-gray-600">Status: <strong class="uppercase text-gray-900">{{ $shift->status }}</strong></div>
                <div class="text-xs text-gray-500">Printed: {{ date('d M Y, h:i A') }}</div>
            </div>
        </div>

        <!-- Shift & Cashier Meta Grid -->
        <div class="grid grid-cols-2 gap-4 border border-gray-300 rounded-lg p-3 bg-gray-50 mb-5 text-xs">
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Assigned Cashier</span>
                <span class="font-bold text-gray-900 text-sm">{{ $shift->user->name ?? 'System Cashier' }}</span>
                <span class="text-gray-500 block text-[11px]">Staff ID: #{{ $shift->user->id ?? '-' }} &bull; Role: {{ strtoupper($shift->user->role ?? 'cashier') }}</span>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Shift Period (Timeframe)</span>
                <span class="font-mono text-gray-900 font-bold">Opened: {{ $shift->opened_at ? $shift->opened_at->format('d M Y, h:i A') : '-' }}</span>
                <span class="font-mono text-gray-600 block text-[11px]">Closed: {{ $shift->closed_at ? $shift->closed_at->format('d M Y, h:i A') : 'Active Shift' }}</span>
            </div>
        </div>

        <!-- Cash Reconciliation Summary Matrix -->
        <div class="mb-5">
            <h3 class="text-xs font-black uppercase tracking-wider text-gray-800 border-b border-gray-300 pb-1 mb-2 flex justify-between">
                <span>1. CASH DRAWER AUDIT & RECONCILIATION</span>
                <span class="font-normal text-gray-500 text-[11px]">Amounts in LKR (Rs.)</span>
            </h3>

            <table class="w-full text-xs font-mono">
                <tbody>
                    <tr class="border-b border-gray-100">
                        <td class="py-1.5 font-sans font-medium text-gray-700">Starting Opening Cash Float (ආරම්භක මුදල් පාවය)</td>
                        <td class="py-1.5 text-right font-bold text-gray-900">Rs. {{ number_format($openingFloat, 2) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100 bg-emerald-50/30">
                        <td class="py-1.5 font-sans font-medium text-emerald-800">(+) Cash Inflow from Register Sales (විකුණුම් මුදල්)</td>
                        <td class="py-1.5 text-right font-bold text-emerald-800">+ Rs. {{ number_format($cashSales, 2) }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 bg-gray-50 font-bold">
                        <td class="py-2 font-sans text-gray-900">(=) Expected Drawer Cash (ලාච්චුවේ තිබිය යුතු මුදල)</td>
                        <td class="py-2 text-right text-gray-900 font-black">Rs. {{ number_format($expectedCash, 2) }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 bg-white font-bold">
                        <td class="py-2 font-sans text-gray-900">Actual Counted Closing Cash (ගණන් කළ මුදල)</td>
                        <td class="py-2 text-right text-gray-900 font-black">Rs. {{ number_format($closingCash, 2) }}</td>
                    </tr>
                    
                    <!-- Discrepancy Row -->
                    <tr class="border-b-2 border-gray-900 {{ $discrepancy < 0 ? 'bg-rose-50 text-rose-900' : ($discrepancy > 0 ? 'bg-amber-50 text-amber-900' : 'bg-emerald-50 text-emerald-900') }}">
                        <td class="py-2.5 font-sans font-black text-sm">
                            CASH DISCREPANCY / VARIANCE (මුදල් වෙනස)
                        </td>
                        <td class="py-2.5 text-right font-black text-sm">
                            @if($discrepancy == 0)
                                <span class="text-emerald-700">BALANCED (Rs. 0.00)</span>
                            @elseif($discrepancy < 0)
                                <span class="text-rose-700">SHORTAGE: -Rs. {{ number_format(abs($discrepancy), 2) }}</span>
                            @else
                                <span class="text-amber-700">SURPLUS: +Rs. {{ number_format($discrepancy, 2) }}</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Sales by Payment Method Breakdown -->
        <div class="mb-5">
            <h3 class="text-xs font-black uppercase tracking-wider text-gray-800 border-b border-gray-300 pb-1 mb-2 flex justify-between">
                <span>2. SALES BY PAYMENT METHOD</span>
                <span class="font-normal text-gray-500 text-[11px]">{{ $totalSalesCount }} Transactions Completed</span>
            </h3>

            <table class="w-full text-xs font-mono">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-500 text-[10px] uppercase font-sans font-bold text-left">
                        <th class="py-1">Payment Method</th>
                        <th class="py-1 text-right">Transactions</th>
                        <th class="py-1 text-right">Total Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="border-b border-gray-100">
                        <td class="py-1.5 font-sans font-medium text-gray-800">Cash Currency</td>
                        <td class="py-1.5 text-right text-gray-600">-</td>
                        <td class="py-1.5 text-right font-bold text-gray-900">Rs. {{ number_format($cashSales, 2) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="py-1.5 font-sans font-medium text-gray-800">Credit / Debit Card</td>
                        <td class="py-1.5 text-right text-gray-600">-</td>
                        <td class="py-1.5 text-right font-bold text-gray-900">Rs. {{ number_format($cardSales, 2) }}</td>
                    </tr>
                    @if($otherSales > 0)
                        <tr class="border-b border-gray-100">
                            <td class="py-1.5 font-sans font-medium text-gray-800">Other (QR / Transfer / Credit)</td>
                            <td class="py-1.5 text-right text-gray-600">-</td>
                            <td class="py-1.5 text-right font-bold text-gray-900">Rs. {{ number_format($otherSales, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="border-b-2 border-gray-900 bg-gray-50 font-sans font-black">
                        <td class="py-2 text-gray-900">TOTAL SHIFT SALES TURNOVER</td>
                        <td class="py-2 text-right font-mono">{{ $totalSalesCount }}</td>
                        <td class="py-2 text-right font-mono text-emerald-800 text-sm">Rs. {{ number_format($totalTurnover ?: ($cashSales + $cardSales + $otherSales), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($shift->notes)
            <div class="p-3 rounded border border-gray-300 bg-gray-50 text-xs text-gray-700 mb-6">
                <strong class="font-bold">Cashier Shift Notes:</strong> {{ $shift->notes }}
            </div>
        @endif

        <!-- Authorization Signatures Block -->
        <div class="border-t border-gray-300 pt-6 mt-8 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-gray-400 pb-1 h-10 flex items-end justify-center font-cursive text-gray-700">
                    {{ $shift->user->name ?? 'Cashier' }}
                </div>
                <div class="font-bold text-gray-800 mt-1">Cashier's Signature & Count Confirmation</div>
                <div class="text-[10px] text-gray-500">I certify that the above cash count is true and accurate.</div>
            </div>

            <div>
                <div class="border-b border-gray-400 pb-1 h-10 flex items-end justify-center text-gray-400 italic">
                    (Sign Here)
                </div>
                <div class="font-bold text-gray-800 mt-1">Store Manager / Supervisor Verification</div>
                <div class="text-[10px] text-gray-500">Drawer audit inspected and verified for shift sign-off.</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 pt-3 border-t border-gray-200 text-center text-[10px] text-gray-400">
            Computerized Z-Report generated by Psi One Global POS. Document Integrity ID: SHIFT-{{ $shift->id }}-{{ md5($shift->id . $expectedCash . $closingCash) }}.
        </div>

    </div>

    <script>
        let isThermal = false;
        function toggleThermalMode() {
            const container = document.getElementById('printContainer');
            const text = document.getElementById('layoutToggleText');
            isThermal = !isThermal;
            if (isThermal) {
                container.classList.remove('max-w-3xl');
                container.classList.add('max-w-sm', 'thermal-mode');
                text.innerText = 'Switch to A4 Audit Sheet';
            } else {
                container.classList.remove('max-w-sm', 'thermal-mode');
                container.classList.add('max-w-3xl');
                text.innerText = 'Switch to 80mm Thermal Slip';
            }
        }
    </script>
</body>
</html>

