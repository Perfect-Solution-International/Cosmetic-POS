@extends('layouts.pos')

@section('title', 'Edit Product - ' . $product->name)

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('products.index') }}" class="w-9 h-9 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-400 hover:text-white flex items-center justify-center border border-carbon-700 transition">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-extrabold text-white">Edit Product: {{ $product->name }}</h2>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase {{ $product->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }}">
                        {{ $product->status }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-carbon-800 text-silver-400 border border-carbon-700">
                        {{ strtoupper($product->type) }}
                    </span>
                </div>
                <p class="text-xs text-silver-400">Update pricing, barcodes, variations, and catalog details</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('products.attributes') }}" class="text-xs text-brand-400 hover:underline flex items-center gap-1 font-semibold">
                <i class="fa-solid fa-sliders text-[11px]"></i> Manage Attributes
            </a>
        </div>
    </div>

    <!-- Form Body -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-6">
        <div class="max-w-5xl mx-auto">
            
            @if ($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs">
                    <div class="font-bold mb-1 flex items-center gap-1.5 text-rose-400">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>Please correct the errors below:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data" id="editProductForm" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Section 1: General Product Information -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
                        <h3 class="text-sm font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-tag text-brand-500"></i>
                            <span>1. General Product Information</span>
                        </h3>
                        <div class="flex items-center gap-3">
                            <label class="text-xs font-bold text-silver-400">Product Status:</label>
                            <select name="status" class="px-2.5 py-1 rounded-lg bg-carbon-850 border border-carbon-700 text-xs font-bold {{ $product->status === 'active' ? 'text-emerald-400' : 'text-rose-400' }} focus:outline-none focus:border-brand-500">
                                <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active (In Sale)</option>
                                <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive (Archived)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Product Name *</label>
                            <input 
                                type="text" 
                                name="name" 
                                value="{{ old('name', $product->name) }}" 
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs sm:text-sm focus:outline-none focus:border-brand-500 font-medium"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Product Code / SKU Prefix *</label>
                            <input 
                                type="text" 
                                name="code" 
                                value="{{ old('code', $product->code) }}" 
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs sm:text-sm font-mono font-bold focus:outline-none focus:border-brand-500"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Category *</label>
                            <select name="category_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Brand</label>
                            <select name="brand_id" class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                                <option value="">Select Brand (Optional)</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Batch Expiry & Serial Tracking Flags -->
                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center gap-4">
                        <label class="relative flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="has_expiry" value="1" {{ old('has_expiry', $product->has_expiry) ? 'checked' : '' }} class="w-4 h-4 rounded bg-carbon-800 border-carbon-600 text-brand-600 focus:ring-brand-500">
                            <span class="text-xs font-bold text-silver-300">
                                Track Batch Number & Expiry Date (Cosmetics / Food)
                            </span>
                        </label>

                        @php
                            $firstVar = $product->variations->first();
                            $trackSerialVal = $firstVar ? $firstVar->track_serial : false;
                            $warrantyVal = $firstVar ? $firstVar->warranty_months : 0;
                        @endphp

                        @if($product->type === 'simple')
                        <label class="relative flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="track_serial" value="1" {{ old('track_serial', $trackSerialVal) ? 'checked' : '' }} class="w-4 h-4 rounded bg-carbon-800 border-carbon-600 text-brand-600 focus:ring-brand-500">
                            <span class="text-xs font-bold text-silver-300">
                                Track Unique IMEI / Serial Number
                            </span>
                        </label>
                        @endif
                    </div>

                    @if($product->type === 'simple')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1">Standard Warranty Period (Months)</label>
                            <input 
                                type="number" 
                                name="warranty_months" 
                                value="{{ old('warranty_months', $warrantyVal) }}" 
                                min="0" 
                                placeholder="e.g. 12 (0 for no warranty)" 
                                class="w-full px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500 font-medium"
                            >
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Description & Details</label>
                        <textarea 
                            name="description" 
                            rows="2" 
                            placeholder="Product notes, ingredients, shade info..."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                        >{{ old('description', $product->description) }}</textarea>
                    </div>

                    <!-- Hidden Type Input (Locked to prevent schema corruption) -->
                    <input type="hidden" name="type" value="{{ $product->type }}">
                </div>

                <!-- Section 2: Product Type & Pricing -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-carbon-800 pb-3">
                        <h3 class="text-sm font-extrabold text-white uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-money-bill-wave text-emerald-400"></i>
                            <span>2. Pricing, Barcode & Variations</span>
                        </h3>
                        <span class="text-xs font-mono text-silver-400">
                            Mode: <strong class="text-white uppercase">{{ $product->type }}</strong>
                        </span>
                    </div>

                    @if($product->type === 'simple')
                        <!-- SIMPLE PRODUCT EDIT FIELDS -->
                        @php
                            $singleVar = $product->variations->first();
                            $currentStock = $singleVar ? $singleVar->stocks->sum('quantity') : 0;
                        @endphp

                        <div class="p-4 rounded-xl bg-carbon-850/70 border border-carbon-800 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-brand-500/20 border border-brand-500/30 flex items-center justify-center text-brand-400">
                                    <i class="fa-solid fa-box text-base"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-white block">Standard Single Product</span>
                                    <span class="text-[11px] text-silver-400">Current Stock: <strong class="text-emerald-400">{{ $currentStock }} Units</strong> across all branches</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">SKU *</label>
                                <input 
                                    type="text" 
                                    name="single_sku" 
                                    value="{{ old('single_sku', $singleVar?->sku) }}" 
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold focus:outline-none focus:border-brand-500"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Barcode (EAN-13 / UPC)</label>
                                <input 
                                    type="text" 
                                    name="single_barcode" 
                                    value="{{ old('single_barcode', $singleVar?->barcode) }}" 
                                    placeholder="e.g. 794504100231" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Purchase Cost (Rs)</label>
                                <input 
                                    type="number" 
                                    name="single_purchase_cost" 
                                    step="any" 
                                    min="0"
                                    value="{{ old('single_purchase_cost', $singleVar?->purchase_cost ?? 0.00) }}" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-emerald-400 uppercase block mb-1.5">Selling Price (Rs) *</label>
                                <input 
                                    type="number" 
                                    name="single_selling_price" 
                                    step="any" 
                                    min="0"
                                    value="{{ old('single_selling_price', $singleVar?->selling_price ?? 0.00) }}" 
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border-2 border-emerald-500/50 text-emerald-300 text-sm font-mono font-extrabold focus:outline-none focus:border-emerald-400"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Wholesale Price (Rs)</label>
                                <input 
                                    type="number" 
                                    name="single_wholesale_price" 
                                    step="any" 
                                    min="0"
                                    value="{{ old('single_wholesale_price', $singleVar?->wholesale_price) }}" 
                                    placeholder="Optional"
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Reorder Alert Level</label>
                                <input 
                                    type="number" 
                                    name="single_reorder_level" 
                                    min="0"
                                    value="{{ old('single_reorder_level', $singleVar?->reorder_level ?? 5) }}" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                >
                            </div>
                        </div>

                    @else
                        <!-- VARIABLE PRODUCT MATRIX EDIT BLOCK -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold text-white uppercase tracking-wider">Product Variations & Shades</h4>
                                    <p class="text-[11px] text-silver-400">Edit individual variation prices, barcodes, and statuses or add new shades</p>
                                </div>
                                <button 
                                    type="button" 
                                    onclick="addVariationRow()" 
                                    class="px-3.5 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-brand-600/30"
                                >
                                    <i class="fa-solid fa-plus text-xs"></i> Add Variation
                                </button>
                            </div>

                            <div class="border border-carbon-800 rounded-xl overflow-hidden bg-carbon-850">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs text-silver-300">
                                        <thead class="bg-carbon-900 border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400">
                                            <tr>
                                                <th class="py-2.5 px-3">Variation / Shade Name *</th>
                                                <th class="py-2.5 px-3">SKU *</th>
                                                <th class="py-2.5 px-3">Barcode</th>
                                                <th class="py-2.5 px-3 text-right">Cost (Rs)</th>
                                                <th class="py-2.5 px-3 text-right">Price (Rs) *</th>
                                                <th class="py-2.5 px-3 text-right">Wholesale (Rs)</th>
                                                <th class="py-2.5 px-3 text-center">Stock</th>
                                                <th class="py-2.5 px-3 text-center">Status</th>
                                                <th class="py-2.5 px-3 text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-carbon-800/60" id="variationRowsContainer">
                                            @foreach($product->variations as $index => $var)
                                                @php
                                                    $varStock = $var->stocks->sum('quantity');
                                                @endphp
                                                <tr class="hover:bg-carbon-800/40 transition-colors" id="varRow_{{ $index }}">
                                                    <td class="py-2.5 px-3">
                                                        <input type="hidden" name="variations[{{ $index }}][id]" value="{{ $var->id }}">
                                                        <input 
                                                            type="text" 
                                                            name="variations[{{ $index }}][name]" 
                                                            value="{{ old("variations.{$index}.name", $var->name) }}" 
                                                            required
                                                            placeholder="e.g. Ruby Red"
                                                            class="w-full min-w-[130px] px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500"
                                                        >
                                                    </td>
                                                    <td class="py-2.5 px-3">
                                                        <input 
                                                            type="text" 
                                                            name="variations[{{ $index }}][sku]" 
                                                            value="{{ old("variations.{$index}.sku", $var->sku) }}" 
                                                            required
                                                            class="w-full min-w-[100px] px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                                        >
                                                    </td>
                                                    <td class="py-2.5 px-3">
                                                        <input 
                                                            type="text" 
                                                            name="variations[{{ $index }}][barcode]" 
                                                            value="{{ old("variations.{$index}.barcode", $var->barcode) }}" 
                                                            placeholder="Barcode"
                                                            class="w-full min-w-[110px] px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                                        >
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right">
                                                        <input 
                                                            type="number" 
                                                            name="variations[{{ $index }}][purchase_cost]" 
                                                            step="any"
                                                            min="0"
                                                            value="{{ old("variations.{$index}.purchase_cost", $var->purchase_cost) }}" 
                                                            class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500"
                                                        >
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right">
                                                        <input 
                                                            type="number" 
                                                            name="variations[{{ $index }}][selling_price]" 
                                                            step="any"
                                                            min="0"
                                                            value="{{ old("variations.{$index}.selling_price", $var->selling_price) }}" 
                                                            required
                                                            class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border-2 border-emerald-500/50 text-emerald-300 text-xs font-mono font-bold text-right focus:outline-none focus:border-emerald-400"
                                                        >
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right">
                                                        <input 
                                                            type="number" 
                                                            name="variations[{{ $index }}][wholesale_price]" 
                                                            step="any"
                                                            min="0"
                                                            value="{{ old("variations.{$index}.wholesale_price", $var->wholesale_price) }}" 
                                                            placeholder="Optional"
                                                            class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500"
                                                        >
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold {{ $varStock > 0 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }}">
                                                            {{ $varStock }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <input type="hidden" name="variations[{{ $index }}][is_active]" value="0">
                                                        <label class="inline-flex items-center cursor-pointer" title="Toggle active status">
                                                            <input type="checkbox" name="variations[{{ $index }}][is_active]" value="1" {{ old("variations.{$index}.is_active", $var->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded bg-carbon-900 border-carbon-700 text-brand-600 focus:ring-brand-500">
                                                        </label>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <button 
                                                            type="button" 
                                                            onclick="removeVariationRow({{ $index }}, {{ $varStock }})" 
                                                            class="w-7 h-7 rounded text-rose-500 hover:text-white hover:bg-rose-600 flex items-center justify-center transition-all mx-auto"
                                                            title="{{ $varStock > 0 ? 'Item has stock. Removing will deactivate it.' : 'Remove variation' }}"
                                                        >
                                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

                <!-- Save Action Buttons -->
                <div class="flex items-center justify-between pt-2">
                    <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete or deactivate this product?');" class="inline">
                        <!-- Left blank, separate delete form handled below -->
                    </form>

                    <div class="flex items-center gap-3">
                        <button 
                            type="button"
                            onclick="confirmDeleteProduct()" 
                            class="px-4 py-2.5 rounded-xl bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 text-xs font-bold transition flex items-center gap-1.5"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Delete / Deactivate</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('products.index') }}" class="px-5 py-2.5 rounded-xl bg-carbon-850 hover:bg-carbon-800 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn">
                            <i class="fa-solid fa-check"></i>
                            <span>Update Product</span>
                        </button>
                    </div>
                </div>

            </form>

            <!-- Standalone Delete Form for safety -->
            <form id="deleteProductForm" action="{{ route('products.destroy', $product->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>

        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    let variationCounter = {{ $product->variations->count() + 10 }};

    function addVariationRow(defaultName = '') {
        const container = document.getElementById('variationRowsContainer');
        if (!container) return;

        const i = variationCounter++;
        const tr = document.createElement('tr');
        tr.className = "hover:bg-carbon-800/40 transition-colors";
        tr.id = `varRow_${i}`;

        tr.innerHTML = `
            <td class="py-2.5 px-3">
                <input 
                    type="text" 
                    name="variations[${i}][name]" 
                    value="${defaultName}" 
                    placeholder="e.g. Shade / Size" 
                    required
                    class="w-full min-w-[130px] px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2.5 px-3">
                <input 
                    type="text" 
                    name="variations[${i}][sku]" 
                    placeholder="SKU" 
                    required
                    class="w-full min-w-[100px] px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2.5 px-3">
                <input 
                    type="text" 
                    name="variations[${i}][barcode]" 
                    placeholder="Barcode" 
                    class="w-full min-w-[110px] px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2.5 px-3 text-right">
                <input 
                    type="number" 
                    name="variations[${i}][purchase_cost]" 
                    step="any"
                    min="0"
                    placeholder="Cost" 
                    class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2.5 px-3 text-right">
                <input 
                    type="number" 
                    name="variations[${i}][selling_price]" 
                    step="any"
                    min="0"
                    placeholder="Price" 
                    required
                    class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border-2 border-emerald-500/50 text-emerald-300 text-xs font-mono font-bold text-right focus:outline-none focus:border-emerald-400"
                >
            </td>
            <td class="py-2.5 px-3 text-right">
                <input 
                    type="number" 
                    name="variations[${i}][wholesale_price]" 
                    step="any"
                    min="0"
                    placeholder="Wholesale" 
                    class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2.5 px-3 text-center">
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-carbon-800 text-silver-400 border border-carbon-700">
                    New
                </span>
            </td>
            <td class="py-2.5 px-3 text-center">
                <input type="hidden" name="variations[${i}][is_active]" value="0">
                <input type="checkbox" name="variations[${i}][is_active]" value="1" checked class="w-4 h-4 rounded bg-carbon-900 border-carbon-700 text-brand-600 focus:ring-brand-500">
            </td>
            <td class="py-2.5 px-3 text-center">
                <button type="button" onclick="removeVariationRow(${i}, 0)" class="w-7 h-7 rounded text-rose-500 hover:text-white hover:bg-rose-600 flex items-center justify-center transition-all mx-auto">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
            </td>
        `;

        container.appendChild(tr);
    }

    function removeVariationRow(index, stock) {
        if (stock > 0) {
            if (!confirm(`This variation has ${stock} units in stock. Removing it will deactivate it instead of deleting history. Proceed?`)) {
                return;
            }
        }
        const row = document.getElementById(`varRow_${index}`);
        if (row) row.remove();
    }

    function confirmDeleteProduct() {
        if (confirm('Are you sure you want to delete or deactivate this product? If it has past sales or stock, it will be marked as Inactive.')) {
            document.getElementById('deleteProductForm').submit();
        }
    }
</script>
@endsection

