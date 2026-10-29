@extends('layouts.pos')

@section('title', 'Quotation ' . $quotation->quotation_no)

@section('content')
<div class="h-full flex flex-col justify-between overflow-hidden bg-carbon-950">
    <!-- Header -->
    <header class="p-4 sm:p-5 border-b border-carbon-800 bg-carbon-900/40 backdrop-blur-md flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('quotations.index') }}" class="w-9 h-9 rounded-xl bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-base sm:text-lg font-black text-white tracking-tight flex items-center gap-2">
                    {{ $quotation->quotation_no }}
                    @php
                        $badgeClasses = [
                            'draft' => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
                            'sent' => 'bg-blue-500/15 text-blue-300 border-blue-500/30',
                            'accepted' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
                            'converted' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
                            'rejected' => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
                        ];
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border {{ $badgeClasses[$quotation->status] ?? 'bg-carbon-800 text-silver-400' }}">
                        {{ $quotation->status }}
                    </span>
                </h1>
                <p class="text-xs text-silver-400">Created on {{ $quotation->date->format('F d, Y') }} by {{ $quotation->user->name }} &bull; Branch: {{ $quotation->branch->name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('quotations.print', $quotation->id) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i>
                <span>Print PDF</span>
            </a>

            @if($quotation->status !== 'converted')
                <!-- Status Updater Dropdown -->
                <form method="POST" action="{{ route('quotations.status', $quotation->id) }}" class="inline">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="px-3 py-2 rounded-xl bg-carbon-800 border border-carbon-700 text-silver-300 text-xs font-bold focus:outline-none">
                        <option value="draft" {{ $quotation->status === 'draft' ? 'selected' : '' }}>Mark as Draft</option>
                        <option value="sent" {{ $quotation->status === 'sent' ? 'selected' : '' }}>Mark as Sent</option>
                        <option value="accepted" {{ $quotation->status === 'accepted' ? 'selected' : '' }}>Mark as Accepted</option>
                        <option value="rejected" {{ $quotation->status === 'rejected' ? 'selected' : '' }}>Mark as Rejected</option>
                    </select>
                </form>

                <!-- 1-Click Convert to Sale -->
                <form method="POST" action="{{ route('quotations.convert', $quotation->id) }}" onsubmit="return confirm('Convert this quotation to an active Sale invoice and deduct inventory?');" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-emerald-600/30">
                        <i class="fa-solid fa-bolt"></i>
                        <span>1-Click Convert to Sale</span>
                    </button>
                </form>
            @else
                <a href="{{ route('pos.receipt', $quotation->converted_sale_id) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-600/30">
                    <i class="fa-solid fa-receipt"></i>
                    <span>View Sale Invoice</span>
                </a>
            @endif
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
        <div class="max-w-5xl mx-auto space-y-6">
            
            @if(session('status'))
                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-sm"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <!-- Metadata Card -->
            <div class="p-6 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-xs">
                <div>
                    <span class="text-silver-500 uppercase tracking-wider font-bold block mb-1">Customer / Client</span>
                    <div class="font-bold text-white text-sm">{{ $quotation->customer_name ?: 'Generic Walk-in' }}</div>
                    @if($quotation->customer_phone)
                        <div class="text-silver-400 mt-0.5"><i class="fa-solid fa-phone mr-1 text-[10px]"></i> {{ $quotation->customer_phone }}</div>
                    @endif
                    @if($quotation->customer_email)
                        <div class="text-silver-400 mt-0.5"><i class="fa-solid fa-envelope mr-1 text-[10px]"></i> {{ $quotation->customer_email }}</div>
                    @endif
                </div>

                <div>
                    <span class="text-silver-500 uppercase tracking-wider font-bold block mb-1">Commercial Terms</span>
                    <div class="text-white font-medium">Pricing Mode: <strong class="uppercase text-brand-400 font-bold">{{ $quotation->pricing_mode }}</strong></div>
                    <div class="text-silver-400 mt-0.5">Discount: Rs. {{ number_format($quotation->discount_amount, 2) }}</div>
                    <div class="text-silver-400 mt-0.5">Tax Rate: {{ $quotation->tax_rate }}%</div>
                </div>

                <div>
                    <span class="text-silver-500 uppercase tracking-wider font-bold block mb-1">Dates &amp; Validity</span>
                    <div class="text-white font-medium">Issued: {{ $quotation->date->format('Y-m-d') }}</div>
                    @if($quotation->valid_until)
                        <div class="text-amber-400 font-medium mt-0.5">Valid Until: {{ $quotation->valid_until->format('Y-m-d') }}</div>
                    @else
                        <div class="text-silver-400 mt-0.5">Valid Until: Open</div>
                    @endif
                </div>

                <div>
                    <span class="text-silver-500 uppercase tracking-wider font-bold block mb-1">Total Valuation</span>
                    <div class="font-mono font-black text-emerald-400 text-xl">Rs. {{ number_format($quotation->grand_total, 2) }}</div>
                    <div class="text-[10px] text-silver-400 mt-0.5">{{ $quotation->items->count() }} items listed</div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="rounded-3xl bg-carbon-900 border border-carbon-800 shadow-xl overflow-hidden">
                <div class="p-4 border-b border-carbon-800 bg-carbon-850/50 flex items-center justify-between">
                    <h2 class="text-xs font-black text-silver-300 uppercase tracking-wider">Quoted Scope &amp; Deliverables</h2>
                    <span class="text-[11px] font-mono text-silver-400">{{ $quotation->items->sum('quantity') }} Total Units</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-carbon-800 bg-carbon-800/40 text-silver-400 font-bold uppercase tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">#</th>
                                <th class="py-3 px-4">Item &amp; Description</th>
                                <th class="py-3 px-4 text-center">Qty</th>
                                <th class="py-3 px-4 text-right">Unit Price</th>
                                <th class="py-3 px-4 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-carbon-800/60">
                            @foreach($quotation->items as $idx => $item)
                                <tr class="hover:bg-carbon-800/30 transition">
                                    <td class="py-3 px-4 text-center font-mono text-silver-500">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-white text-sm">{{ $item->productVariation->product->name }}</div>
                                        <div class="text-silver-400 font-mono text-[11px]">{{ $item->productVariation->name }} &bull; SKU: {{ $item->productVariation->sku }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-bold text-white">{{ $item->quantity }}</td>
                                    <td class="py-3 px-4 text-right font-mono text-silver-300">Rs. {{ number_format($item->unit_price, 2) }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-white">Rs. {{ number_format($item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-carbon-800 bg-carbon-850/40">
                                <td colspan="4" class="py-2.5 px-4 text-right font-bold text-silver-400">Subtotal:</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-white">Rs. {{ number_format($quotation->subtotal, 2) }}</td>
                            </tr>
                            @if($quotation->discount_amount > 0)
                                <tr class="bg-carbon-850/40">
                                    <td colspan="4" class="py-2 px-4 text-right font-bold text-amber-400">Discount:</td>
                                    <td class="py-2 px-4 text-right font-mono font-bold text-amber-400">-Rs. {{ number_format($quotation->discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($quotation->tax_amount > 0)
                                <tr class="bg-carbon-850/40">
                                    <td colspan="4" class="py-2 px-4 text-right font-bold text-silver-400">Tax ({{ $quotation->tax_rate }}%):</td>
                                    <td class="py-2 px-4 text-right font-mono font-bold text-white">Rs. {{ number_format($quotation->tax_amount, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="bg-carbon-800/60 border-t border-carbon-700">
                                <td colspan="4" class="py-3.5 px-4 text-right font-black text-white text-sm uppercase tracking-wider">Grand Total:</td>
                                <td class="py-3.5 px-4 text-right font-mono font-black text-emerald-400 text-base">Rs. {{ number_format($quotation->grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes & Terms -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                @if($quotation->terms)
                    <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800">
                        <span class="text-silver-500 uppercase tracking-wider font-bold block mb-2">Terms &amp; Payment Conditions</span>
                        <div class="text-silver-300 whitespace-pre-line leading-relaxed">{{ $quotation->terms }}</div>
                    </div>
                @endif

                @if($quotation->notes)
                    <div class="p-5 rounded-2xl bg-carbon-900 border border-carbon-800">
                        <span class="text-silver-500 uppercase tracking-wider font-bold block mb-2">Internal Remarks</span>
                        <div class="text-silver-300 whitespace-pre-line leading-relaxed">{{ $quotation->notes }}</div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection

