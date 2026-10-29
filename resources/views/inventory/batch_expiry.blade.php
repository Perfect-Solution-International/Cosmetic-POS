@extends('layouts.pos')

@section('title', 'Cosmetics Batch & Expiry Tracker')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Top Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400">
                <i class="fa-solid fa-calendar-xmark text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Cosmetics Batch & Expiry Monitor</h2>
                <p class="text-xs text-silver-400">FEFO (First Expired First Out) stock tracking & near-expiry warnings</p>
            </div>
        </div>

        <!-- Branch Selector -->
        <form action="{{ route('inventory.batch_expiry') }}" method="GET" class="flex items-center gap-2">
            <select name="branch_id" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500">
                <option value="">All Branches & Warehouses</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Expiry Alert Stat Strips -->
    <div class="bg-carbon-900/60 border-b border-carbon-800 px-4 sm:px-6 py-3.5 shrink-0">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <!-- Expired Warning -->
            <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center">
                        <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-rose-300">Expired Stock (Do Not Sell)</span>
                        <p class="text-[10px] text-rose-400/80">Quarantine or return to supplier</p>
                    </div>
                </div>
                <span class="text-xl font-black text-rose-400 font-mono">{{ $expiredItems->count() }} items</span>
            </div>

            <!-- Near Expiry Warning (<90 Days) -->
            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-amber-300">Expiring Soon (Within 90 Days)</span>
                        <p class="text-[10px] text-amber-400/80">Promote via clearance discounts</p>
                    </div>
                </div>
                <span class="text-xl font-black text-amber-400 font-mono">{{ $nearExpiryItems->count() }} items</span>
            </div>

        </div>
    </div>

    <!-- Batch Table -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Cosmetics / Skincare Item</th>
                            <th class="py-3 px-4">Location</th>
                            <th class="py-3 px-4">Batch Number</th>
                            <th class="py-3 px-4">Expiry Date</th>
                            <th class="py-3 px-4 text-center">Remaining Days</th>
                            <th class="py-3 px-4 text-center">Units In Stock</th>
                            <th class="py-3 px-4 text-right">FEFO Priority</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($allBatches as $batch)
                            @php
                                $exp = \Carbon\Carbon::parse($batch->exp_date);
                                $daysDiff = now()->diffInDays($exp, false);
                                $isExpired = $daysDiff < 0;
                                $isNear = !$isExpired && $daysDiff <= 90;
                            @endphp
                            <tr class="hover:bg-carbon-850/60 transition-colors {{ $isExpired ? 'bg-rose-950/20' : ($isNear ? 'bg-amber-950/15' : '') }}">
                                <!-- Item details -->
                                <td class="py-3.5 px-4 font-sans">
                                    <div class="font-bold text-white text-xs">
                                        {{ $batch->productVariation->product->name ?? 'Product' }}
                                    </div>
                                    <div class="text-[10px] text-silver-400 mt-0.5">
                                        {{ $batch->productVariation->name ?? 'Standard' }} • 
                                        <span class="font-mono text-brand-400">{{ $batch->productVariation->sku ?? '' }}</span>
                                    </div>
                                </td>

                                <!-- Location -->
                                <td class="py-3.5 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                        {{ $batch->branch->name ?? 'Main Branch' }}
                                    </span>
                                </td>

                                <!-- Batch Number -->
                                <td class="py-3.5 px-4 font-bold text-white">
                                    {{ $batch->batch_number }}
                                </td>

                                <!-- Expiry Date -->
                                <td class="py-3.5 px-4">
                                    <span class="font-bold {{ $isExpired ? 'text-rose-400' : ($isNear ? 'text-amber-300' : 'text-silver-300') }}">
                                        {{ $exp->format('d M Y') }}
                                    </span>
                                </td>

                                <!-- Days Remaining -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($isExpired)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                            EXPIRED ({{ abs($daysDiff) }}d ago)
                                        </span>
                                    @elseif($isNear)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                            {{ $daysDiff }} Days Left
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            {{ $daysDiff }} Days
                                        </span>
                                    @endif
                                </td>

                                <!-- Stock on hand -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="text-white font-bold text-sm">{{ $batch->quantity }}</span>
                                </td>

                                <!-- FEFO Status Action -->
                                <td class="py-3.5 px-4 text-right font-sans">
                                    @if($isExpired)
                                        <span class="text-[10px] text-rose-400 font-bold uppercase">Quarantine Stock</span>
                                    @elseif($isNear)
                                        <span class="text-[10px] text-amber-400 font-bold uppercase">Sell First (FEFO)</span>
                                    @else
                                        <span class="text-[10px] text-emerald-400 font-semibold uppercase">Good Condition</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-calendar-check text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No batch-tracked cosmetics records found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($allBatches->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $allBatches->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

