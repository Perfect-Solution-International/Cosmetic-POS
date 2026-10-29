@extends('layouts.pos')

@section('title', 'Add New Product')

@section('content')
<div class="h-full flex flex-col overflow-hidden bg-carbon-950">

    <!-- Header -->
    <div class="bg-carbon-900 border-b border-carbon-800 px-4 sm:px-6 py-4 flex items-center justify-between gap-4 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('products.index') }}" class="w-9 h-9 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-silver-400 hover:text-white flex items-center justify-center border border-carbon-700">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-white">Add New Product</h2>
                <p class="text-xs text-silver-400">Configure catalog details, pricing, batches & dynamic variations</p>
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

            <form action="{{ route('products.store') }}" method="POST" id="productForm" class="space-y-6">
                @csrf

                <!-- Section 1: Basic Information -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider flex items-center gap-2 border-b border-carbon-800 pb-3">
                        <i class="fa-solid fa-tag text-brand-500"></i>
                        <span>1. General Product Information</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Product Name *</label>
                            <input 
                                type="text" 
                                name="name" 
                                value="{{ old('name') }}" 
                                placeholder="e.g. Matte Velvet Lip Gloss" 
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs sm:text-sm focus:outline-none focus:border-brand-500 font-medium"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Product Code / Barcode *</label>
                            <input 
                                type="text" 
                                name="code" 
                                value="{{ old('code', 'PSI-' . strtoupper(bin2hex(random_bytes(3)))) }}" 
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs sm:text-sm font-mono font-bold focus:outline-none focus:border-brand-500"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Category *</label>
                            <select name="category_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-medium focus:outline-none focus:border-brand-500">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
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
                                    <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Cosmetics Expiry Tracking Checkbox -->
                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center gap-4">
                        <label class="relative flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="has_expiry" value="1" {{ old('has_expiry') ? 'checked' : '' }} class="w-4 h-4 rounded bg-carbon-800 border-carbon-600 text-brand-600 focus:ring-brand-500">
                            <span class="text-xs font-bold text-silver-300">
                                Track Batch Number & Expiry Date
                            </span>
                        </label>

                        <label class="relative flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="track_serial" id="trackSerialCheckbox" value="1" {{ old('track_serial') ? 'checked' : '' }} class="w-4 h-4 rounded bg-carbon-800 border-carbon-600 text-brand-600 focus:ring-brand-500">
                            <span class="text-xs font-bold text-silver-300">
                                Track Unique IMEI / Serial Number
                            </span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div>
                            <label class="text-xs font-bold text-silver-300 uppercase block mb-1">Standard Warranty Period (Months)</label>
                            <input 
                                type="number" 
                                name="warranty_months" 
                                value="{{ old('warranty_months', 0) }}" 
                                min="0" 
                                placeholder="e.g. 12 (0 for no warranty)" 
                                class="w-full px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white placeholder-silver-500 text-xs focus:outline-none focus:border-brand-500 font-medium"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Description & Details</label>
                        <textarea 
                            name="description" 
                            rows="2" 
                            placeholder="Product notes, ingredients, shade info..."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs focus:outline-none focus:border-brand-500"
                        >{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- Section 2: Product Type Switch (Simple vs Variable) -->
                <div class="bg-carbon-900 border border-carbon-800 rounded-2xl p-5 sm:p-6 shadow-xl space-y-4">
                    <h3 class="text-sm font-extrabold text-white uppercase tracking-wider flex items-center gap-2 border-b border-carbon-800 pb-3">
                        <i class="fa-solid fa-shapes text-brand-500"></i>
                        <span>2. Product Type & Pricing</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Simple Product Card Option -->
                        <label class="cursor-pointer border-2 rounded-2xl p-4 flex items-start gap-3 transition-all product-type-option" id="typeOptionSimple">
                            <input type="radio" name="type" value="simple" checked onchange="toggleProductType('simple')" class="mt-1 text-brand-600">
                            <div>
                                <div class="text-xs sm:text-sm font-bold text-white flex items-center gap-2">
                                    <i class="fa-solid fa-box text-brand-400"></i>
                                    <span>Single Item</span>
                                </div>
                                <p class="text-[11px] text-silver-400 mt-1">
                                    Single price and barcode (e.g. perfume bottle, serum, standalone lotion).
                                </p>
                            </div>
                        </label>

                        <!-- Variable Product Card Option -->
                        <label class="cursor-pointer border-2 rounded-2xl p-4 flex items-start gap-3 transition-all product-type-option" id="typeOptionVariable">
                            <input type="radio" name="type" value="variable" onchange="toggleProductType('variable')" class="mt-1 text-brand-600">
                            <div>
                                <div class="text-xs sm:text-sm font-bold text-white flex items-center gap-2">
                                    <i class="fa-solid fa-swatchbook text-rose-400"></i>
                                    <span>Item with Options (Shades, Colors, Sizes)</span>
                                </div>
                                <p class="text-[11px] text-silver-400 mt-1">
                                    Multiple shades, colors, or sizes with independent barcodes and prices (e.g. Lipsticks, Bags).
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- A: SIMPLE PRODUCT PRICING & SKU BLOCK -->
                    <div id="simpleProductFields" class="pt-4 border-t border-carbon-800 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">SKU / Item Code</label>
                                <input 
                                    type="text" 
                                    name="single_sku" 
                                    id="inputSingleSku"
                                    value="{{ old('single_sku') }}" 
                                    placeholder="e.g. LIP-MAT-01" 
                                    class="w-full px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Barcode (EAN-13 / UPC)</label>
                                <input 
                                    type="text" 
                                    name="single_barcode" 
                                    value="{{ old('single_barcode') }}" 
                                    placeholder="e.g. 794504100231" 
                                    class="w-full px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Purchase Cost (Rs)</label>
                                <input 
                                    type="number" 
                                    name="single_purchase_cost" 
                                    step="any" 
                                    min="0"
                                    value="{{ old('single_purchase_cost', '0.00') }}" 
                                    class="w-full px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Selling Price (Rs) *</label>
                                <input 
                                    type="number" 
                                    name="single_selling_price" 
                                    step="any" 
                                    min="0"
                                    value="{{ old('single_selling_price', '0.00') }}" 
                                    class="w-full px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono font-bold focus:outline-none focus:border-brand-500"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-bold text-silver-300 uppercase block mb-1.5">Wholesale Price (Optional)</label>
                                <input 
                                    type="number" 
                                    name="single_wholesale_price" 
                                    step="any" 
                                    min="0"
                                    value="{{ old('single_wholesale_price') }}" 
                                    class="w-full px-3.5 py-2 rounded-xl bg-carbon-850 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- B: VARIABLE PRODUCT DYNAMIC MATRIX BLOCK -->
                    <div id="variableProductFields" class="pt-4 border-t border-carbon-800 space-y-4 hidden">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Variation Rows</h4>
                                <p class="text-[11px] text-silver-400">Add variations with individual prices, barcodes, and attribute values</p>
                            </div>
                            <button 
                                type="button" 
                                onclick="addVariationRow()" 
                                class="px-3 py-1.5 rounded-xl bg-carbon-800 hover:bg-carbon-750 text-brand-400 hover:text-white border border-brand-500/30 text-xs font-bold transition flex items-center gap-1.5"
                            >
                                <i class="fa-solid fa-plus text-xs"></i> Add Variation
                            </button>
                        </div>

                        <!-- Dynamic Variations Table -->
                        <div class="border border-carbon-800 rounded-xl overflow-hidden bg-carbon-850">
                            <table class="w-full text-left text-xs text-silver-300" id="variationsTable">
                                <thead class="bg-carbon-900 border-b border-carbon-800 text-[10px] font-bold uppercase tracking-wider text-silver-400">
                                    <tr>
                                        <th class="py-2.5 px-3">Variation Name / Shade</th>
                                        <th class="py-2.5 px-3">SKU *</th>
                                        <th class="py-2.5 px-3">Barcode</th>
                                        <th class="py-2.5 px-3">Cost (Rs)</th>
                                        <th class="py-2.5 px-3">Price (Rs) *</th>
                                        <th class="py-2.5 px-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-carbon-800/60" id="variationRowsContainer">
                                    <!-- Rows added dynamically via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Save Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('products.index') }}" class="px-5 py-2.5 rounded-xl bg-carbon-850 hover:bg-carbon-800 border border-carbon-700 text-silver-300 hover:text-white text-xs font-bold transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-rose-700 hover:from-brand-500 hover:to-rose-600 text-white text-xs font-extrabold uppercase tracking-wider shadow-lg shadow-brand-600/30 transition flex items-center gap-2 touch-action-btn">
                        <i class="fa-solid fa-check"></i>
                        <span>Save Product</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    let variationIndex = 0;

    function toggleProductType(type) {
        const simpleBox = document.getElementById('simpleProductFields');
        const variableBox = document.getElementById('variableProductFields');
        const optSimple = document.getElementById('typeOptionSimple');
        const optVar = document.getElementById('typeOptionVariable');

        if (type === 'simple') {
            simpleBox.classList.remove('hidden');
            variableBox.classList.add('hidden');
            optSimple.className = "cursor-pointer border-2 border-brand-500 bg-brand-950/20 rounded-2xl p-4 flex items-start gap-3 transition-all";
            optVar.className = "cursor-pointer border-2 border-carbon-700 bg-carbon-850/40 rounded-2xl p-4 flex items-start gap-3 transition-all";
        } else {
            simpleBox.classList.add('hidden');
            variableBox.classList.remove('hidden');
            optVar.className = "cursor-pointer border-2 border-brand-500 bg-brand-950/20 rounded-2xl p-4 flex items-start gap-3 transition-all";
            optSimple.className = "cursor-pointer border-2 border-carbon-700 bg-carbon-850/40 rounded-2xl p-4 flex items-start gap-3 transition-all";
            
            // Add initial row if empty
            if (variationIndex === 0) {
                addVariationRow('Ruby Red');
                addVariationRow('Nude Rose');
            }
        }
    }

    function addVariationRow(defaultName = '') {
        const container = document.getElementById('variationRowsContainer');
        const i = variationIndex++;

        const tr = document.createElement('tr');
        tr.className = "hover:bg-carbon-800/40 transition-colors";
        tr.id = `varRow_${i}`;

        tr.innerHTML = `
            <td class="py-2 px-3">
                <input 
                    type="text" 
                    name="variations[${i}][name]" 
                    value="${defaultName}" 
                    placeholder="e.g. Shade 01 / Red" 
                    required
                    class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-semibold focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2 px-3">
                <input 
                    type="text" 
                    name="variations[${i}][sku]" 
                    placeholder="e.g. LIP-01" 
                    required
                    class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2 px-3">
                <input 
                    type="text" 
                    name="variations[${i}][barcode]" 
                    placeholder="Barcode" 
                    class="w-full px-2.5 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2 px-3">
                <input 
                    type="number" 
                    name="variations[${i}][purchase_cost]" 
                    step="any"
                    min="0"
                    placeholder="Cost" 
                    class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono text-right focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2 px-3">
                <input 
                    type="number" 
                    name="variations[${i}][selling_price]" 
                    step="any"
                    min="0"
                    placeholder="Price" 
                    required
                    class="w-24 px-2 py-1.5 rounded-lg bg-carbon-900 border border-carbon-700 text-white text-xs font-mono font-bold text-right focus:outline-none focus:border-brand-500"
                >
            </td>
            <td class="py-2 px-3 text-center">
                <button type="button" onclick="removeVariationRow(${i})" class="w-7 h-7 rounded text-rose-500 hover:text-white hover:bg-rose-600 flex items-center justify-center transition-all mx-auto">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
            </td>
        `;

        container.appendChild(tr);
    }

    function removeVariationRow(index) {
        const row = document.getElementById(`varRow_${index}`);
        if (row) row.remove();
    }

    // Initialize toggle style
    document.addEventListener('DOMContentLoaded', () => {
        toggleProductType('simple');
    });
</script>
@endsection

