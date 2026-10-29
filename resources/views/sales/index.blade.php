@extends('layouts.pos')

@section('title', 'Sales Ledger & Invoices')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-receipt text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Sales Invoices & Transactions</h2>
                <p class="text-xs text-silver-400">All retail bills, payments, returns & exchange audits</p>
            </div>
        </div>

        <a 
            href="{{ route('pos.terminal') }}" 
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
        >
            <i class="fa-solid fa-cash-register"></i>
            <span>Open Cashier Register</span>
        </a>
    </div>

    <!-- Filters Bar -->
    <div class="bg-carbon-900/60 border-b border-carbon-800 px-4 sm:px-6 py-3 shrink-0">
        <form action="{{ route('sales.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-silver-400 text-xs"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search invoice # (e.g. INV-20261019) or customer phone..." 
                        class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <div class="w-48 sm:w-56">
                <select name="branch_id" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-40 sm:w-44">
                <select name="sales_channel" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="all">All Origins</option>
                    <option value="register" {{ request('sales_channel') == 'register' ? 'selected' : '' }}>Register / Staff</option>
                    <option value="online" {{ request('sales_channel') == 'online' ? 'selected' : '' }}>Online Portal</option>
                </select>
            </div>

            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-white text-xs font-semibold border border-carbon-700">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'branch_id', 'sales_channel']))
                <a href="{{ route('sales.index') }}" class="text-xs text-rose-400 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Invoice No</th>
                            <th class="py-3 px-4">Date & Time</th>
                            <th class="py-3 px-4">Branch & Register</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Sales Origin</th>
                            <th class="py-3 px-4">Payment Method</th>
                            <th class="py-3 px-4 text-right">Grand Total</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <!-- Invoice No -->
                                <td class="py-3.5 px-4 font-mono">
                                    <a href="{{ route('sales.show', $sale->id) }}" class="text-brand-400 font-bold hover:underline">
                                        {{ $sale->invoice_no }}
                                    </a>
                                    <span class="block text-[10px] text-silver-500 font-sans">By {{ $sale->cashier->name ?? 'Cashier' }}</span>
                                </td>

                                <!-- Date & Time -->
                                <td class="py-3.5 px-4 text-silver-400 text-xs">
                                    <div>{{ $sale->created_at->format('d M Y') }}</div>
                                    <div class="text-[10px] text-silver-500 font-mono">{{ $sale->created_at->format('h:i A') }}</div>
                                </td>

                                <!-- Branch & Register -->
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                        {{ $sale->branch->name ?? 'Store' }}
                                    </span>
                                    <span class="block text-[10px] text-silver-500 mt-0.5">
                                        {{ $sale->register->name ?? 'Counter' }}
                                    </span>
                                </td>

                                <!-- Customer -->
                                <td class="py-3.5 px-4">
                                    @if($sale->customer)
                                        <div class="font-bold text-white text-xs">{{ $sale->customer->name }}</div>
                                        <div class="text-[10px] text-silver-500 font-mono">{{ $sale->customer->phone }}</div>
                                    @else
                                        <span class="text-silver-500 text-xs italic">Walk-in Customer</span>
                                    @endif
                                </td>

                                <!-- Sales Origin / Channel -->
                                <td class="py-3.5 px-4">
                                    @if($sale->sales_channel === 'online')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                            <i class="fa-solid fa-globe text-[9px]"></i> Online
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            <i class="fa-solid fa-cash-register text-[9px]"></i> Register
                                        </span>
                                        <span class="block text-[10px] text-silver-400 mt-0.5">{{ $sale->cashier->name ?? 'Staff' }}</span>
                                    @endif
                                </td>

                                <!-- Payment Method -->
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($sale->payments as $payment)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-carbon-800 text-silver-300 border border-carbon-700">
                                                {{ $payment->payment_method }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Grand Total -->
                                <td class="py-3.5 px-4 text-right font-mono font-black text-white text-sm">
                                    Rs. {{ number_format($sale->grand_total, 2) }}
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ $sale->payment_status === 'paid' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($sale->payment_status === 'refunded' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-carbon-800 text-silver-400') }}">
                                        {{ $sale->payment_status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a 
                                            href="{{ route('sales.show', $sale->id) }}" 
                                            class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs transition"
                                            title="View Details & Returns"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a 
                                            href="{{ route('pos.receipt', $sale->id) }}" 
                                            target="_blank"
                                            class="px-2.5 py-1 rounded-lg bg-brand-600/20 hover:bg-brand-600 text-brand-400 hover:text-white border border-brand-500/30 text-xs transition"
                                            title="Print Receipt"
                                        >
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-silver-500">
                                    <i class="fa-solid fa-receipt text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No sales transactions recorded yet</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sales->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

