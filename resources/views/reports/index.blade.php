@extends('layouts.pos')

@section('title', 'Executive Financial & Sales Reports')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-chart-pie text-base"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-extrabold text-white">Financial & Profitability Analytics</h2>
                    <span class="text-xs text-silver-500">•</span>
                    <span class="text-xs text-silver-400 font-mono">{{ $dateLabel }}</span>
                </div>
                <p class="text-xs text-silver-400">COGS, Gross Margins, Net Operating Result & Stock Valuation</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Dedicated Profit & Loss Report View Button -->
            <a
                href="{{ route('reports.pnl', request()->query()) }}"
                class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-white text-xs font-bold border border-carbon-700 flex items-center gap-1.5 transition touch-action-btn"
                title="Comprehensive Profit & Loss Statement & Margin Analysis"
            >
                <i class="fa-solid fa-file-invoice-dollar text-emerald-400"></i>
                <span>Profit & Loss</span>
            </a>

            <!-- Download Native Excel (.xlsx) -->
            <a
                href="{{ route('reports.pnl.export-excel', request()->query()) }}"
                class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition touch-action-btn"
                title="Download Executive P&L as Microsoft Excel (.xlsx) Sheet"
            >
                <i class="fa-solid fa-file-excel"></i>
                <span>Excel Sheet (P&L)</span>
            </a>

            <!-- Print P&L -->
            <a
                href="{{ route('reports.pnl.print', request()->query()) }}"
                target="_blank"
                class="px-3 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold flex items-center gap-1.5 transition touch-action-btn"
                title="Print Formal A4 P&L Statement"
            >
                <i class="fa-solid fa-print"></i>
                <span class="hidden sm:inline">Print</span>
            </a>

            <!-- Export P&L (CSV) -->
            <a
                href="{{ route('reports.pnl.export-csv', request()->query()) }}"
                class="px-3 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-emerald-400 hover:text-white border border-emerald-500/30 text-xs font-bold flex items-center gap-1.5 transition touch-action-btn"
                title="Download P&L Statement Spreadsheet"
            >
                <i class="fa-solid fa-file-csv"></i>
                <span>Export P&L</span>
            </a>

            <!-- Z-Reports -->
            <a
                href="{{ route('reports.shifts') }}"
                class="px-3 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold flex items-center gap-1.5 transition touch-action-btn"
                title="View Cashier Shift Z-Reports"
            >
                <i class="fa-solid fa-calculator text-brand-500"></i>
                <span>Z-Reports</span>
            </a>

            <!-- Sales Invoices CSV -->
            <a
                href="{{ route('reports.export-csv') }}"
                class="px-3 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold flex items-center gap-1.5 transition touch-action-btn"
                title="Export Sales Invoices Ledger"
            >
                <i class="fa-solid fa-file-excel text-teal-400"></i>
                <span class="hidden md:inline">Sales CSV</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar with Custom Date Range Support -->
    <div class="bg-carbon-900/60 border-b border-carbon-800/80 px-4 sm:px-6 py-2.5 shrink-0">
        <form action="{{ route('reports.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5 text-xs">
            <div class="flex items-center gap-1.5">
                <span class="text-silver-400 font-medium text-[11px] hidden sm:inline">Timeframe:</span>
                <select name="date_filter" id="reportsDateFilterSelect" onchange="toggleReportsDateInputs(this.value); this.form.submit();" class="px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500">
                    <option value="all" {{ $dateFilter == 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ $dateFilter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $dateFilter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ $dateFilter == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ $dateFilter == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ $dateFilter == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_quarter" {{ $dateFilter == 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                    <option value="this_year" {{ $dateFilter == 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="custom" {{ $dateFilter == 'custom' ? 'selected' : '' }}>Custom Date Range...</option>
                </select>
            </div>

            <!-- Custom Date Inputs -->
            <div id="reportsCustomDateInputs" class="{{ $dateFilter == 'custom' ? 'flex' : 'hidden' }} items-center gap-2">
                <input 
                    type="date" 
                    name="start_date" 
                    value="{{ $startDate }}"
                    class="px-2.5 py-1 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                >
                <span class="text-silver-500">to</span>
                <input 
                    type="date" 
                    name="end_date" 
                    value="{{ $endDate }}"
                    class="px-2.5 py-1 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                >
                <button type="submit" class="px-2.5 py-1 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold">
                    Filter
                </button>
            </div>

            <div class="flex items-center gap-1.5 ml-auto">
                <span class="text-silver-400 font-medium text-[11px] hidden sm:inline">Outlet:</span>
                <select name="branch_id" onchange="this.form.submit()" class="px-3.5 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500">
                    <option value="">All Outlets</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>

                @if(request()->filled('branch_id') || request()->filled('date_filter') || request()->filled('start_date'))
                    <a href="{{ route('reports.index') }}" class="px-2.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Scrollable Report Cards -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

        <!-- 1. Executive Financial Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Net Sales -->
            <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-silver-400 font-bold uppercase tracking-wider">Net Sales Revenue</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-white font-mono">
                    Rs. {{ number_format($netRevenue, 2) }}
                </div>
                <div class="text-[10px] text-silver-500 flex items-center justify-between">
                    <span>Gross: Rs. {{ number_format($grossSales, 2) }}</span>
                    <span class="text-rose-400">Disc: -{{ number_format($totalDiscounts, 2) }}</span>
                </div>
            </div>

            <!-- Cost of Goods Sold (COGS) -->
            <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-silver-400 font-bold uppercase tracking-wider">Cost of Goods (COGS)</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-amber-300 font-mono">
                    Rs. {{ number_format($totalCogs, 2) }}
                </div>
                <p class="text-[10px] text-silver-500">Total purchase cost of items sold</p>
            </div>

            <!-- Gross Profit & Margin -->
            <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-silver-400 font-bold uppercase tracking-wider">Gross Profit</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-brand-600/20 text-brand-400 border border-brand-500/30 font-mono">
                        {{ number_format($grossMarginPercent, 1) }}% Margin
                    </span>
                </div>
                <div class="text-2xl font-black text-emerald-400 font-mono">
                    Rs. {{ number_format($grossProfit, 2) }}
                </div>
                <p class="text-[10px] text-silver-500">Sales revenue minus item purchase costs</p>
            </div>

            <!-- Net Operating Result -->
            <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-silver-400 font-bold uppercase tracking-wider">Net Operating Profit</span>
                    <div class="w-8 h-8 rounded-lg {{ $netOperatingResult >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }} flex items-center justify-center text-xs">
                        <i class="fa-solid {{ $netOperatingResult >= 0 ? 'fa-chart-line' : 'fa-chart-line-down' }}"></i>
                    </div>
                </div>
                <div class="text-2xl font-black {{ $netOperatingResult >= 0 ? 'text-emerald-400' : 'text-rose-400' }} font-mono">
                    Rs. {{ number_format($netOperatingResult, 2) }}
                </div>
                <div class="text-[10px] text-silver-500 flex items-center justify-between">
                    <span>Store Expenses: Rs. {{ number_format($totalExpenses, 2) }}</span>
                    <span class="{{ $netMarginPercent >= 0 ? 'text-emerald-400' : 'text-rose-400' }} font-mono font-bold">{{ number_format($netMarginPercent, 1) }}% Net</span>
                </div>
            </div>

        </div>

        <!-- 2. Detailed Profit & Loss Breakdown Statement & Operating Expenses -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Income & Expense Ledger -->
            <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-brand-500"></i>
                        <span>Income Statement Breakdown (P&L)</span>
                    </h3>
                    <a href="{{ route('reports.pnl', request()->query()) }}" class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
                        <span>Full Statement</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="space-y-3 font-mono text-xs">
                    <div class="flex justify-between text-silver-300">
                        <span>(+) Gross Sales:</span>
                        <span class="text-white font-bold">Rs. {{ number_format($grossSales, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-rose-400">
                        <span>(-) Discounts Allowed:</span>
                        <span>- Rs. {{ number_format($totalDiscounts, 2) }}</span>
                    </div>
                    @if($totalReturns > 0)
                        <div class="flex justify-between text-rose-400">
                            <span>(-) Sales Returns & Customer Refunds:</span>
                            <span>- Rs. {{ number_format($totalReturns, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-silver-400">
                        <span>(+) Tax / VAT Collected:</span>
                        <span>Rs. {{ number_format($totalTax, 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-carbon-800 flex justify-between font-bold text-white">
                        <span>(=) Net Sales Turnover:</span>
                        <span class="text-emerald-400">Rs. {{ number_format($netRevenue, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-amber-400">
                        <span>(-) Cost of Goods Sold (COGS):</span>
                        <span>- Rs. {{ number_format($totalCogs, 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-carbon-800 flex justify-between font-bold text-white">
                        <span>(=) Gross Profit:</span>
                        <span class="text-teal-400 text-sm">Rs. {{ number_format($grossProfit, 2) }} ({{ number_format($grossMarginPercent, 1) }}%)</span>
                    </div>
                    
                    <!-- Itemized Expenses Breakdown in Ledger -->
                    <div class="flex justify-between text-rose-400 font-bold pt-1">
                        <span>(-) Operating Expenses:</span>
                        <span>- Rs. {{ number_format($totalExpenses, 2) }}</span>
                    </div>
                    @if($expensesByCategory->isNotEmpty())
                        <div class="pl-3 py-1 space-y-1 border-l-2 border-rose-500/30 text-[11px] text-silver-400 font-sans">
                            @foreach($expensesByCategory as $exp)
                                <div class="flex justify-between items-center">
                                    <span>&bull; {{ $exp['name'] }}:</span>
                                    <span class="font-mono text-rose-300">- Rs. {{ number_format($exp['amount'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="pt-3 border-t-2 border-carbon-700 flex justify-between font-black text-sm">
                        <span class="text-white uppercase font-sans">Net Operating Result:</span>
                        <span class="{{ $netOperatingResult >= 0 ? 'text-emerald-400' : 'text-rose-400' }} text-base">
                            Rs. {{ number_format($netOperatingResult, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Methods & Stock Valuation -->
            <div class="space-y-6">
                <!-- Payment Breakdown -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 shadow-xl space-y-4">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2 border-b border-carbon-800 pb-3">
                        <i class="fa-solid fa-credit-card text-brand-500"></i>
                        <span>Payment Method Distribution</span>
                    </h3>

                    <div class="space-y-2.5">
                        @foreach($paymentBreakdown as $pay)
                            <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5 capitalize font-bold text-white">
                                    <i class="fa-solid {{ $pay->payment_method === 'cash' ? 'fa-money-bill-wave text-emerald-400' : ($pay->payment_method === 'card' ? 'fa-credit-card text-blue-400' : 'fa-building-columns text-purple-400') }}"></i>
                                    <span>{{ $pay->payment_method }}</span>
                                </div>
                                <span class="font-mono font-bold text-white">Rs. {{ number_format($pay->total, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Stock Valuation Card -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 shadow-xl space-y-3">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2 border-b border-carbon-800 pb-2">
                        <i class="fa-solid fa-boxes-stacked text-brand-500"></i>
                        <span>Total Inventory Valuation</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-3 text-xs font-mono">
                        <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800">
                            <span class="text-[10px] text-silver-400 uppercase font-sans block mb-1">Cost Value (COGS)</span>
                            <span class="text-sm font-bold text-amber-300">Rs. {{ number_format($stockValuationCost, 2) }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800">
                            <span class="text-[10px] text-silver-400 uppercase font-sans block mb-1">Retail Value</span>
                            <span class="text-sm font-bold text-emerald-400">Rs. {{ number_format($stockValuationRetail, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. Category Profitability Breakdown Matrix -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-carbon-800 pb-3 flex-wrap gap-2">
                <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-purple-400"></i>
                    <span>Category & Department Profitability</span>
                </h3>
                <a href="{{ route('reports.pnl', request()->query()) }}" class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
                    <span>Detailed P&L Matrix</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            @if($categoryProfitability->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead>
                            <tr class="border-b border-carbon-800 text-[10px] uppercase font-sans text-silver-400">
                                <th class="py-2 px-3">Category Name</th>
                                <th class="py-2 px-3 text-right">Units Sold</th>
                                <th class="py-2 px-3 text-right">Turnover (Rs)</th>
                                <th class="py-2 px-3 text-right">COGS (Rs)</th>
                                <th class="py-2 px-3 text-right">Gross Profit (Rs)</th>
                                <th class="py-2 px-3 text-right">Margin %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon-800/60 font-mono">
                            @foreach($categoryProfitability as $cp)
                                <tr class="hover:bg-carbon-850/50">
                                    <td class="py-2.5 px-3 font-sans font-bold text-white">{{ $cp['category'] }}</td>
                                    <td class="py-2.5 px-3 text-right text-silver-300">{{ number_format($cp['qty']) }}</td>
                                    <td class="py-2.5 px-3 text-right text-silver-200">Rs. {{ number_format($cp['revenue'], 2) }}</td>
                                    <td class="py-2.5 px-3 text-right text-amber-400">- Rs. {{ number_format($cp['cogs'], 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-400">Rs. {{ number_format($cp['profit'], 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-white">{{ number_format($cp['margin'], 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 text-center text-silver-500 text-xs">
                    No category sales data recorded in this period.
                </div>
            @endif
        </div>

        <!-- 4. Branch Performance Comparison -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl p-5 space-y-4">
            <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2 border-b border-carbon-800 pb-3">
                <i class="fa-solid fa-shop text-brand-500"></i>
                <span>Branch-wise Sales Comparison</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($branchSummaries as $bSummary)
                    <div class="p-4 rounded-xl bg-carbon-850 border border-carbon-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold text-white">{{ $bSummary->name }}</h4>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-carbon-800 text-silver-400">
                                {{ $bSummary->sales_count }} sales
                            </span>
                        </div>
                        <div class="text-lg font-black text-brand-400 font-mono">
                            Rs. {{ number_format($bSummary->sales_sum_grand_total ?? 0, 2) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 5. Top Fast-Moving Bestsellers -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl p-5 space-y-4">
            <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2 border-b border-carbon-800 pb-3">
                <i class="fa-solid fa-fire text-amber-500"></i>
                <span>Fast-Moving Inventory & Top Sellers</span>
            </h3>

            @if(isset($topSellers) && $topSellers->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="text-[10px] uppercase text-silver-400 border-b border-carbon-800">
                            <tr>
                                <th class="py-2 px-3 font-sans">Product Item</th>
                                <th class="py-2 px-3 text-center">SKU</th>
                                <th class="py-2 px-3 text-center">Quantity Sold</th>
                                <th class="py-2 px-3 text-right">Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon-800/60 font-sans">
                            @foreach($topSellers as $seller)
                                <tr class="hover:bg-carbon-850/50">
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-white text-xs">
                                            {{ $seller->productVariation->product->name ?? 'Product' }}
                                        </div>
                                        <div class="text-[10px] text-silver-400">
                                            {{ $seller->productVariation->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono text-silver-400 text-xs">
                                        {{ $seller->productVariation->sku ?? '-' }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-emerald-400 text-sm">
                                        {{ number_format($seller->total_qty) }} pcs
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-black text-brand-400 text-sm">
                                        Rs. {{ number_format($seller->total_revenue, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-silver-500 text-xs">
                    No sales data available for top bestsellers in this period.
                </div>
            @endif
        </div>

    </div>
</div>

<script>
function toggleReportsDateInputs(val) {
    const el = document.getElementById('reportsCustomDateInputs');
    if (val === 'custom') {
        el.classList.remove('hidden');
        el.classList.add('flex');
    } else {
        el.classList.add('hidden');
        el.classList.remove('flex');
    }
}
</script>
@endsection
