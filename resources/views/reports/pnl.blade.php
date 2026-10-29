@extends('layouts.pos')

@section('title', 'Profit & Loss (P&L) Statement & Financial Analysis')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Top Toolbar Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                <i class="fa-solid fa-file-invoice-dollar text-base"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-extrabold text-white">Profit & Loss (P&L) Statement</h2>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        FINANCIAL AUDIT
                    </span>
                </div>
                <p class="text-xs text-silver-400">
                    {{ $dateLabel }} &bull; {{ $branchName }}
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('reports.index', request()->query()) }}"
                class="px-3 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold flex items-center gap-1.5 transition touch-action-btn"
                title="Back to Reports Overview"
            >
                <i class="fa-solid fa-arrow-left"></i>
                <span>Reports Overview</span>
            </a>

            <a
                href="{{ route('reports.pnl.print', request()->query()) }}"
                target="_blank"
                class="px-3 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold flex items-center gap-1.5 transition shadow-sm touch-action-btn"
                title="Open Printable A4 Statement"
            >
                <i class="fa-solid fa-print"></i>
                <span class="hidden sm:inline">Print (A4)</span>
            </a>

            <!-- Download Native Excel (.xlsx) -->
            <a
                href="{{ route('reports.pnl.export-excel', request()->query()) }}"
                class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition touch-action-btn"
                title="Download Executive P&L as Microsoft Excel (.xlsx) Spreadsheet"
            >
                <i class="fa-solid fa-file-excel"></i>
                <span>Download Excel Sheet</span>
            </a>

            <!-- Download CSV -->
            <a
                href="{{ route('reports.pnl.export-csv', request()->query()) }}"
                class="px-3 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-emerald-400 hover:text-white border border-emerald-500/30 text-xs font-bold flex items-center gap-1.5 transition touch-action-btn"
                title="Download P&L as CSV"
            >
                <i class="fa-solid fa-file-csv"></i>
                <span>CSV</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-carbon-900/60 border-b border-carbon-800/80 px-4 sm:px-6 py-2.5 shrink-0">
        <form action="{{ route('reports.pnl') }}" method="GET" class="flex flex-wrap items-center gap-2.5 text-xs">
            
            <!-- Quick Date Presets -->
            <div class="flex items-center gap-1.5">
                <span class="text-silver-400 font-medium text-[11px] hidden sm:inline">Period:</span>
                <select name="date_filter" id="dateFilterSelect" onchange="toggleDateInputs(this.value); this.form.submit();" class="px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500">
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

            <!-- Custom Date Range Inputs -->
            <div id="customDateInputs" class="{{ $dateFilter == 'custom' ? 'flex' : 'hidden' }} items-center gap-2">
                <input 
                    type="date" 
                    name="start_date" 
                    value="{{ $startDate }}"
                    placeholder="Start Date" 
                    class="px-2.5 py-1 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                >
                <span class="text-silver-500">to</span>
                <input 
                    type="date" 
                    name="end_date" 
                    value="{{ $endDate }}"
                    placeholder="End Date" 
                    class="px-2.5 py-1 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                >
                <button type="submit" class="px-2.5 py-1 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold">
                    Filter
                </button>
            </div>

            <!-- Branch Filter -->
            <div class="flex items-center gap-1.5 ml-auto">
                <span class="text-silver-400 font-medium text-[11px] hidden sm:inline">Branch:</span>
                <select name="branch_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500">
                    <option value="">All Outlets Combined</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>

                @if(request()->filled('branch_id') || request()->filled('date_filter') || request()->filled('start_date'))
                    <a href="{{ route('reports.pnl') }}" class="px-2.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Scrollable Content Area -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

        <!-- 1. Executive Performance Banner -->
        @if($netOperatingResult >= 0)
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 flex flex-wrap items-center justify-between gap-3 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 shrink-0">
                        <i class="fa-solid fa-arrow-trend-up text-lg"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-white">Net Operating Profit: Rs. {{ number_format($netOperatingResult, 2) }} ({{ number_format($netMarginPercent, 1) }}% Net Margin)</h4>
                        <p class="text-xs text-emerald-400/90 mt-0.5">The business operations yielded positive net earnings after covering inventory purchase costs and all store overheads.</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                    OPERATIONAL SURPLUS
                </span>
            </div>
        @else
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 flex flex-wrap items-center justify-between gap-3 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/40 flex items-center justify-center text-rose-400 shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-white">Net Operating Deficit / Loss: - Rs. {{ number_format(abs($netOperatingResult), 2) }} ({{ number_format($netMarginPercent, 1) }}% Net Margin)</h4>
                        <p class="text-xs text-rose-400/90 mt-0.5">Operating overheads (Rs. {{ number_format($totalExpenses, 2) }}) exceeded realized gross profit (Rs. {{ number_format($grossProfit, 2) }}) during this evaluation period.</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-rose-500/20 text-rose-300 border border-rose-500/40">
                    OPERATING DEFICIT
                </span>
            </div>
        @endif

        <!-- 2. Top Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Net Turnover -->
            <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-silver-400 font-bold uppercase tracking-wider">Net Sales Turnover</span>
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                </div>
                <div class="text-2xl font-black text-white font-mono">
                    Rs. {{ number_format($netRevenue, 2) }}
                </div>
                <div class="text-[10px] text-silver-500 flex items-center justify-between">
                    <span>Gross: Rs. {{ number_format($grossSales, 2) }}</span>
                    <span>Disc/Refund: -{{ number_format($totalDiscounts + $totalReturns, 2) }}</span>
                </div>
            </div>

            <!-- Cost of Goods Sold -->
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
                <p class="text-[10px] text-silver-500">Direct purchase cost of inventory items sold</p>
            </div>

            <!-- Gross Profit & Margin -->
            <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-silver-400 font-bold uppercase tracking-wider">Gross Profit</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-mono">
                        {{ number_format($grossMarginPercent, 1) }}% Margin
                    </span>
                </div>
                <div class="text-2xl font-black text-emerald-400 font-mono">
                    Rs. {{ number_format($grossProfit, 2) }}
                </div>
                <div class="text-[10px] text-silver-500 flex items-center justify-between">
                    <span>Markup: {{ number_format($markupPercent, 1) }}%</span>
                    <span>{{ $salesCount }} sales completed</span>
                </div>
            </div>

            <!-- Net Operating Profit / Loss -->
            <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] text-silver-400 font-bold uppercase tracking-wider">Net Operating Result</span>
                    <div class="w-8 h-8 rounded-lg {{ $netOperatingResult >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }} flex items-center justify-center text-xs">
                        <i class="fa-solid {{ $netOperatingResult >= 0 ? 'fa-chart-line' : 'fa-chart-line-down' }}"></i>
                    </div>
                </div>
                <div class="text-2xl font-black {{ $netOperatingResult >= 0 ? 'text-emerald-400' : 'text-rose-400' }} font-mono">
                    Rs. {{ number_format($netOperatingResult, 2) }}
                </div>
                <div class="text-[10px] text-silver-500 flex items-center justify-between">
                    <span>Expenses: Rs. {{ number_format($totalExpenses, 2) }}</span>
                    <span class="{{ $netMarginPercent >= 0 ? 'text-emerald-400' : 'text-rose-400' }} font-mono font-bold">{{ number_format($netMarginPercent, 1) }}% Net</span>
                </div>
            </div>

        </div>

        <!-- 3. Financial Statements Grid: Formal Income Statement vs Operating Expenses Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Formal Income Statement (Trading & Profit & Loss Ledger) -->
            <div class="bg-carbon-900 border border-carbon-800 rounded-3xl p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-brand-500"></i>
                        <span>Income Statement Ledger (P&L)</span>
                    </h3>
                    <span class="text-[11px] text-silver-400 font-mono">{{ $dateLabel }}</span>
                </div>

                <div class="space-y-3 font-mono text-xs">
                    <!-- Revenue Section -->
                    <div class="text-[10px] font-sans font-bold uppercase tracking-wider text-silver-400 border-b border-carbon-800/60 pb-1">
                        1. Trading Revenue
                    </div>
                    <div class="flex justify-between text-silver-300">
                        <span>Gross Invoiced Sales:</span>
                        <span class="text-white font-bold">Rs. {{ number_format($grossSales, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-rose-400">
                        <span>(-) Sales Discounts Allowed:</span>
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
                        <span class="font-sans">(=) Net Sales Turnover:</span>
                        <span class="text-blue-400">Rs. {{ number_format($netRevenue, 2) }}</span>
                    </div>

                    <!-- COGS Section -->
                    <div class="text-[10px] font-sans font-bold uppercase tracking-wider text-silver-400 border-b border-carbon-800/60 pt-2 pb-1">
                        2. Cost of Sales
                    </div>
                    <div class="flex justify-between text-amber-400">
                        <span>(-) Cost of Goods Sold (COGS):</span>
                        <span>- Rs. {{ number_format($totalCogs, 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-carbon-800 flex justify-between font-bold text-white">
                        <span class="font-sans">(=) Gross Profit (Trading Surplus):</span>
                        <span class="text-emerald-400 text-sm">Rs. {{ number_format($grossProfit, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-[11px] text-silver-400 font-sans">
                        <span>Gross Profit Margin %:</span>
                        <span class="font-mono text-emerald-400 font-bold">{{ number_format($grossMarginPercent, 2) }}%</span>
                    </div>

                    <!-- Expenses Section -->
                    <div class="text-[10px] font-sans font-bold uppercase tracking-wider text-silver-400 border-b border-carbon-800/60 pt-2 pb-1">
                        3. Operating Overheads
                    </div>
                    <div class="flex justify-between text-rose-400">
                        <span>(-) Total Operating Expenses:</span>
                        <span>- Rs. {{ number_format($totalExpenses, 2) }}</span>
                    </div>

                    <!-- Bottom Line -->
                    <div class="pt-3 border-t-2 border-carbon-700 flex justify-between font-black text-sm">
                        <span class="text-white uppercase font-sans">Net Operating Result:</span>
                        <span class="{{ $netOperatingResult >= 0 ? 'text-emerald-400' : 'text-rose-400' }} text-base">
                            Rs. {{ number_format($netOperatingResult, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between text-[11px] text-silver-400 font-sans">
                        <span>Net Profit Margin %:</span>
                        <span class="font-mono {{ $netMarginPercent >= 0 ? 'text-emerald-400' : 'text-rose-400' }} font-bold">{{ number_format($netMarginPercent, 2) }}%</span>
                    </div>
                </div>
            </div>

            <!-- Operating Expenses Breakdown by Category -->
            <div class="bg-carbon-900 border border-carbon-800 rounded-3xl p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-rose-500"></i>
                        <span>Operating Expenses Breakdown</span>
                    </h3>
                    <span class="text-xs font-mono font-bold text-rose-400">Total: Rs. {{ number_format($totalExpenses, 2) }}</span>
                </div>

                @if($expensesByCategory->isEmpty())
                    <div class="text-center py-10 space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-carbon-800 text-silver-500 mx-auto flex items-center justify-center text-lg">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                        <p class="text-xs text-silver-400">No operational expenses recorded during this period.</p>
                    </div>
                @else
                    <div class="space-y-3.5">
                        @foreach($expensesByCategory as $exp)
                            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="font-bold text-white flex items-center gap-2">
                                        <i class="fa-solid fa-circle-dot text-rose-400 text-[10px]"></i>
                                        <span>{{ $exp['name'] }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-mono font-bold text-rose-300">Rs. {{ number_format($exp['amount'], 2) }}</span>
                                        <span class="text-[10px] text-silver-400 block font-mono">({{ number_format($exp['percentage'], 1) }}% of total)</span>
                                    </div>
                                </div>
                                <!-- Expense share progress bar -->
                                <div class="w-full bg-carbon-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-gradient-to-r from-rose-600 to-amber-500 h-1.5 rounded-full" style="width: {{ min(100, max(2, $exp['percentage'])) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <!-- 4. Category-Wise Profitability Breakdown -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-3xl overflow-hidden shadow-xl p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-carbon-800 pb-3 flex-wrap gap-2">
                <div>
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-purple-400"></i>
                        <span>Category Profitability & Margin Contribution</span>
                    </h3>
                    <p class="text-[11px] text-silver-400 mt-0.5">Which product departments generate the highest margins?</p>
                </div>
            </div>

            @if($categoryProfitability->isEmpty())
                <div class="text-center py-8 text-xs text-silver-400">
                    No sales items found in this timeframe to compute department profitability.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400 bg-carbon-850/60">
                                <th class="p-3">Department / Category</th>
                                <th class="p-3 text-right">Units Sold</th>
                                <th class="p-3 text-right">Revenue (Rs)</th>
                                <th class="p-3 text-right">COGS Cost (Rs)</th>
                                <th class="p-3 text-right">Gross Profit (Rs)</th>
                                <th class="p-3 text-right">Margin %</th>
                                <th class="p-3 text-center">Profitability Tier</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon-800 font-mono">
                            @foreach($categoryProfitability as $cp)
                                <tr class="hover:bg-carbon-850/50 transition">
                                    <td class="p-3 font-sans font-bold text-white flex items-center gap-2">
                                        <i class="fa-solid fa-tag text-purple-400 text-[10px]"></i>
                                        <span>{{ $cp['category'] }}</span>
                                    </td>
                                    <td class="p-3 text-right text-silver-300">{{ number_format($cp['qty']) }}</td>
                                    <td class="p-3 text-right text-silver-200">Rs. {{ number_format($cp['revenue'], 2) }}</td>
                                    <td class="p-3 text-right text-amber-400">- Rs. {{ number_format($cp['cogs'], 2) }}</td>
                                    <td class="p-3 text-right font-bold text-emerald-400">Rs. {{ number_format($cp['profit'], 2) }}</td>
                                    <td class="p-3 text-right font-bold text-white">{{ number_format($cp['margin'], 1) }}%</td>
                                    <td class="p-3 text-center">
                                        @if($cp['margin'] >= 50)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                High Margin
                                            </span>
                                        @elseif($cp['margin'] >= 20)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                                Healthy
                                            </span>
                                        @elseif($cp['margin'] >= 0)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                Low Margin
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                Negative Loss
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- 5. Top Profit-Generating Products Matrix -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-3xl overflow-hidden shadow-xl p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-carbon-800 pb-3 flex-wrap gap-2">
                <div>
                    <h3 class="text-xs font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-trophy text-amber-400"></i>
                        <span>Top Profit-Generating Products</span>
                    </h3>
                    <p class="text-[11px] text-silver-400 mt-0.5">Top inventory items driving gross cash flow surplus</p>
                </div>
            </div>

            @if($productProfitability->isEmpty())
                <div class="text-center py-8 text-xs text-silver-400">
                    No individual product sales data recorded for this period.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400 bg-carbon-850/60">
                                <th class="p-3">Product Name</th>
                                <th class="p-3">SKU</th>
                                <th class="p-3 text-right">Units Sold</th>
                                <th class="p-3 text-right">Revenue (Rs)</th>
                                <th class="p-3 text-right">Cost (COGS)</th>
                                <th class="p-3 text-right">Gross Profit (Rs)</th>
                                <th class="p-3 text-right">Margin %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon-800 font-mono">
                            @foreach($productProfitability as $pp)
                                <tr class="hover:bg-carbon-850/50 transition">
                                    <td class="p-3 font-sans font-bold text-white">{{ $pp['product'] }}</td>
                                    <td class="p-3 text-silver-400 text-[11px]">{{ $pp['sku'] ?? 'N/A' }}</td>
                                    <td class="p-3 text-right text-silver-300">{{ number_format($pp['qty']) }}</td>
                                    <td class="p-3 text-right text-silver-200">Rs. {{ number_format($pp['revenue'], 2) }}</td>
                                    <td class="p-3 text-right text-amber-400">- Rs. {{ number_format($pp['cogs'], 2) }}</td>
                                    <td class="p-3 text-right font-bold text-emerald-400">Rs. {{ number_format($pp['profit'], 2) }}</td>
                                    <td class="p-3 text-right font-bold text-white">{{ number_format($pp['margin'], 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

</div>

<script>
function toggleDateInputs(val) {
    const el = document.getElementById('customDateInputs');
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

