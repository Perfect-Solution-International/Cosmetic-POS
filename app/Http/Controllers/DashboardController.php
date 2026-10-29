<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display POS dashboard / landing page after authentication.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedBranchId = $request->query('branch_id', $user->branch_id);

        $branches = Branch::where('status', 'active')->get();

        // Query sales for today in a single combined query
        $salesQuery = Sale::whereDate('created_at', today());
        if ($selectedBranchId) {
            $salesQuery->where('branch_id', $selectedBranchId);
        }
        $salesStats = (clone $salesQuery)->selectRaw('COALESCE(SUM(grand_total), 0) as total, COUNT(*) as orders')->first();
        $todaySales = (float) ($salesStats->total ?? 0);
        $todayOrders = (int) ($salesStats->orders ?? 0);

        // Products & variations count
        $totalProducts = Product::where('status', 'active')->count();
        $totalVariations = ProductVariation::where('is_active', true)->count();

        // Low stock items count
        $stocksQuery = Stock::query();
        if ($selectedBranchId) {
            $stocksQuery->where('branch_id', $selectedBranchId);
        }
        $lowStockCount = (clone $stocksQuery)->whereRaw('quantity <= reorder_level')->count();

        // Near expiry cosmetics count (expiring in next 90 days)
        $nearExpiryCount = (clone $stocksQuery)
            ->whereNotNull('exp_date')
            ->whereBetween('exp_date', [now(), now()->addDays(90)])
            ->count();

        // Open registers count
        $openRegistersCount = Register::where('status', 'open')->count();

        // Recent sales
        $recentSales = Sale::with(['cashier', 'branch', 'customer'])
            ->latest()
            ->take(6)
            ->get();

        // Active shift
        $activeShiftQuery = CashierShift::with(['register', 'cashier'])->where('status', 'open');
        if ($selectedBranchId) {
            $activeShiftQuery->where('branch_id', $selectedBranchId);
        }
        $activeShift = $activeShiftQuery->latest()->first();

        $metrics = [
            'today_sales' => $todaySales,
            'today_orders' => $todayOrders,
            'open_registers' => $openRegistersCount,
            'total_products' => $totalProducts,
            'total_variations' => $totalVariations,
            'low_stock_items' => $lowStockCount,
            'near_expiry_items' => $nearExpiryCount,
            'terminal_id' => 'REG-01-MAIN',
        ];

        return view('dashboard', compact(
            'user',
            'branches',
            'selectedBranchId',
            'metrics',
            'recentSales',
            'activeShift'
        ));
    }

    /**
     * Multi-Outlet Master HQ Dashboard (Consolidated command center across all branches)
     */
    public function hqMasterView(Request $request)
    {
        $user = Auth::user();
        $activeSub = Subscription::with(['package', 'shopOwner'])
            ->whereIn('status', ['trial', 'active', 'grace_period'])
            ->when($user && $user->isShopOwner(), fn($q) => $q->where('shop_owner_id', $user->id))
            ->latest()
            ->first();

        // Query active branches
        $branchesQuery = Branch::with(['registers', 'users'])->where('status', 'active');
        if ($user && $user->isShopOwner()) {
            $branchesQuery->where(function($q) use ($user) {
                $q->where('owner_id', $user->id)->orWhereNull('owner_id');
            });
        }
        $branches = $branchesQuery->get();

        // Compile per-branch metrics
        $branchMetrics = [];
        $totalSalesToday = 0;
        $totalOrdersToday = 0;
        $totalOpenShifts = 0;

        foreach ($branches as $branch) {
            $salesToday = Sale::where('branch_id', $branch->id)
                ->whereDate('created_at', today())
                ->sum('grand_total');

            $ordersToday = Sale::where('branch_id', $branch->id)
                ->whereDate('created_at', today())
                ->count();

            $activeShift = CashierShift::with('cashier')
                ->where('branch_id', $branch->id)
                ->where('status', 'open')
                ->latest()
                ->first();

            $lowStockCount = Stock::where('branch_id', $branch->id)
                ->whereRaw('quantity <= reorder_level')
                ->count();

            $totalUnits = Stock::where('branch_id', $branch->id)->sum('quantity');

            $branchMetrics[] = [
                'branch' => $branch,
                'sales_today' => (float) $salesToday,
                'orders_today' => (int) $ordersToday,
                'active_shift' => $activeShift,
                'low_stock_count' => (int) $lowStockCount,
                'total_units' => (int) $totalUnits,
                'open_registers' => $branch->registers->where('status', 'open')->count(),
                'total_registers' => $branch->registers->count(),
            ];

            $totalSalesToday += (float) $salesToday;
            $totalOrdersToday += (int) $ordersToday;
            if ($activeShift) {
                $totalOpenShifts++;
            }
        }

        // Inter-branch stock matrix for top variations
        $variations = ProductVariation::with(['product', 'stocks'])
            ->where('is_active', true)
            ->take(10)
            ->get();

        $stockMatrix = [];
        foreach ($variations as $var) {
            $branchStocks = [];
            $varTotal = 0;
            foreach ($branches as $b) {
                $st = $var->stocks->firstWhere('branch_id', $b->id);
                $qty = $st ? (int) $st->quantity : 0;
                $branchStocks[$b->id] = $qty;
                $varTotal += $qty;
            }
            $stockMatrix[] = [
                'variation' => $var,
                'product_name' => $var->product->name ?? 'Product #' . $var->id,
                'sku' => $var->sku,
                'total_stock' => $varTotal,
                'branches' => $branchStocks,
            ];
        }

        // Consolidated recent sales
        $recentSales = Sale::with(['cashier', 'branch', 'customer'])
            ->whereIn('branch_id', $branches->pluck('id'))
            ->latest()
            ->take(8)
            ->get();

        $allocatedBranches = $activeSub?->allocated_branches ?? max(3, $branches->count());
        $usedBranches = $branches->count();

        $consolidatedKPIs = [
            'total_sales_today' => $totalSalesToday,
            'total_orders_today' => $totalOrdersToday,
            'total_branches' => $branches->count(),
            'open_shifts' => $totalOpenShifts,
            'allocated_branches' => $allocatedBranches,
            'used_branches' => $usedBranches,
        ];

        return view('hq.index', compact(
            'user',
            'activeSub',
            'branches',
            'branchMetrics',
            'consolidatedKPIs',
            'stockMatrix',
            'recentSales'
        ));
    }
}
