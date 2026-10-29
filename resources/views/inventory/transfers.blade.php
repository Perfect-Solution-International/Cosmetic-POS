@extends('layouts.pos')

@section('title', 'Inter-Branch Stock Transfers')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-truck-ramp-box text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Inter-Branch Stock Transfers</h2>
                <p class="text-xs text-silver-400">Transfer stock between Central Warehouse & Retail Outlets</p>
            </div>
        </div>

        <button 
            type="button" 
            onclick="openNewTransferModal()"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
        >
            <i class="fa-solid fa-plus"></i>
            <span>Create Transfer</span>
        </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">

        @if(session('status'))
            <div class="p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Transfer #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Source (From)</th>
                            <th class="py-3 px-4">Destination (To)</th>
                            <th class="py-3 px-4">Items Transferred</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($transfers as $trf)
                            <tr class="hover:bg-carbon-850/60 transition-colors">
                                <!-- Transfer No -->
                                <td class="py-3.5 px-4">
                                    <span class="text-white font-bold">{{ $trf->transfer_no }}</span>
                                    <span class="block text-[10px] text-silver-500 font-sans">By {{ $trf->requester->name ?? 'Admin' }}</span>
                                </td>

                                <!-- Date -->
                                <td class="py-3.5 px-4 text-silver-400 font-sans">
                                    {{ \Carbon\Carbon::parse($trf->transfer_date)->format('d M Y') }}
                                </td>

                                <!-- Source Branch -->
                                <td class="py-3.5 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                        {{ $trf->fromBranch->name ?? 'Source' }}
                                    </span>
                                </td>

                                <!-- Destination Branch -->
                                <td class="py-3.5 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-brand-950/40 text-brand-400 border border-brand-500/30">
                                        {{ $trf->toBranch->name ?? 'Destination' }}
                                    </span>
                                </td>

                                <!-- Items -->
                                <td class="py-3.5 px-4 font-sans">
                                    <div class="space-y-1">
                                        @foreach($trf->items as $item)
                                            <div class="text-[11px] text-white">
                                                <span class="font-bold">{{ $item->quantity_sent }}x</span> 
                                                <span>{{ $item->productVariation->product->name ?? '' }} ({{ $item->productVariation->name }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center font-sans">
                                    @if($trf->status === 'in_transit')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 inline-flex items-center gap-1">
                                            <i class="fa-solid fa-truck-fast text-[9px]"></i> In Transit
                                        </span>
                                    @elseif($trf->status === 'received')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1">
                                            <i class="fa-solid fa-circle-check text-[9px]"></i> Received
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-carbon-800 text-silver-400 border border-carbon-700 uppercase">
                                            {{ $trf->status }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Action (Receive button) -->
                                <td class="py-3.5 px-4 text-right font-sans">
                                    @if($trf->status === 'in_transit')
                                        <form action="{{ route('inventory.transfers.receive', $trf->id) }}" method="POST" onsubmit="return confirm('Confirm receipt of these items into destination branch stock?');">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm touch-action-btn">
                                                Mark Received
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-silver-500 italic">Completed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-truck-plane text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No inter-branch transfers recorded</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transfers->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Create Transfer Modal -->
<div id="newTransferModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl p-6 space-y-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-truck-moving text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Create Inter-Branch Transfer</h3>
                    <p class="text-xs text-silver-400">Dispatch items from source to receiving location</p>
                </div>
            </div>
            <button type="button" onclick="closeNewTransferModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('inventory.transfers.store') }}" method="POST" class="space-y-4 overflow-y-auto flex-1 pr-1">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Source (From) *</label>
                    <select name="from_branch_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Destination (To) *</label>
                    <select name="to_branch_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $loop->iteration == 2 ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Transfer Date *</label>
                    <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Transfer Items Matrix -->
            <div class="space-y-2 pt-2 border-t border-carbon-800">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-silver-300 uppercase block">Items to Transfer</label>
                    <button type="button" onclick="addTransferItemRow()" class="text-xs text-brand-400 font-bold hover:underline">+ Add Another Item</button>
                </div>

                <div id="transferItemsContainer" class="space-y-2">
                    <div class="grid grid-cols-12 gap-2 p-2 rounded-xl bg-carbon-850 border border-carbon-800 items-center">
                        <div class="col-span-8">
                            <select name="items[0][product_variation_id]" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                                @foreach($variations as $v)
                                    <option value="{{ $v->id }}">{{ $v->product->name ?? '' }} - {{ $v->name }} ({{ $v->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-4">
                            <input type="number" name="items[0][quantity]" min="1" value="5" required placeholder="Qty" class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono font-bold text-right focus:outline-none focus:border-brand-500">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Transfer Dispatch Notes</label>
                <input type="text" name="notes" placeholder="Vehicle no, driver name, urgency..." class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition">
                Dispatch Transfer (Put In-Transit)
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let transferRowIndex = 1;

    function openNewTransferModal() {
        const modal = document.getElementById('newTransferModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeNewTransferModal() {
        const modal = document.getElementById('newTransferModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function addTransferItemRow() {
        const container = document.getElementById('transferItemsContainer');
        const i = transferRowIndex++;
        const div = document.createElement('div');
        div.className = "grid grid-cols-12 gap-2 p-2 rounded-xl bg-carbon-850 border border-carbon-800 items-center";
        
        div.innerHTML = `
            <div class="col-span-8">
                <select name="items[${i}][product_variation_id]" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    @foreach($variations as $v)
                        <option value="{{ $v->id }}">{{ $v->product->name ?? '' }} - {{ $v->name }} ({{ $v->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-3">
                <input type="number" name="items[${i}][quantity]" min="1" value="1" required placeholder="Qty" class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono font-bold text-right focus:outline-none focus:border-brand-500">
            </div>
            <div class="col-span-1 text-center">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-rose-500 hover:text-white text-xs">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;

        container.appendChild(div);
    }
</script>
@endsection

