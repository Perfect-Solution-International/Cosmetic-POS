<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GoodsReceivedNote;
use App\Models\ProductVariation;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcurementController extends Controller
{
    public function suppliers()
    {
        $suppliers = Supplier::withCount(['purchaseOrders', 'grns'])->get();
        return view('procurement.suppliers', compact('suppliers'));
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'code' => 'required|string|max:50|unique:suppliers,code',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'payment_terms' => 'nullable|string|max:100',
        ]);

        $validated['current_balance'] = $validated['opening_balance'] ?? 0.00;
        Supplier::create($validated);

        return redirect()->route('procurement.suppliers')
            ->with('status', 'Supplier ' . $validated['name'] . ' registered successfully.');
    }

    public function purchaseOrders()
    {
        $orders = PurchaseOrder::with(['supplier', 'branch', 'items.productVariation', 'creator'])->latest()->paginate(15);
        $suppliers = Supplier::where('status', 'active')->get();
        $branches = Branch::where('status', 'active')->get();
        $variations = ProductVariation::with('product')->where('is_active', true)->get();

        return view('procurement.po', compact('orders', 'suppliers', 'branches', 'variations'));
    }

    public function storePo(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'payment_terms' => 'nullable|string|max:100',
            'payment_due_date' => 'nullable|date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'delivery_instructions' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:draft,submitted,approved',
            'items' => 'required|array|min:1',
            'items.*.product_variation_id' => 'required|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.free_quantity' => 'nullable|integer|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $po = DB::transaction(function () use ($validated, $request) {
            $poNumber = 'PO-' . date('Ymd') . '-' . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);
            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $cost = (float) $item['unit_cost'];
                $itemDisc = (float) ($item['discount_percent'] ?? 0);
                $lineSubtotal = $qty * $cost * (1 - ($itemDisc / 100));
                $subtotal += $lineSubtotal;
            }

            $discountPercent = (float) ($validated['discount_percent'] ?? 0);
            $discountAmount = round($subtotal * ($discountPercent / 100), 2);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0);
            $shippingCost = (float) ($validated['shipping_cost'] ?? 0);
            $totalAmount = max(0, ($subtotal - $discountAmount) + $taxAmount + $shippingCost);

            // Default status: If cashier or inventory staff, default to 'draft' or 'submitted'. If manager/admin, default to 'approved'
            $status = $validated['status'] ?? (auth()->user()?->isManager() || auth()->user()?->isAdmin() ? 'approved' : 'submitted');

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'branch_id' => $validated['branch_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'payment_terms' => $validated['payment_terms'] ?? 'Net 30',
                'payment_due_date' => $validated['payment_due_date'] ?? null,
                'delivery_instructions' => $validated['delivery_instructions'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $status,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $freeQty = (int) ($item['free_quantity'] ?? 0);
                $cost = (float) $item['unit_cost'];
                $itemDisc = (float) ($item['discount_percent'] ?? 0);
                $lineSubtotal = $qty * $cost * (1 - ($itemDisc / 100));

                $po->items()->create([
                    'product_variation_id' => $item['product_variation_id'],
                    'quantity_ordered' => $qty,
                    'free_quantity' => $freeQty,
                    'quantity_received' => 0,
                    'unit_cost' => $cost,
                    'discount_percent' => $itemDisc,
                    'subtotal' => round($lineSubtotal, 2),
                ]);
            }

            return $po;
        });

        return redirect()->route('procurement.po.show', $po->id)
            ->with('status', 'Purchase Order ' . $po->po_number . ' successfully created with status: ' . $po->status_label . '.');
    }

    public function showPo($id)
    {
        $po = PurchaseOrder::with([
            'supplier',
            'branch',
            'items.productVariation.product',
            'creator',
            'grns.receiver',
            'grns.items.productVariation',
        ])->findOrFail($id);

        return view('procurement.po_show', compact('po'));
    }

    public function printPo($id)
    {
        $po = PurchaseOrder::with([
            'supplier',
            'branch',
            'items.productVariation.product',
            'creator',
        ])->findOrFail($id);

        return view('procurement.po_print', compact('po'));
    }

    public function updatePoStatus(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:draft,submitted,approved,cancelled',
        ]);

        if (in_array($validated['status'], ['approved', 'cancelled']) && !auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Only Store Managers or Administrators can approve or cancel Purchase Orders.');
        }

        $po->update(['status' => $validated['status']]);

        return redirect()->back()
            ->with('status', 'Purchase Order status updated to ' . $po->status_label . '.');
    }

    public function grnList()
    {
        $grns = GoodsReceivedNote::with(['supplier', 'branch', 'items.productVariation', 'receiver'])->latest()->paginate(15);
        $suppliers = Supplier::where('status', 'active')->get();
        $branches = Branch::where('status', 'active')->get();
        $variations = ProductVariation::with('product')->where('is_active', true)->get();
        $pendingPos = PurchaseOrder::whereIn('status', ['submitted', 'approved', 'partially_received'])->with('items')->get();

        return view('procurement.grn', compact('grns', 'suppliers', 'branches', 'variations', 'pendingPos'));
    }

    public function storeGrn(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_invoice_no' => 'nullable|string|max:100',
            'received_date' => 'required|date',
            'payment_status' => 'nullable|in:unpaid,partially_paid,paid',
            'payment_method' => 'nullable|in:bank_transfer,cash,cheque,credit_card',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_variation_id' => 'required|exists:product_variations,id',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.exp_date' => 'nullable|date',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $message = DB::transaction(function () use ($validated) {
            $grnNumber = 'GRN-' . date('Ymd') . '-' . str_pad(GoodsReceivedNote::count() + 1, 4, '0', STR_PAD_LEFT);
            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $totalAmount += ($item['quantity'] * $item['unit_cost']);
            }

            $paymentStatusInput = $validated['payment_status'] ?? 'unpaid';
            $initialPaid = 0.00;
            if ($paymentStatusInput === 'paid') {
                $initialPaid = $totalAmount;
                $finalStatus = 'paid';
            } elseif ($paymentStatusInput === 'partially_paid') {
                $initialPaid = min($totalAmount, (float) ($validated['paid_amount'] ?? 0));
                $finalStatus = $initialPaid >= $totalAmount ? 'paid' : ($initialPaid > 0 ? 'partially_paid' : 'unpaid');
            } else {
                $finalStatus = 'unpaid';
            }

            $grn = GoodsReceivedNote::create([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'branch_id' => $validated['branch_id'],
                'supplier_invoice_no' => $validated['supplier_invoice_no'] ?? null,
                'received_date' => $validated['received_date'],
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $initialPaid,
                'payment_status' => $finalStatus,
                'payment_method' => $validated['payment_method'] ?? 'bank_transfer',
                'payment_reference' => $validated['payment_reference'] ?? null,
                'paid_date' => $finalStatus !== 'unpaid' ? $validated['received_date'] : null,
                'received_by' => Auth::id(),
            ]);

            // Update Supplier outstanding balance (add unpaid amount)
            $unpaidAmount = max(0, $totalAmount - $initialPaid);
            if ($unpaidAmount > 0) {
                Supplier::find($validated['supplier_id'])->increment('current_balance', $unpaidAmount);
            }

            // If initial payment was made, record payment voucher
            if ($initialPaid > 0) {
                SupplierPayment::create([
                    'payment_no' => 'PAY-' . date('Ymd') . '-' . str_pad(SupplierPayment::count() + 1, 4, '0', STR_PAD_LEFT),
                    'supplier_id' => $validated['supplier_id'],
                    'grn_id' => $grn->id,
                    'amount' => $initialPaid,
                    'payment_method' => $validated['payment_method'] ?? 'bank_transfer',
                    'reference_no' => $validated['payment_reference'] ?? null,
                    'payment_date' => $validated['received_date'],
                    'notes' => 'Initial payment upon GRN receipt.',
                    'created_by' => Auth::id(),
                ]);
            }

            // Record items & update branch stock with batch/expiry
            foreach ($validated['items'] as $item) {
                $grn->items()->create([
                    'product_variation_id' => $item['product_variation_id'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'exp_date' => $item['exp_date'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $item['quantity'] * $item['unit_cost'],
                ]);

                // Increment stock in the designated branch
                $stock = Stock::firstOrNew([
                    'branch_id' => $validated['branch_id'],
                    'product_variation_id' => $item['product_variation_id'],
                    'batch_number' => $item['batch_number'] ?? null,
                ]);

                $stock->exp_date = $item['exp_date'] ?? $stock->exp_date;
                $stock->quantity = ($stock->quantity ?? 0) + $item['quantity'];
                $stock->save();
            }

            // Advanced Partial Receiving & Backorder Tracking
            $statusNotice = 'Goods Received Note (GRN) posted. Inventory stock and batches updated successfully.';
            if (!empty($validated['purchase_order_id'])) {
                $po = PurchaseOrder::with('items')->find($validated['purchase_order_id']);
                if ($po) {
                    foreach ($validated['items'] as $item) {
                        $poItem = $po->items->where('product_variation_id', $item['product_variation_id'])->first();
                        if ($poItem) {
                            $poItem->quantity_received += $item['quantity'];
                            $poItem->save();
                        }
                    }

                    // Re-calculate total ordered vs total received
                    $po->refresh();
                    $totalOrdered = $po->items->sum('quantity_ordered');
                    $totalReceived = $po->items->sum('quantity_received');

                    if ($totalReceived >= $totalOrdered) {
                        $po->update(['status' => 'completed']);
                        $statusNotice = 'Goods Received Note (GRN) posted. Purchase Order ' . $po->po_number . ' is now fully completed.';
                    } else {
                        $po->update(['status' => 'partially_received']);
                        $backorderCount = max(0, $totalOrdered - $totalReceived);
                        $statusNotice = 'Goods Received Note (GRN) posted. Purchase Order ' . $po->po_number . ' partially received. (' . $backorderCount . ' items remaining on backorder).';
                    }
                }
            }

            return $statusNotice;
        });

        return redirect()->route('procurement.grn')
            ->with('status', $message);
    }

    public function recordPayment(Request $request, $id)
    {
        $grn = GoodsReceivedNote::with(['supplier', 'payments'])->findOrFail($id);

        $validated = $request->validate([
            'payment_status' => 'required|in:paid,partially_paid,unpaid',
            'payment_method' => 'required|string|in:bank_transfer,cash,cheque,credit_card',
            'amount' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($grn, $validated) {
            $oldPaidAmount = (float) ($grn->paid_amount ?? 0);
            $totalAmount = (float) $grn->total_amount;

            if ($validated['payment_status'] === 'paid') {
                $newPaidAmount = $totalAmount;
            } elseif ($validated['payment_status'] === 'unpaid') {
                $newPaidAmount = 0.00;
            } else { // partially_paid
                $newPaidAmount = min($totalAmount, (float) ($validated['amount'] ?? $oldPaidAmount));
                if ($newPaidAmount >= $totalAmount) {
                    $validated['payment_status'] = 'paid';
                } elseif ($newPaidAmount <= 0) {
                    $validated['payment_status'] = 'unpaid';
                }
            }

            $diff = $newPaidAmount - $oldPaidAmount;

            // Update Supplier current_balance (outstanding payable balance)
            if ($diff > 0) {
                // Paid to supplier, so liability decreases
                Supplier::find($grn->supplier_id)->decrement('current_balance', $diff);

                // Create SupplierPayment audit record
                SupplierPayment::create([
                    'payment_no' => 'PAY-' . date('Ymd') . '-' . str_pad(SupplierPayment::count() + 1, 4, '0', STR_PAD_LEFT),
                    'supplier_id' => $grn->supplier_id,
                    'grn_id' => $grn->id,
                    'amount' => $diff,
                    'payment_method' => $validated['payment_method'],
                    'reference_no' => $validated['reference_no'] ?? null,
                    'payment_date' => $validated['payment_date'] ?? date('Y-m-d'),
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            } elseif ($diff < 0) {
                // Payment was reversed / refunded / cancelled, payable liability increases
                Supplier::find($grn->supplier_id)->increment('current_balance', abs($diff));
            }

            // Update GRN payment details
            $grn->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $validated['payment_status'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['reference_no'] ?? $grn->payment_reference,
                'paid_date' => $validated['payment_status'] !== 'unpaid' ? ($validated['payment_date'] ?? date('Y-m-d')) : null,
            ]);
        });

        $statusLabel = match($validated['payment_status']) {
            'paid' => 'Paid (Full Payment)',
            'partially_paid' => 'Partially Paid',
            default => 'Unpaid',
        };

        return redirect()->route('procurement.grn')
            ->with('status', 'Payment status for ' . $grn->grn_number . ' updated to ' . $statusLabel . ' via ' . $grn->payment_method_label . '.');
    }

    public function purchaseReturns(Request $request)
    {
        $returns = PurchaseReturn::with(['supplier', 'branch', 'grn', 'items.productVariation.product', 'creator'])
            ->latest()
            ->paginate(15);

        $suppliers = Supplier::where('status', 'active')->get();
        $branches = Branch::where('status', 'active')->get();
        $variations = ProductVariation::with('product')->where('is_active', true)->get();
        $grns = GoodsReceivedNote::latest()->take(30)->get();

        return view('procurement.returns', compact('returns', 'suppliers', 'branches', 'variations', 'grns'));
    }

    public function storePurchaseReturn(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'grn_id' => 'nullable|exists:goods_received_notes,id',
            'credit_note_no' => 'nullable|string|max:100',
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_variation_id' => 'required|exists:product_variations,id',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $purchaseReturn = DB::transaction(function () use ($validated) {
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += ($item['quantity'] * $item['unit_cost']);
            }

            $returnNo = 'RET-SUP-' . date('Ymd') . '-' . str_pad(PurchaseReturn::count() + 1, 4, '0', STR_PAD_LEFT);

            $return = PurchaseReturn::create([
                'return_no' => $returnNo,
                'supplier_id' => $validated['supplier_id'],
                'branch_id' => $validated['branch_id'],
                'grn_id' => $validated['grn_id'] ?? null,
                'total_amount' => $totalAmount,
                'reason' => $validated['reason'],
                'credit_note_no' => $validated['credit_note_no'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_cost'];
                $return->items()->create([
                    'product_variation_id' => $item['product_variation_id'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);

                // Deduct stock from branch
                $stock = Stock::where('branch_id', $validated['branch_id'])
                    ->where('product_variation_id', $item['product_variation_id'])
                    ->first();

                if ($stock) {
                    $stock->decrement('quantity', min($stock->quantity, $item['quantity']));
                }
            }

            // Deduct liability to supplier (Debit Note)
            Supplier::find($validated['supplier_id'])->decrement('current_balance', $totalAmount);

            return $return;
        });

        return redirect()->route('procurement.returns')
            ->with('status', 'Supplier Return ' . $purchaseReturn->return_no . ' recorded. Debit Note of Rs. ' . number_format($purchaseReturn->total_amount, 2) . ' deducted from supplier balance.');
    }

    public function printPurchaseReturn($id)
    {
        $return = PurchaseReturn::with(['supplier', 'branch', 'grn', 'items.productVariation.product', 'creator'])->findOrFail($id);
        return view('procurement.returns_print', compact('return'));
    }
}

