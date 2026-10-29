<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Expense;
use App\Models\ProductVariation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Compute comprehensive Profit & Loss (P&L) and Income Statement data
     */
    protected function getProfitAndLossData(Request $request): array
    {
        $branches = Branch::where('status', 'active')->get();
        $selectedBranchId = $request->query('branch_id');
        $dateFilter = $request->query('date_filter', 'all');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Date range calculation
        $dateRange = null;
        $dateLabel = 'All Time';

        if ($dateFilter === 'today') {
            $dateRange = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            $dateLabel = 'Today (' . Carbon::today()->format('d M Y') . ')';
        } elseif ($dateFilter === 'yesterday') {
            $dateRange = [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
            $dateLabel = 'Yesterday (' . Carbon::yesterday()->format('d M Y') . ')';
        } elseif ($dateFilter === 'this_week') {
            $dateRange = [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            $dateLabel = 'This Week (' . Carbon::now()->startOfWeek()->format('d M') . ' - ' . Carbon::now()->endOfWeek()->format('d M Y') . ')';
        } elseif ($dateFilter === 'this_month') {
            $dateRange = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            $dateLabel = 'This Month (' . Carbon::now()->format('F Y') . ')';
        } elseif ($dateFilter === 'last_month') {
            $dateRange = [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
            $dateLabel = 'Last Month (' . Carbon::now()->subMonth()->format('F Y') . ')';
        } elseif ($dateFilter === 'this_quarter') {
            $dateRange = [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()];
            $dateLabel = 'This Quarter (Q' . Carbon::now()->quarter . ' ' . Carbon::now()->year . ')';
        } elseif ($dateFilter === 'this_year') {
            $dateRange = [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            $dateLabel = 'This Year (' . Carbon::now()->year . ')';
        } elseif ($dateFilter === 'custom' && $startDate && $endDate) {
            $dateRange = [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
            $dateLabel = Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y');
        }

        $selectedBranch = $selectedBranchId ? Branch::find($selectedBranchId) : null;
        $branchName = $selectedBranch ? $selectedBranch->name : 'All Outlets Combined (Consolidated)';

        // 1. Sales & Revenue
        $salesQuery = Sale::query();
        if ($selectedBranchId) {
            $salesQuery->where('branch_id', $selectedBranchId);
        }
        if ($dateRange) {
            $salesQuery->whereBetween('created_at', $dateRange);
        }

        $salesCount = (clone $salesQuery)->count();
        $grossSales = (float) (clone $salesQuery)->sum('subtotal');
        $totalDiscounts = (float) (clone $salesQuery)->sum('discount_amount');
        $totalTax = (float) (clone $salesQuery)->sum('tax_amount');
        $netSales = (float) (clone $salesQuery)->sum('grand_total');

        // Customer returns / refunds
        $returnsQuery = SaleReturn::query();
        if ($selectedBranchId) {
            $returnsQuery->where('branch_id', $selectedBranchId);
        }
        if ($dateRange) {
            $returnsQuery->whereBetween('created_at', $dateRange);
        }
        $totalReturns = (float) (clone $returnsQuery)->sum('total_refund_amount');
        $netRevenue = max(0, $netSales - $totalReturns);

        // 2. Cost of Goods Sold (COGS)
        $cogsQuery = SaleItem::query();
        if ($selectedBranchId || $dateRange) {
            $cogsQuery->whereHas('sale', function ($q) use ($selectedBranchId, $dateRange) {
                if ($selectedBranchId) $q->where('branch_id', $selectedBranchId);
                if ($dateRange) $q->whereBetween('created_at', $dateRange);
            });
        }
        $totalCogs = (float) $cogsQuery->sum(DB::raw('quantity * purchase_cost'));

        // Gross Profit & Margins
        $grossProfit = $netRevenue - $totalCogs;
        $grossMarginPercent = $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0;
        $markupPercent = $totalCogs > 0 ? ($grossProfit / $totalCogs) * 100 : 0;

        // 3. Operating Expenses by Category
        $expenseQuery = Expense::with('category');
        if ($selectedBranchId) {
            $expenseQuery->where('branch_id', $selectedBranchId);
        }
        if ($dateRange) {
            $expenseQuery->whereBetween('expense_date', [
                $dateRange[0]->format('Y-m-d'),
                $dateRange[1]->format('Y-m-d')
            ]);
        }
        $expenses = (clone $expenseQuery)->get();
        $totalExpenses = (float) $expenses->sum('amount');

        // Group expenses by category
        $expensesByCategory = $expenses->groupBy('expense_category_id')->map(function ($items) use ($totalExpenses) {
            $catName = $items->first()->category->name ?? 'General Store Expenses';
            $amount = (float) $items->sum('amount');
            $percentage = $totalExpenses > 0 ? ($amount / $totalExpenses) * 100 : 0;
            return [
                'category_id' => $items->first()->expense_category_id,
                'name' => $catName,
                'count' => $items->count(),
                'amount' => $amount,
                'percentage' => $percentage,
            ];
        })->sortByDesc('amount')->values();

        // 4. Net Operating Profit / Loss (Bottom Line)
        $netOperatingResult = $grossProfit - $totalExpenses;
        $netMarginPercent = $netRevenue > 0 ? ($netOperatingResult / $netRevenue) * 100 : 0;
        $expenseRatio = $netRevenue > 0 ? ($totalExpenses / $netRevenue) * 100 : 0;

        // 5. Category-wise Profitability Breakdown
        $categoryProfitability = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variations', 'sale_items.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->when($selectedBranchId, fn($q) => $q->where('sales.branch_id', $selectedBranchId))
            ->when($dateRange, fn($q) => $q->whereBetween('sales.created_at', $dateRange))
            ->select(
                DB::raw('COALESCE(categories.name, "Uncategorized") as category_name'),
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                DB::raw('SUM(sale_items.quantity * sale_items.purchase_cost) as total_cogs'),
                DB::raw('SUM(sale_items.subtotal) - SUM(sale_items.quantity * sale_items.purchase_cost) as gross_profit')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('gross_profit')
            ->get()
            ->map(function ($row) {
                $margin = $row->total_revenue > 0 ? ($row->gross_profit / $row->total_revenue) * 100 : 0;
                return [
                    'category' => $row->category_name,
                    'qty' => (float) $row->total_qty,
                    'revenue' => (float) $row->total_revenue,
                    'cogs' => (float) $row->total_cogs,
                    'profit' => (float) $row->gross_profit,
                    'margin' => $margin,
                ];
            });

        // 6. Product Profitability Breakdown
        $productProfitability = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variations', 'sale_items.product_variation_id', '=', 'product_variations.id')
            ->join('products', 'product_variations.product_id', '=', 'products.id')
            ->when($selectedBranchId, fn($q) => $q->where('sales.branch_id', $selectedBranchId))
            ->when($dateRange, fn($q) => $q->whereBetween('sales.created_at', $dateRange))
            ->select(
                'products.name as product_name',
                'product_variations.name as variation_name',
                'product_variations.sku',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                DB::raw('SUM(sale_items.quantity * sale_items.purchase_cost) as total_cogs'),
                DB::raw('SUM(sale_items.subtotal) - SUM(sale_items.quantity * sale_items.purchase_cost) as gross_profit')
            )
            ->groupBy('products.name', 'product_variations.name', 'product_variations.sku')
            ->orderByDesc('gross_profit')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $margin = $row->total_revenue > 0 ? ($row->gross_profit / $row->total_revenue) * 100 : 0;
                return [
                    'product' => $row->product_name . ($row->variation_name ? ' (' . $row->variation_name . ')' : ''),
                    'sku' => $row->sku,
                    'qty' => (float) $row->total_qty,
                    'revenue' => (float) $row->total_revenue,
                    'cogs' => (float) $row->total_cogs,
                    'profit' => (float) $row->gross_profit,
                    'margin' => $margin,
                ];
            });

        // 7. Payment Methods Breakdown
        $paymentQuery = SalePayment::query();
        if ($selectedBranchId || $dateRange) {
            $paymentQuery->whereHas('sale', function ($q) use ($selectedBranchId, $dateRange) {
                if ($selectedBranchId) $q->where('branch_id', $selectedBranchId);
                if ($dateRange) $q->whereBetween('created_at', $dateRange);
            });
        }
        $paymentBreakdown = $paymentQuery->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // 8. Branch summaries
        $branchSummaries = Branch::withCount('sales')
            ->withSum('sales', 'grand_total')
            ->get();

        // 9. Stock Valuation
        $stockValuationCost = (float) Stock::join('product_variations', 'stocks.product_variation_id', '=', 'product_variations.id')
            ->when($selectedBranchId, fn($q) => $q->where('stocks.branch_id', $selectedBranchId))
            ->sum(DB::raw('stocks.quantity * product_variations.purchase_cost'));

        $stockValuationRetail = (float) Stock::join('product_variations', 'stocks.product_variation_id', '=', 'product_variations.id')
            ->when($selectedBranchId, fn($q) => $q->where('stocks.branch_id', $selectedBranchId))
            ->sum(DB::raw('stocks.quantity * product_variations.selling_price'));

        return compact(
            'branches',
            'selectedBranchId',
            'selectedBranch',
            'branchName',
            'dateFilter',
            'startDate',
            'endDate',
            'dateLabel',
            'salesCount',
            'grossSales',
            'totalDiscounts',
            'totalTax',
            'totalReturns',
            'netSales',
            'netRevenue',
            'totalCogs',
            'grossProfit',
            'grossMarginPercent',
            'markupPercent',
            'totalExpenses',
            'expensesByCategory',
            'expenseRatio',
            'netOperatingResult',
            'netMarginPercent',
            'categoryProfitability',
            'productProfitability',
            'paymentBreakdown',
            'branchSummaries',
            'stockValuationCost',
            'stockValuationRetail'
        );
    }

    /**
     * Reports & Analytics Overview Dashboard
     */
    public function index(Request $request)
    {
        $data = $this->getProfitAndLossData($request);

        // Top Fast-Moving Sellers
        $topSellersQuery = SaleItem::with('productVariation.product')
            ->select('product_variation_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_variation_id')
            ->orderByDesc('total_qty')
            ->take(8);

        $selectedBranchId = $data['selectedBranchId'];
        $dateFilter = $data['dateFilter'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];

        if ($selectedBranchId || ($startDate && $endDate) || ($dateFilter && $dateFilter !== 'all')) {
            $dateRange = null;
            if ($dateFilter === 'today') $dateRange = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            elseif ($dateFilter === 'yesterday') $dateRange = [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
            elseif ($dateFilter === 'this_week') $dateRange = [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            elseif ($dateFilter === 'this_month') $dateRange = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            elseif ($dateFilter === 'last_month') $dateRange = [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
            elseif ($dateFilter === 'this_quarter') $dateRange = [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()];
            elseif ($dateFilter === 'this_year') $dateRange = [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            elseif ($dateFilter === 'custom' && $startDate && $endDate) $dateRange = [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];

            $topSellersQuery->whereHas('sale', function ($q) use ($selectedBranchId, $dateRange) {
                if ($selectedBranchId) $q->where('branch_id', $selectedBranchId);
                if ($dateRange) $q->whereBetween('created_at', $dateRange);
            });
        }
        $topSellers = $topSellersQuery->get();

        return view('reports.index', array_merge($data, [
            'topSellers' => $topSellers,
            'profitMargin' => $data['grossMarginPercent'], // for backwards compatibility
        ]));
    }

    /**
     * Dedicated Profit & Loss (P&L) Statement & Financial Analysis View
     */
    public function profitAndLoss(Request $request)
    {
        $data = $this->getProfitAndLossData($request);

        return view('reports.pnl', $data);
    }

    /**
     * Printable Formal A4 Profit & Loss Statement (Income Statement)
     */
    public function printPnl(Request $request)
    {
        $data = $this->getProfitAndLossData($request);

        return view('reports.pnl_print', $data);
    }

    /**
     * Export Executive Profit & Loss (P&L) Report as Native Microsoft Excel (.xlsx)
     */
    public function exportPnlExcel(Request $request)
    {
        $data = $this->getProfitAndLossData($request);

        $writer = new \App\Services\SimpleXlsxWriter();

        // 1. Header Banner
        $writer->addRow([
            ['value' => 'PSI ONE GLOBAL POS - PROFIT & LOSS STATEMENT', 'style' => 2],
            ['value' => '', 'style' => 2],
            ['value' => '', 'style' => 2],
            ['value' => '', 'style' => 2],
        ]);
        $writer->addRow([['value' => 'Reporting Period: ' . $data['dateLabel'], 'style' => 1]]);
        $writer->addRow([['value' => 'Outlet / Branch: ' . $data['branchName'], 'style' => 1]]);
        $writer->addRow([['value' => 'Generated On: ' . date('Y-m-d H:i:s') . ' | By: ' . (auth()->user()->name ?? 'System Administrator'), 'style' => 0]]);
        $writer->addRow([]);

        // 2. Section 1: TRADING REVENUE
        $writer->addRow([
            ['value' => '1. TRADING & SALES REVENUE', 'style' => 3],
            ['value' => 'Amount (LKR)', 'style' => 3],
            ['value' => 'Description / Accounting Notes', 'style' => 3],
        ]);
        $writer->addRow([
            ['value' => 'Gross Invoiced Sales (Subtotal)', 'style' => 0],
            ['value' => $data['grossSales'], 'style' => 4, 'type' => 'number'],
            ['value' => 'Total invoiced sales before discount deductions', 'style' => 0],
        ]);
        $writer->addRow([
            ['value' => 'Less: Sales Discounts Allowed', 'style' => 0],
            ['value' => -$data['totalDiscounts'], 'style' => 7, 'type' => 'number'],
            ['value' => 'Point of sale and promotional discount deductions', 'style' => 0],
        ]);
        if ($data['totalReturns'] > 0) {
            $writer->addRow([
                ['value' => 'Less: Sales Returns & Customer Refunds', 'style' => 0],
                ['value' => -$data['totalReturns'], 'style' => 7, 'type' => 'number'],
                ['value' => 'Customer merchandise returns and refunds', 'style' => 0],
            ]);
        }
        $writer->addRow([
            ['value' => 'Tax / VAT Collected', 'style' => 0],
            ['value' => $data['totalTax'], 'style' => 4, 'type' => 'number'],
            ['value' => 'Indirect tax liabilities collected', 'style' => 0],
        ]);
        $writer->addRow([
            ['value' => 'NET SALES TURNOVER', 'style' => 1],
            ['value' => $data['netRevenue'], 'style' => 5, 'type' => 'number'],
            ['value' => 'Effective realized sales revenue', 'style' => 1],
        ]);
        $writer->addRow([]);

        // 3. Section 2: COST OF SALES & GROSS PROFIT
        $writer->addRow([
            ['value' => '2. COST OF SALES & GROSS PROFIT', 'style' => 3],
            ['value' => 'Amount (LKR)', 'style' => 3],
            ['value' => 'Description / Accounting Notes', 'style' => 3],
        ]);
        $writer->addRow([
            ['value' => 'Cost of Goods Sold (COGS)', 'style' => 0],
            ['value' => -$data['totalCogs'], 'style' => 7, 'type' => 'number'],
            ['value' => 'FIFO direct purchase cost of inventory items sold', 'style' => 0],
        ]);
        $writer->addRow([
            ['value' => 'GROSS PROFIT (TRADING SURPLUS)', 'style' => 1],
            ['value' => $data['grossProfit'], 'style' => 6, 'type' => 'number'],
            ['value' => 'Net Sales Turnover minus COGS', 'style' => 1],
        ]);
        $writer->addRow([
            ['value' => 'Gross Profit Margin %', 'style' => 0],
            ['value' => number_format($data['grossMarginPercent'], 1) . '%', 'style' => 1],
            ['value' => 'Gross margin on net revenue', 'style' => 0],
        ]);
        $writer->addRow([
            ['value' => 'Markup on Cost %', 'style' => 0],
            ['value' => number_format($data['markupPercent'], 1) . '%', 'style' => 1],
            ['value' => 'Markup percentage above inventory purchase cost', 'style' => 0],
        ]);
        $writer->addRow([]);

        // 4. Section 3: OPERATING EXPENSES
        $writer->addRow([
            ['value' => '3. OPERATING OVERHEADS & STORE EXPENSES', 'style' => 3],
            ['value' => 'Amount (LKR)', 'style' => 3],
            ['value' => 'Expense Share (%)', 'style' => 3],
        ]);
        if ($data['expensesByCategory']->isEmpty()) {
            $writer->addRow([
                ['value' => 'No Operating Expenses Recorded in this period', 'style' => 0],
                ['value' => 0, 'style' => 4, 'type' => 'number'],
                ['value' => '0.0%', 'style' => 0],
            ]);
        } else {
            foreach ($data['expensesByCategory'] as $exp) {
                $writer->addRow([
                    ['value' => $exp['name'], 'style' => 0],
                    ['value' => -$exp['amount'], 'style' => 7, 'type' => 'number'],
                    ['value' => number_format($exp['percentage'], 1) . '%', 'style' => 0],
                ]);
            }
        }
        $writer->addRow([
            ['value' => 'TOTAL OPERATING EXPENSES', 'style' => 1],
            ['value' => -$data['totalExpenses'], 'style' => 7, 'type' => 'number'],
            ['value' => '100.0%', 'style' => 1],
        ]);
        $writer->addRow([
            ['value' => 'Operating Expense Ratio %', 'style' => 0],
            ['value' => number_format($data['expenseRatio'], 1) . '%', 'style' => 1],
            ['value' => 'Operating expenses as percentage of net turnover', 'style' => 0],
        ]);
        $writer->addRow([]);

        // 5. Section 4: NET OPERATING PROFIT / LOSS
        $writer->addRow([
            ['value' => '4. NET OPERATING RESULT (BOTTOM LINE)', 'style' => 3],
            ['value' => 'Amount (LKR)', 'style' => 3],
            ['value' => 'Performance Status', 'style' => 3],
        ]);
        $isProfitable = ($data['netOperatingResult'] >= 0);
        $writer->addRow([
            ['value' => 'NET OPERATING PROFIT / (LOSS)', 'style' => 1],
            ['value' => $data['netOperatingResult'], 'style' => $isProfitable ? 6 : 9, 'type' => 'number'],
            ['value' => $isProfitable ? 'PROFITABLE (OPERATING SURPLUS)' : 'DEFICIT (OPERATING LOSS)', 'style' => 1],
        ]);
        $writer->addRow([
            ['value' => 'Net Profit Margin %', 'style' => 0],
            ['value' => number_format($data['netMarginPercent'], 1) . '%', 'style' => 1],
            ['value' => 'Net earnings as percentage of turnover', 'style' => 0],
        ]);
        $writer->addRow([]);

        // 6. Section 5: CATEGORY PROFITABILITY BREAKDOWN
        if ($data['categoryProfitability']->isNotEmpty()) {
            $writer->addRow([
                ['value' => '5. DEPARTMENT & CATEGORY PROFITABILITY MATRIX', 'style' => 3],
                ['value' => 'Units Sold', 'style' => 3],
                ['value' => 'Revenue (LKR)', 'style' => 3],
                ['value' => 'COGS Cost (LKR)', 'style' => 3],
                ['value' => 'Gross Profit (LKR)', 'style' => 3],
                ['value' => 'Margin %', 'style' => 3],
            ]);
            foreach ($data['categoryProfitability'] as $cp) {
                $writer->addRow([
                    ['value' => $cp['category'], 'style' => 1],
                    ['value' => $cp['qty'], 'style' => 0, 'type' => 'number'],
                    ['value' => $cp['revenue'], 'style' => 4, 'type' => 'number'],
                    ['value' => -$cp['cogs'], 'style' => 7, 'type' => 'number'],
                    ['value' => $cp['profit'], 'style' => 6, 'type' => 'number'],
                    ['value' => number_format($cp['margin'], 1) . '%', 'style' => 1],
                ]);
            }
            $writer->addRow([]);
        }

        // 7. Section 6: TOP PRODUCT PROFITABILITY
        if ($data['productProfitability']->isNotEmpty()) {
            $writer->addRow([
                ['value' => '6. TOP PROFIT-GENERATING PRODUCTS', 'style' => 3],
                ['value' => 'SKU', 'style' => 3],
                ['value' => 'Units Sold', 'style' => 3],
                ['value' => 'Revenue (LKR)', 'style' => 3],
                ['value' => 'COGS Cost (LKR)', 'style' => 3],
                ['value' => 'Gross Profit (LKR)', 'style' => 3],
                ['value' => 'Margin %', 'style' => 3],
            ]);
            foreach ($data['productProfitability'] as $pp) {
                $writer->addRow([
                    ['value' => $pp['product'], 'style' => 0],
                    ['value' => $pp['sku'] ?? 'N/A', 'style' => 0],
                    ['value' => $pp['qty'], 'style' => 0, 'type' => 'number'],
                    ['value' => $pp['revenue'], 'style' => 4, 'type' => 'number'],
                    ['value' => -$pp['cogs'], 'style' => 7, 'type' => 'number'],
                    ['value' => $pp['profit'], 'style' => 6, 'type' => 'number'],
                    ['value' => number_format($pp['margin'], 1) . '%', 'style' => 1],
                ]);
            }
        }

        $filename = "Profit_and_Loss_Statement_" . date('Y_m_d_His') . ".xlsx";
        $tempPath = tempnam(sys_get_temp_dir(), 'pnl_xlsx_');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export Executive Profit & Loss (P&L) Report as CSV
     */
    public function exportPnlCsv(Request $request)
    {
        $data = $this->getProfitAndLossData($request);

        $filename = "Profit_and_Loss_Statement_" . date('Y_m_d_His') . ".csv";
        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['PSI ONE GLOBAL POS - EXECUTIVE PROFIT & LOSS STATEMENT']);
            fputcsv($file, ['Reporting Period:', $data['dateLabel']]);
            fputcsv($file, ['Branch / Outlet:', $data['branchName']]);
            fputcsv($file, ['Generated On:', date('Y-m-d H:i:s')]);
            fputcsv($file, []);

            // 1. REVENUE
            fputcsv($file, ['1. TRADING & SALES REVENUE', 'Amount (Rs)', 'Description / Notes']);
            fputcsv($file, ['Gross Invoiced Sales', number_format($data['grossSales'], 2, '.', ''), 'Total sales before discounts']);
            fputcsv($file, ['Less: Discounts Allowed', '-' . number_format($data['totalDiscounts'], 2, '.', ''), 'Promotions and store discounts']);
            fputcsv($file, ['Less: Sales Returns & Customer Refunds', '-' . number_format($data['totalReturns'], 2, '.', ''), 'Refunded customer returns']);
            fputcsv($file, ['Tax / VAT Liability Collected', number_format($data['totalTax'], 2, '.', ''), 'Tax collected']);
            fputcsv($file, ['NET SALES TURNOVER', number_format($data['netRevenue'], 2, '.', ''), 'Effective realized revenue']);
            fputcsv($file, []);

            // 2. COGS & GROSS PROFIT
            fputcsv($file, ['2. COST OF GOODS SOLD & GROSS PROFIT', 'Amount (Rs)', 'Description / Notes']);
            fputcsv($file, ['Cost of Goods Sold (COGS)', '-' . number_format($data['totalCogs'], 2, '.', ''), 'Direct purchase cost of inventory items sold']);
            fputcsv($file, ['GROSS PROFIT', number_format($data['grossProfit'], 2, '.', ''), 'Net Sales Revenue - COGS']);
            fputcsv($file, ['Gross Profit Margin', number_format($data['grossMarginPercent'], 2, '.', '') . '%', 'Margin percentage on revenue']);
            fputcsv($file, ['Markup on Cost', number_format($data['markupPercent'], 2, '.', '') . '%', 'Markup percentage over cost']);
            fputcsv($file, []);

            // 3. OPERATING EXPENSES
            fputcsv($file, ['3. OPERATING EXPENSES (STORE OVERHEADS)', 'Amount (Rs)', 'Share of Total Expenses']);
            if ($data['expensesByCategory']->isEmpty()) {
                fputcsv($file, ['No Operating Expenses Recorded in this period', '0.00', '0.0%']);
            } else {
                foreach ($data['expensesByCategory'] as $exp) {
                    fputcsv($file, [
                        $exp['name'],
                        number_format($exp['amount'], 2, '.', ''),
                        number_format($exp['percentage'], 1, '.', '') . '%'
                    ]);
                }
            }
            fputcsv($file, ['TOTAL OPERATING EXPENSES', '-' . number_format($data['totalExpenses'], 2, '.', ''), '100.0%']);
            fputcsv($file, ['Operating Expense Ratio', number_format($data['expenseRatio'], 2, '.', '') . '%', 'Expenses relative to net sales']);
            fputcsv($file, []);

            // 4. NET PROFIT / BOTTOM LINE
            fputcsv($file, ['4. NET OPERATING RESULT (BOTTOM LINE)', 'Amount (Rs)', 'Operational Status']);
            fputcsv($file, [
                'NET OPERATING PROFIT / (LOSS)',
                number_format($data['netOperatingResult'], 2, '.', ''),
                $data['netOperatingResult'] >= 0 ? 'PROFITABLE' : 'OPERATING LOSS'
            ]);
            fputcsv($file, ['Net Profit Margin', number_format($data['netMarginPercent'], 2, '.', '') . '%', 'Net profit as % of revenue']);
            fputcsv($file, []);

            // 5. CATEGORY PROFITABILITY
            fputcsv($file, ['5. CATEGORY PROFITABILITY BREAKDOWN']);
            fputcsv($file, ['Category Name', 'Units Sold', 'Revenue (Rs)', 'COGS (Rs)', 'Gross Profit (Rs)', 'Margin %']);
            foreach ($data['categoryProfitability'] as $cp) {
                fputcsv($file, [
                    $cp['category'],
                    $cp['qty'],
                    number_format($cp['revenue'], 2, '.', ''),
                    number_format($cp['cogs'], 2, '.', ''),
                    number_format($cp['profit'], 2, '.', ''),
                    number_format($cp['margin'], 1, '.', '') . '%'
                ]);
            }
            fputcsv($file, []);

            // 6. TOP PRODUCT PROFITABILITY
            fputcsv($file, ['6. TOP PRODUCT PROFITABILITY MATRIX']);
            fputcsv($file, ['Product & Variation', 'SKU', 'Units Sold', 'Revenue (Rs)', 'COGS (Rs)', 'Gross Profit (Rs)', 'Margin %']);
            foreach ($data['productProfitability'] as $pp) {
                fputcsv($file, [
                    $pp['product'],
                    $pp['sku'],
                    $pp['qty'],
                    number_format($pp['revenue'], 2, '.', ''),
                    number_format($pp['cogs'], 2, '.', ''),
                    number_format($pp['profit'], 2, '.', ''),
                    number_format($pp['margin'], 1, '.', '') . '%'
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Cashier Shift Z-Reports & Daily Cash Reconciliation
     */
    public function shifts(Request $request)
    {
        $branches = Branch::where('status', 'active')->get();
        $selectedBranchId = $request->query('branch_id');
        $dateFilter = $request->query('date_filter', 'all');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = CashierShift::with(['user', 'register', 'branch']);
        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        $dateRange = null;
        if ($dateFilter === 'today') {
            $dateRange = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
        } elseif ($dateFilter === 'yesterday') {
            $dateRange = [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
        } elseif ($dateFilter === 'this_week') {
            $dateRange = [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
        } elseif ($dateFilter === 'this_month') {
            $dateRange = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
        } elseif ($dateFilter === 'custom' && $startDate && $endDate) {
            $dateRange = [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
        }

        if ($dateRange) {
            $query->whereBetween('opened_at', $dateRange);
        }

        // Summary metrics
        $totalShiftsCount = (clone $query)->count();
        $totalCashSales = (float) (clone $query)->sum('cash_sales');
        $totalCardSales = (float) (clone $query)->sum('card_sales');
        $totalDiscrepancy = (float) (clone $query)->sum('cash_discrepancy');
        $activeShiftsCount = (clone $query)->where('status', 'open')->count();

        $shifts = $query->latest('opened_at')->paginate(20);

        return view('reports.shifts', compact(
            'shifts',
            'branches',
            'selectedBranchId',
            'dateFilter',
            'startDate',
            'endDate',
            'totalShiftsCount',
            'totalCashSales',
            'totalCardSales',
            'totalDiscrepancy',
            'activeShiftsCount'
        ));
    }

    /**
     * Printable Formal A4 & 80mm POS Thermal Slip Cashier Shift Z-Report
     */
    public function printShiftZReport($id)
    {
        $shift = CashierShift::with(['user', 'register', 'branch', 'sales.payments'])->findOrFail($id);

        $totalSalesCount = $shift->sales->count();
        $totalTurnover = (float) $shift->sales->sum('grand_total');
        $openingFloat = (float) $shift->opening_float;
        $cashSales = (float) $shift->cash_sales;
        $cardSales = (float) $shift->card_sales;
        $otherSales = (float) $shift->other_sales;
        $expectedCash = $openingFloat + $cashSales;
        $closingCash = (float) ($shift->closing_cash ?? $expectedCash);
        $discrepancy = (float) ($shift->cash_discrepancy ?? ($closingCash - $expectedCash));

        // Payment distribution in this shift
        $paymentDistribution = DB::table('sale_payments')
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->where('sales.shift_id', $shift->id)
            ->select('sale_payments.payment_method', DB::raw('SUM(sale_payments.amount) as total_amount'), DB::raw('COUNT(*) as tx_count'))
            ->groupBy('sale_payments.payment_method')
            ->get();

        return view('reports.shift_z_print', compact(
            'shift',
            'totalSalesCount',
            'totalTurnover',
            'openingFloat',
            'cashSales',
            'cardSales',
            'otherSales',
            'expectedCash',
            'closingCash',
            'discrepancy',
            'paymentDistribution'
        ));
    }

    /**
     * Export Cashier Shift Z-Reports as Native Microsoft Excel (.xlsx)
     */
    public function exportShiftsExcel(Request $request)
    {
        $branches = Branch::where('status', 'active')->get();
        $selectedBranchId = $request->query('branch_id');
        $dateFilter = $request->query('date_filter', 'all');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = CashierShift::with(['user', 'register', 'branch']);
        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        $dateRange = null;
        $dateLabel = 'All Time';
        if ($dateFilter === 'today') {
            $dateRange = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            $dateLabel = 'Today (' . Carbon::today()->format('d M Y') . ')';
        } elseif ($dateFilter === 'yesterday') {
            $dateRange = [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
            $dateLabel = 'Yesterday (' . Carbon::yesterday()->format('d M Y') . ')';
        } elseif ($dateFilter === 'this_week') {
            $dateRange = [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            $dateLabel = 'This Week';
        } elseif ($dateFilter === 'this_month') {
            $dateRange = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            $dateLabel = 'This Month (' . Carbon::now()->format('F Y') . ')';
        } elseif ($dateFilter === 'custom' && $startDate && $endDate) {
            $dateRange = [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
            $dateLabel = $startDate . ' to ' . $endDate;
        }

        if ($dateRange) {
            $query->whereBetween('opened_at', $dateRange);
        }

        $shifts = $query->latest('opened_at')->get();
        $writer = new \App\Services\SimpleXlsxWriter();

        // Title & Meta
        $writer->addRow([['value' => 'PSI ONE GLOBAL POS - CASHIER SHIFT Z-REPORTS & CASH AUDIT', 'style' => 2]]);
        $writer->addRow([['value' => 'Report Timeframe: ' . $dateLabel, 'style' => 1]]);
        $writer->addRow([['value' => 'Generated At: ' . date('Y-m-d H:i:s') . ' | By: ' . (auth()->user()->name ?? 'System Administrator'), 'style' => 0]]);
        $writer->addRow([]);

        // Table Header
        $writer->addRow([
            ['value' => 'Shift ID', 'style' => 3],
            ['value' => 'Date & Time Opened', 'style' => 3],
            ['value' => 'Time Closed', 'style' => 3],
            ['value' => 'Cashier Name', 'style' => 3],
            ['value' => 'Counter Register', 'style' => 3],
            ['value' => 'Branch Store', 'style' => 3],
            ['value' => 'Opening Float (LKR)', 'style' => 3],
            ['value' => 'Cash Sales (LKR)', 'style' => 3],
            ['value' => 'Card Sales (LKR)', 'style' => 3],
            ['value' => 'Total Invoiced (LKR)', 'style' => 3],
            ['value' => 'Expected Cash (LKR)', 'style' => 3],
            ['value' => 'Counted Cash (LKR)', 'style' => 3],
            ['value' => 'Variance (LKR)', 'style' => 3],
            ['value' => 'Reconciliation Status', 'style' => 3],
            ['value' => 'Status', 'style' => 3],
        ]);

        foreach ($shifts as $s) {
            $expected = (float) ($s->expected_cash ?: ($s->opening_float + $s->cash_sales));
            $counted = (float) ($s->closing_cash ?: $expected);
            $diff = (float) $s->cash_discrepancy;
            $totalSales = (float) ($s->cash_sales + $s->card_sales + $s->other_sales);

            $statusText = 'Balanced (0.00)';
            $diffStyle = 4;
            if ($diff < 0) {
                $statusText = 'SHORTAGE (-' . number_format(abs($diff), 2) . ')';
                $diffStyle = 7;
            } elseif ($diff > 0) {
                $statusText = 'SURPLUS (+' . number_format($diff, 2) . ')';
                $diffStyle = 6;
            }

            $writer->addRow([
                ['value' => '#' . str_pad($s->id, 5, '0', STR_PAD_LEFT), 'style' => 1],
                ['value' => $s->opened_at ? $s->opened_at->format('Y-m-d H:i') : 'N/A', 'style' => 0],
                ['value' => $s->closed_at ? $s->closed_at->format('Y-m-d H:i') : 'Active Shift', 'style' => 0],
                ['value' => $s->user->name ?? 'Unknown', 'style' => 1],
                ['value' => $s->register->name ?? 'Terminal', 'style' => 0],
                ['value' => $s->branch->name ?? 'Main Store', 'style' => 0],
                ['value' => (float)$s->opening_float, 'style' => 4, 'type' => 'number'],
                ['value' => (float)$s->cash_sales, 'style' => 4, 'type' => 'number'],
                ['value' => (float)$s->card_sales, 'style' => 4, 'type' => 'number'],
                ['value' => $totalSales, 'style' => 5, 'type' => 'number'],
                ['value' => $expected, 'style' => 4, 'type' => 'number'],
                ['value' => $counted, 'style' => 5, 'type' => 'number'],
                ['value' => $diff, 'style' => $diffStyle, 'type' => 'number'],
                ['value' => $statusText, 'style' => 1],
                ['value' => strtoupper($s->status), 'style' => 1],
            ]);
        }

        $filename = "Cashier_Shift_Z_Reports_" . date('Y_m_d_His') . ".xlsx";
        $tempPath = tempnam(sys_get_temp_dir(), 'shifts_xlsx_');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    /**
     * Detailed Invoices Sales Ledger Export (CSV)
     */
    public function exportCsv(Request $request)
    {
        $sales = Sale::with(['branch', 'cashier', 'customer', 'payments'])->latest()->get();

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=psi_sales_report_" . date('Y_m_d_His') . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($sales) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Invoice No', 'Date', 'Branch', 'Cashier', 'Customer', 'Subtotal (Rs)', 'Discount (Rs)', 'Tax (Rs)', 'Grand Total (Rs)', 'Payment Method', 'Status']);

            foreach ($sales as $s) {
                $methods = $s->payments->pluck('payment_method')->unique()->implode(', ');
                fputcsv($file, [
                    $s->invoice_no,
                    $s->created_at->format('Y-m-d H:i:s'),
                    $s->branch->name ?? 'Main',
                    $s->cashier->name ?? 'Cashier',
                    $s->customer->name ?? 'Walk-in',
                    number_format($s->subtotal, 2, '.', ''),
                    number_format($s->discount_amount, 2, '.', ''),
                    number_format($s->tax_amount, 2, '.', ''),
                    number_format($s->grand_total, 2, '.', ''),
                    $methods ?: 'cash',
                    $s->payment_status,
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
