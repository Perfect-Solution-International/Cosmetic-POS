@extends('layouts.pos')

@section('title', 'Customers CRM & Loyalty Points')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-users text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Customer CRM & Loyalty Program</h2>
                <p class="text-xs text-silver-400">Track client purchase history, contact profiles & reward points</p>
            </div>
        </div>

        <button 
            type="button" 
            onclick="openNewCustomerModal()"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
        >
            <i class="fa-solid fa-user-plus"></i>
            <span>Register Customer</span>
        </button>
    </div>

    <!-- Search Bar -->
    <div class="bg-carbon-900/60 border-b border-carbon-800 px-4 sm:px-6 py-3 shrink-0">
        <form action="{{ route('customers.index') }}" method="GET" class="flex items-center gap-3">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-silver-400 text-xs"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search by customer name, phone number, or email..." 
                        class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>
            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-white text-xs font-semibold border border-carbon-700">
                Search
            </button>
            @if(request()->filled('search'))
                <a href="{{ route('customers.index') }}" class="text-xs text-rose-400 hover:underline">Reset</a>
            @endif
        </form>
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
                    <span>Could not register customer:</span>
                </div>
                <ul class="list-disc list-inside pl-4 space-y-0.5 text-xs text-rose-300">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[11px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Customer Name</th>
                            <th class="py-3 px-4">Contact Phone</th>
                            <th class="py-3 px-4">Customer Group</th>
                            <th class="py-3 px-4 text-center">Loyalty Points</th>
                            <th class="py-3 px-4 text-right">Credit Limit</th>
                            <th class="py-3 px-4 text-right">Outstanding Due</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($customers as $c)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <!-- Name -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white text-xs flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-brand-600/20 text-brand-400 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ substr($c->name, 0, 1) }}
                                        </div>
                                        <span>{{ $c->name }}</span>
                                    </div>
                                    <div class="text-[10px] text-silver-500 pl-9 font-mono">{{ $c->email ?? 'No email' }}</div>
                                </td>

                                <!-- Phone -->
                                <td class="py-3.5 px-4 font-mono font-bold text-white">
                                    {{ $c->phone }}
                                </td>

                                <!-- Group -->
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                        {{ $c->group->name ?? 'Standard Retail' }}
                                    </span>
                                </td>

                                <!-- Loyalty Points -->
                                <td class="py-3.5 px-4 text-center font-mono">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-500/20 text-amber-400 border border-amber-500/30 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-star text-[10px]"></i> {{ number_format($c->loyalty_points) }} pts
                                    </span>
                                </td>

                                <!-- Credit Limit -->
                                <td class="py-3.5 px-4 text-right font-mono text-silver-400">
                                    Rs. {{ number_format($c->credit_limit, 2) }}
                                </td>

                                <!-- Outstanding Due Balance -->
                                <td class="py-3.5 px-4 text-right font-mono">
                                    @if($c->current_balance > 0)
                                        <span class="text-rose-400 font-bold px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/20">
                                            Rs. {{ number_format($c->current_balance, 2) }}
                                        </span>
                                    @else
                                        <span class="text-emerald-400 font-semibold text-xs">
                                            Rs. 0.00
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if($c->current_balance > 0)
                                            <button
                                                type="button"
                                                onclick="openSettleModal({{ json_encode($c) }})"
                                                class="px-2.5 py-1 rounded-lg bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white border border-emerald-500/30 text-[11px] font-bold transition flex items-center gap-1 touch-action-btn"
                                                title="Settle Outstanding Credit"
                                            >
                                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                                <span>Settle</span>
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            onclick="openLedgerModal({{ $c->id }}, '{{ addslashes($c->name) }}')"
                                            class="px-2 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white border border-carbon-700 text-[11px] font-medium transition"
                                            title="View Payment History"
                                        >
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-users text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No customers found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Register Customer Modal -->
<div id="newCustomerModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-user-plus text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Register Customer</h3>
                    <p class="text-xs text-silver-400">Create client profile & loyalty account</p>
                </div>
            </div>
            <button type="button" onclick="closeNewCustomerModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('customers.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Anoma Jayawardena" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Mobile Phone *</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="0771234567" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="client@gmail.com" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Customer Tier / Group</label>
                    <select name="customer_group_id" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                        <option value="">Default Retail Group</option>
                        @foreach($groups as $grp)
                            <option value="{{ $grp->id }}" {{ old('customer_group_id') == $grp->id ? 'selected' : '' }}>{{ $grp->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Residential Address</label>
                <input type="text" name="address" value="{{ old('address') }}" placeholder="Address, City" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Credit Limit (LKR)</label>
                <input type="number" name="credit_limit" min="0" step="any" value="{{ old('credit_limit', '0.00') }}" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition">
                Create Customer
            </button>
        </form>
    </div>
</div>

<!-- Settle Customer Credit Modal -->
<div id="customerSettleModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-hand-holding-dollar text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Settle Customer Credit</h3>
                    <p id="settleCustomerSubtitle" class="text-xs text-silver-400 font-mono">Customer Name</p>
                </div>
            </div>
            <button onclick="closeSettleModal()" class="w-8 h-8 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="customerSettleForm" method="POST" action="" class="space-y-3.5 text-xs">
            @csrf

            <!-- Balance Banner -->
            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 space-y-1.5 font-mono">
                <div class="flex justify-between text-silver-400">
                    <span>Credit Limit:</span>
                    <span id="settleCreditLimit" class="font-bold text-white">Rs. 0.00</span>
                </div>
                <div class="flex justify-between text-silver-400">
                    <span>Outstanding Due:</span>
                    <span id="settleCurrentDue" class="font-black text-rose-400 text-sm">Rs. 0.00</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Amount to Settle (Rs) *</label>
                    <input type="number" step="any" min="0.01" name="amount" id="settleAmountInput" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono font-bold text-right focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Method *</label>
                    <select name="payment_method" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-emerald-500">
                        <option value="cash">Cash in Hand</option>
                        <option value="card">Credit / Debit Card</option>
                        <option value="bank_transfer">Bank Transfer / Online</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Payment Date *</label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Reference / Cheque #</label>
                    <input type="text" name="reference_no" placeholder="e.g. TXN-1002" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="font-bold text-silver-300 uppercase block mb-1">Notes / Remarks</label>
                <input type="text" name="notes" placeholder="e.g. Received full settlement against credit" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-emerald-500">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-check"></i>
                <span>Confirm Settlement & Update Balance</span>
            </button>
        </form>
    </div>
</div>

<!-- Customer Payment Ledger Modal -->
<div id="customerLedgerModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl p-6 space-y-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-carbon-800 border border-carbon-700 flex items-center justify-center text-silver-300">
                    <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Payment Ledger & Receipts</h3>
                    <p id="ledgerCustomerSubtitle" class="text-xs text-silver-400 font-mono">Customer</p>
                </div>
            </div>
            <button onclick="closeLedgerModal()" class="w-8 h-8 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="ledgerItemsContainer" class="flex-1 overflow-y-auto space-y-2 text-xs">
            <div class="py-12 text-center text-silver-500">
                <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-brand-500"></i>
                <p>Loading payment history...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openNewCustomerModal() {
        const modal = document.getElementById('newCustomerModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                const nameInput = modal.querySelector('input[name="name"]');
                if (nameInput) nameInput.focus();
            }, 100);
        }
    }

    function closeNewCustomerModal() {
        const modal = document.getElementById('newCustomerModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function openSettleModal(customer) {
        const modal = document.getElementById('customerSettleModal');
        const form = document.getElementById('customerSettleForm');
        if (modal && form) {
            form.action = `/customers/${customer.id}/settle`;
            document.getElementById('settleCustomerSubtitle').textContent = `${customer.name} • ${customer.phone}`;
            document.getElementById('settleCreditLimit').textContent = 'Rs. ' + (parseFloat(customer.credit_limit) || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
            const due = parseFloat(customer.current_balance) || 0;
            document.getElementById('settleCurrentDue').textContent = 'Rs. ' + due.toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.getElementById('settleAmountInput').value = due > 0 ? due.toFixed(2) : '0.00';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeSettleModal() {
        const modal = document.getElementById('customerSettleModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function openLedgerModal(customerId, customerName) {
        const modal = document.getElementById('customerLedgerModal');
        const container = document.getElementById('ledgerItemsContainer');
        document.getElementById('ledgerCustomerSubtitle').textContent = customerName;

        if (modal && container) {
            container.innerHTML = `
                <div class="py-12 text-center text-silver-500">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-brand-500"></i>
                    <p>Loading payment history...</p>
                </div>
            `;
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            fetch(`/customers/${customerId}/payments`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.payments && data.payments.length > 0) {
                        let html = `
                            <table class="w-full text-left font-mono">
                                <thead class="text-[10px] uppercase text-silver-500 border-b border-carbon-800">
                                    <tr>
                                        <th class="py-2">Voucher #</th>
                                        <th class="py-2">Date</th>
                                        <th class="py-2">Method</th>
                                        <th class="py-2">Ref / Memo</th>
                                        <th class="py-2 text-right">Amount (Rs)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-carbon-800/50">
                        `;
                        data.payments.forEach(p => {
                            html += `
                                <tr class="hover:bg-carbon-850/50">
                                    <td class="py-2 font-bold text-white">${p.payment_no}</td>
                                    <td class="py-2 text-silver-400">${p.payment_date ? p.payment_date.split('T')[0] : ''}</td>
                                    <td class="py-2 capitalize text-silver-300 font-sans">${p.payment_method.replace('_', ' ')}</td>
                                    <td class="py-2 text-silver-500 font-sans">${p.reference_no || p.notes || '-'}</td>
                                    <td class="py-2 text-right font-black text-emerald-400">Rs. ${parseFloat(p.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                </tr>
                            `;
                        });
                        html += `</tbody></table>`;
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="py-12 text-center text-silver-500 font-sans">
                                <i class="fa-solid fa-receipt text-3xl mb-2 text-carbon-700"></i>
                                <p class="text-xs font-semibold text-silver-400">No settlement payment records yet.</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    container.innerHTML = `<div class="p-4 text-center text-rose-400">Failed to load payment history.</div>`;
                });
        }
    }

    function closeLedgerModal() {
        const modal = document.getElementById('customerLedgerModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNewCustomerModal();
            closeSettleModal();
            closeLedgerModal();
        }
    });

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function() {
            openNewCustomerModal();
        });
    @endif
</script>
@endsection

