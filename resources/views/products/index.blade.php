@extends('layouts.pos')

@section('title', 'Product Catalog & Variations')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Top Action Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-600/20 border border-brand-500/30 flex items-center justify-center text-brand-500">
                <i class="fa-solid fa-boxes-stacked text-base"></i>
            </div>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Product Catalog</h2>
                <p class="text-xs text-silver-400">Cosmetics, Handbags, Skincare, Jewellery & Fashion Accessories</p>
            </div>
        </div>

        @if(auth()->user()?->isAdmin() || auth()->user()?->isManager())
        <div class="flex items-center gap-2">
            <a 
                href="{{ route('products.attributes') }}" 
                class="px-3.5 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-sliders text-brand-400"></i>
                <span>Shades & Sizes</span>
            </a>

            <a 
                href="{{ route('products.create') }}" 
                class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg transition flex items-center gap-2 touch-action-btn"
            >
                <i class="fa-solid fa-plus"></i>
                <span>+ Add Product</span>
            </a>
        </div>
        @endif
    </div>

    <!-- Filters Bar -->
    <div class="bg-carbon-900/60 border-b border-carbon-800 px-4 sm:px-6 py-3 shrink-0">
        <form action="{{ route('products.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-silver-400 text-xs"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search product name, shade, barcode or SKU..." 
                        class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500"
                    >
                </div>
            </div>

            <div class="w-40 sm:w-48">
                <select name="category_id" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-36 sm:w-44">
                <select name="brand_id" class="w-full px-3 py-1.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500">
                    <option value="">All Brands</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-white text-xs font-semibold border border-carbon-700">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'category_id', 'brand_id']))
                <a href="{{ route('products.index') }}" class="text-xs text-rose-400 hover:underline">Clear</a>
            @endif
        </form>
    </div>

    <!-- Product Table / Cards View -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
        
        @if(session('status'))
            <div class="p-3 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="bg-carbon-900 border border-carbon-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-850 border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400">
                        <tr>
                            <th class="py-3 px-4">Product Item</th>
                            <th class="py-3 px-4">Item Code</th>
                            <th class="py-3 px-4">Category & Brand</th>
                            <th class="py-3 px-4">Shades & Options</th>
                            <th class="py-3 px-4">Selling Price</th>
                            <th class="py-3 px-4">Current Stock</th>
                            <th class="py-3 px-4 text-center">Expiry Tracker</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60">
                        @forelse($products as $product)
                            @php
                                $totalStock = $product->variations->sum(function($v) {
                                    return $v->stocks->sum('quantity');
                                });
                                $minPrice = $product->variations->min('selling_price') ?? 0;
                                $maxPrice = $product->variations->max('selling_price') ?? 0;
                                $productPayload = [
                                    'id' => $product->id,
                                    'name' => $product->name,
                                    'code' => $product->code,
                                    'type' => $product->type,
                                    'variations' => $product->variations->map(function($v) {
                                        return [
                                            'id' => $v->id,
                                            'name' => $v->name,
                                            'sku' => $v->sku,
                                            'barcode' => $v->barcode,
                                            'purchase_cost' => (float)$v->purchase_cost,
                                            'selling_price' => (float)$v->selling_price,
                                            'wholesale_price' => (float)($v->wholesale_price ?? 0),
                                        ];
                                    })->values(),
                                ];
                            @endphp
                            <tr class="hover:bg-carbon-850/60 transition-colors">
                                <!-- Product Details -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-carbon-800 border border-carbon-700 flex items-center justify-center text-silver-400 shrink-0 overflow-hidden">
                                            @if($product->image)
                                                <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover">
                                            @else
                                                <i class="fa-solid {{ $product->category->name == 'Cosmetics' ? 'fa-spray-can' : 'fa-box' }} text-brand-500"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-white text-xs truncate max-w-[200px]">{{ $product->name }}</div>
                                            <div class="text-[10px] text-silver-500 truncate max-w-[200px]">{{ $product->description ?? 'No description' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Code & Type -->
                                <td class="py-3.5 px-4 font-mono">
                                    <span class="text-white font-bold">{{ $product->code }}</span>
                                    <span class="block text-[10px] text-silver-400 capitalize">{{ $product->type }}</span>
                                </td>

                                <!-- Category & Brand -->
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-carbon-800 text-silver-300 border border-carbon-700">
                                        {{ $product->category->name ?? 'Unassigned' }}
                                    </span>
                                    <span class="block text-[10px] text-silver-400 mt-1">
                                        {{ $product->brand->name ?? 'Generic' }}
                                    </span>
                                </td>

                                <!-- Variations & SKUs -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        @foreach($product->variations->take(3) as $var)
                                            <div class="flex items-center gap-1.5 text-[10px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                                                <span class="font-bold text-white">{{ $var->name }}</span>
                                                <span class="text-silver-500 font-mono">({{ $var->sku }})</span>
                                            </div>
                                        @endforeach
                                        @if($product->variations->count() > 3)
                                            <span class="text-[9px] text-brand-400 font-semibold">+{{ $product->variations->count() - 3 }} more variations</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Pricing Range -->
                                <td class="py-3.5 px-4 font-mono text-xs">
                                    @if($minPrice == $maxPrice)
                                        <span class="text-white font-bold">Rs. {{ number_format($minPrice, 2) }}</span>
                                    @else
                                        <span class="text-white font-bold">Rs. {{ number_format($minPrice, 0) }} - {{ number_format($maxPrice, 0) }}</span>
                                    @endif
                                    <span class="block text-[10px] text-silver-500">Cost: Rs. {{ number_format($product->variations->min('purchase_cost') ?? 0, 2) }}</span>
                                </td>

                                <!-- Stock Count -->
                                <td class="py-3.5 px-4">
                                    @if($totalStock <= 0)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                            0 In Stock
                                        </span>
                                    @elseif($totalStock <= 10)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                            {{ $totalStock }} (Low Stock)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            {{ $totalStock }} Units
                                        </span>
                                    @endif
                                </td>

                                <!-- Cosmetics Expiry Monitor -->
                                <td class="py-3.5 px-4 text-center">
                                    @if($product->has_expiry)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                            <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> Batch Tracked
                                        </span>
                                    @else
                                        <span class="text-silver-600 text-[10px]">-</span>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $product->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }}">
                                        {{ $product->status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Quick Price Modal Button -->
                                        <button 
                                            type="button" 
                                            data-product="{{ json_encode($productPayload) }}"
                                            onclick="handleQuickPriceClick(this)"
                                            class="px-2.5 py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500 text-emerald-400 hover:text-black border border-emerald-500/30 text-xs font-bold transition flex items-center gap-1 touch-action-btn"
                                            title="Quick Price & Barcode Update"
                                        >
                                            <i class="fa-solid fa-tag text-[10px]"></i>
                                            <span class="hidden sm:inline">Price</span>
                                        </button>

                                        @if(auth()->user()?->isAdmin() || auth()->user()?->isManager() || auth()->user()?->isShopOwner() || auth()->user()?->isInventoryStaff())
                                            <!-- Full Edit Button -->
                                            <a 
                                                href="{{ route('products.edit', $product->id) }}" 
                                                class="px-2.5 py-1.5 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-300 hover:text-white border border-carbon-700 text-xs font-bold transition flex items-center gap-1 touch-action-btn"
                                                title="Full Product Edit"
                                            >
                                                <i class="fa-solid fa-pen-to-square text-[10px] text-brand-400"></i>
                                                <span class="hidden sm:inline">Edit</span>
                                            </a>

                                            <!-- Toggle Active / Inactive Status -->
                                            <form action="{{ route('products.toggle-status', $product->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button 
                                                    type="submit" 
                                                    class="p-1.5 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-400 hover:text-white border border-carbon-700 text-xs transition"
                                                    title="{{ $product->status === 'active' ? 'Deactivate Product' : 'Activate Product' }}"
                                                >
                                                    <i class="fa-solid {{ $product->status === 'active' ? 'fa-eye-slash text-amber-400' : 'fa-eye text-emerald-400' }}"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-silver-500">
                                    <i class="fa-solid fa-box-open text-3xl mb-2 text-carbon-700"></i>
                                    <p class="text-xs font-semibold text-silver-400">No products found matching filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="p-3 border-t border-carbon-800 bg-carbon-850/50">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

<!-- QUICK PRICE & BARCODE UPDATE MODAL -->
<div id="quickPriceModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-carbon-900 border border-carbon-700 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="px-5 py-4 border-b border-carbon-800 flex items-center justify-between bg-carbon-850">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-tag text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                        <span>Quick Price & Barcode Update</span>
                        <span id="quickModalProductCode" class="text-[10px] font-mono px-2 py-0.5 rounded bg-carbon-800 text-silver-400 border border-carbon-700"></span>
                    </h3>
                    <p id="quickModalProductName" class="text-xs text-silver-400 truncate max-w-md"></p>
                </div>
            </div>
            <button type="button" onclick="closeQuickPriceModal()" class="w-8 h-8 rounded-lg bg-carbon-800 hover:bg-carbon-700 text-silver-400 hover:text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form id="quickPriceForm" method="POST" action="" class="p-5 space-y-4">
            @csrf

            <div class="overflow-x-auto border border-carbon-800 rounded-xl bg-carbon-850/60 max-h-[380px]">
                <table class="w-full text-left text-xs text-silver-300">
                    <thead class="bg-carbon-900 border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400 sticky top-0">
                        <tr>
                            <th class="py-2.5 px-3">Variation / Shade</th>
                            <th class="py-2.5 px-3">Barcode</th>
                            <th class="py-2.5 px-3 text-right">Cost (Rs)</th>
                            <th class="py-2.5 px-3 text-right">Selling Price (Rs) *</th>
                            <th class="py-2.5 px-3 text-right">Wholesale (Rs)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-carbon-800/60" id="quickPriceModalRows">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-between pt-2">
                <a id="quickModalFullEditLink" href="#" class="text-xs text-brand-400 hover:underline flex items-center gap-1 font-semibold">
                    <i class="fa-solid fa-sliders text-[11px]"></i> Open Full Product Editor
                </a>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeQuickPriceModal()" class="px-4 py-2 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-300 hover:text-white text-xs font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition flex items-center gap-1.5 touch-action-btn">
                        <i class="fa-solid fa-check"></i>
                        <span>Save Prices</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function handleQuickPriceClick(btn) {
        try {
            const product = JSON.parse(btn.getAttribute('data-product'));
            openQuickPriceModal(product);
        } catch (e) {
            console.error('Error parsing product payload:', e);
        }
    }

    function openQuickPriceModal(product) {
        document.getElementById('quickModalProductName').textContent = product.name;
        document.getElementById('quickModalProductCode').textContent = product.code;
        document.getElementById('quickModalFullEditLink').href = `/products/${product.id}/edit`;

        const form = document.getElementById('quickPriceForm');
        form.action = `/products/${product.id}/quick-price`;

        const rowsContainer = document.getElementById('quickPriceModalRows');
        rowsContainer.innerHTML = '';

        product.variations.forEach((v, index) => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-carbon-800/40 transition-colors";
            tr.innerHTML = `
                <td class="py-2.5 px-3">
                    <input type="hidden" name="variations[${index}][id]" value="${v.id}">
                    <div class="font-bold text-white text-xs">${v.name}</div>
                    <div class="text-[10px] text-silver-500 font-mono">${v.sku}</div>
                </td>
                <td class="py-2.5 px-3">
                    <input 
                        type="text" 
                        name="variations[${index}][barcode]" 
                        value="${v.barcode || ''}" 
                        placeholder="Scan Barcode"
                        class="w-full min-w-[120px] px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                    >
                </td>
                <td class="py-2.5 px-3 text-right">
                    <input 
                        type="number" 
                        name="variations[${index}][purchase_cost]" 
                        step="any"
                        min="0"
                        value="${v.purchase_cost || 0}" 
                        class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500"
                    >
                </td>
                <td class="py-2.5 px-3 text-right">
                    <input 
                        type="number" 
                        name="variations[${index}][selling_price]" 
                        step="any"
                        min="0"
                        value="${v.selling_price || 0}" 
                        required
                        class="w-28 px-2 py-1.5 rounded-lg bg-carbon-900 border-2 border-emerald-500/50 text-emerald-300 text-xs font-mono font-bold text-right focus:outline-none focus:border-emerald-400"
                    >
                </td>
                <td class="py-2.5 px-3 text-right">
                    <input 
                        type="number" 
                        name="variations[${index}][wholesale_price]" 
                        step="any"
                        min="0"
                        value="${v.wholesale_price || ''}" 
                        placeholder="Optional"
                        class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500"
                    >
                </td>
            `;
            rowsContainer.appendChild(tr);
        });

        const modal = document.getElementById('quickPriceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Focus first selling price input
        setTimeout(() => {
            const firstPriceInput = form.querySelector('input[name="variations[0][selling_price]"]');
            if (firstPriceInput) firstPriceInput.select();
        }, 100);
    }

    function closeQuickPriceModal() {
        const modal = document.getElementById('quickPriceModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeQuickPriceModal();
    });
</script>
@endsection

