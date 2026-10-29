@extends('layouts.pos')

@section('title', 'Branches & POS Registers')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-code-branch text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Multi-Branch & POS Register Counters</h2>
                <p class="text-xs text-silver-400">Centralized, Decentralized & Hybrid retail locations management</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a 
                href="{{ route('hq.index') }}" 
                class="px-3.5 py-2 rounded-xl bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 text-xs font-bold transition flex items-center gap-2"
            >
                <i class="fa-solid fa-network-wired"></i>
                <span>Master HQ View</span>
            </a>

            <button 
                type="button" 
                onclick="openNewBranchModal()"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Add New Branch</span>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

        @if($errors->has('branch_quota'))
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-center justify-between gap-3 shadow-lg">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-base"></i>
                    <span>{{ $errors->first('branch_quota') }}</span>
                </div>
                <a href="{{ route('subscriptions.index') }}" class="px-3 py-1.5 rounded-xl bg-amber-500 text-black font-extrabold text-[11px] hover:bg-amber-400 transition">
                    Upgrade Subscription →
                </a>
            </div>
        @endif

        <!-- Multi-Outlet Branch Quota Banner -->
        <div class="p-4 rounded-2xl bg-carbon-900 border border-carbon-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-md">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold text-white">Branch License Quota:</span>
                        <span class="font-mono font-black text-xs {{ $usedBranches >= $allocatedBranches ? 'text-amber-400' : 'text-emerald-400' }}">
                            {{ $usedBranches }} / {{ $allocatedBranches }} Licensed Outlets Active
                        </span>
                        @if($activeSub)
                            <span class="px-2 py-0.5 rounded text-[9px] font-mono font-bold bg-purple-500/20 text-purple-300 uppercase">
                                {{ $activeSub->package->name ?? 'Plan' }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[10px] text-silver-400 mt-0.5">
                        Each extra branch adds +Rs. {{ number_format($activeSub->additional_branch_price ?? 1500, 0) }}/mo to your subscription package.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @php
                    $pct = min(100, round(($usedBranches / max(1, $allocatedBranches)) * 100));
                @endphp
                <div class="w-36 h-2 bg-carbon-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-emerald-400 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
                <a href="{{ route('subscriptions.index') }}" class="text-[11px] text-purple-400 hover:text-purple-300 font-bold underline">
                    Manage Plan
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($branches as $branch)
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 shadow-xl flex flex-col justify-between space-y-4">
                    
                    <div>
                        <!-- Branch Header -->
                        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 mb-3">
                            <div>
                                <h3 class="text-sm font-extrabold text-white">{{ $branch->name }}</h3>
                                <div class="flex items-center gap-2 text-[10px] text-silver-400 mt-0.5">
                                    <span class="font-mono text-brand-400 font-bold">{{ $branch->code }}</span>
                                    <span>•</span>
                                    <span class="uppercase font-semibold">{{ str_replace('_', ' ', $branch->type) }}</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase {{ $branch->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-carbon-800 text-silver-400' }}">
                                {{ $branch->status }}
                            </span>
                        </div>

                        <!-- Contact & Address -->
                        <div class="space-y-1 text-xs text-silver-400">
                            <p class="truncate"><i class="fa-solid fa-location-dot w-4 text-center text-silver-500"></i> {{ $branch->address ?? 'No address' }}</p>
                            <p class="font-mono text-[11px]"><i class="fa-solid fa-phone w-4 text-center text-silver-500"></i> {{ $branch->phone ?? 'No phone' }}</p>
                        </div>

                        <!-- Register Counters Section -->
                        <div class="mt-4 pt-3 border-t border-carbon-800/80">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-bold text-silver-400 uppercase tracking-wider">
                                    POS Counters ({{ $branch->registers->count() }})
                                </span>
                                <button 
                                    type="button" 
                                    onclick="openAddRegisterModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                                    class="text-[10px] text-brand-400 font-bold hover:underline"
                                >
                                    + Add Counter
                                </button>
                            </div>

                            <div class="space-y-1.5">
                                @forelse($branch->registers as $reg)
                                    <div class="p-2 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between text-xs font-mono">
                                        <div class="flex items-center gap-2 font-sans font-semibold text-white">
                                            <i class="fa-solid fa-cash-register text-[10px] text-brand-500"></i>
                                            <span>{{ $reg->name }}</span>
                                            <span class="text-[10px] text-silver-500 font-mono">({{ $reg->code }})</span>
                                        </div>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] uppercase font-bold {{ $reg->status === 'open' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-carbon-800 text-silver-500' }}">
                                            {{ $reg->status }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-[10px] text-silver-600 italic">No registers configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Footer Details -->
                    <div class="pt-3 border-t border-carbon-800/80 flex items-center justify-between text-[10px] text-silver-500">
                        <span>Staff: {{ $branch->users->count() }} operators</span>
                        <a href="{{ route('inventory.index', ['branch_id' => $branch->id]) }}" class="text-brand-400 hover:underline font-semibold">
                            View Stock →
                        </a>
                    </div>

                </div>
            @endforeach
        </div>

    </div>
</div>

<!-- Modal 1: Add New Branch -->
<div id="newBranchModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-store text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Add New Store / Branch</h3>
                    <p class="text-xs text-silver-400">Retail outlet or central warehouse</p>
                </div>
            </div>
            <button type="button" onclick="closeNewBranchModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('branches.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Branch Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Galle Flagship Store" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Branch Code *</label>
                    <input type="text" name="code" value="BR-{{ rand(10,99) }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Location Type *</label>
                    <select name="type" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                        <option value="retail_store">Retail Store</option>
                        <option value="central_warehouse">Central Warehouse</option>
                        <option value="franchise">Franchise Store</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">City</label>
                    <input type="text" name="city" placeholder="e.g. Colombo, Galle, Kandy" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Street Address</label>
                <input type="text" name="address" placeholder="Store Address, Mall Level..." class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Telephone</label>
                    <input type="text" name="phone" placeholder="+94 11 234 5678" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Store Email</label>
                    <input type="email" name="email" placeholder="branch@psi.lk" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition">
                Create Branch
            </button>
        </form>
    </div>
</div>

<!-- Modal 2: Add Register Counter -->
<div id="newRegisterModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-cash-register text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Add Register Counter</h3>
                    <p id="regModalBranchName" class="text-xs text-silver-400">Branch Name</p>
                </div>
            </div>
            <button type="button" onclick="closeAddRegisterModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="addRegisterForm" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Register Counter Name *</label>
                <input type="text" name="name" required placeholder="e.g. Counter 2 - Ground Floor" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Counter Code *</label>
                <input type="text" name="code" value="REG-{{ rand(10,99) }}" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition">
                Create Register Counter
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openNewBranchModal() {
        const modal = document.getElementById('newBranchModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeNewBranchModal() {
        const modal = document.getElementById('newBranchModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openAddRegisterModal(branchId, branchName) {
        document.getElementById('regModalBranchName').innerText = branchName;
        document.getElementById('addRegisterForm').action = `/branches/${branchId}/registers`;
        const modal = document.getElementById('newRegisterModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAddRegisterModal() {
        const modal = document.getElementById('newRegisterModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

