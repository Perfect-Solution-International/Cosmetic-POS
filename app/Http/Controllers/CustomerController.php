<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('group');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        $customers = $query->latest()->paginate(15);
        $groups = CustomerGroup::all();

        return view('customers.index', compact('customers', 'groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50|unique:customers,phone',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'Customer full name is required.',
            'phone.required' => 'Mobile phone number is required.',
            'phone.unique' => 'A customer with this phone number is already registered in the system.',
        ]);

        $validated['credit_limit'] = isset($validated['credit_limit']) && is_numeric($validated['credit_limit']) ? (float) $validated['credit_limit'] : 0.00;
        $validated['current_balance'] = 0.00;
        $validated['loyalty_points'] = 0;

        $customer = Customer::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'message' => 'Customer ' . $customer->name . ' registered successfully.',
            ]);
        }

        return redirect()->route('customers.index')
            ->with('status', 'Customer ' . $customer->name . ' registered successfully.');
    }

    public function settlePayment(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,bank_transfer,cheque',
            'payment_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $settleAmount = min((float)$customer->current_balance, (float)$validated['amount']);

        $payment = CustomerPayment::create([
            'payment_no' => 'CPAY-' . date('Ymd') . '-' . str_pad(CustomerPayment::count() + 1, 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'branch_id' => Auth::user()->branch_id ?? Branch::first()?->id ?? 1,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'reference_no' => $validated['reference_no'] ?? null,
            'payment_date' => $validated['payment_date'] ?? date('Y-m-d'),
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        $customer->decrement('current_balance', $validated['amount']);

        $newBalance = max(0, (float)$customer->fresh()->current_balance);

        return redirect()->route('customers.index')
            ->with('status', 'Payment of Rs. ' . number_format($validated['amount'], 2) . ' received from ' . $customer->name . '. Outstanding balance is now Rs. ' . number_format($newBalance, 2));
    }

    public function customerPayments($id)
    {
        $customer = Customer::with(['payments.creator', 'payments.branch'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'customer' => $customer,
            'payments' => $customer->payments,
        ]);
    }
}

