@extends('layouts.pos')

@section('title', 'Suppliers Directory & Ledger')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-truck-field text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Suppliers & Vendors Directory</h2>
                <p class="text-xs text-silver-400">Cosmetics manufacturers, handbag suppliers & credit balances</p>
            </div>
        </div>

        <button 
            type="button" 
            onclick="openNewSupplierModal()"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
        >
            <i class="fa-solid fa-plus"></i>
            <span>Register Supplier</span>
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
                            <th class="py-3 px-4">Supplier Name & Company</th>
                            <th class="py-3 px-4">Vendor Code</th>
                            <th class="py-3 px-4">Contact Info</th>
                            <th class="py-3 px-4 text-center">Purchase Orders</th>
                            <th class="py-3 px-4 text-center">GRN Received</th>
                            <th class="py-3 px-4 text-right">Outstanding Balance</th>
                            <th class="py-3 px-4 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60 font-mono">
                        @forelse($suppliers as $supplier)
                            <tr class="hover:bg-carbon-850/60 transition-colors font-sans">
                                <!-- Name & Company -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white text-xs">{{ $supplier->name }}</div>
                                    <div class="text-[10px] text-silver-400 mt-0.5">{{ $supplier->company_name ?? 'Individual Supplier' }}</div>
                                </td>

                                <!-- Code -->
                                <td class="py-3.5 px-4 font-mono font-bold text-white">
                                    {{ $supplier->code }}
                                </td>

                                <!-- Contact Info -->
                                <td class="py-3.5 px-4">
                                    <div class="text-white text-xs">{{ $supplier->phone ?? 'No Phone' }}</div>
                                    <div class="text-[10px] text-silver-500 font-mono">{{ $supplier->email ?? 'No email' }}</div>
                                </td>

                                <!-- POs Count -->
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-white">
                                    {{ $supplier->purchase_orders_count }}
                                </td>

                                <!-- GRNs Count -->
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-white">
                                    {{ $supplier->grns_count }}
                                </td>

                                <!-- Outstanding Balance -->
                                <td class="py-3.5 px-4 text-right font-mono font-extrabold {{ $supplier->current_balance > 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                                    Rs. {{ number_format($supplier->current_balance, 2) }}
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $supplier->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-carbon-800 text-silver-400' }}">
                                        {{ $supplier->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-silver-500 font-sans">
                                    <i class="fa-solid fa-truck-field text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No suppliers registered</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Register Supplier Modal -->
<div id="newSupplierModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-truck-field text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Register Supplier</h3>
                    <p class="text-xs text-silver-400">Add procurement vendor and payment terms</p>
                </div>
            </div>
            <button type="button" onclick="closeNewSupplierModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('procurement.suppliers.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Supplier Name *</label>
                    <input type="text" name="name" required placeholder="e.g. L'Oreal Paris Distributors" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Vendor Code *</label>
                    <input type="text" name="code" value="SUP-{{ rand(100,999) }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Contact Person</label>
                    <input type="text" name="contact_person" placeholder="Manager Name" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Phone Number</label>
                    <input type="text" name="phone" placeholder="+94 77 123 4567" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Email Address</label>
                    <input type="email" name="email" placeholder="orders@vendor.com" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Payment Terms</label>
                    <input type="text" name="payment_terms" placeholder="Net 30 Days" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Address</label>
                <input type="text" name="address" placeholder="Industrial Zone, Colombo" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition">
                Register Supplier
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openNewSupplierModal() {
        const modal = document.getElementById('newSupplierModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeNewSupplierModal() {
        const modal = document.getElementById('newSupplierModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

