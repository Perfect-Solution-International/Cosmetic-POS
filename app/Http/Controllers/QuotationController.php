<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\ProductVariation;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = $request->query('branch_id', $user->branch_id ?? Branch::first()?->id);

        $query = Quotation::with(['customer', 'user', 'branch'])
            ->where('branch_id', $branchId);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('quotation_no', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $quotations = $query->latest()->paginate(15);
        $branches = Branch::where('status', 'active')->get();

        $metrics = [
            'total' => Quotation::where('branch_id', $branchId)->count(),
            'draft' => Quotation::where('branch_id', $branchId)->where('status', 'draft')->count(),
            'accepted' => Quotation::where('branch_id', $branchId)->where('status', 'accepted')->count(),
            'converted' => Quotation::where('branch_id', $branchId)->where('status', 'converted')->count(),
            'converted_value' => Quotation::where('branch_id', $branchId)->where('status', 'converted')->sum('grand_total'),
        ];

        return view('quotations.index', compact('quotations', 'branches', 'branchId', 'metrics'));
    }

    public function create()
    {
        $user = Auth::user();
        $branches = Branch::where('status', 'active')->get();
        $selectedBranch = Branch::find($user->branch_id) ?? $branches->first();
        $customers = Customer::orderBy('name')->get();
        $variations = ProductVariation::with(['product', 'stocks'])
            ->where('is_active', true)
            ->get();

        $nextNumber = 'QT-' . date('Ymd') . '-' . str_pad(Quotation::count() + 1, 4, '0', STR_PAD_LEFT);

        return view('quotations.create', compact('branches', 'selectedBranch', 'customers', 'variations', 'nextNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:150',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:100',
            'date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:date',
            'pricing_mode' => 'nullable|in:retail,wholesale',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_variation_id' => 'required|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();

        $quotation = DB::transaction(function () use ($validated, $user) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $discount = $item['discount_amount'] ?? 0;
                $lineSubtotal = ($item['quantity'] * $item['unit_price']) - $discount;
                $subtotal += $lineSubtotal;

                $itemsData[] = [
                    'product_variation_id' => $item['product_variation_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $discount,
                    'subtotal' => max(0, $lineSubtotal),
                ];
            }

            $discountAmount = $validated['discount_amount'];
            if ($validated['discount_type'] === 'percentage') {
                $discountAmount = ($subtotal * $validated['discount_amount']) / 100;
            }

            $taxAmount = 0;
            if ($validated['tax_rate'] > 0) {
                $taxAmount = (($subtotal - $discountAmount) * $validated['tax_rate']) / 100;
            }

            $grandTotal = max(0, ($subtotal - $discountAmount) + $taxAmount);

            $quotationNo = 'QT-' . date('Ymd') . '-' . str_pad(Quotation::count() + 1, 4, '0', STR_PAD_LEFT);

            // Fetch customer name/phone if customer_id provided
            $customerName = $validated['customer_name'] ?? null;
            $customerPhone = $validated['customer_phone'] ?? null;
            $customerEmail = $validated['customer_email'] ?? null;
            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customerName = $customerName ?: $customer->name;
                    $customerPhone = $customerPhone ?: $customer->phone;
                    $customerEmail = $customerEmail ?: $customer->email;
                }
            }

            $quotation = Quotation::create([
                'quotation_no' => $quotationNo,
                'branch_id' => $validated['branch_id'],
                'user_id' => $user->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => $customerEmail,
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'] ?? null,
                'status' => 'draft',
                'pricing_mode' => $validated['pricing_mode'],
                'subtotal' => $subtotal,
                'discount_type' => $validated['discount_type'],
                'discount_amount' => $discountAmount,
                'tax_rate' => $validated['tax_rate'],
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? '1. Quotation valid for 14 days from issue date. 2. Prices subject to inventory availability.',
            ]);

            foreach ($itemsData as $data) {
                $quotation->items()->create($data);
            }

            return $quotation;
        });

        return redirect()->route('quotations.show', $quotation->id)
            ->with('status', 'Quotation ' . $quotation->quotation_no . ' created successfully.');
    }

    public function show($id)
    {
        $quotation = Quotation::with(['branch', 'user', 'customer', 'items.productVariation.product', 'convertedSale'])
            ->findOrFail($id);

        return view('quotations.show', compact('quotation'));
    }

    public function print($id)
    {
        $quotation = Quotation::with(['branch', 'user', 'customer', 'items.productVariation.product'])
            ->findOrFail($id);

        return view('quotations.print', compact('quotation'));
    }

    public function updateStatus(Request $request, $id)
    {
        $quotation = Quotation::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected',
        ]);

        $quotation->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $quotation->status, 'message' => 'Quotation status updated to ' . ucfirst($validated['status'])]);
        }

        return back()->with('status', 'Quotation status updated to ' . ucfirst($validated['status']));
    }

    public function convertToSale(Request $request, $id)
    {
        $quotation = Quotation::with(['items.productVariation', 'customer', 'branch'])->findOrFail($id);

        if ($quotation->status === 'converted') {
            return back()->withErrors(['error' => 'This quotation has already been converted to an invoice.']);
        }

        $user = Auth::user();
        $branchId = $quotation->branch_id;

        // Ensure active cashier shift exists
        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->where('status', 'open')
            ->latest()
            ->first();

        $register = $activeShift ? Register::find($activeShift->register_id) : Register::where('branch_id', $branchId)->first();
        if (!$register) {
            $register = Register::create([
                'branch_id' => $branchId,
                'name' => 'Counter 01',
                'status' => 'active',
            ]);
        }

        $sale = DB::transaction(function () use ($quotation, $user, $branchId, $register, $activeShift) {
            $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'branch_id' => $branchId,
                'register_id' => $register->id,
                'customer_id' => $quotation->customer_id,
                'cashier_id' => $user->id,
                'shift_id' => $activeShift?->id,
                'subtotal' => $quotation->subtotal,
                'discount_type' => $quotation->discount_type,
                'discount_rate' => 0.00,
                'discount_amount' => $quotation->discount_amount,
                'tax_rate' => $quotation->tax_rate,
                'tax_amount' => $quotation->tax_amount,
                'grand_total' => $quotation->grand_total,
                'paid_amount' => $quotation->grand_total,
                'change_amount' => 0.00,
                'loyalty_points_redeemed' => 0,
                'loyalty_discount_amount' => 0.00,
                'pricing_mode' => $quotation->pricing_mode,
                'quotation_id' => $quotation->id,
                'payment_status' => 'paid',
                'notes' => 'Converted from Quotation ' . $quotation->quotation_no,
            ]);

            // Create items and deduct branch stock
            foreach ($quotation->items as $qItem) {
                $createdItem = $sale->items()->create([
                    'product_variation_id' => $qItem->product_variation_id,
                    'quantity' => $qItem->quantity,
                    'purchase_cost' => $qItem->productVariation->purchase_cost ?? 0.00,
                    'unit_price' => $qItem->unit_price,
                    'discount_amount' => $qItem->discount_amount,
                    'subtotal' => $qItem->subtotal,
                    'is_exchange' => false,
                ]);

                // Deduct stock via FIFO
                $remainingQty = $qItem->quantity;
                $stocks = Stock::where('branch_id', $branchId)
                    ->where('product_variation_id', $qItem->product_variation_id)
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
                        'product_variation_id' => $qItem->product_variation_id,
                        'quantity' => -$remainingQty,
                        'reorder_level' => 5,
                    ]);
                }
            }

            // Create payment record (Credit or Bank Transfer or Cash)
            $sale->payments()->create([
                'payment_method' => 'bank_transfer',
                'amount' => $quotation->grand_total,
                'tender_amount' => $quotation->grand_total,
                'reference_no' => 'CONVERT-' . $quotation->quotation_no,
            ]);

            // Mark quotation as converted
            $quotation->update([
                'status' => 'converted',
                'converted_sale_id' => $sale->id,
            ]);

            return $sale;
        });

        return redirect()->route('pos.receipt', $sale->id)
            ->with('status', "Quotation {$quotation->quotation_no} successfully converted to Official Sale Invoice {$sale->invoice_no}!");
    }
}
