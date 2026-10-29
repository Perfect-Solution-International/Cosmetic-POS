@extends('layouts.pos')

@section('title', 'Physical Stock Takes & Inventory Audits')

@section('content')
<div class="h-full flex flex-col justify-between overflow-hidden bg-carbon-950">
    <!-- Header -->
    <header class="p-4 sm:p-5 border-b border-carbon-800 bg-carbon-900/40 backdrop-blur-md flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                <i class="fa-solid fa-clipboard-check text-lg"></i>
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-black text-white tracking-tight flex items-center gap-2">
                    Physical Stock Takes &amp; Cycle Audits
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Variance Reconciliation</span>
                </h1>
                <p class="text-xs text-silver-400">Scan barcodes, compare system vs physical counts, and reconcile inventory discrepancies</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('inventory.stock_takes.create') }}" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-black text-xs font-black transition flex items-center gap-2 shadow-lg shadow-amber-600/30">
                <i class="fa-solid fa-barcode"></i>
                <span>Start New Stock Take</span>
            </a>
        </div>
    </header>

    <!-- Metrics Row -->
    <div class="p-4 sm:p-5 pb-0 grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0">
        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-silver-400 uppercase tracking-wider">Total Audits</div>
                <div class="text-xl font-black text-white mt-1">{{ number_format($metrics['total']) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-carbon-800 text-silver-300 flex items-center justify-center text-sm">
                <i class="fa-solid fa-list-check"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">In Progress</div>
                <div class="text-xl font-black text-amber-300 mt-1">{{ number_format($metrics['in_progress']) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm">
                <i class="fa-solid fa-spinner fa-spin-pulse"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Completed &amp; Synced</div>
                <div class="text-xl font-black text-emerald-300 mt-1">{{ number_format($metrics['completed']) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm">
                <i class="fa-solid fa-check-double"></i>
            </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-rose-400 uppercase tracking-wider">Net Variance Valuation</div>
                <div class="text-xl font-black text-rose-300 mt-1">Rs. {{ number_format($metrics['total_variance_cost'], 2) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-sm">
                <i class="fa-solid fa-scale-unbalanced"></i>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-5">
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-carbon-800 bg-carbon-850/50 text-silver-400 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Audit Ref #</th>
                            <th class="py-3 px-4">Branch</th>
                            <th class="py-3 px-4">Audited By</th>
                            <th class="py-3 px-4">Started / Closed</th>
                            <th class="py-3 px-4 text-center">System Qty</th>
                            <th class="py-3 px-4 text-center">Physical Qty</th>
                            <th class="py-3 px-4 text-center">Variance (Units)</th>
                            <th class="py-3 px-4 text-right">Variance Value</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60">
                        @forelse($stockTakes as $st)
                            <tr class="hover:bg-carbon-800/40 transition">
                                <td class="py-3 px-4 font-mono font-bold text-white">
                                    <a href="{{ route('inventory.stock_takes.show', $st->id) }}" class="hover:text-amber-400">
                                        {{ $st->reference_no }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 font-medium text-white">{{ $st->branch->name }}</td>
                                <td class="py-3 px-4 text-silver-300">{{ $st->user->name }}</td>
                                <td class="py-3 px-4 text-silver-400">
                                    <div>{{ $st->started_at->format('Y-m-d H:i') }}</div>
                                    @if($st->completed_at)
                                        <div class="text-[10px] text-emerald-400">Closed: {{ $st->completed_at->format('Y-m-d H:i') }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-silver-300">{{ number_format($st->total_system_qty) }}</td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-white">{{ number_format($st->total_physical_qty) }}</td>
                                <td class="py-3 px-4 text-center font-mono font-black {{ $st->total_variance_qty < 0 ? 'text-rose-400' : ($st->total_variance_qty > 0 ? 'text-emerald-400' : 'text-silver-400') }}">
                                    {{ $st->total_variance_qty > 0 ? '+' : '' }}{{ number_format($st->total_variance_qty) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ $st->total_variance_cost < 0 ? 'text-rose-400' : ($st->total_variance_cost > 0 ? 'text-emerald-400' : 'text-silver-300') }}">
                                    Rs. {{ number_format($st->total_variance_cost, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($st->status === 'in_progress')
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            Scanning Active
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            Reconciled
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('inventory.stock_takes.show', $st->id) }}" class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white font-bold flex items-center gap-1">
                                            <i class="fa-solid fa-folder-open"></i>
                                            <span>{{ $st->status === 'in_progress' ? 'Continue Scan' : 'Review' }}</span>
                                        </a>
                                        <a href="{{ route('inventory.stock_takes.print', $st->id) }}" target="_blank" class="p-1 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-400 hover:text-white" title="Print Discrepancy Report">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-silver-500">
                                    <i class="fa-solid fa-boxes-packing text-3xl mb-2 text-silver-600"></i>
                                    <div>No stock take audit sessions recorded yet. Start your first count session!</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($stockTakes->hasPages())
                <div class="p-3.5 border-t border-carbon-800">
                    {{ $stockTakes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

