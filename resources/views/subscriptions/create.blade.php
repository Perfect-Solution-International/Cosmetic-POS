@extends('layouts.pos')

@section('title', 'Enroll Subscription / Multi-Outlet Free Trial')

@section('content')
<div class="w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-silver-400">
                <a href="{{ route('subscriptions.index') }}" class="hover:text-white transition flex items-center gap-1">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Subscriptions
                </a>
                <span>/</span>
                <span class="text-purple-400 font-bold uppercase tracking-wider">Multi-Outlet Enrollment</span>
            </div>
            <h1 class="text-2xl font-black text-white mt-1">Enroll Subscription & Multi-Outlet Plan</h1>
            <p class="text-xs text-silver-400">Attach a merchant to a monthly package with 7-day trial and per-branch add-on licensing.</p>
        </div>

        <div class="px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 font-mono text-xs font-bold text-silver-300">
            {{ $nextNumber }}
        </div>
    </div>

    <!-- Error Display -->
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs space-y-1">
            <div class="font-bold flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Please fix the following validation errors:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('subscriptions.store') }}" id="subCreateForm" class="space-y-5 text-xs">
        @csrf

        <!-- SECTION 1: Business Profile & Shop Owner -->
        <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 space-y-4 shadow-xl">
            <div class="flex items-center gap-2.5 border-b border-carbon-800 pb-3">
                <div class="w-8 h-8 rounded-xl bg-brand-600/20 text-brand-400 flex items-center justify-center font-bold">1</div>
                <div>
                    <h3 class="text-sm font-bold text-white">Business Profile & Shop Owner</h3>
                    <p class="text-[10px] text-silver-400">Specify retail shop / merchant name, subscriber account and primary branch</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Business / Shop Name -->
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Business / Shop Name *</label>
                    <input 
                        type="text" 
                        name="business_name" 
                        required 
                        value="{{ old('business_name', 'Grand Central Retail Store') }}"
                        placeholder="e.g. Royal Fashion House, Green Mart..."
                        class="w-full px-3 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-purple-500"
                    >
                </div>

                <!-- Shop Owner User Account -->
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Shop Owner (Tenant Admin Account)</label>
                    <select name="shop_owner_id" class="w-full px-3 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-purple-500">
                        <option value="">-- Assign Shop Owner User Account --</option>
                        @foreach($shopOwners as $so)
                            <option value="{{ $so->id }}" {{ (old('shop_owner_id', auth()->id()) == $so->id) ? 'selected' : '' }}>
                                {{ $so->name }} ({{ $so->roleDisplayName }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Customer Selection -->
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Billing Customer Contact *</label>
                    <select name="customer_id" id="subCustomerId" required class="w-full px-3 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-purple-500">
                        <option value="">-- Choose Existing Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id', $loop->first ? $c->id : '') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->phone }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Primary Branch Selection -->
                <div>
                    <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Primary Outlet / Headquarters *</label>
                    <select name="branch_id" required class="w-full px-3 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-purple-500">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (old('branch_id', $selectedBranch->id ?? 0) == $b->id) ? 'selected' : '' }}>
                                {{ $b->name }} ({{ $b->city ?? 'Central' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Package & Multi-Outlet Allocation -->
        <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 space-y-4 shadow-xl">
            <div class="flex items-center gap-2.5 border-b border-carbon-800 pb-3">
                <div class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold">2</div>
                <div>
                    <h3 class="text-sm font-bold text-white">Subscription Package & Branch Add-ons</h3>
                    <p class="text-[10px] text-silver-400">Choose base package and specify how many branch outlets the shop owner requires</p>
                </div>
            </div>

            <!-- Package Selection Grid -->
            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-2">Select Base Package *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($packages as $pkg)
                        <label class="relative block p-4 rounded-2xl border cursor-pointer transition-all pkg-card hover:bg-carbon-850 bg-carbon-850/60 border-carbon-750">
                            <input 
                                type="radio" 
                                name="package_id" 
                                value="{{ $pkg->id }}" 
                                data-price="{{ $pkg->price }}" 
                                data-cycle="{{ $pkg->billing_cycle }}"
                                data-included-branches="{{ $pkg->included_branches ?? 1 }}"
                                data-add-branch-price="{{ $pkg->price_per_additional_branch ?? 1500 }}"
                                data-trial="{{ $pkg->default_trial_days }}"
                                class="sr-only pkg-radio" 
                                required
                                onchange="onPackageSelect(this)"
                                {{ (old('package_id', request('package_id')) == $pkg->id || $loop->first) ? 'checked' : '' }}
                            >
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-white text-xs">{{ $pkg->name }}</span>
                                <span class="w-4 h-4 rounded-full border border-silver-500 flex items-center justify-center radio-circle">
                                    <span class="w-2 h-2 rounded-full bg-purple-500 hidden radio-dot"></span>
                                </span>
                            </div>
                            <div class="mt-2 flex items-baseline gap-1">
                                <span class="text-[10px] text-silver-400 font-mono">Rs.</span>
                                <span class="text-lg font-black text-white font-mono">{{ number_format($pkg->price, 0) }}</span>
                                <span class="text-[9px] text-silver-500 uppercase">/ {{ $pkg->billing_cycle }}</span>
                            </div>
                            <div class="mt-2 flex items-center gap-1.5 flex-wrap text-[9px] font-bold">
                                <span class="px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    {{ $pkg->included_branches ?? 1 }} Outlet Inc.
                                </span>
                                <span class="px-1.5 py-0.5 rounded bg-carbon-800 text-silver-400 border border-carbon-700">
                                    +Rs. {{ number_format($pkg->price_per_additional_branch ?? 1500, 0) }}/extra
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Number of Outlets / Branches Selection (Interactive Add-on Calculator) -->
            <div class="p-4 rounded-2xl bg-carbon-850 border border-purple-500/30 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <label class="block font-bold text-white uppercase text-[10px]">Total Outlets / Branch Licenses Required *</label>
                        <p class="text-[10px] text-silver-400">Set how many branches the merchant will manage simultaneously</p>
                    </div>

                    <!-- Outlet Presets (1 Branch, 2 Branches, 3 Branches, 5 Branches) -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button" onclick="setOutletCount(1)" class="outlet-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white transition">1 Outlet</button>
                        <button type="button" onclick="setOutletCount(2)" class="outlet-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white transition">2 Outlets</button>
                        <button type="button" onclick="setOutletCount(3)" class="outlet-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-purple-600 text-white border border-purple-500 transition shadow">3 Outlets</button>
                        <button type="button" onclick="setOutletCount(5)" class="outlet-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white transition">5 Outlets</button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-carbon-900 border border-purple-500 rounded-xl overflow-hidden shadow-inner">
                        <button type="button" onclick="stepOutlet(-1)" class="px-3 py-2 text-purple-400 hover:text-white hover:bg-purple-600 font-black text-base transition">−</button>
                        <input 
                            type="number" 
                            id="allocatedBranchesInput" 
                            name="allocated_branches" 
                            value="3" 
                            min="1" 
                            max="100" 
                            required 
                            oninput="recalculatePricing()"
                            class="w-16 text-center py-2 bg-transparent text-white font-mono font-black text-sm focus:outline-none"
                        >
                        <button type="button" onclick="stepOutlet(1)" class="px-3 py-2 text-purple-400 hover:text-white hover:bg-purple-600 font-black text-base transition">+</button>
                    </div>
                    <span class="text-xs text-silver-300 font-semibold">Total Licensed Outlets (Branches)</span>
                </div>

                <!-- Live Dynamic Pricing Breakdown Box -->
                <div class="p-3.5 rounded-xl bg-carbon-900 border border-carbon-750 space-y-2 text-xs">
                    <span class="text-[10px] font-extrabold uppercase text-silver-400 tracking-wider block">Live Price Breakdown:</span>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 font-mono text-[11px]">
                        <div class="p-2 rounded-lg bg-carbon-850 border border-carbon-800">
                            <span class="text-silver-500 block text-[9px] uppercase">Base Package Price</span>
                            <span class="text-white font-bold" id="breakdownBasePrice">Rs. 0.00</span>
                            <span class="text-silver-500 block text-[9px]" id="breakdownIncludedBranches">(Includes 1 Outlet)</span>
                        </div>

                        <div class="p-2 rounded-lg bg-carbon-850 border border-carbon-800">
                            <span class="text-silver-500 block text-[9px] uppercase">Additional Outlets Fee</span>
                            <span class="text-purple-300 font-bold" id="breakdownAddBranchFee">+Rs. 0.00</span>
                            <span class="text-silver-500 block text-[9px]" id="breakdownExtraCount">(0 Extra Outlets)</span>
                        </div>

                        <div class="p-2 rounded-lg bg-purple-950/30 border border-purple-500/40">
                            <span class="text-purple-400 block text-[9px] uppercase font-bold">Total Subscription</span>
                            <span class="text-lg font-black text-white" id="breakdownTotalPrice">Rs. 0.00</span>
                            <span class="text-silver-400 block text-[9px]" id="breakdownCycle">/ monthly</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Free Trial vs Immediate Paid Mode Toggle -->
            <div class="pt-2">
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-2">Plan Activation Mode *</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    
                    <label class="p-4 rounded-2xl border cursor-pointer transition-all plan-mode-card bg-purple-950/20 border-purple-500/50">
                        <input 
                            type="radio" 
                            name="plan_type" 
                            value="free_trial" 
                            checked
                            onchange="togglePlanType('free_trial')"
                            class="sr-only"
                        >
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-gift text-purple-400 text-sm"></i>
                            <span class="font-extrabold text-white text-xs">Payment-Free Trial</span>
                        </div>
                        <p class="text-[10px] text-silver-400 mt-1">Start on free trial without immediate payment. Client will be billed after trial period expires.</p>
                    </label>

                    <label class="p-4 rounded-2xl border cursor-pointer transition-all plan-mode-card bg-carbon-850/60 border-carbon-750">
                        <input 
                            type="radio" 
                            name="plan_type" 
                            value="paid" 
                            onchange="togglePlanType('paid')"
                            class="sr-only"
                        >
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-credit-card text-emerald-400 text-sm"></i>
                            <span class="font-extrabold text-white text-xs">Immediate Paid Activation</span>
                        </div>
                        <p class="text-[10px] text-silver-400 mt-1">Pay first month/quarter upfront now. Generates immediate POS sales receipt.</p>
                    </label>

                </div>
            </div>

            <!-- Trial Days Input & Presets (Visible when Free Trial selected) -->
            <div id="trialDaysContainer" class="p-4 rounded-2xl bg-carbon-850 border border-purple-500/30 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <label class="block font-bold text-purple-300 uppercase text-[10px]">Free Trial Duration (Days) *</label>
                        <p class="text-[10px] text-silver-400">Click a preset or enter custom number of trial evaluation days</p>
                    </div>
                    
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button" onclick="setTrialDays(3)" class="trial-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white transition">3 Days</button>
                        <button type="button" onclick="setTrialDays(7)" class="trial-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-purple-600 text-white border border-purple-500 transition shadow">7 Days</button>
                        <button type="button" onclick="setTrialDays(14)" class="trial-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white transition">14 Days</button>
                        <button type="button" onclick="setTrialDays(30)" class="trial-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white transition">30 Days</button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-32">
                        <input 
                            type="number" 
                            id="trialDaysInput" 
                            name="trial_days" 
                            value="7" 
                            min="1" 
                            max="365" 
                            required 
                            oninput="updateTrialPreview()"
                            class="w-full text-center py-2 px-3 rounded-xl bg-carbon-900 border border-purple-500 text-white font-mono font-black text-sm focus:outline-none"
                        >
                    </div>
                    <span class="text-xs text-silver-300 font-semibold">Evaluation Days</span>
                </div>

                <!-- Live Trial Dates Box -->
                <div class="pt-2 border-t border-carbon-800 grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] font-mono">
                    <div class="text-silver-400">
                        <span>Trial Starts: </span>
                        <strong class="text-white">{{ now()->format('d M Y') }}</strong>
                    </div>
                    <div class="text-purple-300 font-bold">
                        <span>Trial Ends: </span>
                        <strong id="previewTrialEnds">{{ now()->addDays(7)->format('d M Y') }}</strong>
                    </div>
                    <div class="text-emerald-400 font-bold">
                        <span>First Billing Due: </span>
                        <strong id="previewBillingDue">{{ now()->addDays(7)->format('d M Y') }}</strong>
                    </div>
                </div>
            </div>

            <!-- Upfront Payment Method (Visible when Immediate Paid selected) -->
            <div id="paymentMethodContainer" class="hidden p-4 rounded-2xl bg-carbon-850 border border-emerald-500/30 space-y-2">
                <label class="block font-bold text-emerald-400 uppercase text-[10px]">Payment Method (First Term) *</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-900 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="cash" checked class="text-emerald-500">
                        <span class="font-bold text-white text-xs">Cash</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-900 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="card" class="text-emerald-500">
                        <span class="font-bold text-white text-xs">Card</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-900 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="bank_transfer" class="text-emerald-500">
                        <span class="font-bold text-white text-xs">Bank Transfer</span>
                    </label>
                    <label class="p-2.5 rounded-xl border border-carbon-700 bg-carbon-900 flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="qr" class="text-emerald-500">
                        <span class="font-bold text-white text-xs">LankaQR</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Sales Origin & Acquisition Channel (Online vs Register) -->
        <div class="p-5 rounded-3xl bg-carbon-900 border border-carbon-800 space-y-4 shadow-xl">
            <div class="flex items-center gap-2.5 border-b border-carbon-800 pb-3">
                <div class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold">3</div>
                <div>
                    <h3 class="text-sm font-bold text-white">Sales Origin / Acquisition Channel</h3>
                    <p class="text-[10px] text-silver-400">Track who closed this sale (In-Store Register Staff vs Online Self-Service)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Register / Cashier Option -->
                <label class="p-4 rounded-2xl border cursor-pointer transition-all channel-card bg-emerald-950/20 border-emerald-500/50">
                    <input 
                        type="radio" 
                        name="sales_channel" 
                        value="register" 
                        checked
                        onchange="toggleSalesChannel('register')"
                        class="sr-only"
                    >
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-cash-register text-emerald-400 text-sm"></i>
                        <span class="font-extrabold text-white text-xs">In-Store Register (Counter)</span>
                    </div>
                    <p class="text-[10px] text-silver-400 mt-1">Acquired directly at the cashier counter by in-store sales representative.</p>
                </label>

                <!-- Online Portal Option -->
                <label class="p-4 rounded-2xl border cursor-pointer transition-all channel-card bg-carbon-850/60 border-carbon-750">
                    <input 
                        type="radio" 
                        name="sales_channel" 
                        value="online" 
                        onchange="toggleSalesChannel('online')"
                        class="sr-only"
                    >
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-globe text-blue-400 text-sm"></i>
                        <span class="font-extrabold text-white text-xs">Online / Web Self-Service</span>
                    </div>
                    <p class="text-[10px] text-silver-400 mt-1">Acquired through website, social media, e-commerce self-signup, or online portal.</p>
                </label>
            </div>

            <!-- Staff Assignee (Visible when Register selected) -->
            <div id="staffAssigneeContainer" class="pt-1">
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Assigned Sales Representative / Cashier</label>
                <select name="sales_agent_id" class="w-full sm:w-1/2 px-3 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-purple-500">
                    @foreach($staffUsers as $u)
                        <option value="{{ $u->id }}" {{ (auth()->id() == $u->id) ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->roleDisplayName }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-silver-300 uppercase text-[10px] mb-1">Enrollment Notes / Commercial Terms</label>
                <textarea name="notes" rows="2" placeholder="Optional notes (e.g. promotional agreement, 3-outlet starter package)..." class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-purple-500"></textarea>
            </div>
        </div>

        <!-- Form Submission Footer -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('subscriptions.index') }}" class="px-5 py-2.5 rounded-xl bg-carbon-800 text-silver-400 font-bold hover:text-white transition">Cancel</a>
            <button 
                type="submit" 
                class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 via-brand-600 to-rose-600 hover:from-purple-500 hover:to-brand-500 text-white font-extrabold text-xs shadow-xl shadow-purple-600/30 flex items-center gap-2 transition touch-action-btn"
            >
                <i class="fa-solid fa-circle-check"></i>
                <span id="btnSubmitText">Enroll Subscription (+ 7-Day Free Trial)</span>
            </button>
        </div>

    </form>

</div>

<script>
    function onPackageSelect(radio) {
        document.querySelectorAll('.pkg-card').forEach(c => {
            c.classList.remove('bg-purple-950/20', 'border-purple-500');
            c.classList.add('bg-carbon-850/60', 'border-carbon-750');
            c.querySelector('.radio-dot')?.classList.add('hidden');
        });

        const card = radio.closest('.pkg-card');
        if (card) {
            card.classList.add('bg-purple-950/20', 'border-purple-500');
            card.classList.remove('bg-carbon-850/60', 'border-carbon-750');
            card.querySelector('.radio-dot')?.classList.remove('hidden');
        }

        const defaultTrial = parseInt(radio.getAttribute('data-trial')) || 7;
        const trialInput = document.getElementById('trialDaysInput');
        if (trialInput && document.querySelector('input[name="plan_type"]:checked')?.value === 'free_trial') {
            trialInput.value = defaultTrial;
            updateTrialPreview();
        }

        recalculatePricing();
    }

    function stepOutlet(change) {
        const input = document.getElementById('allocatedBranchesInput');
        let val = parseInt(input.value) || 1;
        val = Math.max(1, Math.min(100, val + change));
        setOutletCount(val);
    }

    function setOutletCount(count) {
        document.getElementById('allocatedBranchesInput').value = count;
        document.querySelectorAll('.outlet-preset-btn').forEach(b => {
            if (b.innerText.includes(count + ' Outlet')) {
                b.className = "outlet-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-purple-600 text-white border border-purple-500 transition shadow";
            } else {
                b.className = "outlet-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white transition";
            }
        });
        recalculatePricing();
    }

    function recalculatePricing() {
        const pkgRadio = document.querySelector('.pkg-radio:checked');
        if (!pkgRadio) return;

        const basePrice = parseFloat(pkgRadio.getAttribute('data-price')) || 0;
        const includedBranches = parseInt(pkgRadio.getAttribute('data-included-branches')) || 1;
        const addBranchRate = parseFloat(pkgRadio.getAttribute('data-add-branch-price')) || 1500;
        const billingCycle = pkgRadio.getAttribute('data-cycle') || 'monthly';

        const totalBranches = parseInt(document.getElementById('allocatedBranchesInput').value) || 1;
        const extraBranches = Math.max(0, totalBranches - includedBranches);
        const addBranchFee = extraBranches * addBranchRate;
        const totalPrice = basePrice + addBranchFee;

        // Update breakdown elements
        document.getElementById('breakdownBasePrice').innerText = 'Rs. ' + basePrice.toLocaleString('en-US', { minimumFractionDigits: 2 });
        document.getElementById('breakdownIncludedBranches').innerText = `(Includes ${includedBranches} Outlet${includedBranches > 1 ? 's' : ''})`;

        document.getElementById('breakdownAddBranchFee').innerText = '+Rs. ' + addBranchFee.toLocaleString('en-US', { minimumFractionDigits: 2 });
        document.getElementById('breakdownExtraCount').innerText = `(${extraBranches} Extra Outlet${extraBranches !== 1 ? 's' : ''} @ Rs. ${addBranchRate.toLocaleString()})`;

        document.getElementById('breakdownTotalPrice').innerText = 'Rs. ' + totalPrice.toLocaleString('en-US', { minimumFractionDigits: 2 });
        document.getElementById('breakdownCycle').innerText = `/ ${billingCycle}`;
    }

    function togglePlanType(type) {
        const trialBox = document.getElementById('trialDaysContainer');
        const payBox = document.getElementById('paymentMethodContainer');
        const btnText = document.getElementById('btnSubmitText');

        document.querySelectorAll('.plan-mode-card').forEach(c => {
            c.classList.remove('bg-purple-950/20', 'border-purple-500/50', 'bg-emerald-950/20', 'border-emerald-500/50');
            c.classList.add('bg-carbon-850/60', 'border-carbon-750');
        });

        const checkedRadio = document.querySelector(`input[name="plan_type"][value="${type}"]`);
        const card = checkedRadio?.closest('.plan-mode-card');

        if (type === 'free_trial') {
            card?.classList.add('bg-purple-950/20', 'border-purple-500/50');
            trialBox.classList.remove('hidden');
            payBox.classList.add('hidden');
            const days = document.getElementById('trialDaysInput').value || 7;
            const branches = document.getElementById('allocatedBranchesInput').value || 1;
            btnText.innerText = `Enroll Subscription (+ ${days}-Day Free Trial, ${branches} Outlets)`;
        } else {
            card?.classList.add('bg-emerald-950/20', 'border-emerald-500/50');
            trialBox.classList.add('hidden');
            payBox.classList.remove('hidden');
            btnText.innerText = `Complete & Activate Paid Subscription`;
        }
    }

    function setTrialDays(days) {
        document.getElementById('trialDaysInput').value = days;
        document.querySelectorAll('.trial-preset-btn').forEach(b => {
            if (b.innerText.includes(days + ' Days')) {
                b.className = "trial-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-purple-600 text-white border border-purple-500 transition shadow";
            } else {
                b.className = "trial-preset-btn px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-white transition";
            }
        });
        updateTrialPreview();
    }

    function updateTrialPreview() {
        const days = parseInt(document.getElementById('trialDaysInput').value) || 0;
        const now = new Date();
        const end = new Date();
        end.setDate(now.getDate() + days);

        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        const endStr = end.toLocaleDateString('en-GB', options);

        document.getElementById('previewTrialEnds').innerText = endStr;
        document.getElementById('previewBillingDue').innerText = endStr;

        if (document.querySelector('input[name="plan_type"]:checked')?.value === 'free_trial') {
            const branches = document.getElementById('allocatedBranchesInput').value || 1;
            document.getElementById('btnSubmitText').innerText = `Enroll Subscription (+ ${days}-Day Free Trial, ${branches} Outlets)`;
        }
    }

    function toggleSalesChannel(channel) {
        document.querySelectorAll('.channel-card').forEach(c => {
            c.classList.remove('bg-emerald-950/20', 'border-emerald-500/50', 'bg-blue-950/20', 'border-blue-500/50');
            c.classList.add('bg-carbon-850/60', 'border-carbon-750');
        });

        const checkedRadio = document.querySelector(`input[name="sales_channel"][value="${channel}"]`);
        const card = checkedRadio?.closest('.channel-card');
        const staffBox = document.getElementById('staffAssigneeContainer');

        if (channel === 'register') {
            card?.classList.add('bg-emerald-950/20', 'border-emerald-500/50');
            staffBox.classList.remove('hidden');
        } else {
            card?.classList.add('bg-blue-950/20', 'border-blue-500/50');
            staffBox.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const checkedPkg = document.querySelector('.pkg-radio:checked');
        if (checkedPkg) onPackageSelect(checkedPkg);
    });
</script>
@endsection
