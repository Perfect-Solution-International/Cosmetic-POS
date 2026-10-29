@extends('layouts.pos')

@section('title', 'Monthly Subscriptions & Free Trials')

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Top Action & Title Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-carbon-900/80 p-5 rounded-3xl border border-carbon-800 shadow-xl">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/30">
                    SaaS & RECURRING REVENUE
                </span>
                <span class="text-xs text-silver-500">•</span>
                <span class="text-xs text-silver-400">මාසික සාමාජිකත්ව සහ Multi-Outlet කළමනාකරණය</span>
            </div>
            <h1 class="text-2xl font-black text-white mt-1 flex items-center gap-2.5">
                <i class="fa-solid fa-clock-rotate-left text-purple-400"></i>
                <span>Subscriptions & Free Trials</span>
            </h1>
            <p class="text-xs text-silver-400 mt-0.5">
                Manage monthly client packages, evaluate 3-day / 7-day free trials, and monitor Online vs Cashier Register sales origins.
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a 
                href="{{ route('hq.index') }}" 
                class="px-3.5 py-2.5 rounded-xl bg-purple-600/15 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 font-bold text-xs flex items-center gap-1.5 transition"
                title="Consolidated Multi-Outlet Command Center"
            >
                <i class="fa-solid fa-network-wired text-purple-400"></i>
                <span>Master HQ</span>
            </a>

            <a 
                href="{{ route('subscriptions.packages') }}" 
                class="px-3.5 py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 font-bold text-xs flex items-center gap-1.5 transition touch-action-btn"
            >
                <i class="fa-solid fa-boxes-packing text-brand-400"></i>
                <span>Manage Packages</span>
            </a>

            <a 
                href="{{ route('subscriptions.create') }}" 
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 via-brand-600 to-rose-600 hover:from-purple-500 hover:to-brand-500 text-white font-black text-xs shadow-lg shadow-purple-600/30 flex items-center gap-2 transition touch-action-btn"
            >
                <i class="fa-solid fa-plus"></i>
                <span>+ New Subscription (+ Trial)</span>
            </a>
        </div>
    </div>

    <!-- Alert Status -->
    @if(session('status'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center justify-between gap-2 shadow-lg">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Interactive Top Metrics Cards (With 1-Click Quick Filter Link) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- 1. Total All -->
        <a 
            href="{{ route('subscriptions.index', ['status' => 'all']) }}" 
            class="p-4 rounded-2xl bg-carbon-900 border {{ request('status', 'all') === 'all' ? 'border-purple-500/80 bg-purple-950/15' : 'border-carbon-800 hover:border-carbon-700' }} transition block cursor-pointer group shadow-md"
        >
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-silver-500 block group-hover:text-silver-300">Total Subs</span>
            <div class="text-2xl font-black text-white font-mono mt-1">{{ number_format($metrics['total']) }}</div>
            <span class="text-[10px] text-silver-400 mt-0.5 block">All time clients</span>
        </a>

        <!-- 2. Free Trials -->
        <a 
            href="{{ route('subscriptions.index', ['status' => 'trial']) }}" 
            class="p-4 rounded-2xl bg-carbon-900 border {{ request('status') === 'trial' ? 'border-purple-500 bg-purple-950/20 shadow-purple-900/30 shadow-lg' : 'border-purple-500/30 hover:border-purple-500/60' }} bg-gradient-to-b from-purple-500/10 to-transparent transition block cursor-pointer group"
        >
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-400 block">Free Trials</span>
                <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
            </div>
            <div class="text-2xl font-black text-purple-300 font-mono mt-1">{{ number_format($metrics['trials']) }}</div>
            <span class="text-[10px] text-purple-400/80 mt-0.5 block font-semibold">3d / 7d evaluation</span>
        </a>

        <!-- 3. Active Paid -->
        <a 
            href="{{ route('subscriptions.index', ['status' => 'active']) }}" 
            class="p-4 rounded-2xl bg-carbon-900 border {{ request('status') === 'active' ? 'border-emerald-500 bg-emerald-950/20 shadow-emerald-900/30 shadow-lg' : 'border-emerald-500/30 hover:border-emerald-500/60' }} bg-gradient-to-b from-emerald-500/10 to-transparent transition block cursor-pointer group"
        >
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-400 block">Paid Active</span>
            <div class="text-2xl font-black text-emerald-300 font-mono mt-1">{{ number_format($metrics['active_paid']) }}</div>
            <span class="text-[10px] text-emerald-400/80 mt-0.5 block font-semibold">Paying members</span>
        </a>

        <!-- 4. Expired / Due -->
        <a 
            href="{{ route('subscriptions.index', ['status' => 'expired']) }}" 
            class="p-4 rounded-2xl bg-carbon-900 border {{ request('status') === 'expired' ? 'border-amber-500 bg-amber-950/20 shadow-amber-900/30 shadow-lg' : 'border-amber-500/30 hover:border-amber-500/60' }} transition block cursor-pointer group"
        >
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-400 block">Expired / Due</span>
            <div class="text-2xl font-black text-amber-300 font-mono mt-1">{{ number_format($metrics['expired']) }}</div>
            <span class="text-[10px] text-amber-400/80 mt-0.5 block font-semibold">Action required</span>
        </a>

        <!-- 5. MRR -->
        <div class="p-4 rounded-2xl bg-carbon-900 border border-brand-500/30">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-brand-400 block">Active MRR</span>
            <div class="text-xl font-black text-white font-mono mt-1">Rs. {{ number_format($metrics['mrr'], 0) }}</div>
            <span class="text-[10px] text-silver-500 mt-0.5 block">Recurring Revenue</span>
        </div>

        <!-- 6. Sales Origins -->
        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-400 block">Sales Origin</span>
            <div class="text-xs font-mono font-bold mt-1 text-white">
                <span class="text-blue-400">{{ $metrics['online_count'] }} Online</span> / 
                <span class="text-emerald-400">{{ $metrics['register_count'] }} Reg</span>
            </div>
            <span class="text-[10px] text-silver-500 mt-0.5 block">Acquisition channel</span>
        </div>
    </div>

    <!-- Quick 1-Click Status Filter Pills Bar -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        @php
            $currentStatus = request('status', 'all');
        @endphp
        <span class="text-silver-500 font-bold uppercase text-[10px] mr-1">Quick Filter:</span>
        <a 
            href="{{ route('subscriptions.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}"
            class="px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'all' ? 'bg-purple-600 text-white shadow-md' : 'bg-carbon-900 text-silver-400 hover:text-white border border-carbon-800' }}"
        >
            <span>All ({{ $metrics['total'] }})</span>
        </a>

        <a 
            href="{{ route('subscriptions.index', array_merge(request()->except(['status', 'page']), ['status' => 'trial'])) }}"
            class="px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'trial' ? 'bg-purple-600 text-white shadow-md' : 'bg-carbon-900 text-purple-300 hover:text-white border border-purple-500/30' }}"
        >
            <i class="fa-solid fa-gift text-purple-400"></i>
            <span>Free Trials ({{ $metrics['trials'] }})</span>
        </a>

        <a 
            href="{{ route('subscriptions.index', array_merge(request()->except(['status', 'page']), ['status' => 'active'])) }}"
            class="px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'active' ? 'bg-emerald-600 text-white shadow-md' : 'bg-carbon-900 text-emerald-400 hover:text-white border border-emerald-500/30' }}"
        >
            <i class="fa-solid fa-check"></i>
            <span>Paid Active ({{ $metrics['active_paid'] }})</span>
        </a>

        <a 
            href="{{ route('subscriptions.index', array_merge(request()->except(['status', 'page']), ['status' => 'expired'])) }}"
            class="px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 {{ $currentStatus === 'expired' ? 'bg-amber-600 text-white shadow-md' : 'bg-carbon-900 text-amber-300 hover:text-white border border-amber-500/30' }}"
        >
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Expired / Due ({{ $metrics['expired'] }})</span>
        </a>
    </div>

    <!-- Search & Advanced Filter Card -->
    <div class="p-4 rounded-3xl bg-carbon-900 border border-carbon-800 shadow-md">
        <form method="GET" action="{{ route('subscriptions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <input type="hidden" name="status" value="{{ request('status', 'all') }}">

            <!-- Search -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-silver-400 mb-1">Search Subscriber / Business</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-silver-500 text-xs"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="SUB#, Business, Customer, Phone..." 
                        class="w-full pl-8 pr-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-purple-500"
                    >
                </div>
            </div>

            <!-- Branch Selector -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-silver-400 mb-1">Branch / Store Location</label>
                <select name="branch_id" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-purple-500">
                    <option value="all" {{ request('branch_id', 'all') === 'all' ? 'selected' : '' }}>🏢 All Outlets Combined</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }} ({{ $b->city ?? 'Central' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sales Channel Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-silver-400 mb-1">Sales Origin (Channel)</label>
                <select name="sales_channel" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-purple-500">
                    <option value="all" {{ request('sales_channel') == 'all' ? 'selected' : '' }}>All Sales Channels</option>
                    <option value="register" {{ request('sales_channel') == 'register' ? 'selected' : '' }}>In-Store / Register Staff</option>
                    <option value="online" {{ request('sales_channel') == 'online' ? 'selected' : '' }}>Online / Web Self-Service</option>
                </select>
            </div>

            <!-- Package Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-silver-400 mb-1">Plan Package</label>
                <select name="package_id" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-purple-500">
                    <option value="">All Packages</option>
                    @foreach($packages as $pkg)
                        <option value="{{ $pkg->id }}" {{ request('package_id') == $pkg->id ? 'selected' : '' }}>
                            {{ $pkg->name }} (Rs. {{ number_format($pkg->price, 0) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition touch-action-btn">
                    <i class="fa-solid fa-filter"></i>
                    <span>Apply Filter</span>
                </button>
                <a href="{{ route('subscriptions.index') }}" class="py-2 px-3 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-400 hover:text-white border border-carbon-700 text-xs font-bold transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Subscriptions Table -->
    <div class="rounded-3xl bg-carbon-900 border border-carbon-800 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-carbon-850 border-b border-carbon-800 text-[10px] font-extrabold uppercase tracking-wider text-silver-400">
                    <tr>
                        <th class="py-3.5 px-4">Subscription & Business</th>
                        <th class="py-3.5 px-4">Subscriber Contact</th>
                        <th class="py-3.5 px-4">Plan & Multi-Outlet</th>
                        <th class="py-3.5 px-4 text-center">Lifecycle Status</th>
                        <th class="py-3.5 px-4">Sales Origin</th>
                        <th class="py-3.5 px-4">Current Period / Expiry</th>
                        <th class="py-3.5 px-4 text-right">Quick Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-carbon-800/60 font-sans">
                    @forelse($subscriptions as $sub)
                        @php
                            $isTrial = $sub->isTrialActive();
                            $daysLeft = $sub->days_remaining;
                            $cleanPhone = preg_replace('/[^0-9]/', '', $sub->customer->phone ?? '');
                            if (str_starts_with($cleanPhone, '0')) {
                                $intlPhone = '94' . substr($cleanPhone, 1);
                            } else {
                                $intlPhone = $cleanPhone;
                            }
                        @endphp
                        <tr class="hover:bg-carbon-850/60 transition-colors">
                            
                            <!-- 1. Subscription & Business -->
                            <td class="py-3.5 px-4 font-mono">
                                <a href="{{ route('subscriptions.show', $sub->id) }}" class="text-purple-400 font-bold hover:underline flex items-center gap-1.5 text-xs">
                                    <i class="fa-solid fa-id-badge text-[11px]"></i>
                                    <span>{{ $sub->subscription_no }}</span>
                                </a>
                                @if($sub->business_name)
                                    <div class="text-white font-bold font-sans text-xs mt-0.5 truncate max-w-[180px]">
                                        {{ $sub->business_name }}
                                    </div>
                                @endif
                                <span class="block text-[10px] text-silver-500 font-sans">Enrolled {{ $sub->started_at->format('d M Y') }}</span>
                            </td>

                            <!-- 2. Subscriber Contact -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white text-xs">{{ $sub->customer->name ?? 'Walk-in Customer' }}</div>
                                <div class="text-[10px] text-silver-400 font-mono mt-0.5 flex items-center gap-2">
                                    <span><i class="fa-solid fa-phone text-[9px] text-silver-500"></i> {{ $sub->customer->phone ?? 'N/A' }}</span>
                                    @if($cleanPhone)
                                        <a 
                                            href="https://wa.me/{{ $intlPhone }}?text=Hello%20{{ urlencode($sub->customer->name) }},%20Regarding%20your%20{{ urlencode($sub->package->name ?? 'Subscription') }}%20at%20our%20store." 
                                            target="_blank"
                                            class="text-emerald-400 hover:text-emerald-300"
                                            title="Chat on WhatsApp"
                                        >
                                            <i class="fa-brands fa-whatsapp text-xs"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>

                            <!-- 3. Plan & Multi-Outlet -->
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-silver-200">{{ $sub->package->name ?? 'Standard Package' }}</div>
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-mono font-black uppercase bg-purple-500/15 text-purple-300 border border-purple-500/30">
                                        {{ $sub->allocated_branches ?? 1 }} Outlets
                                    </span>
                                    <span class="text-[10px] font-mono text-brand-400 font-bold">
                                        Rs. {{ number_format($sub->total_price ?: $sub->price, 2) }}
                                    </span>
                                    <span class="text-[9px] text-silver-500 uppercase">/ {{ $sub->billing_cycle ?? $sub->package->billing_cycle ?? 'mo' }}</span>
                                </div>
                            </td>

                            <!-- 4. Lifecycle Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($isTrial)
                                    <div class="inline-flex flex-col items-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-purple-500/20 text-purple-300 border border-purple-500/40">
                                            <i class="fa-solid fa-gift text-purple-400"></i>
                                            <span>Trial ({{ $daysLeft }}d left)</span>
                                        </span>
                                        <!-- Countdown mini bar -->
                                        @php
                                            $totalTrial = max(1, $sub->trial_days);
                                            $trialPct = min(100, max(0, round(($daysLeft / $totalTrial) * 100)));
                                        @endphp
                                        <div class="w-20 h-1 bg-carbon-800 rounded-full mt-1 overflow-hidden">
                                            <div class="h-full bg-purple-400 rounded-full" style="width: {{ $trialPct }}%"></div>
                                        </div>
                                    </div>
                                @elseif($sub->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        <i class="fa-solid fa-circle-check text-[9px]"></i>
                                        <span>Paid Active</span>
                                    </span>
                                @elseif($sub->status === 'trial' && !$isTrial)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                                        <span>Trial Ended</span>
                                    </span>
                                @elseif($sub->status === 'cancelled')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-carbon-800 text-silver-400 border border-carbon-700">
                                        <span>Cancelled</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        <span>{{ ucfirst($sub->status) }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 5. Sales Origin (Online vs Register) -->
                            <td class="py-3.5 px-4">
                                @if($sub->sales_channel === 'online')
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-500/15 text-blue-300 border border-blue-500/30">
                                        <i class="fa-solid fa-globe text-blue-400"></i>
                                        <span>Online Channel</span>
                                    </div>
                                    <span class="block text-[9px] text-silver-500 mt-0.5">Self-Service Signup</span>
                                @else
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                        <i class="fa-solid fa-cash-register text-emerald-400"></i>
                                        <span>{{ $sub->salesAgent->name ?? 'Staff Cashier' }}</span>
                                    </div>
                                    <span class="block text-[9px] text-silver-500 mt-0.5">{{ $sub->branch->name ?? 'Counter' }}</span>
                                @endif
                            </td>

                            <!-- 6. Period / Trial Expiry -->
                            <td class="py-3.5 px-4 font-mono text-silver-300">
                                @if($sub->is_free_trial)
                                    <div class="text-xs font-semibold {{ $isTrial ? 'text-purple-300' : 'text-rose-400' }}">
                                        Trial: {{ $sub->trial_ends_at ? $sub->trial_ends_at->format('d M Y') : 'N/A' }}
                                    </div>
                                    <span class="text-[10px] text-silver-500 font-sans">
                                        {{ $sub->trial_days }} Days Free
                                    </span>
                                @else
                                    <div class="text-xs font-semibold text-emerald-300">
                                        Due: {{ $sub->current_period_ends_at ? $sub->current_period_ends_at->format('d M Y') : 'N/A' }}
                                    </div>
                                    <span class="text-[10px] text-silver-500 font-sans uppercase">
                                        {{ $sub->billing_cycle ?? 'Monthly' }}
                                    </span>
                                @endif
                            </td>

                            <!-- 7. Quick Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- 1-Click Convert / Renew Quick Modals -->
                                    @if($sub->is_free_trial || $sub->status === 'trial')
                                        <button 
                                            type="button" 
                                            onclick="openQuickConvertModal({{ $sub->id }}, '{{ addslashes($sub->subscription_no) }}', '{{ addslashes($sub->customer->name ?? 'Customer') }}', {{ $sub->total_price ?: $sub->price }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-[10px] shadow-sm flex items-center gap-1 transition"
                                            title="1-Click Activate Paid Plan"
                                        >
                                            <i class="fa-solid fa-bolt"></i>
                                            <span>Convert</span>
                                        </button>
                                    @elseif($sub->status === 'active' || $sub->status === 'expired')
                                        <button 
                                            type="button" 
                                            onclick="openQuickRenewModal({{ $sub->id }}, '{{ addslashes($sub->subscription_no) }}', {{ $sub->total_price ?: $sub->price }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 font-bold text-[10px] transition"
                                            title="Renew for another term"
                                        >
                                            <i class="fa-solid fa-rotate text-[9px]"></i>
                                            <span>Renew</span>
                                        </button>
                                    @endif

                                    <!-- Full View -->
                                    <a 
                                        href="{{ route('subscriptions.show', $sub->id) }}" 
                                        class="px-2.5 py-1.5 rounded-lg bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-[10px] font-bold transition flex items-center gap-1"
                                    >
                                        <i class="fa-solid fa-eye text-[9px]"></i>
                                        <span>Details</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-silver-400">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="w-14 h-14 rounded-3xl bg-purple-500/10 border border-purple-500/30 text-purple-400 flex items-center justify-center text-2xl mx-auto shadow-inner">
                                        <i class="fa-solid fa-gift"></i>
                                    </div>
                                    <h3 class="text-base font-extrabold text-white">No Subscriptions Found</h3>
                                    <p class="text-xs text-silver-400">
                                        Start your first customer on a <strong>3-Day or 7-Day Payment-Free Trial</strong> or paid multi-outlet membership package.
                                    </p>
                                    <div class="pt-2">
                                        <a 
                                            href="{{ route('subscriptions.create') }}" 
                                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-brand-600 hover:from-purple-500 hover:to-brand-500 text-white font-extrabold text-xs shadow-xl shadow-purple-600/30 transition"
                                        >
                                            <i class="fa-solid fa-plus"></i>
                                            <span>Enroll First Free Trial</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
            <div class="p-4 border-t border-carbon-800 bg-carbon-900/40">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Quick Convert Trial to Paid Modal -->
<div id="quickConvertModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white">Convert Free Trial to Paid</h3>
                    <p class="text-[10px] text-silver-400">Activate member billing & generate instant sale invoice</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickConvertModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="quickConvertForm" method="POST" action="" class="space-y-4 text-xs">
            @csrf

            <div class="p-3 rounded-2xl bg-carbon-850 border border-carbon-750 flex items-center justify-between">
                <div>
                    <span class="text-[10px] text-silver-400 block font-bold uppercase" id="qcSubNo">SUB-001</span>
                    <span class="text-white font-bold" id="qcCustName">Customer</span>
                </div>
                <div class="text-right">
                    <span class="text-[9px] text-silver-500 uppercase block font-bold">Payable Total</span>
                    <span class="text-base font-black text-emerald-400 font-mono" id="qcAmount">Rs. 0.00</span>
                </div>
            </div>

            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1.5">Payment Method *</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-850 flex items-center gap-2 cursor-pointer hover:border-emerald-500">
                        <input type="radio" name="payment_method" value="cash" checked class="text-emerald-500">
                        <span class="font-bold text-white text-xs">Cash</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-850 flex items-center gap-2 cursor-pointer hover:border-emerald-500">
                        <input type="radio" name="payment_method" value="card" class="text-emerald-500">
                        <span class="font-bold text-white text-xs">Card</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-850 flex items-center gap-2 cursor-pointer hover:border-emerald-500">
                        <input type="radio" name="payment_method" value="qr" class="text-emerald-500">
                        <span class="font-bold text-white text-xs">LankaQR</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-850 flex items-center gap-2 cursor-pointer hover:border-emerald-500">
                        <input type="radio" name="payment_method" value="bank_transfer" class="text-emerald-500">
                        <span class="font-bold text-white text-xs">Bank Transfer</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-carbon-800">
                <button type="button" onclick="closeQuickConvertModal()" class="px-4 py-2 rounded-xl bg-carbon-800 text-silver-400 font-bold hover:text-white">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black shadow-lg shadow-emerald-600/30 flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i>
                    <span>Confirm & Activate</span>
                </button>
            </div>
        </form>

    </div>
</div>

<!-- Quick Renew Modal -->
<div id="quickRenewModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center">
                    <i class="fa-solid fa-rotate"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white">Renew Subscription</h3>
                    <p class="text-[10px] text-silver-400">Extend period and issue renewal invoice</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickRenewModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="quickRenewForm" method="POST" action="" class="space-y-4 text-xs">
            @csrf

            <div class="p-3 rounded-2xl bg-carbon-850 border border-carbon-750 flex items-center justify-between">
                <div>
                    <span class="text-[10px] text-silver-400 block font-bold uppercase" id="qrSubNo">SUB-001</span>
                    <span class="text-white font-bold">Term Renewal</span>
                </div>
                <div class="text-right">
                    <span class="text-[9px] text-silver-500 uppercase block font-bold">Renewal Fee</span>
                    <span class="text-base font-black text-purple-400 font-mono" id="qrAmount">Rs. 0.00</span>
                </div>
            </div>

            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1.5">Payment Method *</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-850 flex items-center gap-2 cursor-pointer hover:border-purple-500">
                        <input type="radio" name="payment_method" value="cash" checked class="text-purple-500">
                        <span class="font-bold text-white text-xs">Cash</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-850 flex items-center gap-2 cursor-pointer hover:border-purple-500">
                        <input type="radio" name="payment_method" value="card" class="text-purple-500">
                        <span class="font-bold text-white text-xs">Card</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-850 flex items-center gap-2 cursor-pointer hover:border-purple-500">
                        <input type="radio" name="payment_method" value="qr" class="text-purple-500">
                        <span class="font-bold text-white text-xs">LankaQR</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-850 flex items-center gap-2 cursor-pointer hover:border-purple-500">
                        <input type="radio" name="payment_method" value="bank_transfer" class="text-purple-500">
                        <span class="font-bold text-white text-xs">Bank Transfer</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-carbon-800">
                <button type="button" onclick="closeQuickRenewModal()" class="px-4 py-2 rounded-xl bg-carbon-800 text-silver-400 font-bold hover:text-white">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black shadow-lg shadow-purple-600/30 flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Confirm Renewal</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function openQuickConvertModal(id, subNo, custName, amount) {
        document.getElementById('quickConvertForm').action = `/subscriptions/${id}/convert-trial`;
        document.getElementById('qcSubNo').innerText = subNo;
        document.getElementById('qcCustName').innerText = custName;
        document.getElementById('qcAmount').innerText = 'Rs. ' + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
        const modal = document.getElementById('quickConvertModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeQuickConvertModal() {
        const modal = document.getElementById('quickConvertModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openQuickRenewModal(id, subNo, amount) {
        document.getElementById('quickRenewForm').action = `/subscriptions/${id}/renew`;
        document.getElementById('qrSubNo').innerText = subNo;
        document.getElementById('qrAmount').innerText = 'Rs. ' + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
        const modal = document.getElementById('quickRenewModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeQuickRenewModal() {
        const modal = document.getElementById('quickRenewModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
