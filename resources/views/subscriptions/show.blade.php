@extends('layouts.pos')

@section('title', 'Subscription ' . $subscription->subscription_no)

@section('content')
<div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Top Action & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-silver-400">
                <a href="{{ route('subscriptions.index') }}" class="hover:text-white transition flex items-center gap-1">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Subscriptions
                </a>
                <span>/</span>
                <span class="text-purple-400 font-bold uppercase tracking-wider">{{ $subscription->subscription_no }}</span>
            </div>
            <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                <h1 class="text-2xl font-black text-white font-mono">{{ $subscription->subscription_no }}</h1>

                <!-- Status Badge -->
                @if($subscription->isTrialActive())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase bg-purple-500/20 text-purple-300 border border-purple-500/40">
                        <i class="fa-solid fa-clock-rotate-left animate-spin text-purple-400"></i>
                        <span>Free Trial Active ({{ $subscription->days_remaining }} Days Left)</span>
                    </span>
                @elseif($subscription->status === 'active')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        <i class="fa-solid fa-check"></i>
                        <span>Paid Active Member</span>
                    </span>
                @elseif($subscription->status === 'trial' && !$subscription->isTrialActive())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase bg-rose-500/20 text-rose-400 border border-rose-500/30">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Free Trial Expired</span>
                    </span>
                @elseif($subscription->status === 'cancelled')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase bg-carbon-800 text-silver-400 border border-carbon-750">
                        <span>Cancelled</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase bg-amber-500/20 text-amber-400 border border-amber-500/30">
                        <span>{{ ucfirst($subscription->status) }}</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @php
                $cleanPhone = preg_replace('/[^0-9]/', '', $subscription->customer->phone ?? '');
                $intlPhone = str_starts_with($cleanPhone, '0') ? ('94' . substr($cleanPhone, 1)) : $cleanPhone;
                $waMessage = "Hello " . ($subscription->customer->name ?? 'Customer') . ", Update regarding your " . ($subscription->package->name ?? 'Subscription') . " (Sub #" . $subscription->subscription_no . "). Your current evaluation/billing ends on " . ($subscription->isTrialActive() ? $subscription->trial_ends_at?->format('d M Y') : $subscription->current_period_ends_at?->format('d M Y')) . ". Thank you!";
            @endphp
            @if($cleanPhone)
                <a 
                    href="https://wa.me/{{ $intlPhone }}?text={{ urlencode($waMessage) }}" 
                    target="_blank"
                    class="px-3.5 py-2.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-xs font-bold transition flex items-center gap-1.5"
                    title="Send WhatsApp Reminder"
                >
                    <i class="fa-brands fa-whatsapp text-sm text-emerald-400"></i>
                    <span>WhatsApp Reminder</span>
                </a>
            @endif

            @if($subscription->is_free_trial || $subscription->status === 'trial')
                <button 
                    type="button" 
                    onclick="openConvertModal()"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-2 transition touch-action-btn"
                >
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>Convert Trial to Paid Plan</span>
                </button>
            @elseif($subscription->status === 'active')
                <button 
                    type="button" 
                    onclick="openRenewModal()"
                    class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-xs shadow-lg shadow-purple-600/30 flex items-center gap-2 transition touch-action-btn"
                >
                    <i class="fa-solid fa-rotate text-[11px]"></i>
                    <span>Renew 1 Month</span>
                </button>
            @endif

            @if($subscription->status !== 'cancelled')
                <form method="POST" action="{{ route('subscriptions.cancel', $subscription->id) }}" onsubmit="return confirm('Cancel this subscription?');">
                    @csrf
                    <button type="submit" class="px-3.5 py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-rose-400 hover:text-rose-300 border border-carbon-700 text-xs font-bold transition">
                        <i class="fa-solid fa-ban mr-1"></i> Cancel
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Alert Status -->
    @if(session('status'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-base"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Free Trial Banner (if on trial) -->
    @if($subscription->is_free_trial)
        <div class="p-5 rounded-3xl bg-gradient-to-r from-purple-950/40 via-purple-900/20 to-carbon-900 border border-purple-500/40 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xl">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-400 animate-pulse"></span>
                    <h3 class="text-sm font-extrabold text-white">
                        {{ $subscription->isTrialActive() ? 'Payment-Free Trial in Progress' : 'Free Trial Has Expired' }}
                    </h3>
                </div>
                <p class="text-xs text-silver-300">
                    Allocated Trial: <strong class="text-white">{{ $subscription->trial_days }} Days Free</strong> • 
                    Started on {{ $subscription->started_at->format('d M Y') }} • 
                    Ends on <strong class="text-purple-300">{{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('d M Y') : 'N/A' }}</strong>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right">
                    <span class="text-[10px] text-silver-400 uppercase font-bold block">Days Remaining</span>
                    <span class="text-2xl font-black font-mono text-purple-300">{{ $subscription->days_remaining }} Days</span>
                </div>
                <button 
                    type="button" 
                    onclick="openConvertModal()" 
                    class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-xs shadow-md transition"
                >
                    Activate Paid Now
                </button>
            </div>
        </div>
    @endif

    <!-- 4 Information Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- 1. Customer Card -->
        <div class="p-4 rounded-3xl bg-carbon-900 border border-carbon-800 space-y-2">
            <div class="flex items-center gap-2 text-silver-400 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-user text-brand-500"></i>
                <span>Subscriber Profile</span>
            </div>
            <div class="pt-1">
                <h4 class="text-sm font-extrabold text-white">{{ $subscription->customer->name ?? 'Walk-in' }}</h4>
                <div class="text-xs text-silver-400 font-mono mt-0.5">{{ $subscription->customer->phone ?? 'No phone' }}</div>
                @if($subscription->customer?->email)
                    <div class="text-[10px] text-silver-500 truncate mt-0.5">{{ $subscription->customer->email }}</div>
                @endif
                <div class="mt-2 pt-2 border-t border-carbon-800 text-[10px] text-silver-400">
                    Loyalty: <strong class="text-emerald-400 font-mono">{{ $subscription->customer->loyalty_points ?? 0 }} pts</strong>
                </div>
            </div>
        </div>

        <!-- 2. Package & Pricing Card -->
        <div class="p-4 rounded-3xl bg-carbon-900 border border-carbon-800 space-y-2">
            <div class="flex items-center gap-2 text-silver-400 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-boxes-packing text-purple-400"></i>
                <span>Plan & Multi-Outlet</span>
            </div>
            <div class="pt-1">
                <h4 class="text-sm font-extrabold text-white">{{ $subscription->package->name ?? 'Custom Plan' }}</h4>
                <div class="text-[11px] text-purple-400 font-bold mt-0.5">
                    <i class="fa-solid fa-shop mr-1"></i> {{ $subscription->allocated_branches ?? 1 }} Outlets Licensed
                </div>
                <div class="mt-2 pt-2 border-t border-carbon-800 flex items-baseline gap-1">
                    <span class="text-xs text-silver-400 font-mono">Rs.</span>
                    <span class="text-lg font-black text-brand-400 font-mono">{{ number_format($subscription->total_price ?: $subscription->price, 2) }}</span>
                    <span class="text-[10px] text-silver-500 uppercase">/ {{ $subscription->billing_cycle }}</span>
                </div>
                <a href="{{ route('hq.index') }}" class="mt-2 block w-full py-1 text-center rounded-lg bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white font-bold text-[10px] border border-purple-500/30 transition">
                    View Multi-Outlet HQ →
                </a>
            </div>
        </div>

        <!-- 3. Period & Timeline Card -->
        <div class="p-4 rounded-3xl bg-carbon-900 border border-carbon-800 space-y-2">
            <div class="flex items-center gap-2 text-silver-400 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-calendar text-emerald-400"></i>
                <span>Billing Period</span>
            </div>
            <div class="pt-1 space-y-1 text-xs">
                <div class="flex justify-between text-silver-400">
                    <span>Started:</span>
                    <span class="text-white font-mono">{{ $subscription->started_at->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between text-silver-400">
                    <span>Current End:</span>
                    <span class="text-white font-mono">{{ $subscription->current_period_ends_at ? $subscription->current_period_ends_at->format('d M Y') : 'N/A' }}</span>
                </div>
                <div class="mt-2 pt-2 border-t border-carbon-800 flex justify-between text-[10px]">
                    <span class="text-silver-500">Auto-Renew:</span>
                    <span class="text-emerald-400 font-bold">Enabled</span>
                </div>
            </div>
        </div>

        <!-- 4. Sales Origin & Acquisition Channel Card -->
        <div class="p-4 rounded-3xl bg-carbon-900 border border-blue-500/30 bg-gradient-to-b from-blue-500/10 to-transparent space-y-2">
            <div class="flex items-center gap-2 text-blue-400 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Sales Origin (Channel)</span>
            </div>
            <div class="pt-1">
                @if($subscription->sales_channel === 'online')
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-extrabold bg-blue-500/20 text-blue-300 border border-blue-500/40">
                        <i class="fa-solid fa-globe text-blue-400"></i>
                        <span>Online Self-Service</span>
                    </div>
                    <p class="text-[10px] text-silver-400 mt-2">Acquired via Web / E-Commerce signup without counter intervention.</p>
                @else
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                        <i class="fa-solid fa-cash-register text-emerald-400"></i>
                        <span>In-Store Register</span>
                    </div>
                    <div class="mt-2 text-xs">
                        <span class="text-[10px] text-silver-500 uppercase block font-bold">Closed by Staff:</span>
                        <span class="font-extrabold text-white">{{ $subscription->salesAgent->name ?? 'Front Desk Staff' }}</span>
                        <span class="block text-[10px] text-silver-400">{{ $subscription->branch->name ?? 'Store Counter' }}</span>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Linked Sales Invoices / Payment History Table -->
    <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-receipt text-brand-500 text-base"></i>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Subscription Invoices & Payments</h3>
            </div>
            <span class="text-xs text-silver-400 font-mono">{{ $subscription->sales->count() }} invoice(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-carbon-850 border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400">
                    <tr>
                        <th class="py-2.5 px-3">Invoice No</th>
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3">Sales Origin</th>
                        <th class="py-2.5 px-3">Cashier / Staff</th>
                        <th class="py-2.5 px-3 text-right">Amount Paid</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon-800/60 font-mono">
                    @forelse($subscription->sales as $sale)
                        <tr class="hover:bg-carbon-850/60 transition font-sans">
                            <td class="py-3 px-3 font-mono font-bold text-brand-400">
                                {{ $sale->invoice_no }}
                            </td>
                            <td class="py-3 px-3 text-silver-300 text-xs">
                                {{ $sale->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-3 px-3">
                                @if($sale->sales_channel === 'online')
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                        <i class="fa-solid fa-globe"></i> Online
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        <i class="fa-solid fa-cash-register"></i> Register
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-silver-300">
                                {{ $sale->cashier->name ?? 'Cashier' }}
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-white font-mono">
                                Rs. {{ number_format($sale->grand_total, 2) }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase bg-emerald-500/20 text-emerald-400">
                                    {{ $sale->payment_status }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold transition">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-silver-500 font-sans">
                                <p class="text-xs">No paid billing invoices generated yet (Current status is Payment-Free Trial).</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Convert Trial to Paid Subscription -->
<div id="convertTrialModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white">Convert Trial to Paid Plan</h3>
                    <p class="text-[10px] text-silver-400">Generate first month invoice and activate subscription</p>
                </div>
            </div>
            <button type="button" onclick="closeConvertModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('subscriptions.convert', $subscription->id) }}" class="space-y-4 text-xs">
            @csrf

            <!-- Due Amount Box -->
            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-750 flex items-center justify-between">
                <div>
                    <span class="text-[10px] text-silver-400 font-bold uppercase block">Monthly Subscription Fee</span>
                    <span class="text-xs text-white font-semibold">{{ $subscription->package->name }}</span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-brand-400 font-mono font-bold mr-1">Rs.</span>
                    <span class="text-xl font-black text-white font-mono">{{ number_format($subscription->price, 2) }}</span>
                </div>
            </div>

            <!-- Payment Method -->
            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1.5">Payment Method *</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="p-2.5 rounded-xl border border-carbon-750 bg-carbon-850 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" checked class="text-emerald-500">
                        <span class="font-bold text-white">Cash</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-750 bg-carbon-850 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="card" class="text-emerald-500">
                        <span class="font-bold text-white">Credit/Debit Card</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-750 bg-carbon-850 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="bank_transfer" class="text-emerald-500">
                        <span class="font-bold text-white">Bank Transfer</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-750 bg-carbon-850 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="qr" class="text-emerald-500">
                        <span class="font-bold text-white">LankaQR</span>
                    </label>
                </div>
            </div>

            <!-- Sales Channel / Staff -->
            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Sales Origin</label>
                <select name="sales_channel" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white">
                    <option value="register" {{ $subscription->sales_channel === 'register' ? 'selected' : '' }}>In-Store Register (Counter Staff)</option>
                    <option value="online" {{ $subscription->sales_channel === 'online' ? 'selected' : '' }}>Online / Web Self-Service</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-carbon-800">
                <button type="button" onclick="closeConvertModal()" class="px-4 py-2 rounded-xl bg-carbon-800 text-silver-400 font-bold hover:text-white">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black shadow-lg shadow-emerald-600/30 flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i>
                    <span>Confirm & Pay Rs. {{ number_format($subscription->price, 2) }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Renew Active Subscription -->
<div id="renewModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center">
                    <i class="fa-solid fa-rotate"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white">Renew Subscription (1 Month)</h3>
                    <p class="text-[10px] text-silver-400">Extend period by +30 days</p>
                </div>
            </div>
            <button type="button" onclick="closeRenewModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('subscriptions.renew', $subscription->id) }}" class="space-y-4 text-xs">
            @csrf

            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-750 flex items-center justify-between">
                <span class="text-xs text-white font-semibold">Renewal Fee:</span>
                <span class="text-lg font-black text-white font-mono">Rs. {{ number_format($subscription->price, 2) }}</span>
            </div>

            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1.5">Payment Method *</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="p-2.5 rounded-xl border border-carbon-750 bg-carbon-850 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" checked class="text-purple-500">
                        <span class="font-bold text-white">Cash</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-750 bg-carbon-850 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="card" class="text-purple-500">
                        <span class="font-bold text-white">Card</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-750 bg-carbon-850 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="bank_transfer" class="text-purple-500">
                        <span class="font-bold text-white">Bank Transfer</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-750 bg-carbon-850 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="qr" class="text-purple-500">
                        <span class="font-bold text-white">LankaQR</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-carbon-800">
                <button type="button" onclick="closeRenewModal()" class="px-4 py-2 rounded-xl bg-carbon-800 text-silver-400 font-bold hover:text-white">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black shadow-lg shadow-purple-600/30 flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i>
                    <span>Confirm Renewal</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openConvertModal() {
        const modal = document.getElementById('convertTrialModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeConvertModal() {
        const modal = document.getElementById('convertTrialModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openRenewModal() {
        const modal = document.getElementById('renewModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeRenewModal() {
        const modal = document.getElementById('renewModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

