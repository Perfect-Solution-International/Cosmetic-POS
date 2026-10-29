<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Subscriptions Listing with Metrics & Filters
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedBranchId = $request->query('branch_id', 'all');

        $query = Subscription::with(['customer', 'package', 'branch', 'salesAgent', 'shopOwner']);

        if ($selectedBranchId && $selectedBranchId !== 'all') {
            $query->where('branch_id', $selectedBranchId);
        }

        // Filter by Status (trial, active, expired, cancelled)
        if ($request->filled('status') && $request->query('status') !== 'all') {
            $status = $request->query('status');
            if ($status === 'trial') {
                $query->where('is_free_trial', true)->where('status', 'trial');
            } elseif ($status === 'expired') {
                $query->where(function ($q) {
                    $q->where('status', 'expired')
                      ->orWhere(function ($tq) {
                          $tq->where('status', 'trial')->where('trial_ends_at', '<', now());
                      });
                });
            } else {
                $query->where('status', $status);
            }
        }

        // Filter by Sales Acquisition Channel (online vs register)
        if ($request->filled('sales_channel') && $request->query('sales_channel') !== 'all') {
            $query->where('sales_channel', $request->query('sales_channel'));
        }

        // Filter by Package
        if ($request->filled('package_id')) {
            $query->where('package_id', $request->query('package_id'));
        }

        // Search by subscription no, business name, customer name, phone
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('subscription_no', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $subscriptions = $query->latest()->paginate(15);
        $branches = Branch::where('status', 'active')->get();
        $packages = SubscriptionPackage::where('is_active', true)->get();

        // Metrics (Branch scoped or all branches)
        $baseScope = Subscription::query();
        if ($selectedBranchId && $selectedBranchId !== 'all') {
            $baseScope->where('branch_id', $selectedBranchId);
        }

        $metrics = [
            'total' => (clone $baseScope)->count(),
            'trials' => (clone $baseScope)->where('status', 'trial')->where('trial_ends_at', '>=', now())->count(),
            'active_paid' => (clone $baseScope)->where('status', 'active')->count(),
            'expired' => (clone $baseScope)->where(function ($q) {
                $q->where('status', 'expired')
                  ->orWhere(function ($tq) {
                      $tq->where('status', 'trial')->where('trial_ends_at', '<', now());
                  });
            })->count(),
            'mrr' => (clone $baseScope)->where('status', 'active')->sum('price'),
            'online_count' => (clone $baseScope)->where('sales_channel', 'online')->count(),
            'register_count' => (clone $baseScope)->where('sales_channel', 'register')->count(),
        ];

        $branchId = $selectedBranchId;
        return view('subscriptions.index', compact('subscriptions', 'branches', 'packages', 'branchId', 'selectedBranchId', 'metrics'));
    }

    /**
     * Manage Subscription Packages
     */
    public function packages(Request $request)
    {
        $packages = SubscriptionPackage::withCount('subscriptions')->latest()->get();
        return view('subscriptions.packages', compact('packages'));
    }

    /**
     * Store new Subscription Package
     */
    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:subscription_packages,code',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:trial,monthly,quarterly,biannual,yearly',
            'included_branches' => 'nullable|integer|min:1|max:100',
            'price_per_additional_branch' => 'nullable|numeric|min:0',
            'max_branches' => 'nullable|integer|min:1|max:200',
            'default_trial_days' => 'required|integer|min:0|max:365',
            'description' => 'nullable|string|max:500',
            'features' => 'nullable|string',
        ]);

        $featuresArray = [];
        if (!empty($validated['features'])) {
            $featuresArray = array_values(array_filter(array_map('trim', explode("\n", $validated['features']))));
        }

        SubscriptionPackage::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'price' => $validated['price'],
            'billing_cycle' => $validated['billing_cycle'],
            'included_branches' => $validated['included_branches'] ?? 1,
            'price_per_additional_branch' => $validated['price_per_additional_branch'] ?? 1500.00,
            'max_branches' => $validated['max_branches'] ?? null,
            'default_trial_days' => $validated['default_trial_days'],
            'description' => $validated['description'] ?? null,
            'features' => $featuresArray,
            'is_active' => true,
        ]);

        return redirect()->route('subscriptions.packages')->with('status', 'Subscription Package created successfully.');
    }

    /**
     * Toggle Package Status
     */
    public function togglePackage($id)
    {
        $package = SubscriptionPackage::findOrFail($id);
        $package->is_active = !$package->is_active;
        $package->save();

        return back()->with('status', "Package {$package->name} status updated.");
    }

    /**
     * Create Subscription Form
     */
    public function create()
    {
        $user = Auth::user();
        $branches = Branch::where('status', 'active')->get();
        $selectedBranch = Branch::find($user->branch_id) ?? $branches->first();
        $customers = Customer::orderBy('name')->get();
        $packages = SubscriptionPackage::where('is_active', true)->get();
        $staffUsers = User::where('status', 'active')->orderBy('name')->get();
        $shopOwners = User::whereIn('role', ['admin', 'super_admin', 'shop_owner'])->orderBy('name')->get();

        $nextNumber = 'SUB-' . date('Ymd') . '-' . str_pad(Subscription::count() + 1, 4, '0', STR_PAD_LEFT);

        return view('subscriptions.create', compact('branches', 'selectedBranch', 'customers', 'packages', 'staffUsers', 'shopOwners', 'nextNumber'));
    }

    /**
     * Store new Subscription (Free Trial with 3/7/custom days OR Immediate Paid, with Multi-Outlet calculation)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'nullable|string|max:150',
            'shop_owner_id' => 'nullable|exists:users,id',
            'customer_id' => 'required|exists:customers,id',
            'package_id' => 'required|exists:subscription_packages,id',
            'branch_id' => 'required|exists:branches,id',
            'allocated_branches' => 'nullable|integer|min:1|max:100',
            'plan_type' => 'required|in:free_trial,paid', // free_trial or paid
            'trial_days' => 'nullable|integer|min:1|max:365', // can be 3, 7 or custom entered
            'sales_channel' => 'required|in:register,online', // who made the sale
            'sales_agent_id' => 'nullable|exists:users,id',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,qr',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $package = SubscriptionPackage::findOrFail($validated['package_id']);
        $isFreeTrial = ($validated['plan_type'] === 'free_trial');

        $salesAgentId = null;
        if ($validated['sales_channel'] === 'register') {
            $salesAgentId = $validated['sales_agent_id'] ?? $user->id;
        }

        // Multi-Branch Add-on Price Calculation
        $allocatedBranches = (int) ($validated['allocated_branches'] ?? 1);
        $includedBranches = (int) ($package->included_branches ?: 1);
        $extraBranches = max(0, $allocatedBranches - $includedBranches);
        $addBranchRate = (float) ($package->price_per_additional_branch ?: 0);
        $basePrice = (float) $package->price;
        $extraBranchTotal = $extraBranches * $addBranchRate;
        $totalPrice = $basePrice + $extraBranchTotal;
        $billingCycle = $package->billing_cycle ?: 'monthly';

        $subscription = DB::transaction(function () use (
            $validated, $package, $isFreeTrial, $salesAgentId, $user,
            $allocatedBranches, $basePrice, $addBranchRate, $totalPrice, $billingCycle
        ) {
            $subscriptionNo = 'SUB-' . date('Ymd') . '-' . str_pad(Subscription::count() + 1, 4, '0', STR_PAD_LEFT);

            // Compute subscription period
            $periodEndsAt = match($billingCycle) {
                'quarterly' => now()->addMonths(3),
                'biannual' => now()->addMonths(6),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            };

            if ($isFreeTrial) {
                // Free Trial logic: 3 days, 7 days, or custom input
                $trialDays = (int) ($validated['trial_days'] ?? $package->default_trial_days ?: 7);
                $trialEndsAt = now()->addDays($trialDays);

                $subscription = Subscription::create([
                    'subscription_no' => $subscriptionNo,
                    'business_name' => $validated['business_name'] ?? ($package->name . ' Store'),
                    'shop_owner_id' => $validated['shop_owner_id'] ?? null,
                    'customer_id' => $validated['customer_id'],
                    'package_id' => $package->id,
                    'branch_id' => $validated['branch_id'],
                    'allocated_branches' => $allocatedBranches,
                    'status' => 'trial',
                    'is_free_trial' => true,
                    'trial_days' => $trialDays,
                    'trial_ends_at' => $trialEndsAt,
                    'started_at' => now(),
                    'current_period_starts_at' => now(),
                    'current_period_ends_at' => $trialEndsAt,
                    'price' => $totalPrice,
                    'base_price' => $basePrice,
                    'additional_branch_price' => $addBranchRate,
                    'total_price' => $totalPrice,
                    'billing_cycle' => $billingCycle,
                    'sales_channel' => $validated['sales_channel'],
                    'sales_agent_id' => $salesAgentId,
                    'auto_renew' => true,
                    'notes' => $validated['notes'] ?? ('Started on ' . $trialDays . '-Day Free Trial with ' . $allocatedBranches . ' Outlets'),
                ]);
            } else {
                // Immediate Paid Subscription logic
                $subscription = Subscription::create([
                    'subscription_no' => $subscriptionNo,
                    'business_name' => $validated['business_name'] ?? ($package->name . ' Store'),
                    'shop_owner_id' => $validated['shop_owner_id'] ?? null,
                    'customer_id' => $validated['customer_id'],
                    'package_id' => $package->id,
                    'branch_id' => $validated['branch_id'],
                    'allocated_branches' => $allocatedBranches,
                    'status' => 'active',
                    'is_free_trial' => false,
                    'trial_days' => 0,
                    'trial_ends_at' => null,
                    'started_at' => now(),
                    'current_period_starts_at' => now(),
                    'current_period_ends_at' => $periodEndsAt,
                    'price' => $totalPrice,
                    'base_price' => $basePrice,
                    'additional_branch_price' => $addBranchRate,
                    'total_price' => $totalPrice,
                    'billing_cycle' => $billingCycle,
                    'sales_channel' => $validated['sales_channel'],
                    'sales_agent_id' => $salesAgentId,
                    'auto_renew' => true,
                    'notes' => $validated['notes'] ?? ('Paid Subscription Activation (' . $allocatedBranches . ' Outlets)'),
                ]);

                // Create a real Sale invoice for the paid subscription
                $branchId = $validated['branch_id'];
                $register = Register::where('branch_id', $branchId)->first();
                $invoiceNo = 'INV-SUB-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT);

                $sale = Sale::create([
                    'invoice_no' => $invoiceNo,
                    'branch_id' => $branchId,
                    'register_id' => $register?->id ?? 1,
                    'customer_id' => $validated['customer_id'],
                    'cashier_id' => $salesAgentId ?? $user->id,
                    'subtotal' => $totalPrice,
                    'discount_type' => 'fixed',
                    'discount_amount' => 0.00,
                    'tax_rate' => 0.00,
                    'tax_amount' => 0.00,
                    'grand_total' => $totalPrice,
                    'paid_amount' => $totalPrice,
                    'change_amount' => 0.00,
                    'payment_status' => 'paid',
                    'sales_channel' => $validated['sales_channel'],
                    'sales_agent_id' => $salesAgentId,
                    'subscription_id' => $subscription->id,
                    'notes' => 'Subscription: ' . $package->name . ' [' . $allocatedBranches . ' Outlets] (' . $subscription->subscription_no . ')',
                ]);

                $sale->payments()->create([
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'amount' => $totalPrice,
                    'tender_amount' => $totalPrice,
                    'change_amount' => 0.00,
                ]);
            }

            // If shop owner was specified, link primary branch to owner if not already set
            if (!empty($validated['shop_owner_id'])) {
                Branch::where('id', $validated['branch_id'])
                    ->whereNull('owner_id')
                    ->update(['owner_id' => $validated['shop_owner_id']]);
            }

            return $subscription;
        });

        return redirect()->route('subscriptions.show', $subscription->id)
            ->with('status', 'Subscription ' . $subscription->subscription_no . ' created successfully for ' . $subscription->allocated_branches . ' Outlets!');
    }

    /**
     * View Subscription Details & Timeline
     */
    public function show($id)
    {
        $subscription = Subscription::with(['customer', 'package', 'branch', 'salesAgent', 'shopOwner', 'sales.payments'])
            ->findOrFail($id);

        return view('subscriptions.show', compact('subscription'));
    }

    /**
     * 1-Click Convert Free Trial to Paid Subscription
     */
    public function convertTrial(Request $request, $id)
    {
        $subscription = Subscription::with(['customer', 'package', 'branch'])->findOrFail($id);
        $user = Auth::user();

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,bank_transfer,qr',
            'sales_channel' => 'nullable|in:register,online',
            'sales_agent_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $channel = $validated['sales_channel'] ?? $subscription->sales_channel;
        $agentId = ($channel === 'register') ? ($validated['sales_agent_id'] ?? $user->id) : null;

        DB::transaction(function () use ($subscription, $validated, $channel, $agentId, $user) {
            $periodEndsAt = now()->addMonth();

            // Update Subscription
            $subscription->update([
                'status' => 'active',
                'is_free_trial' => false,
                'current_period_starts_at' => now(),
                'current_period_ends_at' => $periodEndsAt,
                'sales_channel' => $channel,
                'sales_agent_id' => $agentId,
                'notes' => ($subscription->notes ? $subscription->notes . ' | ' : '') . 'Trial converted to Paid on ' . now()->format('Y-m-d H:i'),
            ]);

            // Create Sale record
            $branchId = $subscription->branch_id;
            $register = Register::where('branch_id', $branchId)->first();
            $invoiceNo = 'INV-SUB-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'branch_id' => $branchId,
                'register_id' => $register?->id ?? 1,
                'customer_id' => $subscription->customer_id,
                'cashier_id' => $agentId ?? $user->id,
                'subtotal' => $subscription->price,
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_rate' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => $subscription->price,
                'paid_amount' => $subscription->price,
                'change_amount' => 0.00,
                'payment_status' => 'paid',
                'sales_channel' => $channel,
                'sales_agent_id' => $agentId,
                'subscription_id' => $subscription->id,
                'notes' => 'Converted Free Trial to Paid Monthly Plan: ' . $subscription->package->name,
            ]);

            $sale->payments()->create([
                'payment_method' => $validated['payment_method'],
                'amount' => $subscription->price,
                'tender_amount' => $subscription->price,
                'change_amount' => 0.00,
            ]);
        });

        return redirect()->route('subscriptions.show', $subscription->id)
            ->with('status', 'Free Trial successfully converted to Paid Active Monthly Subscription!');
    }

    /**
     * Renew Active Subscription for Another Month
     */
    public function renew(Request $request, $id)
    {
        $subscription = Subscription::with(['package', 'customer'])->findOrFail($id);
        $user = Auth::user();

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,bank_transfer,qr',
        ]);

        DB::transaction(function () use ($subscription, $validated, $user) {
            $currentExpiry = $subscription->current_period_ends_at && $subscription->current_period_ends_at->isFuture()
                ? $subscription->current_period_ends_at
                : now();

            $newExpiry = (clone $currentExpiry)->addMonth();

            $subscription->update([
                'status' => 'active',
                'is_free_trial' => false,
                'current_period_starts_at' => $currentExpiry,
                'current_period_ends_at' => $newExpiry,
            ]);

            // Create Sale record for renewal
            $branchId = $subscription->branch_id;
            $register = Register::where('branch_id', $branchId)->first();
            $invoiceNo = 'INV-SUB-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'branch_id' => $branchId,
                'register_id' => $register?->id ?? 1,
                'customer_id' => $subscription->customer_id,
                'cashier_id' => $user->id,
                'subtotal' => $subscription->price,
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_rate' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => $subscription->price,
                'paid_amount' => $subscription->price,
                'change_amount' => 0.00,
                'payment_status' => 'paid',
                'sales_channel' => $subscription->sales_channel,
                'sales_agent_id' => $subscription->sales_agent_id ?? $user->id,
                'subscription_id' => $subscription->id,
                'notes' => 'Subscription Monthly Renewal: ' . $subscription->package->name,
            ]);

            $sale->payments()->create([
                'payment_method' => $validated['payment_method'],
                'amount' => $subscription->price,
                'tender_amount' => $subscription->price,
                'change_amount' => 0.00,
            ]);
        });

        return back()->with('status', 'Subscription renewed successfully until ' . $subscription->fresh()->current_period_ends_at->format('d M Y') . '.');
    }

    /**
     * Cancel Subscription
     */
    public function cancel($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->update(['status' => 'cancelled']);

        return back()->with('status', 'Subscription ' . $subscription->subscription_no . ' has been cancelled.');
    }
}

