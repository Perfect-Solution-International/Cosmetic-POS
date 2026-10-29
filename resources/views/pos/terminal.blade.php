@extends('layouts.pos')

@section('title', 'POS Cashier Terminal')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Top Alert / Shift Status Bar -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-cash-register text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-white uppercase tracking-wider">{{ $branch->name ?? 'Main Branch' }}</span>
                    @if($activeShift)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            ACTIVE REGISTER: {{ $activeShift->register->name ?? 'Counter 1' }}
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center gap-1.5">
                            <i class="fa-solid fa-lock text-[9px]"></i> REGISTER CLOSED
                        </span>
                    @endif
                </div>
                <p class="text-[10px] text-silver-400">
                    Cashier: <span class="text-white font-semibold">{{ $user->name }}</span> | 
                    Opening Float: <span class="font-mono text-brand-400 font-bold">Rs. {{ number_format($activeShift->opening_float ?? 0, 2) }}</span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($activeShift)
                <button 
                    type="button" 
                    onclick="openCloseShiftModal()"
                    class="px-3.5 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-red-500/30 text-red-400 hover:text-white text-xs font-bold transition-all flex items-center gap-2 touch-action-btn"
                >
                    <i class="fa-solid fa-calculator text-xs"></i>
                    <span>End Shift & Count Cash</span>
                </button>
            @else
                <button 
                    type="button" 
                    onclick="openStartShiftModal()"
                    class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-bold shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2 touch-action-btn"
                >
                    <i class="fa-solid fa-key text-xs"></i>
                    <span>Start Shift (Open Drawer)</span>
                </button>
            @endif

            <!-- Wholesale vs Retail Pricing Toggle Pill -->
            <button 
                type="button" 
                onclick="togglePricingMode()"
                id="pricingModeToggleBtn"
                class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition-all flex items-center gap-1.5 touch-action-btn"
                title="Toggle between Retail and Wholesale Pricing Mode"
            >
                <i class="fa-solid fa-tag text-xs text-amber-400"></i>
                <span id="pricingModeLabel">Retail Mode</span>
            </button>

            <!-- Item Exchange / Return Button (F8) -->
            <button 
                type="button" 
                onclick="openExchangeModal()"
                class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-rose-500/30 text-rose-300 hover:text-white text-xs font-bold transition-all flex items-center gap-1.5 touch-action-btn"
                title="Add returned item for exchange / credit deduction (F8)"
            >
                <i class="fa-solid fa-arrow-right-arrow-left text-xs text-rose-400"></i>
                <span class="hidden md:inline">Exchange (F8)</span>
            </button>

            <!-- Dual Screen Customer Display Launch Button -->
            <a 
                href="{{ route('pos.customer-display') }}" 
                target="_blank"
                class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition-all flex items-center gap-1.5 touch-action-btn"
                title="Open Customer-Facing Dual Screen in new window"
            >
                <i class="fa-solid fa-tv text-xs text-indigo-400"></i>
                <span class="hidden xl:inline">Customer Screen</span>
            </a>

            <!-- Hardware Cash Drawer Kick Button (F9) -->
            <button 
                type="button" 
                onclick="triggerCashDrawer()"
                class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition-all flex items-center gap-1.5 touch-action-btn"
                title="Pulse RJ11 Cash Drawer Kick (Shortcut: F9)"
            >
                <i class="fa-solid fa-cash-register text-xs text-brand-400"></i>
                <span class="hidden sm:inline">Drawer (F9)</span>
            </button>
        </div>
    </div>

    <!-- Main Terminal Interface (Split Left: Product Touch Pad, Right: Bill Cart) -->
    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
        
        <!-- LEFT: Product Search, Category Tabs & Grid (Takes 60-65% width on desktop) -->
        <div class="flex-1 flex flex-col min-w-0 border-r border-carbon-800 bg-carbon-950 overflow-hidden">
            
            <!-- Search & Filter Header -->
            <div class="p-3 sm:p-4 border-b border-carbon-800 bg-carbon-900/60 shrink-0 space-y-3">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-barcode absolute left-3.5 top-1/2 -translate-y-1/2 text-silver-400 text-base"></i>
                        <input 
                            type="text" 
                            id="posSearchInput"
                            placeholder="Type product name, shade or scan barcode (Press Enter to add)..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs sm:text-sm font-medium focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
                            onkeyup="filterProducts()"
                            autofocus
                        >
                    </div>
                    <button 
                        type="button"
                        onclick="clearSearch()"
                        class="px-3 py-2.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-400 hover:text-white text-xs font-semibold touch-action-btn"
                        title="Clear search"
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>

                <!-- Category Pills (Horizontal scrollable) -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar text-xs">
                    <button 
                        type="button"
                        onclick="selectCategory('all', this)"
                        class="category-pill active px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all bg-brand-600 text-white shadow-md shadow-brand-600/30"
                    >
                        All Items
                    </button>
                    @foreach($categories as $category)
                        <button 
                            type="button"
                            onclick="selectCategory('{{ $category }}', this)"
                            class="category-pill px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 transition-all"
                        >
                            {{ $category }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Grid -->
            <div class="flex-1 overflow-y-auto p-3 sm:p-4">
                <div id="productGrid" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
                    @forelse($products as $product)
                        @php
                            $firstVar = $product->variations->first();
                            $stockTotal = $product->variations->sum(function($v) use ($branch) {
                                return $v->stocks->where('branch_id', $branch->id)->sum('quantity');
                            });
                            $minPrice = $product->variations->min('selling_price') ?? 0;
                            $maxPrice = $product->variations->max('selling_price') ?? 0;
                            $isVariable = $product->type === 'variable' && $product->variations->count() > 1;
                        @endphp
                        <div 
                            class="product-card group relative rounded-2xl bg-carbon-900 border border-carbon-800 hover:border-brand-500/50 p-3 flex flex-col justify-between transition-all duration-200 hover:shadow-xl hover:shadow-brand-600/10 cursor-pointer touch-action-btn select-none"
                            data-name="{{ strtolower($product->name) }}"
                            data-category="{{ $product->category->name ?? '' }}"
                            data-sku="{{ strtolower($product->code . ' ' . $product->variations->pluck('sku')->implode(' ') . ' ' . $product->variations->pluck('barcode')->implode(' ')) }}"
                            @if($isVariable)
                                onclick="openVariationModal({{ json_encode($product) }}, {{ json_encode($branch->id) }})"
                            @else
                                onclick="addToCart({
                                    id: {{ $firstVar->id ?? 0 }},
                                    productId: {{ $product->id }},
                                    name: '{{ addslashes($product->name) }}',
                                    variationName: 'Standard',
                                    sku: '{{ $firstVar->sku ?? '' }}',
                                    price: {{ $firstVar->selling_price ?? 0 }},
                                    wholesalePrice: {{ $firstVar->wholesale_price ?? ($firstVar->selling_price ?? 0) }},
                                    stock: {{ $stockTotal }},
                                    trackSerial: {{ ($firstVar && $firstVar->track_serial) ? 'true' : 'false' }},
                                    warrantyMonths: {{ $firstVar->warranty_months ?? 0 }},
                                    serials: {{ json_encode($firstVar && $firstVar->serials ? $firstVar->serials->pluck('serial_number') : []) }}
                                })"
                            @endif
                        >
                            <!-- Stock & Category Tag -->
                            <div class="flex items-center justify-between gap-1 mb-2">
                                <span class="px-2 py-0.5 rounded text-[9px] font-semibold bg-carbon-800 text-silver-400 border border-carbon-700 truncate max-w-[90px]">
                                    {{ $product->category->name ?? 'General' }}
                                </span>
                                @if($stockTotal <= 0)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                        Out of Stock
                                    </span>
                                @elseif($stockTotal <= 5)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        Qty: {{ $stockTotal }}
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        Qty: {{ $stockTotal }}
                                    </span>
                                @endif
                            </div>

                            <!-- Icon / Thumbnail Area -->
                            <div class="w-full h-24 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-center text-silver-500 mb-2 overflow-hidden group-hover:scale-[1.02] transition-transform">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="flex flex-col items-center">
                                        <i class="fa-solid {{ $product->category->name == 'Cosmetics' ? 'fa-spray-can' : ($product->category->name == 'Handbags' ? 'fa-bag-shopping' : 'fa-gem') }} text-2xl text-silver-400 group-hover:text-brand-500 transition-colors"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Product Title & Brand -->
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-white group-hover:text-brand-400 transition-colors line-clamp-2 leading-tight">
                                    {{ $product->name }}
                                </h4>
                                <p class="text-[10px] text-silver-400 mt-0.5 truncate">
                                    {{ $product->brand->name ?? 'PSI Luxury' }} • <span class="font-mono">{{ $product->code }}</span>
                                </p>
                            </div>

                            <!-- Price & Action Indicator -->
                            <div class="mt-2.5 pt-2 border-t border-carbon-800/80 flex items-center justify-between">
                                <div>
                                    <span class="text-[9px] text-silver-500 block uppercase font-medium">Price</span>
                                    <span class="text-xs font-mono font-extrabold text-white">
                                        @if($minPrice == $maxPrice || !$isVariable)
                                            Rs. {{ number_format($minPrice, 2) }}
                                        @else
                                            Rs. {{ number_format($minPrice, 0) }} - {{ number_format($maxPrice, 0) }}
                                        @endif
                                    </span>
                                </div>
                                <div class="w-7 h-7 rounded-lg {{ $isVariable ? 'bg-carbon-800 text-silver-300' : 'bg-brand-600/20 text-brand-400' }} border border-carbon-700 flex items-center justify-center text-xs group-hover:bg-brand-600 group-hover:text-white transition-all">
                                    <i class="fa-solid {{ $isVariable ? 'fa-list-check' : 'fa-plus' }}"></i>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-16 text-center">
                            <i class="fa-solid fa-boxes-stacked text-4xl text-carbon-700 mb-3"></i>
                            <h3 class="text-sm font-bold text-silver-400">No products available in this branch</h3>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT: Live POS Cart & Checkout (Takes 35-40% on desktop) -->
        <div class="w-full lg:w-[420px] xl:w-[460px] bg-carbon-900 flex flex-col justify-between shrink-0 shadow-2xl border-t lg:border-t-0 lg:border-l border-carbon-800 h-full overflow-hidden">
            
            <!-- Cart Header & Customer CRM Selector -->
            <div class="p-3.5 border-b border-carbon-800 bg-carbon-900/90 shrink-0 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-basket-shopping text-brand-500 text-sm"></i>
                        <span class="text-xs font-extrabold text-white uppercase tracking-wider">Current Bill</span>
                        <span id="cartBadgeCount" class="px-2 py-0.2 rounded-full text-[10px] font-mono font-bold bg-brand-600/30 text-brand-400 border border-brand-500/30">
                            0 items
                        </span>
                    </div>

                    <button 
                        type="button" 
                        onclick="clearCart()"
                        class="text-[11px] font-semibold text-rose-400 hover:text-rose-300 hover:underline flex items-center gap-1"
                    >
                        <i class="fa-solid fa-trash-can text-[10px]"></i> Clear
                    </button>
                </div>

                <!-- Customer CRM Dropdown & Fast Add -->
                <div class="relative">
                    <div class="flex items-center gap-1.5">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-user absolute left-3 top-1/2 -translate-y-1/2 text-silver-400 text-xs"></i>
                            <select 
                                id="cartCustomerId" 
                                class="w-full pl-8 pr-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500"
                                onchange="onCustomerSelect()"
                            >
                                <option value="">Walk-in Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" data-points="{{ $c->loyalty_points }}" data-pricing-tier="{{ $c->pricing_tier ?? 'retail' }}" data-phone="{{ $c->phone }}" data-credit-limit="{{ $c->credit_limit }}" data-current-balance="{{ $c->current_balance }}">
                                        {{ $c->name }} ({{ $c->phone }}) - {{ $c->loyalty_points }} pts | Due: Rs. {{ number_format($c->current_balance, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button 
                            type="button" 
                            onclick="openTerminalCustomerModal()"
                            class="px-2.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs touch-action-btn"
                            title="Register new customer"
                        >
                            <i class="fa-solid fa-user-plus"></i>
                        </button>
                    </div>
                    <div id="customerPointsInfo" class="hidden text-[10px] text-emerald-400 font-semibold mt-1 px-1 flex items-center justify-between">
                        <span class="flex items-center gap-1">
                            <i class="fa-solid fa-star text-[9px]"></i> Loyalty: <strong id="customerPointsBalance" class="font-mono">0</strong> pts (<span id="customerPointsCashVal">Rs. 0.00</span>)
                        </span>
                        <button type="button" onclick="toggleRedeemPoints()" id="btnRedeemPoints" class="px-2 py-0.5 rounded bg-emerald-500/20 hover:bg-emerald-500 hover:text-black text-emerald-300 text-[10px] font-bold border border-emerald-500/30 transition">
                            Redeem Points
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cart Items Scrollable List -->
            <div id="cartItemsList" class="flex-1 overflow-y-auto p-3 space-y-2">
                <!-- Empty state shown when cart is empty -->
                <div id="emptyCartMessage" class="h-full flex flex-col items-center justify-center py-12 text-center text-silver-500">
                    <div class="w-14 h-14 rounded-2xl bg-carbon-850 border border-carbon-800 flex items-center justify-center mb-3 text-silver-600">
                        <i class="fa-solid fa-cart-arrow-down text-2xl"></i>
                    </div>
                    <p class="text-xs font-semibold text-silver-400">Cart is empty</p>
                    <p class="text-[10px] text-silver-600 mt-0.5">Scan barcode or tap products to begin order</p>
                </div>
            </div>

            <!-- Cart Calculation & Bottom Payment Footer -->
            <div class="p-3.5 border-t border-carbon-800 bg-carbon-900 shrink-0 space-y-3">
                
                <!-- Quick Calculation Breakdown -->
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-silver-400">
                        <span>Subtotal</span>
                        <span id="cartSubtotal" class="font-mono text-white font-medium">Rs. 0.00</span>
                    </div>

                    <!-- Discount Row -->
                    <div class="flex items-center justify-between text-silver-400">
                        <div class="flex items-center gap-1.5">
                            <span>Discount</span>
                            <div class="inline-flex rounded-lg bg-carbon-800 border border-carbon-700 p-0.5 text-[10px]">
                                <button type="button" id="btnDiscountFixed" onclick="setDiscountType('fixed')" class="px-1.5 py-0.5 rounded font-bold bg-brand-600 text-white">Rs</button>
                                <button type="button" id="btnDiscountPercent" onclick="setDiscountType('percentage')" class="px-1.5 py-0.5 rounded font-medium text-silver-400 hover:text-white">%</button>
                            </div>
                        </div>
                        <div class="w-24">
                            <input 
                                type="number" 
                                id="cartDiscountInput" 
                                value="0" 
                                min="0" 
                                step="any"
                                class="w-full text-right px-2 py-1 rounded-lg bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                oninput="calculateTotals()"
                            >
                        </div>
                    </div>

                    <!-- Loyalty Points Discount Row -->
                    <div id="cartLoyaltyRow" class="hidden flex items-center justify-between text-emerald-400">
                        <span class="flex items-center gap-1">
                            <i class="fa-solid fa-star text-[9px]"></i> Loyalty Redeemed (<span id="cartLoyaltyPtsCount">0</span> pts)
                        </span>
                        <span id="cartLoyaltyAmount" class="font-mono font-bold">-Rs. 0.00</span>
                    </div>

                    <!-- Exchange Items Deduction Row -->
                    <div id="cartExchangeRow" class="hidden flex items-center justify-between text-rose-400">
                        <span class="flex items-center gap-1">
                            <i class="fa-solid fa-arrow-right-arrow-left text-[9px]"></i> Exchange Return Credit
                        </span>
                        <span id="cartExchangeAmount" class="font-mono font-bold">-Rs. 0.00</span>
                    </div>

                    <!-- Tax Rate (e.g. 0% or VAT) -->
                    <div class="flex items-center justify-between text-silver-400">
                        <div class="flex items-center gap-1">
                            <span>Tax / VAT (%)</span>
                        </div>
                        <div class="w-24">
                            <input 
                                type="number" 
                                id="cartTaxInput" 
                                value="0" 
                                min="0" 
                                step="any"
                                class="w-full text-right px-2 py-1 rounded-lg bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                oninput="calculateTotals()"
                            >
                        </div>
                    </div>

                    <!-- Grand Total Banner -->
                    <div class="pt-2 border-t border-carbon-800 flex items-baseline justify-between">
                        <span class="text-xs font-extrabold uppercase text-white tracking-wider">Grand Total</span>
                        <div class="text-right">
                            <span class="text-xs text-brand-400 font-mono font-bold mr-1">LKR</span>
                            <span id="cartGrandTotal" class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-rose-100 to-brand-400 font-mono">
                                0.00
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hold Cart & Held Sales Buttons -->
                <div class="grid grid-cols-2 gap-2">
                    <button 
                        type="button" 
                        onclick="openHoldCartModal()"
                        class="py-2 px-3 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-amber-400 hover:text-amber-300 border border-amber-500/30 text-xs font-bold flex items-center justify-center gap-1.5 transition touch-action-btn"
                        title="Temporarily hold current order"
                    >
                        <i class="fa-solid fa-pause"></i>
                        <span>Hold Cart</span>
                    </button>
                    <button 
                        type="button" 
                        onclick="openHeldSalesModal()"
                        class="py-2 px-3 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold flex items-center justify-center gap-1.5 transition touch-action-btn"
                        title="Resume previously held orders"
                    >
                        <i class="fa-solid fa-list-check text-brand-400"></i>
                        <span>Held Carts</span>
                        <span id="heldBadgeCount" class="ml-1 px-1.5 py-0.2 rounded-full text-[9px] font-mono bg-brand-500 text-white font-black">0</span>
                    </button>
                </div>

                <!-- Pay Button (Opens Split Payment Modal) -->
                @if($activeShift)
                    <button 
                        type="button" 
                        id="btnPayCheckout"
                        onclick="openPaymentModal()"
                        disabled
                        class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-brand-600 via-rose-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white font-extrabold text-sm uppercase tracking-wider shadow-lg shadow-brand-600/30 flex items-center justify-center gap-2 transition-all disabled:opacity-40 disabled:cursor-not-allowed touch-action-btn"
                    >
                        <i class="fa-solid fa-credit-card"></i>
                        <span>PAY / COMPLETE BILL (F4)</span>
                    </button>
                @else
                    <button 
                        type="button" 
                        onclick="openStartShiftModal()"
                        class="w-full py-3.5 px-4 rounded-2xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-sm uppercase tracking-wider shadow-lg shadow-amber-600/30 flex items-center justify-center gap-2 transition-all touch-action-btn"
                    >
                        <i class="fa-solid fa-key"></i>
                        <span>Start Shift to Enable Checkout</span>
                    </button>
                @endif
            </div>
        </div>

    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Variation Selector Modal (for Variable Products like Lipsticks/Bags) -->
<div id="variationModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
        <div class="p-4 border-b border-carbon-800 flex items-center justify-between">
            <div>
                <h3 id="varModalTitle" class="text-sm font-bold text-white">Select Product Option</h3>
                <p id="varModalSubtitle" class="text-xs text-silver-400">Choose shade, size or finish</p>
            </div>
            <button type="button" onclick="closeVariationModal()" class="w-8 h-8 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="varModalList" class="p-4 max-h-[60vh] overflow-y-auto space-y-2">
            <!-- Dynamic list of variations injected via JS -->
        </div>
    </div>
</div>

<!-- 2. Split Payment / Checkout Modal -->
<div id="paymentModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-xl overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
        <div class="p-4 sm:p-5 border-b border-carbon-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-wallet text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-white">Payment & Tender</h3>
                    <p class="text-xs text-silver-400">Cash, Card, Bank or Loyalty Points</p>
                </div>
            </div>
            <button type="button" onclick="closePaymentModal()" class="w-8 h-8 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-4 sm:p-5 overflow-y-auto flex-1 space-y-4">
            <!-- Due Amount Summary Display -->
            <div class="p-4 rounded-2xl bg-carbon-850 border border-carbon-700 flex items-center justify-between">
                <div>
                    <span class="text-xs text-silver-400 block font-semibold">Total Amount Due</span>
                    <span id="modalDueAmount" class="text-2xl font-black text-brand-400 font-mono">Rs. 0.00</span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-silver-400 block font-semibold">Amount Received</span>
                    <span id="modalTenderedAmount" class="text-xl font-bold text-white font-mono">Rs. 0.00</span>
                </div>
            </div>

            <!-- Payment Method Rows -->
            <div class="space-y-3">
                <label class="text-xs font-bold text-silver-300 uppercase tracking-wider block">Payment Methods</label>

                <!-- 1. Cash Option -->
                <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-[120px]">
                        <i class="fa-solid fa-money-bill text-emerald-400 text-base"></i>
                        <span class="text-xs font-bold text-white">Cash</span>
                    </div>
                    <div class="flex items-center gap-2 flex-1 justify-end">
                        <span class="text-xs text-silver-500 font-mono">Rs.</span>
                        <input 
                            type="number" 
                            id="payCashAmount" 
                            step="any"
                            min="0"
                            placeholder="0.00" 
                            class="w-32 sm:w-40 px-3 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white font-mono text-sm text-right focus:outline-none focus:border-brand-500"
                            oninput="updatePaymentTotals()"
                        >
                    </div>
                </div>

                <!-- Fast Cash Tender Buttons -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[10px] text-silver-500 uppercase font-bold mr-1">Fast Cash:</span>
                    <button type="button" onclick="setCashExact()" class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-[11px] font-mono font-bold text-brand-400">Exact</button>
                    <button type="button" onclick="addCashTender(500)" class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-[11px] font-mono text-white">+500</button>
                    <button type="button" onclick="addCashTender(1000)" class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-[11px] font-mono text-white">+1,000</button>
                    <button type="button" onclick="addCashTender(2000)" class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-[11px] font-mono text-white">+2,000</button>
                    <button type="button" onclick="addCashTender(5000)" class="px-2.5 py-1 rounded-lg bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-[11px] font-mono text-white">+5,000</button>
                </div>

                <!-- 2. Card Option -->
                <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-[120px]">
                        <i class="fa-solid fa-credit-card text-blue-400 text-base"></i>
                        <span class="text-xs font-bold text-white">Credit / Debit Card</span>
                    </div>
                    <div class="flex items-center gap-2 flex-1 justify-end">
                        <span class="text-xs text-silver-500 font-mono">Rs.</span>
                        <input 
                            type="number" 
                            id="payCardAmount" 
                            step="any"
                            min="0"
                            placeholder="0.00" 
                            class="w-32 sm:w-40 px-3 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white font-mono text-sm text-right focus:outline-none focus:border-brand-500"
                            oninput="updatePaymentTotals()"
                        >
                    </div>
                </div>

                <!-- 3. Bank Transfer Option -->
                <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-[120px]">
                        <i class="fa-solid fa-building-columns text-purple-400 text-base"></i>
                        <span class="text-xs font-bold text-white">Bank Transfer</span>
                    </div>
                    <div class="flex items-center gap-2 flex-1 justify-end">
                        <span class="text-xs text-silver-500 font-mono">Rs.</span>
                        <input 
                            type="number" 
                            id="payBankAmount" 
                            step="any"
                            min="0"
                            placeholder="0.00" 
                            class="w-32 sm:w-40 px-3 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white font-mono text-sm text-right focus:outline-none focus:border-brand-500"
                            oninput="updatePaymentTotals()"
                        >
                    </div>
                </div>

                <!-- 4. Customer Credit (On Account) Option -->
                <div id="payCreditGroup" class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-[120px]">
                        <i class="fa-solid fa-hand-holding-dollar text-amber-400 text-base"></i>
                        <div>
                            <span class="text-xs font-bold text-white block">Customer Credit</span>
                            <span id="creditAvailableNotice" class="text-[9px] text-amber-400 font-mono">Walk-in: N/A</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-1 justify-end">
                        <span class="text-xs text-silver-500 font-mono">Rs.</span>
                        <input 
                            type="number" 
                            id="payCreditAmount" 
                            step="any"
                            min="0"
                            placeholder="0.00" 
                            class="w-32 sm:w-40 px-3 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white font-mono text-sm text-right focus:outline-none focus:border-amber-500"
                            oninput="updatePaymentTotals()"
                        >
                    </div>
                </div>

                <!-- 5. Loyalty Points Redemption Option -->
                <div id="payLoyaltyGroup" class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-[120px]">
                        <i class="fa-solid fa-star text-amber-400 text-base"></i>
                        <div>
                            <span class="text-xs font-bold text-white block">Loyalty Points</span>
                            <span id="loyaltyAvailableNotice" class="text-[9px] text-emerald-400 font-mono">0 pts available</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-1 justify-end">
                        <span class="text-xs text-silver-500 font-mono">Rs.</span>
                        <input 
                            type="number" 
                            id="payLoyaltyAmount" 
                            step="any"
                            min="0"
                            placeholder="0.00" 
                            class="w-32 sm:w-40 px-3 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white font-mono text-sm text-right focus:outline-none focus:border-emerald-500"
                            oninput="updatePaymentTotals()"
                        >
                    </div>
                </div>

                <!-- 6. Dynamic LankaQR Option -->
                <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-[120px]">
                        <i class="fa-solid fa-qrcode text-amber-400 text-base"></i>
                        <div>
                            <span class="text-xs font-bold text-white block">LankaQR Payment</span>
                            <span class="text-[9px] text-silver-400">Flash, FriMi, Genie, Q+</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-1 justify-end">
                        <button type="button" onclick="showLankaQrModal()" class="px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500 text-amber-300 hover:text-black border border-amber-500/30 text-xs font-bold transition flex items-center gap-1.5">
                            <i class="fa-solid fa-qrcode"></i>
                            <span>Show QR</span>
                        </button>
                        <span class="text-xs text-silver-500 font-mono">Rs.</span>
                        <input 
                            type="number" 
                            id="payQrAmount" 
                            step="any"
                            min="0"
                            placeholder="0.00" 
                            class="w-32 sm:w-40 px-3 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white font-mono text-sm text-right focus:outline-none focus:border-amber-500"
                            oninput="updatePaymentTotals()"
                        >
                    </div>
                </div>
            </div>

            <!-- Change Return Box -->
            <div class="p-3.5 rounded-2xl bg-carbon-800/80 border border-carbon-700 flex items-center justify-between">
                <div>
                    <span class="text-xs text-silver-400 font-bold block uppercase">Change Due</span>
                    <span class="text-[10px] text-silver-500">Cash returned to customer</span>
                </div>
                <span id="modalChangeAmount" class="text-xl font-mono font-black text-emerald-400">Rs. 0.00</span>
            </div>
        </div>

        <!-- Submit Checkout Button -->
        <div class="p-4 sm:p-5 border-t border-carbon-800 bg-carbon-900 shrink-0">
            <button 
                type="button" 
                id="btnFinalizeSale"
                onclick="submitCheckout()"
                class="w-full py-3.5 px-4 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-extrabold text-sm uppercase tracking-wider shadow-lg flex items-center justify-center gap-2 transition-all touch-action-btn"
            >
                <i class="fa-solid fa-receipt"></i>
                <span>Complete Sale & Print Receipt</span>
            </button>
        </div>
    </div>
</div>

<!-- 3. Open Cashier Shift Modal -->
<div id="startShiftModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm {{ $activeShift ? 'hidden' : 'flex' }} items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500 text-xl">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-white">Start Cashier Shift</h3>
                <p class="text-xs text-silver-400">{{ $branch->name ?? 'Main Branch' }} • Enter starting cash</p>
            </div>
        </div>

        <form action="{{ route('pos.shift.start') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Select Cash Counter</label>
                <select name="register_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                    @foreach($branch->registers ?? [] as $reg)
                        <option value="{{ $reg->id }}">{{ $reg->name }} ({{ $reg->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Starting Cash in Drawer *</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-silver-400 text-xs font-mono">Rs.</span>
                    <input 
                        type="number" 
                        name="opening_float" 
                        value="5000.00" 
                        min="0" 
                        step="any" 
                        required 
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-sm font-mono font-bold focus:outline-none focus:border-brand-500"
                    >
                </div>
                <p class="text-[10px] text-silver-500 mt-1">Count cash notes in drawer before opening counter.</p>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg transition-all touch-action-btn">
                    Open Register & Start Billing
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Close Shift & Z-Report Modal -->
@if($activeShift)
<div id="closeShiftModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-carbon-800 text-brand-400 flex items-center justify-center text-base">
                    <i class="fa-solid fa-calculator"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">End Shift & Cash Count (Z-Report)</h3>
                    <p class="text-xs text-silver-400">{{ $activeShift->register->name ?? 'Counter 1' }}</p>
                </div>
            </div>
            <button type="button" onclick="closeShiftCloseModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Shift Performance Breakdown -->
        <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-800 space-y-2 text-xs">
            <div class="flex items-center justify-between text-silver-400">
                <span>Starting Cash:</span>
                <span class="font-mono text-white font-bold">Rs. {{ number_format($activeShift->opening_float, 2) }}</span>
            </div>
            <div class="flex items-center justify-between text-silver-400">
                <span>Cash Sales:</span>
                <span class="font-mono text-emerald-400 font-bold">Rs. {{ number_format($activeShift->cash_sales, 2) }}</span>
            </div>
            <div class="flex items-center justify-between text-silver-400">
                <span>Card Sales:</span>
                <span class="font-mono text-blue-400 font-bold">Rs. {{ number_format($activeShift->card_sales, 2) }}</span>
            </div>
            <div class="pt-2 border-t border-carbon-800 flex items-center justify-between font-bold">
                <span class="text-white">Expected Cash in Drawer:</span>
                <span class="font-mono text-brand-400 text-sm">Rs. {{ number_format($activeShift->opening_float + $activeShift->cash_sales, 2) }}</span>
            </div>
        </div>

        <form action="{{ route('pos.shift.close', $activeShift->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Counted Cash in Drawer *</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-silver-400 text-xs font-mono">Rs.</span>
                    <input 
                        type="number" 
                        name="counted_cash" 
                        step="any" 
                        min="0" 
                        required 
                        placeholder="0.00"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-sm font-mono font-bold focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Shift Notes / Comments</label>
                <textarea 
                    name="notes" 
                    rows="2" 
                    placeholder="Discrepancy reason or end of day notes..."
                    class="w-full px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                ></textarea>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-red-600/30 transition-all touch-action-btn">
                Verify Cash & Close Shift (Z-Report)
            </button>
        </form>
    </div>
</div>
@endif

<!-- Modal 4: Quick Customer Registration in Terminal -->
<div id="terminalCustomerModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                    <i class="fa-solid fa-user-plus text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Quick Register Customer</h3>
                    <p class="text-xs text-silver-400">Save client profile & attach to current bill</p>
                </div>
            </div>
            <button type="button" onclick="closeTerminalCustomerModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="terminalCustomerForm" onsubmit="submitTerminalCustomer(event)" class="space-y-4">
            <div id="terminalCustomerError" class="hidden p-3 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs"></div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Full Name *</label>
                    <input type="text" id="quickCustName" required placeholder="e.g. Anoma Jayawardena" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Mobile Phone *</label>
                    <input type="text" id="quickCustPhone" required placeholder="0771234567" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Email Address</label>
                    <input type="email" id="quickCustEmail" placeholder="client@gmail.com" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Customer Tier / Group</label>
                    <select id="quickCustGroup" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                        <option value="">Default Retail Group</option>
                        @if(isset($groups))
                            @foreach($groups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Residential Address</label>
                <input type="text" id="quickCustAddress" placeholder="Address, City" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Credit Limit (LKR)</label>
                <input type="number" id="quickCustCreditLimit" min="0" step="any" value="0.00" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" id="quickCustSubmitBtn" class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition touch-action-btn flex items-center justify-center gap-2">
                <i class="fa-solid fa-check"></i>
                <span>Save & Attach to Bill</span>
            </button>
        </form>
    </div>
</div>

<!-- Modal 5: Hold Current Cart Modal -->
<div id="holdCartModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <i class="fa-solid fa-pause text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Hold Current Cart</h3>
                    <p class="text-xs text-silver-400">Park order to serve next customer</p>
                </div>
            </div>
            <button type="button" onclick="closeHoldCartModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="holdCartForm" onsubmit="submitHoldCart(event)" class="space-y-3.5 text-xs">
            <div>
                <label class="font-bold text-silver-300 uppercase block mb-1">Hold Reference / Tag</label>
                <input type="text" id="holdReferenceInput" placeholder="e.g. Table 4 / Blue Dress / Kasun" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label class="font-bold text-silver-300 uppercase block mb-1">Optional Note / Memo</label>
                <input type="text" id="holdNoteInput" placeholder="e.g. Left wallet in car, returning in 5 mins" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-amber-500">
            </div>

            <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-800 flex justify-between font-mono">
                <span class="text-silver-400">Cart Total to Park:</span>
                <span id="holdCartTotalPreview" class="font-black text-amber-400">Rs. 0.00</span>
            </div>

            <button type="submit" id="btnConfirmHold" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-amber-600/30 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-pause"></i>
                <span>Park Order & Clear Cart</span>
            </button>
        </form>
    </div>
</div>

<!-- Modal 6: Held Sales List Modal -->
<div id="heldSalesModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl p-6 space-y-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-400">
                    <i class="fa-solid fa-list-check text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Parked & Held Sales</h3>
                    <p class="text-xs text-silver-400">Resume orders to complete checkout</p>
                </div>
            </div>
            <button type="button" onclick="closeHeldSalesModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="heldSalesListContainer" class="flex-1 overflow-y-auto space-y-2 text-xs">
            <div class="py-12 text-center text-silver-500">
                <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-brand-500"></i>
                <p>Loading held orders...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal 7: Cross-Branch Stock Lookup Modal -->
<div id="branchStockModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center text-purple-400">
                    <i class="fa-solid fa-shop text-base"></i>
                </div>
                <div>
                    <h3 id="branchStockTitle" class="text-sm font-extrabold text-white">Stock Across Branches</h3>
                    <p id="branchStockSubtitle" class="text-xs text-silver-400 font-mono">Product / SKU</p>
                </div>
            </div>
            <button type="button" onclick="closeBranchStockModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="branchStockContent" class="space-y-2 text-xs">
            <div class="py-8 text-center text-silver-500">
                <i class="fa-solid fa-spinner fa-spin text-xl mb-1 text-purple-400"></i>
                <p>Checking branch inventories...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal 8: Manager PIN Override Modal -->
<div id="managerPinModal" class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl p-6 space-y-4 text-center">
        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 text-amber-400 mx-auto flex items-center justify-center text-xl">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div>
            <h3 class="text-sm font-extrabold text-white">Manager Override Required</h3>
            <p class="text-xs text-silver-400 mt-1">Discounts greater than 10% require approval from a Store Manager</p>
        </div>

        <div id="managerPinError" class="hidden p-2 rounded-lg bg-rose-500/20 text-rose-300 text-xs"></div>

        <div>
            <input 
                type="password" 
                id="managerPinInput" 
                maxlength="6"
                placeholder="Enter Manager PIN" 
                class="w-full text-center px-4 py-3 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-lg font-mono tracking-widest focus:outline-none focus:border-amber-500"
            >
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button type="button" onclick="closeManagerPinModal(false)" class="py-2.5 rounded-xl bg-carbon-800 text-silver-300 font-bold text-xs hover:bg-carbon-750">
                Cancel
            </button>
            <button type="button" onclick="verifyManagerPinSubmission()" class="py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs">
                Authorize
            </button>
        </div>
    </div>
</div>

<!-- Serial / IMEI Assignment Modal -->
<div id="serialAssignModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-carbon-900 border border-carbon-750 rounded-3xl p-6 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3 mb-4">
            <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                <i class="fa-solid fa-microchip text-brand-500"></i>
                <span>Assign Serial / IMEI Number</span>
            </h3>
            <button onclick="closeSerialAssignModal()" class="text-silver-500 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mb-4">
            <div id="serialModalItemTitle" class="text-xs font-bold text-white mb-0.5"></div>
            <div id="serialModalSubtitle" class="text-[11px] text-silver-400"></div>
        </div>

        <!-- Direct Scanner Barcode Input -->
        <div class="mb-4">
            <label class="text-[11px] font-bold text-silver-300 uppercase block mb-1">Scan or Type IMEI</label>
            <div class="flex gap-2">
                <input 
                    type="text" 
                    id="serialScanInput" 
                    placeholder="Scan barcode on device box..." 
                    class="flex-1 px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono text-xs focus:outline-none focus:border-brand-500"
                    onkeydown="if(event.key === 'Enter') { event.preventDefault(); addScannedSerial(); }"
                >
                <button type="button" onclick="addScannedSerial()" class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold">
                    Add
                </button>
            </div>
        </div>

        <!-- Available In-Stock Serials List -->
        <div class="mb-4">
            <label class="text-[11px] font-bold text-silver-300 uppercase block mb-1">Or Select In-Stock Unit</label>
            <div id="serialModalAvailableList" class="max-h-40 overflow-y-auto space-y-1.5 p-2 bg-carbon-950 rounded-xl border border-carbon-800 text-xs"></div>
        </div>

        <!-- Currently Assigned -->
        <div class="mb-4">
            <label class="text-[11px] font-bold text-silver-300 uppercase block mb-1">Assigned to this Line (<span id="serialAssignedCount">0</span>/<span id="serialRequiredCount">1</span>)</label>
            <div id="serialModalAssignedChips" class="flex flex-wrap gap-1.5 min-h-[32px] p-2 bg-carbon-850 rounded-xl border border-carbon-700 text-xs"></div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-carbon-800">
            <button type="button" onclick="closeSerialAssignModal()" class="px-4 py-2 rounded-xl bg-carbon-800 text-silver-300 hover:text-white text-xs font-bold">
                Cancel
            </button>
            <button type="button" onclick="confirmSerialAssignment()" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-extrabold shadow-lg">
                Confirm & Attach
            </button>
        </div>
    </div>
</div>

<!-- Modal 10: Exchange Item / Swap in Same Transaction Modal -->
<div id="exchangeModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400">
                    <i class="fa-solid fa-arrow-right-arrow-left text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Item Return / Exchange</h3>
                    <p class="text-xs text-silver-400">Add returned unit as a negative credit line to deduct from bill</p>
                </div>
            </div>
            <button type="button" onclick="closeExchangeModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form onsubmit="submitExchangeItem(event)" class="space-y-3.5 text-xs">
            <div>
                <label class="font-bold text-silver-300 uppercase block mb-1">Returned Product / SKU *</label>
                <select id="exchangeProductSelect" onchange="onExchangeProductSelect()" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-medium focus:outline-none focus:border-rose-500">
                    <option value="">-- Choose Item Being Returned --</option>
                    @foreach($products as $p)
                        @foreach($p->variations as $v)
                            <option value="{{ $v->id }}" data-price="{{ $v->selling_price }}" data-name="{{ $p->name }} ({{ $v->name }})" data-sku="{{ $v->sku }}">
                                {{ $p->name }} - {{ $v->name }} ({{ $v->sku }}) - Rs. {{ number_format($v->selling_price, 2) }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Quantity Returned *</label>
                    <input type="number" id="exchangeQtyInput" value="1" min="1" oninput="updateExchangePreview()" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="font-bold text-silver-300 uppercase block mb-1">Refund / Credit Value (Rs.) *</label>
                    <input type="number" step="0.01" min="0" id="exchangePriceInput" placeholder="0.00" oninput="updateExchangePreview()" required class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white font-mono focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div>
                <label class="font-bold text-silver-300 uppercase block mb-1">Reason for Return / Exchange</label>
                <input type="text" id="exchangeReasonInput" placeholder="e.g. Size mismatch, Color exchange, Minor defect" class="w-full px-3 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white focus:outline-none focus:border-rose-500">
            </div>

            <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-[11px] flex items-center justify-between font-mono">
                <span>Credit Deduction from Bill:</span>
                <span id="exchangePreviewDeduction" class="font-bold text-sm">-Rs. 0.00</span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-carbon-800">
                <button type="button" onclick="closeExchangeModal()" class="px-4 py-2 rounded-xl bg-carbon-800 text-silver-400 font-bold hover:text-white">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black shadow-lg shadow-rose-600/30 flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Exchange Credit to Bill</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 11: Dynamic LankaQR Screen Display Modal -->
<div id="lankaQrModal" class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md hidden items-center justify-center p-4">
    <div class="bg-carbon-900 border border-amber-500/40 rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl p-6 space-y-4 text-center">
        <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-qrcode text-amber-400 text-lg"></i>
                <h3 class="text-sm font-extrabold text-white">Dynamic LankaQR</h3>
            </div>
            <button type="button" onclick="closeLankaQrModal()" class="w-8 h-8 rounded-lg bg-carbon-800 text-silver-400 hover:text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div>
            <span class="text-xs text-silver-400 block font-semibold">Scan to Pay via any CBSL App</span>
            <div class="text-2xl font-black text-amber-400 font-mono mt-1" id="lankaQrAmountText">Rs. 0.00</div>
            <p class="text-[10px] text-silver-400 mt-0.5">Dialog Genie &bull; FriMi &bull; Commercial Q+ &bull; Flash</p>
        </div>

        <!-- Generated QR Display -->
        <div class="p-3 bg-white rounded-2xl mx-auto w-52 h-52 flex items-center justify-center shadow-inner">
            <img id="lankaQrImage" src="" alt="LankaQR" class="w-full h-full object-contain">
        </div>

        <div class="text-[9px] text-silver-500 font-mono truncate px-2" id="lankaQrPayloadPreview"></div>

        <div class="flex items-center justify-center gap-2 pt-2 border-t border-carbon-800">
            <button type="button" onclick="confirmLankaQrPaid()" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2">
                <i class="fa-solid fa-check"></i>
                <span>Confirm QR Paid &amp; Apply</span>
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Cart state in memory
    let cart = [];
    let discountType = 'fixed'; // 'fixed' or 'percentage'
    let currentPricingMode = 'retail'; // 'retail' or 'wholesale'
    let redeemedLoyaltyPoints = 0;
    const customerDisplayChannel = ('BroadcastChannel' in window) ? new BroadcastChannel('psi_pos_customer_display') : null;

    // Customer Facing Secondary Display Broadcast
    function broadcastToCustomerDisplay(calculatedTotals) {
        const totals = calculatedTotals || calculateTotals(false);
        const custSelect = document.getElementById('cartCustomerId');
        const custName = custSelect && custSelect.selectedIndex > 0 ? custSelect.options[custSelect.selectedIndex].text.split('(')[0].trim() : null;

        const payload = {
            type: 'cart_update',
            cart: cart.map(i => ({
                name: i.name,
                variationName: i.variationName,
                quantity: i.quantity,
                price: i.price,
                isExchange: !!i.isExchange
            })),
            totals: totals,
            pricingMode: currentPricingMode,
            customerName: custName,
            timestamp: Date.now()
        };

        if (customerDisplayChannel) {
            customerDisplayChannel.postMessage(payload);
        }
        try {
            localStorage.setItem('psi_pos_customer_display_payload', JSON.stringify(payload));
        } catch(e) {}
    }

    function openCustomerDisplay() {
        window.open("{{ route('pos.customer-display') }}", 'PSICustomerDisplay', 'width=1024,height=768,menubar=no,toolbar=no,location=no,status=no');
    }

    // Wholesale vs Retail Pricing Toggle
    function togglePricingMode(forcedMode = null) {
        if (forcedMode) {
            currentPricingMode = forcedMode;
        } else {
            currentPricingMode = (currentPricingMode === 'retail') ? 'wholesale' : 'retail';
        }

        const btn = document.getElementById('btnTogglePricingMode');
        const label = document.getElementById('pricingModeLabel');
        if (currentPricingMode === 'wholesale') {
            if (btn) {
                btn.className = "px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs border border-purple-500 shadow-md shadow-purple-600/30 flex items-center gap-1.5 transition";
            }
            if (label) label.innerText = 'Wholesale Mode';
        } else {
            if (btn) {
                btn.className = "px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white text-xs font-semibold border border-carbon-700 flex items-center gap-1.5 transition";
            }
            if (label) label.innerText = 'Retail Pricing';
        }

        // Switch unit price on active cart items
        cart.forEach(item => {
            if (!item.isExchange) {
                item.price = (currentPricingMode === 'wholesale' && item.wholesalePrice) ? item.wholesalePrice : item.retailPrice;
            }
        });

        renderCart();
    }

    // Add Item to Cart
    function addToCart(item) {
        const existing = cart.find(c => c.variationId === item.id && !c.isExchange);
        if (existing) {
            existing.quantity += (item.quantity || 1);
            if (item.initialSerial && !existing.selectedSerials.includes(item.initialSerial)) {
                existing.selectedSerials.push(item.initialSerial);
            }
        } else {
            const retailPrice = parseFloat(item.price);
            const wholesalePrice = parseFloat(item.wholesalePrice || item.price);
            const activePrice = (currentPricingMode === 'wholesale') ? wholesalePrice : retailPrice;

            cart.push({
                variationId: item.id,
                productId: item.productId,
                name: item.name,
                variationName: item.variationName || '',
                sku: item.sku,
                retailPrice: retailPrice,
                wholesalePrice: wholesalePrice,
                price: activePrice,
                quantity: item.quantity || 1,
                stock: item.stock,
                trackSerial: item.trackSerial || false,
                warrantyMonths: item.warrantyMonths || 0,
                availableSerials: item.serials || [],
                selectedSerials: item.initialSerial ? [item.initialSerial] : [],
                isExchange: false,
                exchangeReason: null
            });
        }
        renderCart();
    }

    // Render Cart HTML
    function renderCart() {
        const list = document.getElementById('cartItemsList');
        const emptyMsg = document.getElementById('emptyCartMessage');
        const badge = document.getElementById('cartBadgeCount');
        const payBtn = document.getElementById('btnPayCheckout');

        const totalItemsCount = cart.reduce((sum, i) => sum + (i.isExchange ? 0 : i.quantity), 0);
        badge.innerText = `${totalItemsCount} ${totalItemsCount === 1 ? 'item' : 'items'}`;

        if (cart.length === 0) {
            list.innerHTML = `
                <div id="emptyCartMessage" class="h-full flex flex-col items-center justify-center py-12 text-center text-silver-500">
                    <div class="w-14 h-14 rounded-2xl bg-carbon-850 border border-carbon-800 flex items-center justify-center mb-3 text-silver-600">
                        <i class="fa-solid fa-cart-arrow-down text-2xl"></i>
                    </div>
                    <p class="text-xs font-semibold text-silver-400">Cart is empty</p>
                    <p class="text-[10px] text-silver-600 mt-0.5">Scan barcode or tap products to begin order</p>
                </div>
            `;
            if (payBtn) payBtn.disabled = true;
            calculateTotals();
            return;
        }

        if (payBtn) payBtn.disabled = false;

        let html = '';
        cart.forEach((item, index) => {
            const isEx = !!item.isExchange;
            const itemTotal = item.quantity * item.price;
            html += `
                <div class="p-2.5 rounded-xl ${isEx ? 'bg-rose-950/20 border-rose-500/40' : 'bg-carbon-850 border-carbon-800'} border flex items-center justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-bold ${isEx ? 'text-rose-300' : 'text-white'} truncate">${item.name}</span>
                            ${isEx ? `<span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-rose-500/20 text-rose-400 border border-rose-500/30">Exchange Return</span>` : ''}
                        </div>
                        <div class="text-[10px] text-silver-400 flex items-center gap-1.5 mt-0.5">
                            ${item.variationName && item.variationName !== 'Standard' && !isEx ? `<span class="text-brand-400 font-semibold">${item.variationName}</span> • ` : ''}
                            ${isEx && item.exchangeReason ? `<span class="text-rose-400/80 italic">Reason: "${item.exchangeReason}"</span> • ` : ''}
                            <span class="font-mono ${isEx ? 'text-rose-300' : ''}">Rs. ${item.price.toFixed(2)}</span>
                        </div>
                        ${item.trackSerial && !isEx ? `
                            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                ${item.selectedSerials && item.selectedSerials.length > 0 ? `
                                    <span class="px-2 py-0.5 rounded-md bg-brand-600/20 text-brand-300 border border-brand-500/30 text-[9px] font-mono flex items-center gap-1">
                                        <i class="fa-solid fa-microchip text-[8px]"></i> IMEI: ${item.selectedSerials.join(', ')}
                                        <button type="button" onclick="openSerialAssignModal(${index})" class="text-silver-400 hover:text-white ml-1">
                                            <i class="fa-solid fa-pen text-[7px]"></i>
                                        </button>
                                    </span>
                                ` : `
                                    <button type="button" onclick="openSerialAssignModal(${index})" class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[9px] font-bold hover:bg-amber-500/30 transition flex items-center gap-1">
                                        <i class="fa-solid fa-triangle-exclamation text-[8px]"></i> Assign IMEI (${item.quantity} req)
                                    </button>
                                `}
                            </div>
                        ` : ''}
                    </div>

                    <!-- Quantity Stepper / Display -->
                    <div class="flex items-center gap-1.5 bg-carbon-900 border border-carbon-700 rounded-lg p-0.5">
                        ${!isEx ? `
                            <button type="button" onclick="decrementQty(${index})" class="w-6 h-6 rounded flex items-center justify-center text-silver-400 hover:text-white hover:bg-carbon-800 text-xs">
                                <i class="fa-solid fa-minus text-[10px]"></i>
                            </button>
                            <span class="w-6 text-center text-xs font-mono font-bold text-white">${item.quantity}</span>
                            <button type="button" onclick="incrementQty(${index})" class="w-6 h-6 rounded flex items-center justify-center text-silver-400 hover:text-white hover:bg-carbon-800 text-xs">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                            </button>
                        ` : `
                            <span class="px-2 text-center text-xs font-mono font-bold text-rose-400">${item.quantity}</span>
                        `}
                    </div>

                    <!-- Item Subtotal & Delete -->
                    <div class="text-right flex items-center gap-2">
                        <div class="text-xs font-mono font-bold ${isEx ? 'text-rose-400' : 'text-white'} min-w-[70px]">
                            ${itemTotal < 0 ? `-Rs. ${Math.abs(itemTotal).toFixed(2)}` : `Rs. ${itemTotal.toFixed(2)}`}
                        </div>
                        <button type="button" onclick="removeItem(${index})" class="w-6 h-6 rounded text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        list.innerHTML = html;
        calculateTotals();
    }

    function incrementQty(index) {
        cart[index].quantity += 1;
        renderCart();
    }

    function decrementQty(index) {
        if (cart[index].quantity > 1) {
            cart[index].quantity -= 1;
        } else {
            cart.splice(index, 1);
        }
        renderCart();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        if (cart.length > 0 && confirm('Are you sure you want to clear current bill?')) {
            cart = [];
            renderCart();
        }
    }

    function setDiscountType(type) {
        discountType = type;
        const btnFixed = document.getElementById('btnDiscountFixed');
        const btnPercent = document.getElementById('btnDiscountPercent');

        if (type === 'fixed') {
            btnFixed.className = "px-1.5 py-0.5 rounded font-bold bg-brand-600 text-white";
            btnPercent.className = "px-1.5 py-0.5 rounded font-medium text-silver-400 hover:text-white";
        } else {
            btnPercent.className = "px-1.5 py-0.5 rounded font-bold bg-brand-600 text-white";
            btnFixed.className = "px-1.5 py-0.5 rounded font-medium text-silver-400 hover:text-white";
        }
        calculateTotals();
    }

    function calculateTotals(shouldBroadcast = true) {
        const regularSubtotal = cart.filter(i => !i.isExchange).reduce((sum, i) => sum + (i.quantity * i.price), 0);
        const exchangeCredit = cart.filter(i => i.isExchange).reduce((sum, i) => sum + (Math.abs(i.quantity) * i.price), 0);

        const discountInput = parseFloat(document.getElementById('cartDiscountInput')?.value) || 0;
        const taxInput = parseFloat(document.getElementById('cartTaxInput')?.value) || 0;

        let discountAmount = discountInput;
        if (discountType === 'percentage') {
            discountAmount = (regularSubtotal * discountInput) / 100;
        }

        let loyaltyDiscount = 0;
        if (redeemedLoyaltyPoints > 0) {
            loyaltyDiscount = Math.min(redeemedLoyaltyPoints, Math.max(0, regularSubtotal - discountAmount));
        }

        const netTaxable = Math.max(0, regularSubtotal - discountAmount - loyaltyDiscount - exchangeCredit);
        const taxAmount = (netTaxable * taxInput) / 100;
        const grandTotal = netTaxable + taxAmount;

        const subtotalEl = document.getElementById('cartSubtotal');
        if (subtotalEl) subtotalEl.innerText = 'Rs. ' + regularSubtotal.toFixed(2);

        const grandTotalEl = document.getElementById('cartGrandTotal');
        if (grandTotalEl) grandTotalEl.innerText = grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Loyalty Row in Cart Summary
        const loyaltyRow = document.getElementById('cartLoyaltyRow');
        const loyaltyPtsCount = document.getElementById('cartLoyaltyPtsCount');
        const loyaltyAmountSpan = document.getElementById('cartLoyaltyAmount');
        if (loyaltyRow && loyaltyPtsCount && loyaltyAmountSpan) {
            if (loyaltyDiscount > 0) {
                loyaltyPtsCount.innerText = loyaltyDiscount;
                loyaltyAmountSpan.innerText = '-Rs. ' + loyaltyDiscount.toFixed(2);
                loyaltyRow.classList.remove('hidden');
            } else {
                loyaltyRow.classList.add('hidden');
            }
        }

        // Exchange Row in Cart Summary
        const exchangeRow = document.getElementById('cartExchangeRow');
        const exchangeAmountSpan = document.getElementById('cartExchangeAmount');
        if (exchangeRow && exchangeAmountSpan) {
            if (exchangeCredit > 0) {
                exchangeAmountSpan.innerText = '-Rs. ' + exchangeCredit.toFixed(2);
                exchangeRow.classList.remove('hidden');
            } else {
                exchangeRow.classList.add('hidden');
            }
        }

        const result = {
            subtotal: regularSubtotal,
            exchangeCredit: exchangeCredit,
            discount: discountAmount,
            loyaltyDiscount: loyaltyDiscount,
            taxAmount: taxAmount,
            grandTotal: grandTotal
        };

        if (shouldBroadcast) {
            broadcastToCustomerDisplay(result);
        }

        return result;
    }

    // Customer Loyalty Points display & Wholesale Tier switch
    function onCustomerSelect() {
        const select = document.getElementById('cartCustomerId');
        const selected = select.options[select.selectedIndex];
        const points = parseInt(selected?.getAttribute('data-points')) || 0;
        const tier = selected?.getAttribute('data-pricing-tier') || 'retail';
        const info = document.getElementById('customerPointsInfo');
        const ptsSpan = document.getElementById('customerPointsBalance');
        const cashValSpan = document.getElementById('customerPointsCashVal');
        const btn = document.getElementById('btnRedeemPoints');

        redeemedLoyaltyPoints = 0;
        if (btn) {
            btn.classList.remove('bg-emerald-500', 'text-black');
            btn.classList.add('bg-emerald-500/20', 'text-emerald-300');
            btn.innerText = 'Redeem Points';
        }

        if (select.value && points > 0) {
            ptsSpan.innerText = points;
            if (cashValSpan) cashValSpan.innerText = 'Rs. ' + points.toFixed(2);
            info.classList.remove('hidden');
        } else {
            info.classList.add('hidden');
        }

        // Auto switch pricing mode if customer has wholesale tier
        if (tier === 'wholesale') {
            togglePricingMode('wholesale');
        } else if (currentPricingMode === 'wholesale' && !select.value) {
            togglePricingMode('retail');
        }

        calculateTotals();
    }

    // Toggle Loyalty Points Redemption (1 point = Rs. 1.00)
    function toggleRedeemPoints() {
        const select = document.getElementById('cartCustomerId');
        const selected = select.options[select.selectedIndex];
        const points = parseInt(selected?.getAttribute('data-points')) || 0;
        const btn = document.getElementById('btnRedeemPoints');

        if (points <= 0) {
            alert('Customer has no loyalty points available to redeem.');
            return;
        }

        if (redeemedLoyaltyPoints > 0) {
            // Cancel redemption
            redeemedLoyaltyPoints = 0;
            if (btn) {
                btn.classList.remove('bg-emerald-500', 'text-black');
                btn.classList.add('bg-emerald-500/20', 'text-emerald-300');
                btn.innerText = 'Redeem Points';
            }
        } else {
            // Redeem up to bill amount
            const currentTotals = calculateTotals(false);
            const maxCanRedeem = Math.min(points, Math.floor(currentTotals.subtotal - currentTotals.discount));
            if (maxCanRedeem <= 0) {
                alert('Bill amount is too low for points redemption.');
                return;
            }
            redeemedLoyaltyPoints = maxCanRedeem;
            if (btn) {
                btn.classList.add('bg-emerald-500', 'text-black');
                btn.classList.remove('bg-emerald-500/20', 'text-emerald-300');
                btn.innerText = `Cancel (${redeemedLoyaltyPoints} pts)`;
            }
        }
        calculateTotals();
    }

    // Item Exchange / Swap Handlers
    function openExchangeModal() {
        const modal = document.getElementById('exchangeModal');
        if (modal) {
            document.getElementById('exchangeProductSelect').value = '';
            document.getElementById('exchangeQtyInput').value = '1';
            document.getElementById('exchangePriceInput').value = '';
            document.getElementById('exchangeReasonInput').value = '';
            document.getElementById('exchangePreviewDeduction').innerText = '-Rs. 0.00';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeExchangeModal() {
        const modal = document.getElementById('exchangeModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function onExchangeProductSelect() {
        const select = document.getElementById('exchangeProductSelect');
        const opt = select.options[select.selectedIndex];
        if (opt && opt.value) {
            const price = parseFloat(opt.getAttribute('data-price')) || 0;
            document.getElementById('exchangePriceInput').value = price.toFixed(2);
            updateExchangePreview();
        }
    }

    function updateExchangePreview() {
        const qty = parseInt(document.getElementById('exchangeQtyInput').value) || 0;
        const price = parseFloat(document.getElementById('exchangePriceInput').value) || 0;
        const deduction = qty * price;
        document.getElementById('exchangePreviewDeduction').innerText = '-Rs. ' + deduction.toFixed(2);
    }

    function submitExchangeItem(e) {
        e.preventDefault();
        const select = document.getElementById('exchangeProductSelect');
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) {
            alert('Please select a product to exchange/return.');
            return;
        }

        const varId = parseInt(opt.value);
        const name = opt.getAttribute('data-name') || 'Exchange Item';
        const sku = opt.getAttribute('data-sku') || '';
        const qty = parseInt(document.getElementById('exchangeQtyInput').value) || 1;
        const price = parseFloat(document.getElementById('exchangePriceInput').value) || 0;
        const reason = document.getElementById('exchangeReasonInput').value.trim() || 'Item Exchange / Return Credit';

        cart.push({
            variationId: varId,
            productId: null,
            name: name,
            variationName: 'Return Credit',
            sku: sku,
            retailPrice: price,
            wholesalePrice: price,
            price: price,
            quantity: -Math.abs(qty),
            stock: 0,
            trackSerial: false,
            warrantyMonths: 0,
            availableSerials: [],
            selectedSerials: [],
            isExchange: true,
            exchangeReason: reason
        });

        closeExchangeModal();
        renderCart();
    }

    // Dynamic LankaQR Modal
    function showLankaQrModal() {
        const totals = calculateTotals(false);
        const due = parseFloat(document.getElementById('modalDueAmount')?.getAttribute('data-due')) || totals.grandTotal;
        const ref = 'POS-' + Date.now();

        document.getElementById('lankaQrAmountText').innerText = 'Rs. ' + due.toFixed(2);
        const img = document.getElementById('lankaQrImage');
        const payloadPreview = document.getElementById('lankaQrPayloadPreview');
        img.src = '';
        payloadPreview.innerText = 'Generating CBSL EMVCo LankaQR...';

        const modal = document.getElementById('lankaQrModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        fetch(`{{ route('pos.lankaqr') }}?amount=${due}&reference=${encodeURIComponent(ref)}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.qr_string) {
                    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(data.qr_string)}`;
                    img.src = qrUrl;
                    payloadPreview.innerText = data.qr_string;
                }
            })
            .catch(e => {
                payloadPreview.innerText = 'Failed to generate LankaQR payload';
            });
    }

    function closeLankaQrModal() {
        const modal = document.getElementById('lankaQrModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function confirmLankaQrPaid() {
        const totals = calculateTotals(false);
        const due = parseFloat(document.getElementById('modalDueAmount')?.getAttribute('data-due')) || totals.grandTotal;
        const qrInput = document.getElementById('payQrAmount');
        if (qrInput) {
            qrInput.value = due.toFixed(2);
            document.getElementById('payCashAmount').value = '';
        }
        closeLankaQrModal();
        updatePaymentTotals();
    }

    // Filter Products by search and category
    function filterProducts() {
        const query = document.getElementById('posSearchInput').value.toLowerCase().trim();
        const activeCat = document.querySelector('.category-pill.active')?.innerText.trim() || 'All Items';
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            const category = card.getAttribute('data-category');
            const sku = card.getAttribute('data-sku');

            const matchesSearch = !query || name.includes(query) || sku.includes(query);
            const matchesCat = activeCat === 'All Items' || category === activeCat;

            if (matchesSearch && matchesCat) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function selectCategory(category, btn) {
        document.querySelectorAll('.category-pill').forEach(b => {
            b.className = "category-pill px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white border border-carbon-700 transition-all";
        });
        btn.className = "category-pill active px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all bg-brand-600 text-white shadow-md shadow-brand-600/30";
        filterProducts();
    }

    function clearSearch() {
        document.getElementById('posSearchInput').value = '';
        filterProducts();
    }

    // Variable Product Modal
    function openVariationModal(product, branchId) {
        const modal = document.getElementById('variationModal');
        document.getElementById('varModalTitle').innerText = product.name;
        document.getElementById('varModalSubtitle').innerText = `${product.brand?.name || 'PSI'} • Code: ${product.code}`;
        
        const list = document.getElementById('varModalList');
        let html = '';

        product.variations.forEach(variation => {
            const stock = variation.stocks ? variation.stocks.filter(s => s.branch_id === branchId).reduce((a, b) => a + b.quantity, 0) : 0;
            const attrLabels = variation.attribute_values ? variation.attribute_values.map(av => av.value).join(' / ') : variation.name;
            const activePrice = (currentPricingMode === 'wholesale' && variation.wholesale_price) ? variation.wholesale_price : variation.selling_price;

            html += `
                <div class="p-3 rounded-xl bg-carbon-850 hover:bg-carbon-800 border border-carbon-750 flex items-center justify-between gap-3 cursor-pointer transition-all"
                     onclick="addVariationFromModal({
                         id: ${variation.id},
                         productId: ${product.id},
                         name: '${escapeQuotes(product.name)}',
                         variationName: '${escapeQuotes(attrLabels || variation.name)}',
                         sku: '${variation.sku}',
                         price: ${variation.selling_price},
                         wholesalePrice: ${variation.wholesale_price || variation.selling_price},
                         stock: ${stock},
                         trackSerial: ${variation.track_serial ? 'true' : 'false'},
                         warrantyMonths: ${variation.warranty_months || 0},
                         serials: ${JSON.stringify(variation.serials ? variation.serials.map(s => s.serial_number) : [])}
                     })">
                    <div>
                        <div class="text-xs font-bold text-white">${attrLabels || variation.name}</div>
                        <div class="text-[10px] text-silver-400 font-mono mt-0.5">SKU: ${variation.sku}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold ${stock > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'}">
                            ${stock > 0 ? stock + ' in stock' : 'Out of stock'}
                        </span>
                        <span class="text-xs font-mono font-extrabold text-brand-400">Rs. ${parseFloat(activePrice).toFixed(2)}</span>
                        <div class="w-7 h-7 rounded-lg bg-brand-600 text-white flex items-center justify-center text-xs">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                    </div>
                </div>
            `;
        });

        list.innerHTML = html;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function escapeQuotes(str) {
        return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function closeVariationModal() {
        const modal = document.getElementById('variationModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function addVariationFromModal(item) {
        addToCart(item);
        closeVariationModal();
    }

    // Payment & Tender Modal Logic
    let activeHeldSaleId = null;
    let isManagerPinVerified = false;

    function openPaymentModal() {
        if (cart.length === 0) return;

        // Check if any item requires Serial / IMEI numbers before payment
        for (let i = 0; i < cart.length; i++) {
            const item = cart[i];
            if (item.trackSerial && !item.isExchange && (!item.selectedSerials || item.selectedSerials.length < item.quantity)) {
                openSerialAssignModal(i);
                return;
            }
        }

        const totals = calculateTotals(false);

        // Check if discount requires Manager PIN (> 10%)
        const discountAmount = totals.discount;
        const discountPercent = totals.subtotal > 0 ? (discountAmount / totals.subtotal) * 100 : 0;
        if (discountPercent > 10.01 && !isManagerPinVerified) {
            openManagerPinModal();
            return;
        }

        document.getElementById('modalDueAmount').innerText = 'Rs. ' + totals.grandTotal.toFixed(2);
        document.getElementById('modalDueAmount').setAttribute('data-due', totals.grandTotal);
        
        // Default to exact cash
        document.getElementById('payCashAmount').value = totals.grandTotal.toFixed(2);
        document.getElementById('payCardAmount').value = '';
        document.getElementById('payBankAmount').value = '';
        if (document.getElementById('payQrAmount')) document.getElementById('payQrAmount').value = '';

        // Configure Customer Credit & Loyalty
        const custSelect = document.getElementById('cartCustomerId');
        const selectedOpt = custSelect ? custSelect.options[custSelect.selectedIndex] : null;
        const creditInput = document.getElementById('payCreditAmount');
        const creditNotice = document.getElementById('creditAvailableNotice');
        const loyaltyInput = document.getElementById('payLoyaltyAmount');
        const loyaltyNotice = document.getElementById('loyaltyAvailableNotice');

        if (selectedOpt && selectedOpt.value) {
            const limit = parseFloat(selectedOpt.getAttribute('data-credit-limit')) || 0;
            const balance = parseFloat(selectedOpt.getAttribute('data-current-balance')) || 0;
            const availableCredit = Math.max(0, limit - balance);
            const points = parseInt(selectedOpt.getAttribute('data-points')) || 0;

            if (creditNotice) creditNotice.innerText = `Available: Rs. ${availableCredit.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            if (creditInput) {
                creditInput.value = '';
                creditInput.disabled = (availableCredit <= 0);
            }

            if (loyaltyNotice) loyaltyNotice.innerText = `${points} pts available (Rs. ${points.toFixed(2)})`;
            if (loyaltyInput) {
                loyaltyInput.value = '';
                loyaltyInput.disabled = (points <= 0);
            }
        } else {
            if (creditNotice) creditNotice.innerText = 'Select customer to enable';
            if (creditInput) { creditInput.value = ''; creditInput.disabled = true; }
            if (loyaltyNotice) loyaltyNotice.innerText = 'Walk-in: N/A';
            if (loyaltyInput) { loyaltyInput.value = ''; loyaltyInput.disabled = true; }
        }

        updatePaymentTotals();

        const modal = document.getElementById('paymentModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePaymentModal() {
        const modal = document.getElementById('paymentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function updatePaymentTotals() {
        const due = parseFloat(document.getElementById('modalDueAmount').getAttribute('data-due')) || 0;
        const cash = parseFloat(document.getElementById('payCashAmount').value) || 0;
        const card = parseFloat(document.getElementById('payCardAmount').value) || 0;
        const bank = parseFloat(document.getElementById('payBankAmount').value) || 0;
        const credit = parseFloat(document.getElementById('payCreditAmount')?.value) || 0;
        const loyalty = parseFloat(document.getElementById('payLoyaltyAmount')?.value) || 0;
        const qr = parseFloat(document.getElementById('payQrAmount')?.value) || 0;

        const nonCash = card + bank + credit + loyalty + qr;
        const totalTendered = cash + nonCash;
        const change = Math.max(0, cash - Math.max(0, due - nonCash));

        document.getElementById('modalTenderedAmount').innerText = 'Rs. ' + totalTendered.toFixed(2);
        document.getElementById('modalChangeAmount').innerText = 'Rs. ' + change.toFixed(2);

        const btnFinalize = document.getElementById('btnFinalizeSale');
        if (totalTendered < due - 0.01) {
            btnFinalize.disabled = true;
            btnFinalize.classList.add('opacity-40');
            btnFinalize.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> <span>Tender Short by Rs. ${(due - totalTendered).toFixed(2)}</span>`;
        } else {
            btnFinalize.disabled = false;
            btnFinalize.classList.remove('opacity-40');
            btnFinalize.innerHTML = `<i class="fa-solid fa-receipt"></i> <span>Complete Sale & Print Receipt</span>`;
        }
    }

    function setCashExact() {
        const due = parseFloat(document.getElementById('modalDueAmount').getAttribute('data-due')) || 0;
        document.getElementById('payCashAmount').value = due.toFixed(2);
        document.getElementById('payCardAmount').value = '';
        document.getElementById('payBankAmount').value = '';
        if (document.getElementById('payCreditAmount')) document.getElementById('payCreditAmount').value = '';
        if (document.getElementById('payLoyaltyAmount')) document.getElementById('payLoyaltyAmount').value = '';
        if (document.getElementById('payQrAmount')) document.getElementById('payQrAmount').value = '';
        updatePaymentTotals();
    }

    function addCashTender(amount) {
        const current = parseFloat(document.getElementById('payCashAmount').value) || 0;
        document.getElementById('payCashAmount').value = (current + amount).toFixed(2);
        updatePaymentTotals();
    }

    // Submit Checkout to Server
    function submitCheckout() {
        if (cart.length === 0) return;

        const totals = calculateTotals(false);
        const customerId = document.getElementById('cartCustomerId').value || null;
        const discountInput = parseFloat(document.getElementById('cartDiscountInput').value) || 0;
        const taxInput = parseFloat(document.getElementById('cartTaxInput').value) || 0;

        const cash = parseFloat(document.getElementById('payCashAmount').value) || 0;
        const card = parseFloat(document.getElementById('payCardAmount').value) || 0;
        const bank = parseFloat(document.getElementById('payBankAmount').value) || 0;
        const credit = parseFloat(document.getElementById('payCreditAmount')?.value) || 0;
        const loyalty = parseFloat(document.getElementById('payLoyaltyAmount')?.value) || 0;
        const qr = parseFloat(document.getElementById('payQrAmount')?.value) || 0;

        const payments = [];
        if (cash > 0) payments.push({ method: 'cash', amount: Math.min(cash, totals.grandTotal), tender_amount: cash });
        if (card > 0) payments.push({ method: 'card', amount: card, tender_amount: card });
        if (bank > 0) payments.push({ method: 'bank_transfer', amount: bank, tender_amount: bank });
        if (credit > 0) payments.push({ method: 'credit', amount: credit, tender_amount: credit });
        if (loyalty > 0) payments.push({ method: 'loyalty_points', amount: loyalty, tender_amount: loyalty });
        if (qr > 0) payments.push({ method: 'qr', amount: qr, tender_amount: qr });

        const items = cart.map(item => ({
            variation_id: item.variationId,
            quantity: item.isExchange ? -Math.abs(item.quantity) : item.quantity,
            unit_price: item.price,
            is_exchange: !!item.isExchange,
            exchange_reason: item.exchangeReason || null,
            serials: item.selectedSerials || []
        }));

        const payload = {
            customer_id: customerId,
            pricing_mode: currentPricingMode,
            redeem_points: (redeemedLoyaltyPoints > 0) ? redeemedLoyaltyPoints : (loyalty > 0 ? loyalty : 0),
            discount_type: discountType,
            discount_amount: discountInput,
            tax_rate: taxInput,
            held_sale_id: activeHeldSaleId,
            items: items,
            payments: payments
        };

        const btnFinalize = document.getElementById('btnFinalizeSale');
        btnFinalize.disabled = true;
        btnFinalize.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> <span>Processing Bill...</span>`;

        fetch("{{ route('pos.checkout') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            if (cash > 0) {
                triggerCashDrawer();
            }
            if (customerDisplayChannel) {
                customerDisplayChannel.postMessage({ type: 'sale_completed', invoiceNo: data.invoice_no || '' });
            }
            if (data.success && data.receipt_url) {
                window.location.href = data.receipt_url;
            } else {
                alert('Sale completed successfully.');
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Checkout error:', error);
            alert('Checkout failed: ' + (error.message || JSON.stringify(error.errors || error)));
            btnFinalize.disabled = false;
            btnFinalize.innerHTML = `<i class="fa-solid fa-receipt"></i> <span>Complete Sale & Print Receipt</span>`;
        });
    }

    // ================= HOLD CART & HELD SALES LOGIC =================
    function openHoldCartModal() {
        if (cart.length === 0) {
            alert('Cart is empty. Add products before holding.');
            return;
        }
        const totals = calculateTotals();
        document.getElementById('holdCartTotalPreview').innerText = 'Rs. ' + totals.grandTotal.toFixed(2);
        const modal = document.getElementById('holdCartModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeHoldCartModal() {
        const modal = document.getElementById('holdCartModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function submitHoldCart(e) {
        e.preventDefault();
        const totals = calculateTotals();
        const customerId = document.getElementById('cartCustomerId').value || null;
        const custSelect = document.getElementById('cartCustomerId');
        const custName = custSelect && custSelect.selectedIndex > 0 ? custSelect.options[custSelect.selectedIndex].text.split('(')[0].trim() : null;
        const ref = document.getElementById('holdReferenceInput').value || (custName ? 'Cart: ' + custName : null);
        const note = document.getElementById('holdNoteInput').value;

        const payload = {
            reference: ref,
            customer_id: customerId,
            customer_name: custName,
            discount_type: discountType,
            discount_amount: parseFloat(document.getElementById('cartDiscountInput').value) || 0,
            tax_rate: parseFloat(document.getElementById('cartTaxInput').value) || 0,
            subtotal: totals.subtotal,
            grand_total: totals.grandTotal,
            note: note,
            items: cart.map(i => ({ variation_id: i.variationId, quantity: i.quantity, unit_price: i.price }))
        };

        const btn = document.getElementById('btnConfirmHold');
        btn.disabled = true;

        fetch("{{ route('pos.hold') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                closeHoldCartModal();
                clearCart();
                updateHeldBadge(data.held_count);
                alert(data.message);
            }
        })
        .catch(err => {
            btn.disabled = false;
            alert('Failed to hold cart: ' + err.message);
        });
    }

    function openHeldSalesModal() {
        const modal = document.getElementById('heldSalesModal');
        const container = document.getElementById('heldSalesListContainer');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        container.innerHTML = `
            <div class="py-12 text-center text-silver-500">
                <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 text-brand-500"></i>
                <p>Loading held orders...</p>
            </div>
        `;

        fetch("{{ route('pos.held-sales') }}")
            .then(res => res.json())
            .then(data => {
                if (data.success && data.held_sales && data.held_sales.length > 0) {
                    let html = '<div class="space-y-2">';
                    data.held_sales.forEach(h => {
                        const itemsSummary = h.items.map(i => `${i.product_variation?.product?.name || 'Item'} (${i.quantity}x)`).join(', ');
                        html += `
                            <div class="p-3.5 rounded-2xl bg-carbon-850 border border-carbon-750 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-white text-xs">${h.reference}</span>
                                        ${h.customer ? `<span class="px-2 py-0.5 rounded text-[9px] bg-brand-500/20 text-brand-400 font-bold">${h.customer.name}</span>` : ''}
                                    </div>
                                    <div class="text-[10px] text-silver-400 mt-0.5 max-w-md truncate">${itemsSummary}</div>
                                    ${h.note ? `<div class="text-[10px] text-amber-400/80 italic mt-0.5">Note: "${h.note}"</div>` : ''}
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-black text-white text-sm">Rs. ${parseFloat(h.grand_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="resumeHeldCart(${h.id})" class="px-3 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs flex items-center gap-1 shadow">
                                            <i class="fa-solid fa-play text-[10px]"></i> Resume
                                        </button>
                                        <button onclick="discardHeldCart(${h.id})" class="p-1.5 rounded-xl bg-carbon-800 text-silver-400 hover:text-rose-400 border border-carbon-700 text-xs">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                    updateHeldBadge(data.count);
                } else {
                    container.innerHTML = `
                        <div class="py-12 text-center text-silver-500 font-sans">
                            <i class="fa-solid fa-basket-shopping text-3xl mb-2 text-carbon-700"></i>
                            <p class="text-xs font-semibold text-silver-400">No held orders found</p>
                        </div>
                    `;
                    updateHeldBadge(0);
                }
            })
            .catch(err => {
                container.innerHTML = `<div class="p-4 text-center text-rose-400">Error loading held sales.</div>`;
            });
    }

    function closeHeldSalesModal() {
        const modal = document.getElementById('heldSalesModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function resumeHeldCart(id) {
        fetch(`/pos/held-sales/${id}/resume`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.held_sale) {
                const h = data.held_sale;
                activeHeldSaleId = h.id;
                cart = h.items.map(i => ({
                    variationId: i.product_variation_id,
                    productId: i.product_variation?.product_id,
                    name: i.product_variation?.product?.name || 'Product',
                    variationName: i.product_variation?.name || '',
                    sku: i.product_variation?.sku || '',
                    price: parseFloat(i.unit_price),
                    quantity: parseInt(i.quantity),
                    stock: 99
                }));

                if (h.customer_id) {
                    const custSelect = document.getElementById('cartCustomerId');
                    if (custSelect) custSelect.value = h.customer_id;
                    onCustomerSelect();
                }

                if (document.getElementById('cartDiscountInput')) {
                    document.getElementById('cartDiscountInput').value = parseFloat(h.discount_amount) || 0;
                }
                discountType = h.discount_type || 'fixed';

                renderCart();
                closeHeldSalesModal();
                loadHeldSalesCount();
            }
        });
    }

    function discardHeldCart(id) {
        if (!confirm('Discard this held cart?')) return;
        fetch(`/pos/held-sales/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => res.json())
        .then(data => {
            openHeldSalesModal();
            loadHeldSalesCount();
        });
    }

    function updateHeldBadge(count) {
        const badge = document.getElementById('heldBadgeCount');
        if (badge) badge.innerText = count || 0;
    }

    function loadHeldSalesCount() {
        fetch("{{ route('pos.held-sales') }}")
            .then(res => res.json())
            .then(data => {
                if (data.success) updateHeldBadge(data.count);
            })
            .catch(() => {});
    }

    // ================= CROSS-BRANCH STOCK LOOKUP =================
    function checkOtherBranches(variationId) {
        const modal = document.getElementById('branchStockModal');
        const content = document.getElementById('branchStockContent');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        content.innerHTML = `
            <div class="py-8 text-center text-silver-500">
                <i class="fa-solid fa-spinner fa-spin text-xl mb-1 text-purple-400"></i>
                <p>Checking branch inventories...</p>
            </div>
        `;

        fetch(`/pos/variations/${variationId}/branch-stocks`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('branchStockTitle').innerText = data.product_name;
                    document.getElementById('branchStockSubtitle').innerText = 'SKU: ' + data.sku;

                    let html = '<div class="space-y-2">';
                    data.stocks.forEach(s => {
                        const isAvailable = s.quantity > 0;
                        html += `
                            <div class="p-3 rounded-xl bg-carbon-850 border border-carbon-750 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-white text-xs">${s.branch_name}</div>
                                    <div class="text-[10px] text-silver-500 font-mono">Code: ${s.branch_code} • Batch: ${s.batch_number}</div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-black ${isAvailable ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'}">
                                    ${s.quantity} pcs
                                </span>
                            </div>
                        `;
                    });
                    html += '</div>';
                    content.innerHTML = html;
                }
            })
            .catch(err => {
                content.innerHTML = `<div class="p-4 text-center text-rose-400">Failed to check other branches.</div>`;
            });
    }

    function closeBranchStockModal() {
        const modal = document.getElementById('branchStockModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // ================= MANAGER PIN VERIFICATION =================
    function openManagerPinModal() {
        const modal = document.getElementById('managerPinModal');
        const input = document.getElementById('managerPinInput');
        document.getElementById('managerPinError').classList.add('hidden');
        input.value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => input.focus(), 100);
    }

    function closeManagerPinModal(authorized = false) {
        const modal = document.getElementById('managerPinModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (!authorized) {
            // reset discount back to 10%
            document.getElementById('cartDiscountInput').value = '10';
            setDiscountType('percentage');
        }
    }

    function verifyManagerPinSubmission() {
        const pin = document.getElementById('managerPinInput').value;
        const errorDiv = document.getElementById('managerPinError');

        fetch("{{ route('pos.verify-pin') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ pin: pin })
        })
        .then(res => {
            if (!res.ok) throw new Error('Invalid Manager PIN');
            return res.json();
        })
        .then(data => {
            if (data.success) {
                isManagerPinVerified = true;
                closeManagerPinModal(true);
                openPaymentModal();
            }
        })
        .catch(err => {
            errorDiv.classList.remove('hidden');
            errorDiv.innerText = err.message || 'Invalid PIN. Access denied.';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadHeldSalesCount();
    });

    // Shift modal handlers
    function openStartShiftModal() {
        const modal = document.getElementById('startShiftModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function openCloseShiftModal() {
        const modal = document.getElementById('closeShiftModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeShiftCloseModal() {
        const modal = document.getElementById('closeShiftModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Terminal Customer Quick Registration Modal
    function openTerminalCustomerModal() {
        const modal = document.getElementById('terminalCustomerModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const errDiv = document.getElementById('terminalCustomerError');
            if (errDiv) errDiv.classList.add('hidden');
            setTimeout(() => {
                const nameInput = document.getElementById('quickCustName');
                if (nameInput) nameInput.focus();
            }, 100);
        }
    }

    function closeTerminalCustomerModal() {
        const modal = document.getElementById('terminalCustomerModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function submitTerminalCustomer(e) {
        e.preventDefault();
        const errDiv = document.getElementById('terminalCustomerError');
        const submitBtn = document.getElementById('quickCustSubmitBtn');
        errDiv.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> <span>Saving Client...</span>`;

        const name = document.getElementById('quickCustName').value.trim();
        const phone = document.getElementById('quickCustPhone').value.trim();
        const email = document.getElementById('quickCustEmail').value.trim();
        const customer_group_id = document.getElementById('quickCustGroup').value || null;
        const address = document.getElementById('quickCustAddress').value.trim();
        const credit_limit = parseFloat(document.getElementById('quickCustCreditLimit').value) || 0.00;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const res = await fetch("{{ route('customers.store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    name: name,
                    phone: phone,
                    email: email || null,
                    customer_group_id: customer_group_id,
                    address: address || null,
                    credit_limit: credit_limit
                })
            });

            const data = await res.json();

            if (!res.ok) {
                let msg = 'Failed to register customer.';
                if (data.errors) {
                    msg = Object.values(data.errors).flat().join('<br>');
                } else if (data.message) {
                    msg = data.message;
                }
                errDiv.innerHTML = msg;
                errDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-check"></i><span>Save & Attach to Bill</span>';
                return;
            }

            const select = document.getElementById('cartCustomerId');
            if (select && data.customer) {
                const opt = document.createElement('option');
                opt.value = data.customer.id;
                opt.setAttribute('data-points', data.customer.loyalty_points || 0);
                opt.setAttribute('data-phone', data.customer.phone);
                opt.textContent = `${data.customer.name} (${data.customer.phone}) - ${data.customer.loyalty_points || 0} pts`;
                select.appendChild(opt);
                select.value = data.customer.id;
                onCustomerSelect();
            }

            closeTerminalCustomerModal();
            document.getElementById('terminalCustomerForm').reset();
        } catch (err) {
            console.error(err);
            errDiv.innerHTML = 'An unexpected network error occurred. Please check your connection.';
            errDiv.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-check"></i><span>Save & Attach to Bill</span>';
        }
    }

    // Keyboard Shortcuts (F4 to pay, F8 to exchange, F9 to kick drawer, Esc to close modals)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F4') {
            e.preventDefault();
            const payBtn = document.getElementById('btnPayCheckout');
            if (payBtn && !payBtn.disabled) {
                openPaymentModal();
            }
        } else if (e.key === 'F8') {
            e.preventDefault();
            openExchangeModal();
        } else if (e.key === 'F9') {
            e.preventDefault();
            triggerCashDrawer();
        } else if (e.key === 'Escape') {
            closePaymentModal();
            closeVariationModal();
            closeSerialAssignModal();
            closeExchangeModal();
            closeLankaQrModal();
            closeTerminalCustomerModal();
            if (document.getElementById('closeShiftModal')) closeShiftCloseModal();
        }
    });

    // Global cached product variations for instant barcode / scale / serial lookup
    window.posProductsList = @json($products);
    window.posBranchId = {{ $branch->id }};

    // Hardware Cash Drawer Kick Pulse
    function triggerCashDrawer() {
        fetch("{{ route('pos.open-drawer') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => res.json())
        .then(data => {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-6 right-6 z-50 bg-emerald-600 text-white text-xs font-bold px-4 py-2.5 rounded-2xl shadow-2xl flex items-center gap-2 animate-bounce';
            toast.innerHTML = '<i class="fa-solid fa-cash-register"></i><span>Cash Drawer Kick Signal Sent (ESC/POS: \\x1b\\x70\\x00\\x19\\xfa)</span>';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        })
        .catch(err => console.error('Cash drawer trigger failed:', err));
    }

    // ================= SERIAL / IMEI NUMBER ASSIGNMENT LOGIC =================
    let activeSerialCartIndex = null;
    let tempSelectedSerials = [];

    function openSerialAssignModal(cartIndex) {
        activeSerialCartIndex = cartIndex;
        const item = cart[cartIndex];
        tempSelectedSerials = [...(item.selectedSerials || [])];

        document.getElementById('serialModalItemTitle').innerText = item.name + (item.variationName && item.variationName !== 'Standard' ? ` (${item.variationName})` : '');
        document.getElementById('serialModalSubtitle').innerText = `SKU: ${item.sku} • Quantity to Allocate: ${item.quantity} Unit(s)`;
        document.getElementById('serialRequiredCount').innerText = item.quantity;
        document.getElementById('serialScanInput').value = '';

        renderSerialModalAvailableList(item);
        renderSerialModalAssignedChips(item);

        const modal = document.getElementById('serialAssignModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => document.getElementById('serialScanInput').focus(), 100);
    }

    function closeSerialAssignModal() {
        const modal = document.getElementById('serialAssignModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        activeSerialCartIndex = null;
    }

    function renderSerialModalAvailableList(item) {
        const list = document.getElementById('serialModalAvailableList');
        const avail = item.availableSerials || [];

        if (avail.length === 0) {
            list.innerHTML = `<div class="p-3 text-silver-500 italic text-center text-xs">No available in-stock serial numbers registered for this variation at this branch. You may scan/type an IMEI above.</div>`;
            return;
        }

        let html = '';
        avail.forEach(sn => {
            const isAssigned = tempSelectedSerials.includes(sn);
            html += `
                <div onclick="toggleSerialSelection('${sn}')" class="p-2 rounded-lg border ${isAssigned ? 'bg-brand-600/20 border-brand-500 text-white' : 'bg-carbon-900 border-carbon-800 text-silver-300 hover:bg-carbon-850'} flex items-center justify-between cursor-pointer transition">
                    <span class="font-mono font-bold">${sn}</span>
                    <span class="text-[10px] font-bold ${isAssigned ? 'text-brand-400' : 'text-silver-500'}">
                        ${isAssigned ? '<i class="fa-solid fa-check"></i> Selected' : '+ Select'}
                    </span>
                </div>
            `;
        });
        list.innerHTML = html;
    }

    function renderSerialModalAssignedChips(item) {
        const container = document.getElementById('serialModalAssignedChips');
        document.getElementById('serialAssignedCount').innerText = tempSelectedSerials.length;

        if (tempSelectedSerials.length === 0) {
            container.innerHTML = `<span class="text-silver-500 italic text-[11px] self-center">None assigned yet</span>`;
            return;
        }

        let html = '';
        tempSelectedSerials.forEach((sn, idx) => {
            html += `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-brand-600 text-white font-mono text-[11px] font-bold">
                    <span>${sn}</span>
                    <button type="button" onclick="removeTempSerial(${idx})" class="hover:text-black">
                        <i class="fa-solid fa-xmark text-[9px]"></i>
                    </button>
                </span>
            `;
        });
        container.innerHTML = html;
    }

    function toggleSerialSelection(sn) {
        const item = cart[activeSerialCartIndex];
        const idx = tempSelectedSerials.indexOf(sn);
        if (idx >= 0) {
            tempSelectedSerials.splice(idx, 1);
        } else {
            if (tempSelectedSerials.length >= item.quantity) {
                alert(`You have already selected ${item.quantity} serial number(s) for this line.`);
                return;
            }
            tempSelectedSerials.push(sn);
        }
        renderSerialModalAvailableList(item);
        renderSerialModalAssignedChips(item);
    }

    function removeTempSerial(idx) {
        tempSelectedSerials.splice(idx, 1);
        const item = cart[activeSerialCartIndex];
        renderSerialModalAvailableList(item);
        renderSerialModalAssignedChips(item);
    }

    function addScannedSerial() {
        const input = document.getElementById('serialScanInput');
        const sn = input.value.trim();
        if (!sn) return;

        const item = cart[activeSerialCartIndex];
        if (tempSelectedSerials.includes(sn)) {
            alert('This serial number is already assigned to this item.');
            input.value = '';
            return;
        }
        if (tempSelectedSerials.length >= item.quantity) {
            alert(`You have already allocated ${item.quantity} serial(s) for this line. Increase item quantity or remove one first.`);
            return;
        }

        tempSelectedSerials.push(sn);
        input.value = '';
        renderSerialModalAvailableList(item);
        renderSerialModalAssignedChips(item);
    }

    function confirmSerialAssignment() {
        if (activeSerialCartIndex === null) return;
        const item = cart[activeSerialCartIndex];

        if (tempSelectedSerials.length < item.quantity) {
            if (!confirm(`You have only allocated ${tempSelectedSerials.length} of ${item.quantity} required IMEI(s). Confirm anyway?`)) {
                return;
            }
        }

        item.selectedSerials = [...tempSelectedSerials];
        closeSerialAssignModal();
        renderCart();
    }

    // ================= HARDWARE BARCODE SCANNER & SCALE INTEGRATION =================
    const barcodeInput = document.getElementById('posSearchInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const rawCode = this.value.trim();
                if (!rawCode) return;

                // 1. Weighing Scale Embedded Barcode Detection (Prefix 20 or 02)
                // Format: 20/02 + 4 digits Item Code + 5 digits Weight (grams) + 1 digit Checksum
                const scaleMatch = rawCode.match(/^(02|20)(\d{4})(\d{5})\d$/);
                if (scaleMatch) {
                    const itemCodeDigits = scaleMatch[2];
                    const weightGrams = parseInt(scaleMatch[3], 10);
                    const weightKg = weightGrams / 1000;

                    const match = window.posProductsList.find(p => 
                        p.code.includes(itemCodeDigits) || 
                        p.variations.some(v => v.sku.includes(itemCodeDigits) || (v.barcode && v.barcode.includes(itemCodeDigits)))
                    );

                    if (match) {
                        const v = match.variations[0];
                        addToCart({
                            id: v.id,
                            productId: match.id,
                            name: match.name,
                            variationName: 'Weighted',
                            sku: v.sku,
                            price: parseFloat(v.selling_price),
                            stock: 999,
                            quantity: weightKg
                        });
                        this.value = '';
                        filterProducts();
                        return;
                    }
                }

                // 2. Direct Serial / IMEI Scanner Lookup
                for (const p of window.posProductsList) {
                    for (const v of p.variations) {
                        if (v.serials && v.serials.some(s => s.serial_number.toLowerCase() === rawCode.toLowerCase())) {
                            addToCart({
                                id: v.id,
                                productId: p.id,
                                name: p.name,
                                variationName: v.name,
                                sku: v.sku,
                                price: parseFloat(v.selling_price),
                                stock: 1,
                                trackSerial: true,
                                warrantyMonths: v.warranty_months,
                                serials: v.serials.map(s => s.serial_number),
                                initialSerial: rawCode
                            });
                            this.value = '';
                            filterProducts();
                            return;
                        }
                    }
                }

                // 3. Exact Barcode / SKU Match
                const found = window.posProductsList.find(p => 
                    p.code.toLowerCase() === rawCode.toLowerCase() ||
                    p.variations.some(v => (v.barcode && v.barcode.toLowerCase() === rawCode.toLowerCase()) || v.sku.toLowerCase() === rawCode.toLowerCase())
                );

                if (found) {
                    if (found.type === 'variable' && found.variations.length > 1) {
                        openVariationModal(found, window.posBranchId);
                    } else {
                        const v = found.variations[0];
                        addToCart({
                            id: v.id,
                            productId: found.id,
                            name: found.name,
                            variationName: v.name,
                            sku: v.sku,
                            price: parseFloat(v.selling_price),
                            stock: 99,
                            trackSerial: v.track_serial,
                            warrantyMonths: v.warranty_months,
                            serials: v.serials ? v.serials.map(s => s.serial_number) : []
                        });
                    }
                    this.value = '';
                    filterProducts();
                }
            }
        });
    }
</script>
@endsection

