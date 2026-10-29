<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
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
}

