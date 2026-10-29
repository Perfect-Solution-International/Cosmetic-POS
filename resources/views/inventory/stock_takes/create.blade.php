@extends('layouts.pos')

@section('title', 'Start Stock Take Session')

@section('content')
<div class="h-full flex flex-col justify-between overflow-hidden bg-carbon-950">
    <header class="p-4 sm:p-5 border-b border-carbon-800 bg-carbon-900/40 backdrop-blur-md flex items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.stock_takes.index') }}" class="w-9 h-9 rounded-xl bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-base sm:text-lg font-black text-white tracking-tight">
                    Start New Physical Stock Take Session
                </h1>
                <p class="text-xs text-silver-400">Creates an inventory audit snapshot of all active products in the branch</p>
            </div>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-4 sm:p-8 flex items-center justify-center">
        <div class="w-full max-w-xl bg-carbon-900 border border-carbon-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
            <div class="flex items-center gap-4 border-b border-carbon-800 pb-5">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-xl shrink-0">
                    <i class="fa-solid fa-barcode"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-white uppercase tracking-wider">Inventory Cycle Audit</h2>
                    <p class="text-xs text-silver-400 mt-0.5">When started, current system quantities will be locked as baseline targets.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('inventory.stock_takes.store') }}" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block text-silver-400 font-bold mb-1.5">Select Branch for Physical Audit *</label>
                    <select name="branch_id" class="w-full px-4 py-2.5 rounded-xl bg-carbon-800 border border-carbon-700 text-white font-bold focus:outline-none focus:border-amber-500">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $selectedBranch->id == $b->id ? 'selected' : '' }}>
                                {{ $b->name }} ({{ $b->code }}) - {{ $b->city }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-silver-400 font-bold mb-1.5">Audit Purpose / Cycle Notes</label>
                    <textarea name="notes" rows="3" placeholder="e.g. End of month cycle count for Electronics department" class="w-full p-3 rounded-2xl bg-carbon-800 border border-carbon-700 text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <div class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 text-amber-300 text-xs flex items-start gap-3">
                    <i class="fa-solid fa-circle-info text-amber-400 mt-0.5 shrink-0"></i>
                    <div>
                        <strong>Instructions:</strong> Once you launch this session, you can use any USB or Bluetooth barcode scanner to scan items on shelves. The physical counter will increment with each scan, computing real-time surplus or shortage variances.
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-carbon-800">
                    <a href="{{ route('inventory.stock_takes.index') }}" class="px-4 py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-700 text-silver-400 font-bold transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black shadow-lg shadow-amber-500/20 transition flex items-center gap-2">
                        <i class="fa-solid fa-play"></i>
                        <span>Initialize Audit Session</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

