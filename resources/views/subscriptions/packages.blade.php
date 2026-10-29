@extends('layouts.pos')

@section('title', 'Subscription Packages')

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('subscriptions.index') }}" class="text-xs text-silver-400 hover:text-white transition flex items-center gap-1">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Subscriptions
                </a>
                <span class="text-xs text-silver-600">/</span>
                <span class="text-xs text-purple-400 font-bold uppercase tracking-wider">Packages & Tiers</span>
            </div>
            <h1 class="text-2xl font-black text-white mt-1">Monthly Subscription Packages</h1>
            <p class="text-xs text-silver-400">Configure monthly membership tiers, pricing, and default free evaluation trial days.</p>
        </div>

        <div>
            <button 
                type="button" 
                onclick="openNewPackageModal()"
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-brand-600 hover:from-purple-500 hover:to-brand-500 text-white font-extrabold text-xs shadow-lg shadow-purple-600/30 flex items-center gap-2 transition touch-action-btn"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Add New Package</span>
            </button>
        </div>
    </div>

    <!-- Alert Status -->
    @if(session('status'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-base"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Package Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($packages as $pkg)
            <div class="p-5 rounded-3xl bg-carbon-900 border {{ $pkg->is_active ? 'border-carbon-800 hover:border-purple-500/40' : 'border-carbon-800/40 opacity-60' }} flex flex-col justify-between transition-all shadow-xl space-y-4">
                
                <div>
                    <!-- Package Top Badge & Status -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold uppercase bg-carbon-800 text-silver-300 border border-carbon-700">
                            {{ $pkg->code }}
                        </span>

                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase {{ $pkg->is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-carbon-800 text-silver-500' }}">
                                {{ $pkg->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </div>
                    </div>

                    <!-- Title & Monthly Fee -->
                    <h3 class="text-base font-extrabold text-white">{{ $pkg->name }}</h3>
                    <p class="text-xs text-silver-400 mt-1 line-clamp-2">{{ $pkg->description ?? 'No description provided.' }}</p>

                    <div class="mt-4 pt-3 border-t border-carbon-800/80 flex items-baseline gap-1">
                        <span class="text-xs text-silver-400 font-mono">Rs.</span>
                        <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-purple-100 to-purple-400 font-mono">
                            {{ number_format($pkg->price, 2) }}
                        </span>
                        <span class="text-[11px] text-silver-500 uppercase font-semibold">/ {{ $pkg->billing_cycle }}</span>
                    </div>

                    <!-- Multi-Outlet Breakdown & Free Trial Badges -->
                    <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-[10px] font-bold">
                        <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-300">
                            <i class="fa-solid fa-shop"></i> {{ $pkg->included_branches ?? 1 }} Outlet Included
                        </span>
                        <span class="px-2 py-0.5 rounded-lg bg-carbon-800 text-silver-300 border border-carbon-700">
                            +Rs. {{ number_format($pkg->price_per_additional_branch ?? 1500, 0) }}/extra branch
                        </span>
                    </div>

                    <div class="mt-1.5 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-300 text-[10px] font-bold">
                        <i class="fa-solid fa-gift text-purple-400"></i>
                        <span>Default Free Trial: {{ $pkg->default_trial_days }} Days Free</span>
                    </div>

                    <!-- Features List -->
                    @if(!empty($pkg->features) && is_array($pkg->features))
                        <div class="mt-4 space-y-1.5 border-t border-carbon-800/60 pt-3 text-xs">
                            <span class="text-[10px] uppercase font-bold text-silver-500 tracking-wider block">Included Features:</span>
                            @foreach($pkg->features as $feat)
                                <div class="flex items-center gap-2 text-silver-300 text-xs">
                                    <i class="fa-solid fa-check text-[10px] text-emerald-400"></i>
                                    <span>{{ $feat }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Footer & Subscribers -->
                <div class="pt-4 border-t border-carbon-800 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-silver-500 block">Subscribers</span>
                        <span class="font-mono font-bold text-white text-xs">{{ $pkg->subscriptions_count ?? 0 }} active</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('subscriptions.packages.toggle', $pkg->id) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-semibold transition">
                                {{ $pkg->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                        <a 
                            href="{{ route('subscriptions.create') }}?package_id={{ $pkg->id }}" 
                            class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow transition flex items-center gap-1"
                        >
                            <span>Enroll</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full py-16 text-center text-silver-500">
                <i class="fa-solid fa-boxes-packing text-4xl mb-3 text-carbon-700"></i>
                <h3 class="text-sm font-bold text-silver-400">No subscription packages found</h3>
            </div>
        @endforelse
    </div>

</div>

<!-- Modal: New Package -->
<div id="newPackageModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white">Create Subscription Package</h3>
                    <p class="text-[10px] text-silver-400">Define package tier, price and free trial duration</p>
                </div>
            </div>
            <button type="button" onclick="closeNewPackageModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('subscriptions.packages.store') }}" class="space-y-3.5 text-xs">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Package Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Gold VIP Membership" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Package Code *</label>
                    <input type="text" name="code" required placeholder="e.g. PKG-GOLD" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono uppercase focus:outline-none focus:border-purple-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Base Price (Rs.) *</label>
                    <input type="number" step="0.01" min="0" name="price" required placeholder="5000.00" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-purple-500">
                </div>
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Billing Cycle</label>
                    <select name="billing_cycle" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-purple-500">
                        <option value="trial">7-Day Free Trial</option>
                        <option value="monthly" selected>Monthly (1 Month)</option>
                        <option value="quarterly">Quarterly (3 Months)</option>
                        <option value="biannual">Biannual (6 Months)</option>
                        <option value="yearly">Yearly (12 Months)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Default Trial Days *</label>
                    <input type="number" min="0" max="365" name="default_trial_days" value="7" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-purple-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-2xl bg-carbon-850 border border-carbon-750">
                <div>
                    <label class="block font-bold text-emerald-400 uppercase text-[10px] mb-1">Base Included Outlets *</label>
                    <input type="number" min="1" max="100" name="included_branches" value="1" required class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-mono focus:outline-none focus:border-purple-500">
                    <span class="text-[9px] text-silver-400 block mt-0.5">Outlets included in base package price</span>
                </div>
                <div>
                    <label class="block font-bold text-purple-400 uppercase text-[10px] mb-1">Per-Branch Add-on Fee (Rs.) *</label>
                    <input type="number" step="0.01" min="0" name="price_per_additional_branch" value="1500.00" required class="w-full px-3 py-2 rounded-xl bg-carbon-900 border border-carbon-700 text-white font-mono focus:outline-none focus:border-purple-500">
                    <span class="text-[9px] text-silver-400 block mt-0.5">Additional cost per extra licensed branch</span>
                </div>
            </div>

            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Description</label>
                <input type="text" name="description" placeholder="Brief overview of what client receives" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-purple-500">
            </div>

            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Included Features (1 per line)</label>
                <textarea name="features" rows="3" placeholder="Full gym access&#10;Sauna & Shower access&#10;Weekly body composition review" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-purple-500 font-sans"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-carbon-800">
                <button type="button" onclick="closeNewPackageModal()" class="px-4 py-2 rounded-xl bg-carbon-800 text-silver-400 font-bold hover:text-white">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-black shadow-lg shadow-purple-600/30 flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i>
                    <span>Save Package</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function openNewPackageModal() {
        const modal = document.getElementById('newPackageModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeNewPackageModal() {
        const modal = document.getElementById('newPackageModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

