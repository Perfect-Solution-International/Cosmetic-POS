<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'variations.attributeValues', 'variations.stocks']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('variations', function ($vq) use ($search) {
                      $vq->where('sku', 'like', "%{$search}%")
                         ->orWhere('barcode', 'like', "%{$search}%");
                  });
            });
        }

        $products = $query->latest()->paginate(12);
        $categories = Category::whereNull('parent_id')->with('subcategories')->get();
        $brands = Brand::where('is_active', true)->get();

        return view('products.index', compact('products', 'categories', 'brands'));
    }

    public function create()
    {
        $categories = Category::all();
        $brands = Brand::where('is_active', true)->get();
        $attributes = Attribute::with('values')->get();

        return view('products.create', compact('categories', 'brands', 'attributes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:products,code',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'type' => 'required|in:simple,variable',
            'has_expiry' => 'boolean',
            'description' => 'nullable|string',
            // Simple product inputs
            'single_sku' => 'nullable|required_if:type,simple|string|unique:product_variations,sku',
            'single_barcode' => 'nullable|string',
            'single_purchase_cost' => 'nullable|numeric|min:0',
            'single_selling_price' => 'nullable|numeric|min:0',
            'single_wholesale_price' => 'nullable|numeric|min:0',
            'track_serial' => 'nullable|boolean',
            'warranty_months' => 'nullable|integer|min:0',
            // Variable product inputs (array of variations)
            'variations' => 'nullable|array',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'type' => $validated['type'],
            'has_expiry' => $request->boolean('has_expiry'),
            'description' => $validated['description'] ?? null,
            'status' => 'active',
        ]);

        if ($validated['type'] === 'simple') {
            ProductVariation::create([
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $validated['single_sku'] ?? ($product->code . '-STD'),
                'barcode' => $validated['single_barcode'] ?? null,
                'purchase_cost' => $validated['single_purchase_cost'] ?? 0.00,
                'selling_price' => $validated['single_selling_price'] ?? 0.00,
                'wholesale_price' => $validated['single_wholesale_price'] ?? null,
                'reorder_level' => 5,
                'track_serial' => $request->boolean('track_serial'),
                'warranty_months' => (int) ($validated['warranty_months'] ?? 0),
                'is_active' => true,
            ]);
        } elseif ($validated['type'] === 'variable' && !empty($request->input('variations'))) {
            foreach ($request->input('variations') as $varData) {
                if (empty($varData['sku'])) continue;

                $variation = ProductVariation::create([
                    'product_id' => $product->id,
                    'name' => $varData['name'] ?? $product->name,
                    'sku' => $varData['sku'],
                    'barcode' => $varData['barcode'] ?? null,
                    'purchase_cost' => $varData['purchase_cost'] ?? 0.00,
                    'selling_price' => $varData['selling_price'] ?? 0.00,
                    'wholesale_price' => $varData['wholesale_price'] ?? null,
                    'reorder_level' => $varData['reorder_level'] ?? 5,
                    'track_serial' => !empty($varData['track_serial']),
                    'warranty_months' => (int) ($varData['warranty_months'] ?? 0),
                    'is_active' => true,
                ]);

                if (!empty($varData['attribute_values'])) {
                    $variation->attributeValues()->sync($varData['attribute_values']);
                }
            }
        }

        return redirect()->route('products.index')
            ->with('status', 'Product ' . $product->name . ' created successfully.');
    }

    public function attributes()
    {
        $attributes = Attribute::with('values')->get();
        return view('products.attributes', compact('attributes'));
    }

    public function storeAttribute(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:dropdown,color,text,numeric,date,checkbox',
            'is_variation' => 'boolean',
        ]);

        Attribute::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'type' => $validated['type'],
            'is_variation' => $request->boolean('is_variation'),
        ]);

        return redirect()->route('products.attributes')
            ->with('status', 'Attribute ' . $validated['name'] . ' created successfully.');
    }

    public function storeAttributeValue(Request $request, $attributeId)
    {
        $attribute = Attribute::findOrFail($attributeId);

        $validated = $request->validate([
            'value' => 'required|string|max:100',
            'color_code' => 'nullable|string|max:20',
        ]);

        $attribute->values()->create($validated);

        return redirect()->route('products.attributes')
            ->with('status', 'Value added to ' . $attribute->name . '.');
    }

    public function edit($id)
    {
        $product = Product::with(['category', 'brand', 'variations.attributeValues', 'variations.stocks'])->findOrFail($id);
        $categories = Category::all();
        $brands = Brand::where('is_active', true)->get();
        $attributes = Attribute::with('values')->get();

        return view('products.edit', compact('product', 'categories', 'brands', 'attributes'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::with('variations')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:products,code,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'type' => 'required|in:simple,variable',
            'has_expiry' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
            // Simple product inputs
            'single_sku' => 'nullable|required_if:type,simple|string|max:100',
            'single_barcode' => 'nullable|string|max:100',
            'single_purchase_cost' => 'nullable|numeric|min:0',
            'single_selling_price' => 'nullable|required_if:type,simple|numeric|min:0',
            'single_wholesale_price' => 'nullable|numeric|min:0',
            'single_reorder_level' => 'nullable|integer|min:0',
            'track_serial' => 'nullable|boolean',
            'warranty_months' => 'nullable|integer|min:0',
            // Variable product inputs
            'variations' => 'nullable|array',
            'variations.*.id' => 'nullable|integer',
            'variations.*.name' => 'required_with:variations|string|max:255',
            'variations.*.sku' => 'required_with:variations|string|max:100',
            'variations.*.barcode' => 'nullable|string|max:100',
            'variations.*.purchase_cost' => 'nullable|numeric|min:0',
            'variations.*.selling_price' => 'required_with:variations|numeric|min:0',
            'variations.*.wholesale_price' => 'nullable|numeric|min:0',
            'variations.*.reorder_level' => 'nullable|integer|min:0',
            'variations.*.track_serial' => 'nullable|boolean',
            'variations.*.warranty_months' => 'nullable|integer|min:0',
            'variations.*.is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($product, $validated, $request) {
            $product->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'category_id' => $validated['category_id'],
                'brand_id' => $validated['brand_id'] ?? null,
                'type' => $validated['type'],
                'has_expiry' => $request->boolean('has_expiry'),
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
            ]);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $product->update(['image' => $path]);
            }

            if ($validated['type'] === 'simple') {
                $variation = $product->variations->first();
                if ($variation) {
                    $variation->update([
                        'name' => $product->name,
                        'sku' => $validated['single_sku'] ?? $variation->sku,
                        'barcode' => $validated['single_barcode'] ?? null,
                        'purchase_cost' => $validated['single_purchase_cost'] ?? 0.00,
                        'selling_price' => $validated['single_selling_price'] ?? 0.00,
                        'wholesale_price' => $validated['single_wholesale_price'] ?? null,
                        'reorder_level' => $validated['single_reorder_level'] ?? 5,
                        'track_serial' => $request->boolean('track_serial'),
                        'warranty_months' => (int) ($validated['warranty_months'] ?? 0),
                        'is_active' => $validated['status'] === 'active',
                    ]);
                } else {
                    ProductVariation::create([
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'sku' => $validated['single_sku'] ?? ($product->code . '-STD'),
                        'barcode' => $validated['single_barcode'] ?? null,
                        'purchase_cost' => $validated['single_purchase_cost'] ?? 0.00,
                        'selling_price' => $validated['single_selling_price'] ?? 0.00,
                        'wholesale_price' => $validated['single_wholesale_price'] ?? null,
                        'reorder_level' => $validated['single_reorder_level'] ?? 5,
                        'track_serial' => $request->boolean('track_serial'),
                        'warranty_months' => (int) ($validated['warranty_months'] ?? 0),
                        'is_active' => $validated['status'] === 'active',
                    ]);
                }
            } elseif ($validated['type'] === 'variable' && !empty($request->input('variations'))) {
                $submittedVariationIds = [];

                foreach ($request->input('variations') as $varData) {
                    if (empty($varData['sku'])) continue;

                    $varId = !empty($varData['id']) ? (int) $varData['id'] : null;
                    $existing = $varId ? $product->variations->firstWhere('id', $varId) : null;

                    if ($existing) {
                        $existing->update([
                            'name' => $varData['name'] ?? $product->name,
                            'sku' => $varData['sku'],
                            'barcode' => $varData['barcode'] ?? null,
                            'purchase_cost' => $varData['purchase_cost'] ?? 0.00,
                            'selling_price' => $varData['selling_price'] ?? 0.00,
                            'wholesale_price' => $varData['wholesale_price'] ?? null,
                            'reorder_level' => $varData['reorder_level'] ?? 5,
                            'track_serial' => !empty($varData['track_serial']),
                            'warranty_months' => (int) ($varData['warranty_months'] ?? 0),
                            'is_active' => isset($varData['is_active']) ? (bool) $varData['is_active'] : true,
                        ]);
                        $submittedVariationIds[] = $existing->id;
                    } else {
                        $newVariation = ProductVariation::create([
                            'product_id' => $product->id,
                            'name' => $varData['name'] ?? $product->name,
                            'sku' => $varData['sku'],
                            'barcode' => $varData['barcode'] ?? null,
                            'purchase_cost' => $varData['purchase_cost'] ?? 0.00,
                            'selling_price' => $varData['selling_price'] ?? 0.00,
                            'wholesale_price' => $varData['wholesale_price'] ?? null,
                            'reorder_level' => $varData['reorder_level'] ?? 5,
                            'track_serial' => !empty($varData['track_serial']),
                            'warranty_months' => (int) ($varData['warranty_months'] ?? 0),
                            'is_active' => true,
                        ]);
                        $submittedVariationIds[] = $newVariation->id;
                    }
                }

                // Check for removed variations
                foreach ($product->variations as $oldVar) {
                    if (!in_array($oldVar->id, $submittedVariationIds)) {
                        $hasSales = DB::table('sale_items')->where('product_variation_id', $oldVar->id)->exists();
                        $hasGrn = DB::table('grn_items')->where('product_variation_id', $oldVar->id)->exists();
                        $hasStock = DB::table('stocks')->where('product_variation_id', $oldVar->id)->where('quantity', '>', 0)->exists();

                        if ($hasSales || $hasGrn || $hasStock) {
                            $oldVar->update(['is_active' => false]);
                        } else {
                            DB::table('product_variation_values')->where('product_variation_id', $oldVar->id)->delete();
                            DB::table('stocks')->where('product_variation_id', $oldVar->id)->delete();
                            $oldVar->delete();
                        }
                    }
                }
            }
        });

        return redirect()->route('products.index')
            ->with('status', 'Product "' . $product->fresh()->name . '" updated successfully.');
    }

    public function quickPriceUpdate(Request $request, $id)
    {
        $product = Product::with('variations')->findOrFail($id);

        $validated = $request->validate([
            'variations' => 'required|array|min:1',
            'variations.*.id' => 'required|exists:product_variations,id',
            'variations.*.selling_price' => 'required|numeric|min:0',
            'variations.*.purchase_cost' => 'nullable|numeric|min:0',
            'variations.*.wholesale_price' => 'nullable|numeric|min:0',
            'variations.*.barcode' => 'nullable|string|max:100',
        ]);

        foreach ($validated['variations'] as $vData) {
            $variation = $product->variations->firstWhere('id', $vData['id']);
            if ($variation) {
                $variation->update([
                    'selling_price' => $vData['selling_price'],
                    'purchase_cost' => $vData['purchase_cost'] ?? $variation->purchase_cost,
                    'wholesale_price' => $vData['wholesale_price'] ?? null,
                    'barcode' => $vData['barcode'] ?? $variation->barcode,
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Prices for "' . $product->name . '" updated successfully.',
                'product' => $product->fresh()->load('variations'),
            ]);
        }

        return redirect()->route('products.index')
            ->with('status', 'Prices for "' . $product->name . '" updated successfully.');
    }

    public function toggleStatus($id)
    {
        $product = Product::with('variations')->findOrFail($id);
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';
        $product->update(['status' => $newStatus]);
        $product->variations()->update(['is_active' => ($newStatus === 'active')]);

        return redirect()->route('products.index')
            ->with('status', 'Product "' . $product->name . '" status changed to ' . ucfirst($newStatus) . '.');
    }

    public function destroy($id)
    {
        $product = Product::with('variations')->findOrFail($id);
        $variationIds = $product->variations->pluck('id')->toArray();

        $hasSales = DB::table('sale_items')->whereIn('product_variation_id', $variationIds)->exists();
        $hasGrn = DB::table('grn_items')->whereIn('product_variation_id', $variationIds)->exists();
        $hasStock = DB::table('stocks')->whereIn('product_variation_id', $variationIds)->where('quantity', '>', 0)->exists();

        if ($hasSales || $hasGrn || $hasStock) {
            $product->update(['status' => 'inactive']);
            $product->variations()->update(['is_active' => false]);

            return redirect()->route('products.index')
                ->with('status', 'Product "' . $product->name . '" has historical sales or stock records. It was marked Inactive instead of deleted to protect accounting integrity.');
        }

        DB::transaction(function () use ($product, $variationIds) {
            DB::table('product_variation_values')->whereIn('product_variation_id', $variationIds)->delete();
            DB::table('stocks')->whereIn('product_variation_id', $variationIds)->delete();
            $product->variations()->delete();
            $product->delete();
        });

        return redirect()->route('products.index')
            ->with('status', 'Product "' . $product->name . '" deleted successfully.');
    }
}

