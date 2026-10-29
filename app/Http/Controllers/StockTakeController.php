<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductVariation;
use App\Models\Stock;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTakeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = $request->query('branch_id', $user->branch_id ?? Branch::first()?->id);

        $query = StockTake::with(['branch', 'user'])
            ->where('branch_id', $branchId);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $stockTakes = $query->latest()->paginate(15);
        $branches = Branch::where('status', 'active')->get();

        $metrics = [
            'total' => StockTake::where('branch_id', $branchId)->count(),
            'in_progress' => StockTake::where('branch_id', $branchId)->where('status', 'in_progress')->count(),
            'completed' => StockTake::where('branch_id', $branchId)->where('status', 'completed')->count(),
            'total_variance_cost' => StockTake::where('branch_id', $branchId)->where('status', 'completed')->sum('total_variance_cost'),
        ];

        return view('inventory.stock_takes.index', compact('stockTakes', 'branches', 'branchId', 'metrics'));
    }

    public function create()
    {
        $branches = Branch::where('status', 'active')->get();
        $user = Auth::user();
        $selectedBranch = Branch::find($user->branch_id) ?? $branches->first();

        return view('inventory.stock_takes.create', compact('branches', 'selectedBranch'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $branchId = $validated['branch_id'];

        $stockTake = DB::transaction(function () use ($branchId, $user, $validated) {
            $ref = 'ST-' . date('Ymd') . '-' . str_pad(StockTake::count() + 1, 4, '0', STR_PAD_LEFT);

            $stockTake = StockTake::create([
                'reference_no' => $ref,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'status' => 'in_progress',
                'started_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Snapshot all active variations in this branch
            $variations = ProductVariation::where('is_active', true)->get();
            $totalSystemQty = 0;

            foreach ($variations as $var) {
                $sysQty = Stock::where('branch_id', $branchId)
                    ->where('product_variation_id', $var->id)
                    ->sum('quantity');

                $cost = $var->purchase_cost ?? 0.00;

                StockTakeItem::create([
                    'stock_take_id' => $stockTake->id,
                    'product_variation_id' => $var->id,
                    'system_qty' => $sysQty,
                    'physical_qty' => 0,
                    'variance_qty' => -$sysQty,
                    'unit_cost' => $cost,
                    'variance_cost' => -($sysQty * $cost),
                ]);

                $totalSystemQty += $sysQty;
            }

            $stockTake->update([
                'total_system_qty' => $totalSystemQty,
                'total_physical_qty' => 0,
                'total_variance_qty' => -$totalSystemQty,
            ]);

            return $stockTake;
        });

        return redirect()->route('inventory.stock_takes.show', $stockTake->id)
            ->with('status', 'Stock Take session ' . $stockTake->reference_no . ' started. You can begin scanning barcodes.');
    }

    public function show(Request $request, $id)
    {
        $stockTake = StockTake::with(['branch', 'user'])->findOrFail($id);

        $query = StockTakeItem::with(['productVariation.product'])
            ->where('stock_take_id', $stockTake->id);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('productVariation', function ($vq) use ($search) {
                $vq->where('sku', 'like', "%{$search}%")
                   ->orWhere('barcode', 'like', "%{$search}%")
                   ->orWhereHas('product', function ($pq) use ($search) {
                       $pq->where('name', 'like', "%{$search}%");
                   });
            });
        }

        if ($request->query('filter') === 'variance_only') {
            $query->where('variance_qty', '!=', 0);
        } elseif ($request->query('filter') === 'counted_only') {
            $query->where('physical_qty', '>', 0);
        }

        $items = $query->paginate(30);

        // Calculate live statistics
        $stats = [
            'items_count' => StockTakeItem::where('stock_take_id', $stockTake->id)->count(),
            'counted_items_count' => StockTakeItem::where('stock_take_id', $stockTake->id)->where('physical_qty', '>', 0)->count(),
            'system_total_qty' => StockTakeItem::where('stock_take_id', $stockTake->id)->sum('system_qty'),
            'physical_total_qty' => StockTakeItem::where('stock_take_id', $stockTake->id)->sum('physical_qty'),
            'variance_total_qty' => StockTakeItem::where('stock_take_id', $stockTake->id)->sum('variance_qty'),
            'variance_total_cost' => StockTakeItem::where('stock_take_id', $stockTake->id)->sum('variance_cost'),
            'surplus_items_count' => StockTakeItem::where('stock_take_id', $stockTake->id)->where('variance_qty', '>', 0)->count(),
            'deficit_items_count' => StockTakeItem::where('stock_take_id', $stockTake->id)->where('variance_qty', '<', 0)->count(),
        ];

        return view('inventory.stock_takes.show', compact('stockTake', 'items', 'stats'));
    }

    public function scanItem(Request $request, $id)
    {
        $stockTake = StockTake::findOrFail($id);

        if ($stockTake->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'This audit session is closed.'], 422);
        }

        $queryStr = trim($request->input('barcode', ''));
        $addQty = (int) ($request->input('quantity', 1));
        $setMode = $request->boolean('set_exact'); // if true, sets physical_qty instead of incrementing

        if ($queryStr === '') {
            return response()->json(['success' => false, 'message' => 'Please enter a barcode or SKU.'], 422);
        }

        // Find product variation by barcode or SKU
        $variation = ProductVariation::where('barcode', $queryStr)
            ->orWhere('sku', $queryStr)
            ->first();

        if (!$variation) {
            return response()->json([
                'success' => false,
                'message' => 'No item found with barcode or SKU: ' . $queryStr
            ], 404);
        }

        // Find or create stock take item
        $item = StockTakeItem::firstOrCreate(
            [
                'stock_take_id' => $stockTake->id,
                'product_variation_id' => $variation->id,
            ],
            [
                'system_qty' => Stock::where('branch_id', $stockTake->branch_id)->where('product_variation_id', $variation->id)->sum('quantity'),
                'physical_qty' => 0,
                'unit_cost' => $variation->purchase_cost ?? 0.00,
            ]
        );

        if ($setMode) {
            $item->physical_qty = max(0, $addQty);
        } else {
            $item->physical_qty += $addQty;
        }

        $item->variance_qty = $item->physical_qty - $item->system_qty;
        $item->variance_cost = $item->variance_qty * $item->unit_cost;
        $item->scanned_at = now();
        $item->save();

        // Update session totals
        $stockTake->update([
            'total_physical_qty' => StockTakeItem::where('stock_take_id', $stockTake->id)->sum('physical_qty'),
            'total_variance_qty' => StockTakeItem::where('stock_take_id', $stockTake->id)->sum('variance_qty'),
            'total_variance_cost' => StockTakeItem::where('stock_take_id', $stockTake->id)->sum('variance_cost'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Count updated for ' . $variation->name,
            'item' => [
                'id' => $item->id,
                'sku' => $variation->sku,
                'name' => $variation->name,
                'system_qty' => $item->system_qty,
                'physical_qty' => $item->physical_qty,
                'variance_qty' => $item->variance_qty,
                'variance_cost' => number_format($item->variance_cost, 2),
            ],
            'session_totals' => [
                'physical_qty' => $stockTake->total_physical_qty,
                'variance_qty' => $stockTake->total_variance_qty,
                'variance_cost' => number_format($stockTake->total_variance_cost, 2),
            ]
        ]);
    }

    public function complete(Request $request, $id)
    {
        $stockTake = StockTake::with('items')->findOrFail($id);

        if ($stockTake->status !== 'in_progress') {
            return back()->withErrors(['error' => 'This stock take has already been closed.']);
        }

        DB::transaction(function () use ($stockTake) {
            // Apply physical quantities to actual branch Stock table!
            foreach ($stockTake->items as $item) {
                // Find or create branch stock
                $stock = Stock::firstOrCreate(
                    [
                        'branch_id' => $stockTake->branch_id,
                        'product_variation_id' => $item->product_variation_id,
                    ],
                    [
                        'quantity' => 0,
                        'reorder_level' => 5,
                    ]
                );

                // Set stock to the physically verified quantity
                $stock->update(['quantity' => $item->physical_qty]);
            }

            $totalPhysical = $stockTake->items()->sum('physical_qty');
            $totalVariance = $stockTake->items()->sum('variance_qty');
            $totalVarianceCost = $stockTake->items()->sum('variance_cost');

            $stockTake->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_physical_qty' => $totalPhysical,
                'total_variance_qty' => $totalVariance,
                'total_variance_cost' => $totalVarianceCost,
            ]);
        });

        return redirect()->route('inventory.stock_takes.show', $stockTake->id)
            ->with('status', 'Stock Take session reconciled and inventory successfully updated to physically audited counts.');
    }

    public function printReport($id)
    {
        $stockTake = StockTake::with(['branch', 'user', 'items.productVariation.product'])->findOrFail($id);

        return view('inventory.stock_takes.print', compact('stockTake'));
    }
}

