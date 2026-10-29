<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Statement - {{ $branchName }} ({{ $dateLabel }})</title>
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
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 text-gray-900 font-sans antialiased">

    <!-- Top floating action bar (hidden during print) -->
    <div class="no-print max-w-4xl mx-auto mb-4 px-4 flex items-center justify-between">
        <a href="{{ route('reports.pnl', request()->query()) }}" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-xs font-bold hover:bg-gray-700 flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to P&L Analytics</span>
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Official Statement (A4 / PDF)</span>
            </button>
        </div>
    </div>

    <!-- Official A4 Document Paper Container -->
    <div class="print-page max-w-4xl mx-auto bg-white border border-gray-300 shadow-xl rounded-xl p-8 sm:p-12">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b-2 border-gray-900 pb-6 mb-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/psi-logo.png') }}" alt="PSI Logo" class="w-14 h-14 object-contain">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900">PSI ONE GLOBAL POS</h1>
                    <p class="text-xs font-semibold text-gray-600 tracking-wider">EXECUTIVE FINANCIAL DIVISION &bull; STATEMENT OF COMPREHENSIVE INCOME</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $branchName }}</p>
                    <p class="text-xs text-gray-500">Official Retail Accounting Ledger &bull; Sri Lanka</p>
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-gray-900 text-white text-xs font-black uppercase tracking-widest rounded mb-2">
                    PROFIT & LOSS STATEMENT
                </span>
                <div class="font-mono text-sm font-bold text-gray-800">Period: {{ $dateLabel }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Generated: {{ date('d M Y, h:i A') }}</div>
                <div class="text-xs text-gray-500">By: {{ auth()->user()->name ?? 'Administrator' }}</div>
            </div>
        </div>

        <!-- Executive Highlights Bar -->
        <div class="grid grid-cols-4 gap-3 border border-gray-300 rounded-lg p-3 bg-gray-50 mb-6 text-xs text-center">
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Net Sales Turnover</span>
                <span class="font-mono font-black text-sm text-gray-900">Rs. {{ number_format($netRevenue, 2) }}</span>
            </div>
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Cost of Sales (COGS)</span>
                <span class="font-mono font-black text-sm text-amber-700">Rs. {{ number_format($totalCogs, 2) }}</span>
            </div>
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Gross Profit</span>
                <span class="font-mono font-black text-sm text-emerald-700">Rs. {{ number_format($grossProfit, 2) }}</span>
                <span class="text-[10px] text-gray-500 block">({{ number_format($grossMarginPercent, 1) }}% Margin)</span>
            </div>
            <div>
                <span class="text-[10px] text-gray-500 uppercase font-bold block">Net Operating Profit</span>
                <span class="font-mono font-black text-sm {{ $netOperatingResult >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rs. {{ number_format($netOperatingResult, 2) }}
                </span>
                <span class="text-[10px] {{ $netMarginPercent >= 0 ? 'text-emerald-700' : 'text-rose-700' }} block">({{ number_format($netMarginPercent, 1) }}% Net)</span>
            </div>
        </div>

        <!-- 1. Trading Account (Sales & Gross Profit) -->
        <div class="mb-6">
            <h3 class="text-xs font-black uppercase tracking-wider text-gray-800 border-b border-gray-300 pb-1.5 mb-2 flex justify-between">
                <span>1. TRADING ACCOUNT & GROSS PROFIT</span>
                <span class="font-normal text-gray-500 text-[11px]">Amounts in LKR (Rs.)</span>
            </h3>

            <table class="w-full text-xs font-mono">
                <tbody>
                    <tr class="border-b border-gray-100">
                        <td class="py-1.5 font-sans font-medium text-gray-700">Gross Invoiced Sales (Subtotal)</td>
                        <td class="py-1.5 text-right font-bold text-gray-900">Rs. {{ number_format($grossSales, 2) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100 text-gray-600">
                        <td class="py-1.5 pl-4 font-sans font-normal text-rose-700">Less: Sales Discounts & Promotional Allowances</td>
                        <td class="py-1.5 text-right text-rose-700">- Rs. {{ number_format($totalDiscounts, 2) }}</td>
                    </tr>
                    @if($totalReturns > 0)
                        <tr class="border-b border-gray-100 text-gray-600">
                            <td class="py-1.5 pl-4 font-sans font-normal text-rose-700">Less: Sales Returns & Customer Refunds</td>
                            <td class="py-1.5 text-right text-rose-700">- Rs. {{ number_format($totalReturns, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="border-b border-gray-200 bg-gray-50/50">
                        <td class="py-2 font-sans font-bold text-gray-900">NET SALES TURNOVER</td>
                        <td class="py-2 text-right font-black text-gray-900">Rs. {{ number_format($netRevenue, 2) }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="py-1.5 font-sans font-medium text-gray-700">Less: Cost of Goods Sold (Direct Inventory Cost)</td>
                        <td class="py-1.5 text-right text-amber-700">- Rs. {{ number_format($totalCogs, 2) }}</td>
                    </tr>
                    <tr class="border-b-2 border-gray-900 bg-emerald-50/40">
                        <td class="py-2.5 font-sans font-extrabold text-gray-900 text-sm">GROSS PROFIT (TRADING SURPLUS)</td>
                        <td class="py-2.5 text-right font-black text-emerald-800 text-sm">Rs. {{ number_format($grossProfit, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 2. Operating Expenses Breakdown -->
        <div class="mb-6">
            <h3 class="text-xs font-black uppercase tracking-wider text-gray-800 border-b border-gray-300 pb-1.5 mb-2 flex justify-between">
                <span>2. OPERATING OVERHEADS & STORE EXPENSES</span>
                <span class="font-normal text-gray-500 text-[11px]">Itemized Categories</span>
            </h3>

            @if($expensesByCategory->isEmpty())
                <p class="text-xs text-gray-500 italic py-2">No operating expenses recorded for this evaluation period.</p>
            @else
                <table class="w-full text-xs font-mono">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 text-[10px] uppercase font-sans font-bold text-left">
                            <th class="py-1">Expense Category</th>
                            <th class="py-1 text-right">Transactions</th>
                            <th class="py-1 text-right">Share (%)</th>
                            <th class="py-1 text-right">Amount (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expensesByCategory as $exp)
                            <tr class="border-b border-gray-100">
                                <td class="py-1.5 font-sans text-gray-800 font-medium">{{ $exp['name'] }}</td>
                                <td class="py-1.5 text-right text-gray-500">{{ $exp['count'] }}</td>
                                <td class="py-1.5 text-right text-gray-500">{{ number_format($exp['percentage'], 1) }}%</td>
                                <td class="py-1.5 text-right text-rose-700 font-bold">- Rs. {{ number_format($exp['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="border-b-2 border-gray-900 bg-gray-50 font-sans font-bold">
                            <td colspan="2" class="py-2 font-bold text-gray-900">TOTAL OPERATING EXPENSES</td>
                            <td class="py-2 text-right font-mono text-gray-600">100.0%</td>
                            <td class="py-2 text-right font-mono font-black text-rose-800">- Rs. {{ number_format($totalExpenses, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>

        <!-- 3. Net Operating Result (Bottom Line) -->
        <div class="border-2 border-gray-900 rounded-lg p-4 bg-gray-50 mb-6">
            <div class="flex items-center justify-between text-base font-black">
                <div>
                    <span class="text-gray-900 uppercase">NET OPERATING PROFIT / (LOSS)</span>
                    <span class="block text-[10px] font-sans font-normal text-gray-600">Gross Profit (Rs. {{ number_format($grossProfit, 2) }}) minus Operating Expenses (Rs. {{ number_format($totalExpenses, 2) }})</span>
                </div>
                <div class="text-right">
                    <span class="font-mono text-xl {{ $netOperatingResult >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">
                        Rs. {{ number_format($netOperatingResult, 2) }}
                    </span>
                    <span class="block text-xs font-mono {{ $netMarginPercent >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ number_format($netMarginPercent, 2) }}% Net Margin
                    </span>
                </div>
            </div>
        </div>

        <!-- 4. Category Contribution Summary Table -->
        @if($categoryProfitability->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-xs font-black uppercase tracking-wider text-gray-800 border-b border-gray-300 pb-1.5 mb-2">
                    3. DEPARTMENTAL / CATEGORY PROFITABILITY MATRIX
                </h3>
                <table class="w-full text-xs font-mono">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 text-[10px] uppercase font-sans font-bold text-left">
                            <th class="py-1">Department</th>
                            <th class="py-1 text-right">Units</th>
                            <th class="py-1 text-right">Turnover (Rs.)</th>
                            <th class="py-1 text-right">COGS (Rs.)</th>
                            <th class="py-1 text-right">Gross Profit (Rs.)</th>
                            <th class="py-1 text-right">Margin %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($categoryProfitability as $cp)
                            <tr>
                                <td class="py-1.5 font-sans font-bold text-gray-800">{{ $cp['category'] }}</td>
                                <td class="py-1.5 text-right text-gray-600">{{ number_format($cp['qty']) }}</td>
                                <td class="py-1.5 text-right text-gray-700">Rs. {{ number_format($cp['revenue'], 2) }}</td>
                                <td class="py-1.5 text-right text-amber-700">- Rs. {{ number_format($cp['cogs'], 2) }}</td>
                                <td class="py-1.5 text-right font-bold text-emerald-800">Rs. {{ number_format($cp['profit'], 2) }}</td>
                                <td class="py-1.5 text-right font-bold text-gray-900">{{ number_format($cp['margin'], 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- 5. Formal Certification & Signature Section -->
        <div class="border-t border-gray-300 pt-8 mt-12 grid grid-cols-3 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-gray-400 pb-1 h-12 flex items-end justify-center font-cursive text-gray-700">
                    {{ auth()->user()->name ?? 'System User' }}
                </div>
                <div class="font-bold text-gray-800 mt-1">Prepared By</div>
                <div class="text-[10px] text-gray-500">{{ auth()->user()->role ?? 'Cashier / Admin' }} &bull; {{ date('d/m/Y') }}</div>
            </div>

            <div>
                <div class="border-b border-gray-400 pb-1 h-12 flex items-end justify-center text-gray-400 italic">
                    (Sign Here)
                </div>
                <div class="font-bold text-gray-800 mt-1">Checked & Verified By</div>
                <div class="text-[10px] text-gray-500">Corporate Accountant / Internal Auditor</div>
            </div>

            <div>
                <div class="border-b border-gray-400 pb-1 h-12 flex items-end justify-center text-gray-400 italic">
                    (Sign Here)
                </div>
                <div class="font-bold text-gray-800 mt-1">Approved By</div>
                <div class="text-[10px] text-gray-500">Managing Director / General Manager</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 pt-4 border-t border-gray-200 text-center text-[10px] text-gray-400">
            This document constitutes an official computerized Statement of Profit and Loss generated by Psi One Global POS. Document Integrity Hash: {{ md5($branchName . $dateLabel . $netOperatingResult) }}.
        </div>

    </div>

</body>
</html>

