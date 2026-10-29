<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('status', 'active')->get();
        $query = Sale::with(['branch', 'register', 'cashier', 'customer', 'payments']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('sales_channel') && $request->input('sales_channel') !== 'all') {
            $query->where('sales_channel', $request->input('sales_channel'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->latest()->paginate(15);

        return view('sales.index', compact('sales', 'branches'));
    }

    public function show($id)
    {
        $sale = Sale::with(['branch', 'register', 'cashier', 'customer', 'items.productVariation', 'payments', 'returns.items'])
            ->findOrFail($id);

        return view('sales.show', compact('sale'));
    }

    public function returnSale(Request $request, $id)
    {
        $sale = Sale::with('items')->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'action' => 'required|in:restock,damaged',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($sale, $validated) {
            $returnNo = 'RET-' . date('Ymd') . '-' . str_pad(SaleReturn::count() + 1, 4, '0', STR_PAD_LEFT);
            $totalRefund = 0;

            $saleReturn = SaleReturn::create([
                'return_no' => $returnNo,
                'sale_id' => $sale->id,
                'branch_id' => $sale->branch_id,
                'cashier_id' => Auth::id(),
                'total_refund_amount' => 0,
                'reason' => $validated['reason'],
                'action' => $validated['action'],
            ]);

            foreach ($validated['items'] as $itemData) {
                $saleItem = $sale->items->firstWhere('id', $itemData['item_id']);
                if (!$saleItem) continue;

                $refundSubtotal = $itemData['quantity'] * $saleItem->unit_price;
                $totalRefund += $refundSubtotal;

                $saleReturn->items()->create([
                    'product_variation_id' => $saleItem->product_variation_id,
                    'quantity' => $itemData['quantity'],
                    'refund_price' => $saleItem->unit_price,
                    'subtotal' => $refundSubtotal,
                ]);

                // If restock, increment branch stock
                if ($validated['action'] === 'restock') {
                    Stock::where('branch_id', $sale->branch_id)
                        ->where('product_variation_id', $saleItem->product_variation_id)
                        ->increment('quantity', $itemData['quantity']);
                }
            }

            $saleReturn->update(['total_refund_amount' => $totalRefund]);
            $sale->update(['payment_status' => 'refunded']);
        });

        return redirect()->route('sales.show', $sale->id)
            ->with('status', 'Sale return & refund processed successfully.');
    }
}

