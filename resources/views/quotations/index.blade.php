@extends('layouts.pos')

@section('title', 'Quotations & Proforma Invoices')

@section('content')
<div class="h-full flex flex-col justify-between overflow-hidden bg-carbon-950">
    
    <!-- Top Bar -->
    <header class="p-4 sm:p-5 border-b border-carbon-800 bg-carbon-900/40 backdrop-blur-md flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-500/10 border border-brand-500/30 flex items-center justify-center text-brand-400">
                <i class="fa-solid fa-file-invoice text-lg"></i>
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-black text-white tracking-tight flex items-center gap-2">
                    Quotations &amp; Proforma Invoices
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-500/10 text-brand-400 border border-brand-500/20">B2B Estimates</span>
                </h1>
                <p class="text-xs text-silver-400">Generate commercial estimates and convert to active POS sales in 1-click</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('quotations.create') }}" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-brand-600/30">
                <i class="fa-solid fa-plus"></i>
                <span>New Quotation</span>
            </a>
        </div>
    </header>

    <!-- Metrics Row -->
    <div class="p-4 sm:p-5 pb-0 grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0">
        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-silver-400 uppercase tracking-wider">Total Quotes</div>
                <div class="text-xl font-black text-white mt-1">{{ number_format($metrics['total']) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-carbon-800 text-silver-300 flex items-center justify-center text-sm">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>
        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Draft / Sent</div>
                <div class="text-xl font-black text-amber-300 mt-1">{{ number_format($metrics['draft']) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>
        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Accepted</div>
                <div class="text-xl font-black text-emerald-300 mt-1">{{ number_format($metrics['accepted']) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="p-3.5 rounded-2xl bg-carbon-900 border border-carbon-800 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-indigo-400 uppercase tracking-wider">Converted Sales</div>
                <div class="text-xl font-black text-indigo-300 mt-1">Rs. {{ number_format($metrics['converted_value'], 2) }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-sm">
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-5">
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <!-- Filter Bar -->
            <form method="GET" action="{{ route('quotations.index') }}" class="p-3.5 border-b border-carbon-800 flex flex-wrap items-center justify-between gap-3 bg-carbon-900/60">
                <div class="flex items-center gap-2 flex-1 min-w-[240px]">
                    <div class="relative w-full max-w-sm">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-silver-500 text-xs"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search quote #, customer, phone..." class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-carbon-800 border border-carbon-700/80 text-white text-xs placeholder:text-silver-500 focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl bg-carbon-800 border border-carbon-700/80 text-silver-300 text-xs focus:outline-none focus:border-brand-500">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    @if(request()->anyFilled(['search', 'status']))
                        <a href="{{ route('quotations.index') }}" class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-400 text-xs">Reset</a>
                    @endif
                </div>
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-carbon-800 bg-carbon-850/50 text-silver-400 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Quotation #</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Date / Expiry</th>
                            <th class="py-3 px-4">Pricing Mode</th>
                            <th class="py-3 px-4 text-right">Grand Total</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60">
                        @forelse($quotations as $quote)
                            <tr class="hover:bg-carbon-800/40 transition">
                                <td class="py-3 px-4 font-mono font-bold text-white">
                                    <a href="{{ route('quotations.show', $quote->id) }}" class="hover:text-brand-400">
                                        {{ $quote->quotation_no }}
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white">{{ $quote->customer_name ?: 'Walk-in / Generic' }}</div>
                                    @if($quote->customer_phone)
                                        <div class="text-[11px] text-silver-400">{{ $quote->customer_phone }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-silver-300">
                                    <div>{{ $quote->date->format('Y-m-d') }}</div>
                                    @if($quote->valid_until)
                                        <div class="text-[10px] text-silver-500">Exp: {{ $quote->valid_until->format('Y-m-d') }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $quote->pricing_mode === 'wholesale' ? 'bg-amber-500/10 text-amber-300 border border-amber-500/20' : 'bg-slate-700/30 text-silver-300 border border-slate-700' }}">
                                        {{ $quote->pricing_mode }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-400 text-sm">
                                    Rs. {{ number_format($quote->grand_total, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @php
                                        $badgeClasses = [
                                            'draft' => 'bg-slate-500/10 text-slate-400 border-slate-500/30',
                                            'sent' => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                            'accepted' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                            'converted' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
                                            'rejected' => 'bg-rose-500/10 text-rose-400 border-rose-500/30',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $badgeClasses[$quote->status] ?? 'bg-carbon-800 text-silver-400' }}">
                                        {{ $quote->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('quotations.show', $quote->id) }}" class="p-1.5 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white" title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('quotations.print', $quote->id) }}" target="_blank" class="p-1.5 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white" title="Print Quotation">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        @if($quote->status !== 'converted')
                                            <form method="POST" action="{{ route('quotations.convert', $quote->id) }}" onsubmit="return confirm('Convert this quotation to an official Sale Invoice and deduct inventory?');" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] flex items-center gap-1 shadow" title="1-Click Convert to Sale">
                                                    <i class="fa-solid fa-bolt"></i>
                                                    <span>Convert</span>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('pos.receipt', $quote->converted_sale_id) }}" class="px-2 py-1 rounded-lg bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 font-bold text-[10px] flex items-center gap-1" title="View Resulting Invoice">
                                                <i class="fa-solid fa-file-check"></i>
                                                <span>Invoice</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-silver-500">
                                    <i class="fa-solid fa-file-circle-xmark text-3xl mb-2 text-silver-600"></i>
                                    <div>No quotations found matching your criteria.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($quotations->hasPages())
                <div class="p-3.5 border-t border-carbon-800">
                    {{ $quotations->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

