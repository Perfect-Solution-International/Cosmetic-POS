<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ProductSerial;
use App\Models\ProductVariation;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('status', 'active')->get();
        $selectedBranchId = $request->query('branch_id', Auth::user()->branch_id ?? $branches->first()?->id);

        $stocksQuery = Stock::with(['productVariation.product.category', 'branch'])
            ->where('branch_id', $selectedBranchId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $stocksQuery->whereHas('productVariation', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->boolean('low_stock')) {
            $stocksQuery->whereRaw('quantity <= reorder_level');
        }

        $stocks = $stocksQuery->paginate(15);

        // Valuation calculations
        $totalItemsCount = Stock::where('branch_id', $selectedBranchId)->sum('quantity');
        $totalCostValuation = Stock::where('branch_id', $selectedBranchId)
            ->join('product_variations', 'stocks.product_variation_id', '=', 'product_variations.id')
            ->sum(DB::raw('stocks.quantity * product_variations.purchase_cost'));

        $totalRetailValuation = Stock::where('branch_id', $selectedBranchId)
            ->join('product_variations', 'stocks.product_variation_id', '=', 'product_variations.id')
            ->sum(DB::raw('stocks.quantity * product_variations.selling_price'));

        return view('inventory.index', compact(
            'stocks',
            'branches',
            'selectedBranchId',
            'totalItemsCount',
            'totalCostValuation',
            'totalRetailValuation'
        ));
    }

    public function batchExpiry(Request $request)
    {
        $branches = Branch::where('status', 'active')->get();
        $selectedBranchId = $request->query('branch_id');

        $query = Stock::with(['productVariation.product.brand', 'branch'])
            ->whereNotNull('exp_date');

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        $allBatches = $query->orderBy('exp_date', 'asc')->paginate(15);

        $nearExpiryItems = Stock::with(['productVariation.product', 'branch'])
            ->whereNotNull('exp_date')
            ->whereBetween('exp_date', [now(), now()->addDays(90)])
            ->get();

        $expiredItems = Stock::with(['productVariation.product', 'branch'])
            ->whereNotNull('exp_date')
            ->where('exp_date', '<', now())
            ->get();

        return view('inventory.batch_expiry', compact('allBatches', 'nearExpiryItems', 'expiredItems', 'branches', 'selectedBranchId'));
    }

    public function transfers()
    {
        $transfers = StockTransfer::with(['fromBranch', 'toBranch', 'items.productVariation.product', 'requester'])
            ->latest()
            ->paginate(15);

        $branches = Branch::where('status', 'active')->get();
        $variations = ProductVariation::with('product')->where('is_active', true)->get();

        return view('inventory.transfers', compact('transfers', 'branches', 'variations'));
    }

    public function storeTransfer(Request $request)
    {
        $validated = $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'transfer_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_variation_id' => 'required|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $transferNo = 'TRF-' . date('Ymd') . '-' . str_pad(StockTransfer::count() + 1, 4, '0', STR_PAD_LEFT);

            $transfer = StockTransfer::create([
                'transfer_no' => $transferNo,
                'from_branch_id' => $validated['from_branch_id'],
                'to_branch_id' => $validated['to_branch_id'],
                'transfer_date' => $validated['transfer_date'],
                'status' => 'in_transit',
                'requested_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'notes' => $request->input('notes'),
            ]);

            foreach ($validated['items'] as $item) {
                $transfer->items()->create([
                    'product_variation_id' => $item['product_variation_id'],
                    'quantity_requested' => $item['quantity'],
                    'quantity_sent' => $item['quantity'],
                ]);

                // Deduct from source branch
                Stock::where('branch_id', $validated['from_branch_id'])
                    ->where('product_variation_id', $item['product_variation_id'])
                    ->decrement('quantity', $item['quantity']);
            }
        });

        return redirect()->route('inventory.transfers')
            ->with('status', 'Inter-branch stock transfer initiated. Goods are currently in-transit.');
    }

    public function receiveTransfer($id)
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);

        if ($transfer->status !== 'in_transit') {
            return back()->withErrors(['error' => 'This transfer is not in transit.']);
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $item->update(['quantity_received' => $item->quantity_sent]);

                // Add to destination branch
                $destStock = Stock::firstOrNew([
                    'branch_id' => $transfer->to_branch_id,
                    'product_variation_id' => $item->product_variation_id,
                    'batch_number' => $item->batch_number,
                ]);

                $destStock->quantity = ($destStock->quantity ?? 0) + $item->quantity_sent;
                $destStock->save();
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => Auth::id(),
            ]);
        });

        return redirect()->route('inventory.transfers')
            ->with('status', 'Stock transfer confirmed and added to destination branch inventory.');
    }

    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_variation_id' => 'required|exists:product_variations,id',
            'type' => 'required|in:addition,subtraction,damaged,expired,audit_discrepancy',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $adjNo = 'ADJ-' . date('Ymd') . '-' . str_pad(StockAdjustment::count() + 1, 4, '0', STR_PAD_LEFT);

            StockAdjustment::create([
                'adjustment_no' => $adjNo,
                'branch_id' => $validated['branch_id'],
                'product_variation_id' => $validated['product_variation_id'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'adjusted_by' => Auth::id(),
            ]);

            $stock = Stock::where('branch_id', $validated['branch_id'])
                ->where('product_variation_id', $validated['product_variation_id'])
                ->first();

            if ($stock) {
                if ($validated['type'] === 'addition') {
                    $stock->increment('quantity', $validated['quantity']);
                } else {
                    $stock->decrement('quantity', min($stock->quantity, $validated['quantity']));
                }
            }
        });

        return back()->with('status', 'Stock audit adjustment recorded.');
    }

    public function barcodes(Request $request)
    {
        $branches = Branch::where('status', 'active')->get();
        $variations = ProductVariation::with(['product.brand', 'attributeValues.attribute', 'stocks'])
            ->where('is_active', true)
            ->get();

        $selectedVariationId = $request->query('variation_id');
        $labelQty = max(1, min(100, (int)$request->query('qty', 12)));
        $selectedVariation = $selectedVariationId ? ProductVariation::with(['product.brand', 'stocks'])->find($selectedVariationId) : $variations->first();

        return view('inventory.barcodes', compact('variations', 'selectedVariation', 'labelQty', 'branches'));
    }

    public function serials(Request $request)
    {
        $branches = Branch::where('status', 'active')->get();
        $selectedBranchId = $request->query('branch_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = ProductSerial::with(['productVariation.product', 'branch', 'sale.customer']);

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhereHas('productVariation', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%")
                         ->orWhereHas('product', function ($pq) use ($search) {
                             $pq->where('name', 'like', "%{$search}%");
                         });
                  })
                  ->orWhereHas('sale', function ($sq) use ($search) {
                      $sq->where('invoice_no', 'like', "%{$search}%");
                  });
            });
        }

        $serials = $query->latest()->paginate(20);

        // Summary counts
        $totalCount = ProductSerial::count();
        $inStockCount = ProductSerial::where('status', 'in_stock')->count();
        $soldCount = ProductSerial::where('status', 'sold')->count();
        $underWarrantyCount = ProductSerial::where('status', 'sold')
            ->whereNotNull('warranty_expires_at')
            ->where('warranty_expires_at', '>=', now()->toDateString())
            ->count();

        // Variations available for adding serials
        $variations = ProductVariation::with('product')
            ->where('is_active', true)
            ->get();

        return view('inventory.serials', compact(
            'serials',
            'branches',
            'selectedBranchId',
            'status',
            'search',
            'totalCount',
            'inStockCount',
            'soldCount',
            'underWarrantyCount',
            'variations'
        ));
    }

    public function storeSerials(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_variation_id' => 'required|exists:product_variations,id',
            'serials_text' => 'required|string',
            'purchase_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $rawSerials = preg_split('/[\r\n,]+/', $validated['serials_text']);
        $cleanedSerials = array_filter(array_map('trim', $rawSerials));
        $uniqueSerials = array_unique($cleanedSerials);

        if (empty($uniqueSerials)) {
            return back()->withErrors(['serials_text' => 'Please provide at least one valid serial/IMEI number.']);
        }

        // Check if any serial already exists
        $existing = ProductSerial::whereIn('serial_number', $uniqueSerials)->pluck('serial_number')->toArray();
        if (!empty($existing)) {
            return back()->withErrors([
                'serials_text' => 'The following serial number(s) already exist in the system: ' . implode(', ', array_slice($existing, 0, 5)) . (count($existing) > 5 ? ' and more...' : '')
            ]);
        }

        $variation = ProductVariation::findOrFail($validated['product_variation_id']);
        $cost = $validated['purchase_cost'] ?? $variation->purchase_cost;
        $count = 0;

        DB::transaction(function () use ($uniqueSerials, $validated, $variation, $cost, &$count) {
            foreach ($uniqueSerials as $serialNum) {
                ProductSerial::create([
                    'product_variation_id' => $variation->id,
                    'branch_id' => $validated['branch_id'],
                    'serial_number' => $serialNum,
                    'status' => 'in_stock',
                    'purchase_cost' => $cost,
                    'warranty_months' => $variation->warranty_months,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $count++;
            }

            // Also increment branch stock quantity
            $stock = Stock::firstOrCreate(
                [
                    'branch_id' => $validated['branch_id'],
                    'product_variation_id' => $variation->id,
                ],
                [
                    'quantity' => 0,
                    'reorder_level' => 5,
                ]
            );
            $stock->increment('quantity', $count);

            // Mark variation as tracking serial if not already
            if (!$variation->track_serial) {
                $variation->update(['track_serial' => true]);
            }
        });

        return redirect()->route('inventory.serials')
            ->with('status', "Successfully added {$count} unique Serial / IMEI numbers to branch stock.");
    }

    /**
     * Compute unified data for Weekly Reorder & Batch Expiry Audit
     */
    protected function getReorderExpiryData(Request $request): array
    {
        $branches = Branch::where('status', 'active')->get();
        $selectedBranchId = $request->query('branch_id');
        $selectedBranch = $selectedBranchId ? Branch::find($selectedBranchId) : null;
        $branchName = $selectedBranch ? $selectedBranch->name : 'All Branches & Warehouses';

        // 1. Stock Reorder Matrix (Items at or below reorder threshold)
        $reorderItems = DB::table('stocks')
            ->join('product_variations', 'stocks.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->join('branches', 'stocks.branch_id', '=', 'branches.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->when($selectedBranchId, fn($q) => $q->where('stocks.branch_id', $selectedBranchId))
            ->whereRaw('stocks.quantity <= COALESCE(stocks.reorder_level, product_variations.reorder_level, 5)')
            ->select(
                'stocks.id as stock_id',
                'stocks.batch_number',
                'stocks.quantity as current_stock',
                DB::raw('COALESCE(stocks.reorder_level, product_variations.reorder_level, 5) as reorder_threshold'),
                'product_variations.id as variation_id',
                'product_variations.name as variation_name',
                'product_variations.sku',
                'product_variations.purchase_cost',
                'product_variations.selling_price',
                'products.name as product_name',
                'categories.name as category_name',
                'brands.name as brand_name',
                'branches.name as branch_name',
                'branches.id as branch_id'
            )
            ->orderBy('stocks.quantity', 'asc')
            ->get()
            ->map(function ($item) {
                $threshold = (float) $item->reorder_threshold;
                $current = (float) $item->current_stock;
                $shortage = max(0, $threshold - $current);
                $suggestedOrder = max($shortage + 10, $threshold * 2);
                $cost = (float) $item->purchase_cost;
                $estTotalCost = $suggestedOrder * $cost;
                $urgency = ($current <= 0) ? 'OUT_OF_STOCK' : (($current <= ($threshold * 0.5)) ? 'CRITICAL' : 'REORDER_NOW');

                return (object) array_merge((array) $item, [
                    'shortage' => $shortage,
                    'suggested_order' => $suggestedOrder,
                    'est_total_cost' => $estTotalCost,
                    'urgency' => $urgency,
                ]);
            });

        $totalReorderEstCost = $reorderItems->sum('est_total_cost');
        $outOfStockCount = $reorderItems->where('urgency', 'OUT_OF_STOCK')->count();
        $criticalReorderCount = $reorderItems->where('urgency', 'CRITICAL')->count();

        // 2. Batch Expiry Matrix (Expiring within 90 days or already expired)
        $expiringBatches = Stock::with(['productVariation.product', 'branch'])
            ->whereNotNull('exp_date')
            ->when($selectedBranchId, fn($q) => $q->where('branch_id', $selectedBranchId))
            ->where('exp_date', '<=', now()->addDays(90))
            ->orderBy('exp_date', 'asc')
            ->get()
            ->map(function ($stock) {
                $expDate = \Carbon\Carbon::parse($stock->exp_date);
                $daysLeft = (int) now()->diffInDays($expDate, false);
                $isExpired = $daysLeft < 0;
                $costPrice = (float) ($stock->productVariation->purchase_cost ?? 0);
                $costAtRisk = (float) ($stock->quantity * $costPrice);

                return (object) [
                    'stock_id' => $stock->id,
                    'product_name' => $stock->productVariation->product->name ?? 'Product',
                    'variation_name' => $stock->productVariation->name ?? '',
                    'sku' => $stock->productVariation->sku ?? 'N/A',
                    'branch_name' => $stock->branch->name ?? 'Main',
                    'batch_number' => $stock->batch_number ?? 'BATCH-DEFAULT',
                    'exp_date' => $expDate->format('Y-m-d'),
                    'days_left' => $daysLeft,
                    'is_expired' => $isExpired,
                    'quantity' => (float) $stock->quantity,
                    'cost_price' => $costPrice,
                    'cost_at_risk' => $costAtRisk,
                    'status_label' => $isExpired ? 'EXPIRED' : ($daysLeft <= 30 ? 'CRITICAL (30 Days)' : ($daysLeft <= 60 ? 'HIGH (60 Days)' : 'MEDIUM (90 Days)')),
                ];
            });

        $totalExpiredCost = $expiringBatches->where('is_expired', true)->sum('cost_at_risk');
        $totalNearExpiryCost = $expiringBatches->where('is_expired', false)->sum('cost_at_risk');

        return compact(
            'branches',
            'selectedBranchId',
            'selectedBranch',
            'branchName',
            'reorderItems',
            'totalReorderEstCost',
            'outOfStockCount',
            'criticalReorderCount',
            'expiringBatches',
            'totalExpiredCost',
            'totalNearExpiryCost'
        );
    }

    /**
     * Weekly Stock Reorder & Batch Expiry Report Screen
     */
    public function weeklyReorderExpiryReport(Request $request)
    {
        $data = $this->getReorderExpiryData($request);

        return view('inventory.reorder_expiry', $data);
    }

    /**
     * Printable A4 Formal Stock Reorder & Expiry Audit Sheet
     */
    public function printWeeklyReorderExpiryReport(Request $request)
    {
        $data = $this->getReorderExpiryData($request);

        return view('inventory.reorder_expiry_print', $data);
    }

    /**
     * Export Weekly Stock Reorder & Expiry Report as Native Excel (.xlsx)
     */
    public function exportWeeklyReorderExpiryExcel(Request $request)
    {
        $data = $this->getReorderExpiryData($request);
        $writer = new \App\Services\SimpleXlsxWriter();

        // 1. Title Banner
        $writer->addRow([
            ['value' => 'PSI ONE GLOBAL POS - WEEKLY STOCK REORDER & BATCH EXPIRY AUDIT', 'style' => 2],
            ['value' => '', 'style' => 2],
            ['value' => '', 'style' => 2],
            ['value' => '', 'style' => 2],
        ]);
        $writer->addRow([['value' => 'Branch / Location: ' . $data['branchName'], 'style' => 1]]);
        $writer->addRow([['value' => 'Audit Date: ' . date('Y-m-d H:i:s') . ' | Generated By: ' . (auth()->user()->name ?? 'Administrator'), 'style' => 0]]);
        $writer->addRow([['value' => 'Estimated Total Replenishment Cost: LKR ' . number_format($data['totalReorderEstCost'], 2) . ' | Inventory at Expiry Risk: LKR ' . number_format($data['totalExpiredCost'] + $data['totalNearExpiryCost'], 2), 'style' => 1]]);
        $writer->addRow([]);

        // 2. Section 1: Stock Reorder Schedule
        $writer->addRow([
            ['value' => 'SECTION 1: LOW STOCK REPLENISHMENT & REORDER SCHEDULE', 'style' => 3],
            ['value' => 'SKU', 'style' => 3],
            ['value' => 'Branch', 'style' => 3],
            ['value' => 'Current Stock', 'style' => 3],
            ['value' => 'Reorder Level', 'style' => 3],
            ['value' => 'Shortage Qty', 'style' => 3],
            ['value' => 'Suggested Order Qty', 'style' => 3],
            ['value' => 'Unit Cost (LKR)', 'style' => 3],
            ['value' => 'Est. Order Cost (LKR)', 'style' => 3],
            ['value' => 'Priority Urgency', 'style' => 3],
        ]);

        if ($data['reorderItems']->isEmpty()) {
            $writer->addRow([
                ['value' => 'All product stocks are healthy and above reorder thresholds.', 'style' => 0],
            ]);
        } else {
            foreach ($data['reorderItems'] as $item) {
                $urgencyLabel = match($item->urgency) {
                    'OUT_OF_STOCK' => 'OUT OF STOCK (URGENT)',
                    'CRITICAL' => 'CRITICAL (<50% threshold)',
                    default => 'REORDER NOW'
                };
                $urgencyStyle = match($item->urgency) {
                    'OUT_OF_STOCK' => 7,
                    'CRITICAL' => 7,
                    default => 1
                };

                $writer->addRow([
                    ['value' => $item->product_name . ($item->variation_name ? ' - ' . $item->variation_name : ''), 'style' => 1],
                    ['value' => $item->sku, 'style' => 0],
                    ['value' => $item->branch_name, 'style' => 0],
                    ['value' => $item->current_stock, 'style' => $item->current_stock <= 0 ? 7 : 0, 'type' => 'number'],
                    ['value' => $item->reorder_threshold, 'style' => 0, 'type' => 'number'],
                    ['value' => $item->shortage, 'style' => 0, 'type' => 'number'],
                    ['value' => $item->suggested_order, 'style' => 1, 'type' => 'number'],
                    ['value' => (float)$item->purchase_cost, 'style' => 4, 'type' => 'number'],
                    ['value' => (float)$item->est_total_cost, 'style' => 5, 'type' => 'number'],
                    ['value' => $urgencyLabel, 'style' => $urgencyStyle],
                ]);
            }
        }

        $writer->addRow([]);
        $writer->addRow([
            ['value' => 'TOTAL ESTIMATED REPLENISHMENT PURCHASE COST', 'style' => 1],
            ['value' => '', 'style' => 1],
            ['value' => '', 'style' => 1],
            ['value' => '', 'style' => 1],
            ['value' => '', 'style' => 1],
            ['value' => '', 'style' => 1],
            ['value' => '', 'style' => 1],
            ['value' => '', 'style' => 1],
            ['value' => (float)$data['totalReorderEstCost'], 'style' => 9, 'type' => 'number'],
        ]);
        $writer->addRow([]);

        // 3. Section 2: Batch Expiry Risk Schedule
        $writer->addRow([
            ['value' => 'SECTION 2: BATCH EXPIRY RISK & QUARANTINE SCHEDULE (WITHIN 90 DAYS)', 'style' => 3],
            ['value' => 'SKU', 'style' => 3],
            ['value' => 'Branch', 'style' => 3],
            ['value' => 'Batch Number', 'style' => 3],
            ['value' => 'Expiry Date', 'style' => 3],
            ['value' => 'Days Left', 'style' => 3],
            ['value' => 'Quantity', 'style' => 3],
            ['value' => 'Unit Cost (LKR)', 'style' => 3],
            ['value' => 'Cost At Risk (LKR)', 'style' => 3],
            ['value' => 'Risk Classification', 'style' => 3],
        ]);

        if ($data['expiringBatches']->isEmpty()) {
            $writer->addRow([
                ['value' => 'No batches are expired or expiring within 90 days. Stock is fresh.', 'style' => 0],
            ]);
        } else {
            foreach ($data['expiringBatches'] as $batch) {
                $riskStyle = $batch->is_expired ? 7 : ($batch->days_left <= 30 ? 7 : 1);
                $writer->addRow([
                    ['value' => $batch->product_name . ($batch->variation_name ? ' - ' . $batch->variation_name : ''), 'style' => 1],
                    ['value' => $batch->sku, 'style' => 0],
                    ['value' => $batch->branch_name, 'style' => 0],
                    ['value' => $batch->batch_number, 'style' => 0],
                    ['value' => $batch->exp_date, 'style' => 0],
                    ['value' => $batch->days_left, 'style' => $riskStyle, 'type' => 'number'],
                    ['value' => $batch->quantity, 'style' => 0, 'type' => 'number'],
                    ['value' => (float)$batch->cost_price, 'style' => 4, 'type' => 'number'],
                    ['value' => (float)$batch->cost_at_risk, 'style' => 5, 'type' => 'number'],
                    ['value' => $batch->status_label, 'style' => $riskStyle],
                ]);
            }
        }

        $filename = "Weekly_Stock_Reorder_Expiry_Report_" . date('Y_m_d_His') . ".xlsx";
        $tempPath = tempnam(sys_get_temp_dir(), 'reorder_xlsx_');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    public function warrantyLookup(Request $request)
    {
        $serialNumber = trim($request->query('serial', ''));
        $record = null;

        if ($serialNumber !== '') {
            $record = ProductSerial::with(['productVariation.product', 'branch', 'sale.customer', 'sale.cashier'])
                ->where('serial_number', $serialNumber)
                ->first();
        }

        if ($request->wantsJson() || $request->ajax()) {
            if (!$record) {
                return response()->json([
                    'found' => false,
                    'message' => 'Serial number / IMEI not found in registry.'
                ], 404);
            }

            return response()->json([
                'found' => true,
                'serial' => $record->serial_number,
                'status' => $record->status,
                'product_name' => $record->productVariation->product->name . ' - ' . $record->productVariation->name,
                'sku' => $record->productVariation->sku,
                'branch' => $record->branch->name,
                'sold_at' => $record->sold_at ? $record->sold_at->format('Y-m-d H:i') : null,
                'invoice_no' => $record->sale?->invoice_no,
                'customer_name' => $record->sale?->customer?->name ?? 'Walk-in Customer',
                'customer_phone' => $record->sale?->customer?->phone,
                'warranty_months' => $record->warranty_months,
                'warranty_expires_at' => $record->warranty_expires_at ? $record->warranty_expires_at->format('Y-m-d') : null,
                'is_under_warranty' => $record->is_under_warranty,
            ]);
        }

        return view('inventory.warranty_lookup', compact('serialNumber', 'record'));
    }
}

