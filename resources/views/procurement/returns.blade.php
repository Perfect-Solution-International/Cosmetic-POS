@extends('layouts.pos')

@section('title', 'Supplier Returns & Debit Notes')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">
    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-600/20 border border-rose-500/30 flex items-center justify-center text-rose-500">
                <i class="fa-solid fa-arrow-rotate-left text-lg"></i>
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                    <span>Supplier Returns & Debit Notes</span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-carbon-800 text-silver-300 border border-carbon-700">Procurement</span>
                </h1>
                <p class="text-xs text-silver-400">Return damaged, rejected or expired stocks to suppliers and adjust payables</p>
            </div>
        </div>

        <button
            type="button"
            onclick="openNewReturnModal()"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-brand-700 hover:from-rose-500 hover:to-brand-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-rose-600/30 transition flex items-center gap-2 touch-action-btn"
        >
            <i class="fa-solid fa-plus"></i>
            <span>Create Supplier Return (Debit Note)</span>
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

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs font-semibold space-y-1">
                <div class="flex items-center gap-2 font-bold text-rose-200">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Please resolve the following:</span>
                </div>
                <ul class="list-disc list-inside pl-4 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Table of Returns -->
        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Return Number</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-4">Origin Branch</th>
                            <th class="py-3 px-4">Return Reason</th>
                            <th class="py-3 px-4 text-center">Items</th>
                            <th class="py-3 px-4 text-right">Debit Amount (Rs)</th>
                            <th class="py-3 px-4 text-center">Voucher</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($returns as $r)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white text-xs font-mono">{{ $r->return_no }}</div>
                                    <div class="text-[10px] text-silver-500">{{ $r->created_at->format('d M Y, h:i A') }}</div>
                                </td>

                                <td class="py-3.5 px-4 font-bold text-silver-200">
                                    {{ $r->supplier->name ?? 'Unknown Supplier' }}
                                    @if($r->credit_note_no)
                                        <div class="text-[10px] font-mono text-brand-400">Ref: {{ $r->credit_note_no }}</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-silver-300 text-xs">
                                    {{ $r->branch->name ?? 'Branch' }}
                                </td>

                                <td class="py-3.5 px-4 text-silver-400 text-xs italic max-w-xs truncate">
                                    "{{ $r->reason }}"
                                </td>

                                <td class="py-3.5 px-4 text-center font-mono">
                                    <span class="px-2 py-0.5 rounded bg-carbon-800 text-silver-300 border border-carbon-700 text-[11px]">
                                        {{ $r->items->count() }} items ({{ $r->items->sum('quantity') }} pcs)
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-black text-rose-400 text-sm">
                                    Rs. {{ number_format($r->total_amount, 2) }}
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <a
                                        href="{{ route('procurement.returns.print', $r->id) }}"
                                        target="_blank"
                                        class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white border border-carbon-700 text-[11px] font-bold inline-flex items-center gap-1 transition"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                        <span>Debit Note</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-arrow-rotate-left text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No supplier returns recorded yet</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="p-4 border-t border-carbon-800">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Supplier Return Modal -->
<div id="newReturnModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl p-6 space-y-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-600/20 border border-rose-500/30 flex items-center justify-center text-rose-500">
                    <i class="fa-solid fa-arrow-rotate-left text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Issue Goods Return & Debit Note</h3>
                    <p class="text-xs text-silver-400">Reduces branch inventory & debits supplier payable liability</p>
                </div>
            </div>
            <button onclick="closeNewReturnModal()" class="w-8 h-8 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('procurement.returns.store') }}" class="flex-1 overflow-y-auto space-y-4 text-xs pr-1">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Select Supplier *</label>
                    <select name="supplier_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-rose-500">
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} (Payable: Rs. {{ number_format($s->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Origin Branch *</label>
                    <select name="branch_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-rose-500">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Supplier Credit Note / Reference #</label>
                    <input type="text" name="credit_note_no" placeholder="e.g. CN-SUP-9912" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Return Reason *</label>
                    <input type="text" name="reason" required placeholder="e.g. Expired cosmetic stock / packaging damaged" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <!-- Items Container -->
            <div class="space-y-2 border-t border-carbon-800 pt-3">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-silver-300 uppercase text-[11px]">Items to Return</span>
                    <button type="button" onclick="addReturnItemRow()" class="text-brand-400 hover:text-brand-300 text-xs font-bold flex items-center gap-1">
                        <i class="fa-solid fa-plus"></i> Add Item
                    </button>
                </div>

                <div id="returnItemsContainer" class="space-y-2">
                    <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                            <div class="sm:col-span-6">
                                <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Product Item *</label>
                                <select name="items[0][product_variation_id]" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-rose-500">
                                    @foreach($variations as $v)
                                        <option value="{{ $v->id }}">{{ $v->product->name ?? '' }} - {{ $v->name }} (Cost: Rs. {{ number_format($v->purchase_cost, 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-3">
                                <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Quantity *</label>
                                <input type="number" name="items[0][quantity]" min="1" value="5" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right font-bold focus:outline-none focus:border-rose-500">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Unit Cost (Rs) *</label>
                                <input type="number" name="items[0][unit_cost]" step="any" min="0" value="1500.00" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-rose-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-rose-600 to-brand-700 hover:from-rose-500 hover:to-brand-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-rose-600/30 transition flex items-center justify-center gap-2 mt-4">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Confirm Return & Deduct from Supplier</span>
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let returnRowIndex = 1;

    function openNewReturnModal() {
        const modal = document.getElementById('newReturnModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeNewReturnModal() {
        const modal = document.getElementById('newReturnModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function addReturnItemRow() {
        const container = document.getElementById('returnItemsContainer');
        const i = returnRowIndex++;
        const div = document.createElement('div');
        div.className = "p-3 rounded-xl bg-carbon-850 border border-carbon-800 space-y-2";
        div.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                <div class="sm:col-span-6">
                    <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Product Item *</label>
                    <select name="items[${i}][product_variation_id]" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs focus:outline-none focus:border-rose-500">
                        @foreach($variations as $v)
                            <option value="{{ $v->id }}">{{ $v->product->name ?? '' }} - {{ $v->name }} (Cost: Rs. {{ number_format($v->purchase_cost, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Quantity *</label>
                    <input type="number" name="items[${i}][quantity]" min="1" value="1" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right font-bold focus:outline-none focus:border-rose-500">
                </div>
                <div class="sm:col-span-3 flex items-end gap-2">
                    <div class="flex-1">
                        <label class="text-[10px] text-silver-400 uppercase font-bold block mb-1">Unit Cost (Rs) *</label>
                        <input type="number" name="items[${i}][unit_cost]" step="any" min="0" value="0.00" required class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-rose-500">
                    </div>
                    <button type="button" onclick="this.parentElement.parentElement.parentElement.remove()" class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-400 hover:text-white hover:bg-rose-600 flex items-center justify-center text-xs mb-0.5">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(div);
    }
</script>
@endsection

