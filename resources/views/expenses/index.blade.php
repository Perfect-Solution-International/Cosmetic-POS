@extends('layouts.pos')

@section('title', 'Expense Vouchers & Petty Cash')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-money-bill-transfer text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Daily Expenses & Petty Cash</h2>
                <p class="text-xs text-silver-400">Track utilities, packaging, petty cash vouchers & operational costs</p>
            </div>
        </div>

        <button 
            type="button" 
            onclick="openNewExpenseModal()"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
        >
            <i class="fa-solid fa-plus"></i>
            <span>Record Expense</span>
        </button>
    </div>

    <!-- Filter & Stat Bar -->
    <div class="bg-carbon-900/60 border-b border-carbon-800 px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <form action="{{ route('expenses.index') }}" method="GET" class="flex items-center gap-3">
            <div class="w-48 sm:w-56">
                <select name="branch_id" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(request()->filled('branch_id'))
                <a href="{{ route('expenses.index') }}" class="text-xs text-rose-400 hover:underline">Reset</a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <span class="text-xs text-silver-400 uppercase font-bold">Total Recorded:</span>
            <span class="text-sm sm:text-base font-black text-rose-400 font-mono">Rs. {{ number_format($totalExpense, 2) }}</span>
        </div>
    </div>

    <!-- Expenses Table -->
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
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Branch</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Reference / Voucher</th>
                            <th class="py-3 px-4">Notes & Description</th>
                            <th class="py-3 px-4">Logged By</th>
                            <th class="py-3 px-4 text-right">Amount (LKR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($expenses as $exp)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <!-- Date -->
                                <td class="py-3.5 px-4 font-mono text-silver-400 text-xs">
                                    {{ \Carbon\Carbon::parse($exp->expense_date)->format('d M Y') }}
                                </td>

                                <!-- Branch -->
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                        {{ $exp->branch->name ?? 'Main' }}
                                    </span>
                                </td>

                                <!-- Category -->
                                <td class="py-3.5 px-4 font-bold text-white">
                                    {{ $exp->category->name ?? 'General' }}
                                </td>

                                <!-- Reference -->
                                <td class="py-3.5 px-4 font-mono text-silver-400">
                                    {{ $exp->reference_no ?? '-' }}
                                </td>

                                <!-- Notes -->
                                <td class="py-3.5 px-4 text-silver-300">
                                    {{ $exp->notes ?? 'No description' }}
                                </td>

                                <!-- User -->
                                <td class="py-3.5 px-4 text-silver-400 text-xs">
                                    {{ $exp->user->name ?? 'Staff' }}
                                </td>

                                <!-- Amount -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-rose-400 text-sm">
                                    Rs. {{ number_format($exp->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-money-bill-transfer text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No expenses recorded yet</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($expenses->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Record Expense Modal -->
<div id="newExpenseModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-receipt text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Record Expense Voucher</h3>
                    <p class="text-xs text-silver-400">Petty cash, utility or store expense</p>
                </div>
            </div>
            <button type="button" onclick="closeNewExpenseModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('expenses.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Branch *</label>
                    <select name="branch_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Expense Category *</label>
                    <select name="expense_category_id" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Amount (LKR) *</label>
                    <input type="number" name="amount" min="0.01" step="any" required placeholder="0.00" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Expense Date *</label>
                    <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Reference / Voucher No</label>
                <input type="text" name="reference_no" placeholder="VCH-{{ rand(1000,9999) }}" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Notes & Description</label>
                <textarea name="notes" rows="2" placeholder="Item description, supplier receipt..." class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"></textarea>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition">
                Record Expense Voucher
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openNewExpenseModal() {
        const modal = document.getElementById('newExpenseModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeNewExpenseModal() {
        const modal = document.getElementById('newExpenseModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

