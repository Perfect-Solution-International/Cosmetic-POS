<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\HeldSale;
use App\Models\HeldSaleItem;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductVariation;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PosTerminalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = $user->branch_id ?? Branch::where('status', 'active')->first()?->id;
        $branch = Branch::with('registers')->find($branchId);

        // Check active cashier shift for this user or register
        $activeShift = CashierShift::with('register')
            ->where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        // Products with active variations & branch stocks (optimized column selection)
        $products = Product::select('id', 'name', 'code', 'category_id', 'brand_id', 'type', 'has_expiry', 'status')
            ->with([
                'category:id,name',
                'brand:id,name',
                'variations' => function ($vq) use ($branchId) {
                    $vq->where('is_active', true)
                        ->select('id', 'product_id', 'name', 'sku', 'barcode', 'purchase_cost', 'selling_price', 'wholesale_price', 'track_serial', 'warranty_months')
                        ->with([
                            'stocks' => function ($sq) use ($branchId) {
                                $sq->where('branch_id', $branchId)->select('id', 'branch_id', 'product_variation_id', 'quantity');
                            },
                            'serials' => function ($serQ) use ($branchId) {
                                $serQ->where('branch_id', $branchId)->where('status', 'in_stock')->select('id', 'product_variation_id', 'branch_id', 'serial_number');
                            },
                            'attributeValues:id,attribute_id,value',
                            'attributeValues.attribute:id,name'
                        ]);
                }
            ])
            ->where('status', 'active')
            ->get();

        $categories = $products->pluck('category.name')->filter()->unique()->values();
        $customers = Customer::select('id', 'name', 'phone', 'email', 'credit_limit', 'current_balance', 'loyalty_points', 'pricing_tier')->get();
        $groups = CustomerGroup::select('id', 'name', 'discount_percentage')->get();

        return view('pos.terminal', compact(
            'user',
            'branch',
            'activeShift',
            'products',
            'categories',
            'customers',
            'groups'
        ));
    }

    public function startShift(Request $request)
    {
        $validated = $request->validate([
            'register_id' => 'required|exists:registers,id',
            'opening_float' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $branchId = $user->branch_id ?? Register::find($validated['register_id'])->branch_id;

        $shift = CashierShift::create([
            'branch_id' => $branchId,
            'register_id' => $validated['register_id'],
            'user_id' => $user->id,
            'opening_float' => $validated['opening_float'],
            'expected_cash' => $validated['opening_float'],
            'status' => 'open',
            'opened_at' => now(),
        ]);

        Register::find($validated['register_id'])->update(['status' => 'open']);

        return redirect()->route('pos.terminal')
            ->with('status', 'Cashier Shift opened with float Rs. ' . number_format($validated['opening_float'], 2));
    }

    public function closeShift(Request $request, $id)
    {
        $validated = $request->validate([
            'counted_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $shift = CashierShift::findOrFail($id);

        $expected = $shift->opening_float + $shift->cash_sales;
        $discrepancy = $validated['counted_cash'] - $expected;

        $shift->update([
            'closing_cash' => $validated['counted_cash'],
            'expected_cash' => $expected,
            'cash_discrepancy' => $discrepancy,
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => $validated['notes'],
        ]);

        $shift->register->update(['status' => 'closed']);

        return redirect()->route('pos.terminal')
            ->with('status', 'Shift closed. Z-Report generated with cash discrepancy: Rs. ' . number_format($discrepancy, 2));
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'pricing_mode' => 'nullable|in:retail,wholesale',
            'redeem_points' => 'nullable|integer|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'held_sale_id' => 'nullable|exists:held_sales,id',
            'items' => 'required|array|min:1',
            'items.*.variation_id' => 'required|exists:product_variations,id',
            'items.*.quantity' => 'required|integer',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.is_exchange' => 'nullable|boolean',
            'items.*.exchange_reason' => 'nullable|string|max:255',
            'items.*.serials' => 'nullable|array',
            'items.*.serials.*' => 'string',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,bank_transfer,loyalty_points,credit,qr',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.tender_amount' => 'nullable|numeric',
        ]);

        $user = Auth::user();
        $branchId = $user->branch_id ?? Branch::first()->id;

        // Active shift
        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        $registerId = $activeShift?->register_id ?? Register::where('branch_id', $branchId)->first()?->id ?? 1;

        $sale = DB::transaction(function () use ($validated, $branchId, $registerId, $user, $activeShift) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $variation = ProductVariation::find($item['variation_id']);
                $qty = (int) $item['quantity'];
                $isExchange = !empty($item['is_exchange']) || $qty < 0;
                if ($isExchange && $qty > 0) {
                    $qty = -$qty;
                }
                $itemSubtotal = $qty * (float) $item['unit_price'];
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'product_variation_id' => $variation->id,
                    'quantity' => $qty,
                    'purchase_cost' => $variation->purchase_cost ?? 0.00, // Historical COGS
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $itemSubtotal,
                    'is_exchange' => $isExchange,
                    'exchange_reason' => $item['exchange_reason'] ?? ($isExchange ? 'Customer Item Exchange / Return' : null),
                    'serial_numbers' => !empty($item['serials']) ? (array) $item['serials'] : null,
                ];
            }

            // Standard Discount
            $discountAmount = $validated['discount_amount'] ?? 0.00;
            if (($validated['discount_type'] ?? 'fixed') === 'percentage') {
                $discountAmount = ($subtotal * ($validated['discount_amount'] ?? 0)) / 100;
            }

            // Customer Loyalty Points Redemption (1 point = Rs. 1.00)
            $customer = !empty($validated['customer_id']) ? Customer::find($validated['customer_id']) : null;
            $redeemPoints = (int) ($validated['redeem_points'] ?? 0);
            
            // Check if loyalty_points was provided in payments array instead of top-level
            $cleanPayments = [];
            foreach ($validated['payments'] as $p) {
                if ($p['method'] === 'loyalty_points') {
                    if ($redeemPoints <= 0) {
                        $redeemPoints = (int) $p['amount'];
                    }
                } else {
                    $cleanPayments[] = $p;
                }
            }

            $loyaltyDiscount = 0.00;
            if ($redeemPoints > 0 && $customer && $customer->loyalty_points > 0) {
                $actualPoints = min($customer->loyalty_points, $redeemPoints);
                $loyaltyDiscount = (float) $actualPoints;
                $customer->decrement('loyalty_points', $actualPoints);
            }

            $taxRate = $validated['tax_rate'] ?? 0.00;
            $taxableSubtotal = max(0, $subtotal - $discountAmount - $loyaltyDiscount);
            $taxAmount = ($taxableSubtotal * $taxRate) / 100;
            $grandTotal = max(0, $taxableSubtotal + $taxAmount);

            // If cleanPayments is empty because entire bill was paid with loyalty points
            if (empty($cleanPayments) && $grandTotal <= 0) {
                $cleanPayments[] = ['method' => 'cash', 'amount' => 0.00, 'tender_amount' => 0.00];
            }

            $paidAmount = collect($cleanPayments)->sum('amount');
            $tenderAmount = collect($cleanPayments)->sum('tender_amount') ?: $paidAmount;
            $changeAmount = max(0, $tenderAmount - $grandTotal);

            $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 5, '0', STR_PAD_LEFT);

            // Determine payment status
            $hasCreditPayment = collect($validated['payments'])->contains('method', 'credit');
            $paymentStatus = $hasCreditPayment ? 'partial' : 'paid';

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'branch_id' => $branchId,
                'register_id' => $registerId,
                'customer_id' => $validated['customer_id'] ?? null,
                'cashier_id' => $user->id,
                'shift_id' => $activeShift?->id,
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'discount_amount' => $discountAmount,
                'loyalty_points_redeemed' => $redeemPoints,
                'loyalty_discount_amount' => $loyaltyDiscount,
                'pricing_mode' => $validated['pricing_mode'] ?? 'retail',
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_status' => $paymentStatus,
            ]);

            // Save items & handle inventory adjustments (Deductions vs Return Inflow)
            foreach ($itemsData as $itemRow) {
                $serialsForLine = $itemRow['serial_numbers'] ?? null;
                $createdItem = $sale->items()->create($itemRow);

                if (!empty($serialsForLine)) {
                    $variation = ProductVariation::find($itemRow['product_variation_id']);
                    $wMonths = $variation ? (int) $variation->warranty_months : 0;
                    $wExpiry = $wMonths > 0 ? now()->addMonths($wMonths)->toDateString() : null;

                    foreach ($serialsForLine as $sNum) {
                        ProductSerial::where('serial_number', $sNum)
                            ->where('branch_id', $branchId)
                            ->update([
                                'status' => 'sold',
                                'sale_id' => $sale->id,
                                'sale_item_id' => $createdItem->id,
                                'sold_at' => now(),
                                'warranty_months' => $wMonths,
                                'warranty_expires_at' => $wExpiry,
                            ]);
                    }
                }

                // If this is an exchange or returned item (negative qty), return it back to store stock
                if (!empty($itemRow['is_exchange']) || $itemRow['quantity'] < 0) {
                    $returnQty = abs($itemRow['quantity']);
                    $stock = Stock::firstOrCreate(
                        ['branch_id' => $branchId, 'product_variation_id' => $itemRow['product_variation_id']],
                        ['quantity' => 0, 'reorder_level' => 5]
                    );
                    $stock->increment('quantity', $returnQty);
                    continue;
                }

                // Normal purchase: Deduct from FIFO stocks
                $remainingQty = $itemRow['quantity'];
                $stocks = Stock::where('branch_id', $branchId)
                    ->where('product_variation_id', $itemRow['product_variation_id'])
                    ->orderByRaw('exp_date IS NULL, exp_date ASC')
                    ->get();

                if ($stocks->isNotEmpty()) {
                    foreach ($stocks as $stockBatch) {
                        if ($stockBatch->quantity <= 0) continue;
                        $deduct = min($stockBatch->quantity, $remainingQty);
                        $stockBatch->decrement('quantity', $deduct);
                        $remainingQty -= $deduct;
                        if ($remainingQty <= 0) break;
                    }

                    if ($remainingQty > 0) {
                        $stocks->first()->decrement('quantity', $remainingQty);
                    }
                } else {
                    Stock::create([
                        'branch_id' => $branchId,
                        'product_variation_id' => $itemRow['product_variation_id'],
                        'quantity' => -$remainingQty,
                        'reorder_level' => 5,
                    ]);
                }
            }

            // Save payments & handle customer credit / shifts
            $cashPaid = 0;
            $cardPaid = 0;

            foreach ($validated['payments'] as $paymentRow) {
                $sale->payments()->create([
                    'payment_method' => $paymentRow['method'],
                    'amount' => $paymentRow['amount'],
                    'tender_amount' => $paymentRow['tender_amount'] ?? $paymentRow['amount'],
                    'change_amount' => $paymentRow['method'] === 'cash' ? $changeAmount : 0,
                ]);

                if ($paymentRow['method'] === 'cash') $cashPaid += $paymentRow['amount'];
                if ($paymentRow['method'] === 'card') $cardPaid += $paymentRow['amount'];

                // Customer Credit Payment Handling
                if ($paymentRow['method'] === 'credit' && $customer) {
                    $customer->increment('current_balance', $paymentRow['amount']);
                }
            }

            // Update Shift cash/card totals
            if ($activeShift) {
                $activeShift->increment('cash_sales', $cashPaid);
                $activeShift->increment('card_sales', $cardPaid);
            }

            // Award loyalty points on net spend (1 point per 100 spent)
            if ($customer && $grandTotal > 0) {
                $pointsEarned = floor($grandTotal / 100);
                if ($pointsEarned > 0) {
                    $customer->increment('loyalty_points', $pointsEarned);
                }
            }

            // If this sale was from a held cart, mark it as resumed
            if (!empty($validated['held_sale_id'])) {
                HeldSale::where('id', $validated['held_sale_id'])->update(['status' => 'resumed']);
            }

            return $sale;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'invoice_no' => $sale->invoice_no,
                'grand_total' => $sale->grand_total,
                'change_amount' => $sale->change_amount,
                'receipt_url' => route('pos.receipt', $sale->id),
            ]);
        }

        return redirect()->route('pos.receipt', $sale->id);
    }

    public function holdSale(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'nullable|string|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:150',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.variation_id' => 'required|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.serials' => 'nullable|array',
            'items.*.serials.*' => 'string',
        ]);

        $user = Auth::user();
        $branchId = $user->branch_id ?? Branch::first()->id;
        $activeShift = CashierShift::where('user_id', $user->id)->where('status', 'open')->latest()->first();

        $heldCount = HeldSale::where('branch_id', $branchId)->count() + 1;
        $ref = !empty($validated['reference']) ? $validated['reference'] : 'HOLD #' . str_pad($heldCount, 3, '0', STR_PAD_LEFT);

        $heldSale = DB::transaction(function () use ($validated, $branchId, $user, $activeShift, $ref) {
            $held = HeldSale::create([
                'reference' => $ref,
                'branch_id' => $branchId,
                'register_id' => $activeShift?->register_id,
                'cashier_id' => $user->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'discount_amount' => $validated['discount_amount'] ?? 0.00,
                'tax_rate' => $validated['tax_rate'] ?? 0.00,
                'subtotal' => $validated['subtotal'],
                'grand_total' => $validated['grand_total'],
                'note' => $validated['note'] ?? null,
                'status' => 'held',
            ]);

            foreach ($validated['items'] as $item) {
                $held->items()->create([
                    'product_variation_id' => $item['variation_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $held;
        });

        $activeHeldCount = HeldSale::where('branch_id', $branchId)->where('status', 'held')->count();

        return response()->json([
            'success' => true,
            'message' => 'Cart held under ' . $heldSale->reference,
            'held_count' => $activeHeldCount,
            'held_sale' => $heldSale,
        ]);
    }

    public function getHeldSales(Request $request)
    {
        $user = Auth::user();
        $branchId = $user->branch_id ?? Branch::first()->id;

        $heldSales = HeldSale::with(['items.productVariation.product', 'customer'])
            ->where('branch_id', $branchId)
            ->where('status', 'held')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'held_sales' => $heldSales,
            'count' => $heldSales->count(),
        ]);
    }

    public function resumeHeldSale(Request $request, $id)
    {
        $held = HeldSale::with(['items.productVariation.product', 'customer'])->findOrFail($id);
        $held->update(['status' => 'resumed']);

        return response()->json([
            'success' => true,
            'held_sale' => $held,
        ]);
    }

    public function cancelHeldSale(Request $request, $id)
    {
        $held = HeldSale::findOrFail($id);
        $held->update(['status' => 'cancelled']);

        $user = Auth::user();
        $branchId = $user->branch_id ?? Branch::first()->id;
        $activeHeldCount = HeldSale::where('branch_id', $branchId)->where('status', 'held')->count();

        return response()->json([
            'success' => true,
            'message' => 'Held sale cancelled',
            'held_count' => $activeHeldCount,
        ]);
    }

    public function checkBranchStocks($id)
    {
        $variation = ProductVariation::with('product')->findOrFail($id);
        $stocks = Stock::with('branch')
            ->where('product_variation_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'product_name' => $variation->product->name . ' - ' . $variation->name,
            'sku' => $variation->sku,
            'stocks' => $stocks->map(function ($s) {
                return [
                    'branch_name' => $s->branch->name ?? 'Unknown Branch',
                    'branch_code' => $s->branch->code ?? '',
                    'quantity' => $s->quantity,
                    'batch_number' => $s->batch_number ?? 'General',
                    'exp_date' => $s->exp_date ? $s->exp_date->format('Y-m-d') : 'N/A',
                ];
            }),
        ]);
    }

    public function verifyManagerPin(Request $request)
    {
        $request->validate(['pin' => 'required|string']);

        $pin = $request->input('pin');
        $manager = User::whereIn('role', ['admin', 'manager', 'head_office_manager', 'super_admin'])
            ->where('status', 'active')
            ->where('manager_pin', $pin)
            ->first();

        if ($manager) {
            return response()->json([
                'success' => true,
                'authorized_by' => $manager->name,
                'role' => $manager->role,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid Manager PIN. Please verify with a Store Manager or Administrator.',
        ], 403);
    }

    public function receipt($id)
    {
        $sale = Sale::with(['branch', 'register', 'cashier', 'customer', 'items.productVariation', 'payments'])->findOrFail($id);
        return view('pos.receipt', compact('sale'));
    }

    public function escposReceipt($id)
    {
        $sale = Sale::with(['branch', 'register', 'cashier', 'customer', 'items.productVariation.product', 'payments'])
            ->findOrFail($id);

        $branch = $sale->branch;
        $width = 48; // Standard 80mm printer character width

        $esc = "\x1b";
        $gs = "\x1d";

        $output = "";
        // 1. Initialize printer
        $output .= "{$esc}@";

        // 2. Center alignment, bold, double-height for Store Name
        $output .= "{$esc}a\x01"; // Center
        $output .= "{$esc}E\x01"; // Bold on
        $output .= "{$gs}!\x11";  // Double size
        $output .= ($branch->receipt_header ?? $branch->name ?? 'PSI ONE GLOBAL POS') . "\n";
        $output .= "{$gs}!\x00";  // Normal size
        $output .= "{$esc}E\x00"; // Bold off

        if ($branch->address) $output .= $branch->address . "\n";
        if ($branch->phone) $output .= "Tel: " . $branch->phone . "\n";
        $output .= str_repeat("-", $width) . "\n";

        // 3. Receipt Details
        $output .= "{$esc}a\x00"; // Left align
        $output .= sprintf("%-24s %23s\n", "Invoice: " . $sale->invoice_no, "Date: " . $sale->created_at->format('d/m/Y H:i'));
        $output .= sprintf("%-24s %23s\n", "Cashier: " . ($sale->cashier->name ?? 'Cashier'), "Reg: " . ($sale->register->code ?? 'REG-01'));
        if ($sale->customer) {
            $output .= "Customer: " . $sale->customer->name . " (" . $sale->customer->phone . ")\n";
        }
        $output .= str_repeat("-", $width) . "\n";

        // 4. Items Table Header
        $output .= sprintf("%-24s %4s %8s %9s\n", "Item", "Qty", "Price", "Total");
        $output .= str_repeat("-", $width) . "\n";

        // 5. Items Lines
        foreach ($sale->items as $item) {
            $prodName = $item->productVariation->product->name ?? 'Product';
            if (strlen($prodName) > 24) {
                $prodName = substr($prodName, 0, 24);
            }
            $lineTotal = number_format($item->subtotal, 2);
            $unitPrice = number_format($item->unit_price, 2);
            $output .= sprintf("%-24s %4d %8s %9s\n", $prodName, $item->quantity, $unitPrice, $lineTotal);

            // Print Serial / IMEI and Warranty if present
            if (!empty($item->serial_numbers) && is_array($item->serial_numbers)) {
                $warrantyMonths = $item->productVariation->warranty_months ?? 0;
                $wText = $warrantyMonths > 0 ? " ({$warrantyMonths}M Warranty)" : "";
                foreach ($item->serial_numbers as $s) {
                    $output .= "  * IMEI: " . $s . $wText . "\n";
                }
            }
        }
        $output .= str_repeat("-", $width) . "\n";

        // 6. Totals
        $output .= sprintf("%-30s %17s\n", "Subtotal:", "Rs. " . number_format($sale->subtotal, 2));
        if ($sale->discount_amount > 0) {
            $output .= sprintf("%-30s %17s\n", "Discount:", "-Rs. " . number_format($sale->discount_amount, 2));
        }
        if ($sale->tax_amount > 0) {
            $output .= sprintf("%-30s %17s\n", "Tax:", "Rs. " . number_format($sale->tax_amount, 2));
        }

        // Bold Grand Total
        $output .= "{$esc}E\x01"; // Bold on
        $output .= sprintf("%-26s %21s\n", "GRAND TOTAL:", "Rs. " . number_format($sale->grand_total, 2));
        $output .= "{$esc}E\x00"; // Bold off

        // Payments
        foreach ($sale->payments as $p) {
            $output .= sprintf("%-30s %17s\n", "Paid (" . ucfirst(str_replace('_', ' ', $p->payment_method)) . "):", "Rs. " . number_format($p->amount, 2));
        }
        if ($sale->change_amount > 0) {
            $output .= sprintf("%-30s %17s\n", "Change Due:", "Rs. " . number_format($sale->change_amount, 2));
        }

        $output .= str_repeat("=", $width) . "\n";

        // Footer & Barcode
        $output .= "{$esc}a\x01"; // Center align
        $output .= ($branch->receipt_footer ?? 'Thank you for your business!') . "\n";
        $output .= "Powered by Psi One Global POS\n\n";

        // Cash drawer pulse kick
        $output .= "{$esc}p\x00\x19\xfa"; // 25ms on, 250ms off

        // Feed & Paper Cut (GS V 66 0 = Partial Cut with Feed)
        $output .= "\n\n\n\n{$gs}V\x42\x00";

        if (request()->query('format') === 'base64') {
            return response()->json([
                'success' => true,
                'invoice_no' => $sale->invoice_no,
                'raw_base64' => base64_encode($output)
            ]);
        }

        return response($output, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="receipt-' . $sale->invoice_no . '.bin"',
        ]);
    }

    public function openCashDrawer(Request $request)
    {
        // Standard ESC/POS Cash Drawer Kick command: ESC p 0 25 250
        $drawerKickCommand = "\x1b\x70\x00\x19\xfa";

        return response()->json([
            'success' => true,
            'message' => 'Cash drawer pulse signal triggered.',
            'raw_base64' => base64_encode($drawerKickCommand),
        ]);
    }

    public function customerDisplay(Request $request)
    {
        $user = Auth::user();
        $branch = Branch::find($user->branch_id) ?? Branch::first();
        return view('pos.customer_display', compact('branch'));
    }

    public function generateLankaQr(Request $request)
    {
        $user = Auth::user();
        $branch = Branch::find($user->branch_id) ?? Branch::first();
        $amount = (float) $request->input('amount', 0);
        $invRef = $request->input('reference', 'POS-' . date('Ymd-His'));

        $qrString = \App\Services\LankaQrService::generatePayload(
            $branch->name ?? 'PSI ONE POS',
            $branch->city ?? 'Colombo',
            $amount,
            $invRef
        );

        return response()->json([
            'success' => true,
            'amount' => $amount,
            'reference' => $invRef,
            'qr_string' => $qrString,
            'merchant_name' => $branch->name ?? 'PSI ONE GLOBAL',
            'merchant_city' => $branch->city ?? 'Colombo',
        ]);
    }

    public function whatsappReceipt($id)
    {
        $sale = Sale::with(['branch', 'customer', 'cashier', 'items.productVariation.product', 'payments'])->findOrFail($id);
        $phone = preg_replace('/[^0-9]/', '', $sale->customer?->phone ?? request()->query('phone', ''));

        if (str_starts_with($phone, '0')) {
            $phone = '94' . substr($phone, 1);
        }

        $lines = [];
        $lines[] = "*PSI ONE GLOBAL POS - Electronic Receipt*";
        $lines[] = "=============================";
        $lines[] = "Invoice: #" . $sale->invoice_no;
        $lines[] = "Branch: " . ($sale->branch->name ?? 'Main Branch');
        $lines[] = "Date: " . $sale->created_at->format('Y-m-d H:i');
        $lines[] = "Cashier: " . ($sale->cashier->name ?? 'Staff');
        if ($sale->customer) {
            $lines[] = "Customer: " . $sale->customer->name;
        }
        $lines[] = "-----------------------------";

        foreach ($sale->items as $item) {
            $itemPrefix = $item->is_exchange ? "[EXCHANGE/RETURN] " : "";
            $line = $itemPrefix . $item->quantity . "x " . $item->productVariation->product->name . " (" . $item->productVariation->name . ") - Rs. " . number_format($item->subtotal, 2);
            $lines[] = $line;
            if (!empty($item->serial_numbers)) {
                $lines[] = "  IMEI/SN: " . implode(', ', (array) $item->serial_numbers);
            }
        }

        $lines[] = "-----------------------------";
        $lines[] = "Subtotal: Rs. " . number_format($sale->subtotal, 2);
        if ($sale->discount_amount > 0) {
            $lines[] = "Discount: -Rs. " . number_format($sale->discount_amount, 2);
        }
        if ($sale->loyalty_discount_amount > 0) {
            $lines[] = "Loyalty Redeemed: -Rs. " . number_format($sale->loyalty_discount_amount, 2);
        }
        if ($sale->tax_amount > 0) {
            $lines[] = "Tax: Rs. " . number_format($sale->tax_amount, 2);
        }
        $lines[] = "*GRAND TOTAL: Rs. " . number_format($sale->grand_total, 2) . "*";
        $lines[] = "=============================";
        $lines[] = "View Full E-Receipt Online:";
        $lines[] = route('pos.receipt', $sale->id);
        $lines[] = "";
        $lines[] = "Thank you for shopping with us!";

        $fullText = implode("\n", $lines);
        $whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($fullText);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'phone' => $phone,
                'text' => $fullText,
                'whatsapp_url' => $whatsappUrl,
            ]);
        }

        return redirect()->away($whatsappUrl);
    }
}

