@extends('layouts.pos')

@section('title', 'Cashier Shift Z-Reports')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">
    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-calculator text-base"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-base sm:text-lg font-black text-white">Daily Cashier Shift Z-Reports</h1>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-carbon-800 text-silver-300 border border-carbon-700">Audit Trail</span>
                </div>
                <p class="text-xs text-silver-400">Shift drawer reconciliations, cash sales, card sales & shortage/overage logs</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Download Excel Sheet (.xlsx) -->
            <a
                href="{{ route('reports.shifts.export-excel', request()->query()) }}"
                class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition touch-action-btn"
                title="Download Shift Z-Reports as Microsoft Excel (.xlsx) Sheet"
            >
                <i class="fa-solid fa-file-excel"></i>
                <span>Download Excel Sheet</span>
            </a>

            <a
                href="{{ route('reports.index') }}"
                class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold transition flex items-center gap-2"
            >
                <i class="fa-solid fa-chart-pie"></i>
                <span>Reports Overview</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar with Date Range Support -->
    <div class="bg-carbon-900/60 border-b border-carbon-800/80 px-4 sm:px-6 py-2.5 shrink-0">
        <form action="{{ route('reports.shifts') }}" method="GET" class="flex flex-wrap items-center gap-2.5 text-xs">
            <div class="flex items-center gap-1.5">
                <span class="text-silver-400 font-medium text-[11px] hidden sm:inline">Timeframe:</span>
                <select name="date_filter" id="shiftsDateFilterSelect" onchange="toggleShiftsDateInputs(this.value); this.form.submit();" class="px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500">
                    <option value="all" {{ ($dateFilter ?? 'all') == 'all' ? 'selected' : '' }}>All Recorded Shifts</option>
                    <option value="today" {{ ($dateFilter ?? '') == 'today' ? 'selected' : '' }}>Today's Shifts</option>
                    <option value="yesterday" {{ ($dateFilter ?? '') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ ($dateFilter ?? '') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ ($dateFilter ?? '') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ ($dateFilter ?? '') == 'custom' ? 'selected' : '' }}>Custom Date Range...</option>
                </select>
            </div>

            <!-- Custom Date Inputs -->
            <div id="shiftsCustomDateInputs" class="{{ ($dateFilter ?? '') == 'custom' ? 'flex' : 'hidden' }} items-center gap-2">
                <input 
                    type="date" 
                    name="start_date" 
                    value="{{ $startDate ?? '' }}"
                    class="px-2.5 py-1 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                >
                <span class="text-silver-500">to</span>
                <input 
                    type="date" 
                    name="end_date" 
                    value="{{ $endDate ?? '' }}"
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
                        <option value="{{ $b->id }}" {{ ($selectedBranchId ?? '') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>

                @if(request()->filled('branch_id') || request()->filled('date_filter') || request()->filled('start_date'))
                    <a href="{{ route('reports.shifts') }}" class="px-2.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

        <!-- Top Summary Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Total Shifts -->
            <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-xs text-silver-400 font-bold uppercase">
                    <span>Evaluated Shifts</span>
                    <i class="fa-solid fa-clock-rotate-left text-brand-400"></i>
                </div>
                <div class="text-2xl font-black text-white font-mono">
                    {{ $totalShiftsCount ?? $shifts->total() }}
                </div>
                <p class="text-[10px] text-silver-500">{{ $activeShiftsCount ?? 0 }} shift(s) currently open</p>
            </div>

            <!-- Cash Sales Collected -->
            <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-xs text-silver-400 font-bold uppercase">
                    <span>Cash Inflow</span>
                    <i class="fa-solid fa-money-bill-wave text-emerald-400"></i>
                </div>
                <div class="text-2xl font-black text-emerald-400 font-mono">
                    Rs. {{ number_format($totalCashSales ?? 0, 2) }}
                </div>
                <p class="text-[10px] text-silver-500">Collected in register cash drawers</p>
            </div>

            <!-- Card Sales Collected -->
            <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-xs text-silver-400 font-bold uppercase">
                    <span>Card Inflow</span>
                    <i class="fa-solid fa-credit-card text-blue-400"></i>
                </div>
                <div class="text-2xl font-black text-blue-400 font-mono">
                    Rs. {{ number_format($totalCardSales ?? 0, 2) }}
                </div>
                <p class="text-[10px] text-silver-500">Processed through card terminals</p>
            </div>

            <!-- Net Discrepancy -->
            <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 shadow-xl space-y-1">
                <div class="flex items-center justify-between text-xs text-silver-400 font-bold uppercase">
                    <span>Net Discrepancy</span>
                    <i class="fa-solid fa-scale-balanced {{ ($totalDiscrepancy ?? 0) >= 0 ? 'text-emerald-400' : 'text-rose-400' }}"></i>
                </div>
                <div class="text-2xl font-black {{ ($totalDiscrepancy ?? 0) == 0 ? 'text-emerald-400' : (($totalDiscrepancy ?? 0) > 0 ? 'text-amber-400' : 'text-rose-400') }} font-mono">
                    Rs. {{ number_format($totalDiscrepancy ?? 0, 2) }}
                </div>
                <p class="text-[10px] text-silver-500">
                    @if(($totalDiscrepancy ?? 0) == 0)
                        Balanced across all counters
                    @elseif(($totalDiscrepancy ?? 0) < 0)
                        Total Shortage: -Rs. {{ number_format(abs($totalDiscrepancy ?? 0), 2) }}
                    @else
                        Total Surplus: +Rs. {{ number_format($totalDiscrepancy ?? 0, 2) }}
                    @endif
                </p>
            </div>

        </div>
        
        <!-- Shifts Table Card -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850/80 border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Shift Details</th>
                            <th class="py-3 px-4">Cashier & Terminal</th>
                            <th class="py-3 px-4 text-right">Opening Float</th>
                            <th class="py-3 px-4 text-right">Cash Sales</th>
                            <th class="py-3 px-4 text-right">Card Sales</th>
                            <th class="py-3 px-4 text-right">Counted Cash</th>
                            <th class="py-3 px-4 text-right">Discrepancy</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Print / Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($shifts as $s)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <!-- Shift Period -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white text-xs font-mono flex items-center gap-1.5">
                                        <span class="text-brand-400">#{{ str_pad($s->id, 5, '0', STR_PAD_LEFT) }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $s->opened_at ? $s->opened_at->format('d M Y, h:i A') : '-' }}</span>
                                    </div>
                                    <div class="text-[10px] text-silver-500 font-mono">
                                        Closed: {{ $s->closed_at ? $s->closed_at->format('h:i A') : 'Active Shift' }}
                                    </div>
                                </td>

                                <!-- Cashier & Terminal -->
                                <td class="py-3.5 px-4 font-bold text-silver-200">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-brand-600/20 text-brand-400 flex items-center justify-center text-[10px] font-bold">
                                            {{ substr($s->user->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-white text-xs">{{ $s->user->name ?? 'Unknown Cashier' }}</div>
                                            <div class="text-[10px] text-silver-400 font-normal">
                                                {{ $s->register->name ?? 'Terminal' }} &bull; {{ $s->branch->name ?? 'Main' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Opening Float -->
                                <td class="py-3.5 px-4 text-right font-mono text-silver-400">
                                    Rs. {{ number_format($s->opening_float, 2) }}
                                </td>

                                <!-- Cash Sales -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-400">
                                    Rs. {{ number_format($s->cash_sales, 2) }}
                                </td>

                                <!-- Card Sales -->
                                <td class="py-3.5 px-4 text-right font-mono text-blue-400">
                                    Rs. {{ number_format($s->card_sales, 2) }}
                                </td>

                                <!-- Counted Cash -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-white">
                                    Rs. {{ number_format($s->closing_cash ?? ($s->opening_float + $s->cash_sales), 2) }}
                                </td>

                                <!-- Discrepancy -->
                                <td class="py-3.5 px-4 text-right font-mono font-black">
                                    @if($s->status === 'open')
                                        <span class="text-silver-500 text-[10px] font-sans">Active Shift</span>
                                    @elseif($s->cash_discrepancy == 0)
                                        <span class="text-emerald-400 text-xs">Balanced (0.00)</span>
                                    @elseif($s->cash_discrepancy < 0)
                                        <span class="text-rose-400 text-xs px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/20">
                                            -Rs. {{ number_format(abs($s->cash_discrepancy), 2) }}
                                        </span>
                                    @else
                                        <span class="text-amber-400 text-xs px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">
                                            +Rs. {{ number_format($s->cash_discrepancy, 2) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($s->status === 'open')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 animate-pulse">
                                            OPEN
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-carbon-800 text-silver-400 border border-carbon-700">
                                            CLOSED
                                        </span>
                                    @endif
                                </td>

                                <!-- Print Action Button -->
                                <td class="py-3.5 px-4 text-center">
                                    <a
                                        href="{{ route('reports.shifts.print', $s->id) }}"
                                        target="_blank"
                                        class="px-2.5 py-1.5 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-200 hover:text-white border border-carbon-700 text-[11px] font-bold inline-flex items-center gap-1.5 transition touch-action-btn"
                                        title="Print Official Shift Z-Report (A4 / 80mm POS Slip)"
                                    >
                                        <i class="fa-solid fa-print text-brand-400"></i>
                                        <span>Print Z-Report</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-calculator text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No cashier shift records found for this period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($shifts->hasPages())
                <div class="p-4 border-t border-carbon-800">
                    {{ $shifts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function toggleShiftsDateInputs(val) {
    const el = document.getElementById('shiftsCustomDateInputs');
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
