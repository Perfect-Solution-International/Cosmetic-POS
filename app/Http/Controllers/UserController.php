<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Staff management is restricted to system administrators.');
        }

        $query = User::with('branch');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $metrics = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'cashiers_count' => User::where('role', 'cashier')->count(),
            'managers_count' => User::whereIn('role', ['admin', 'super_admin', 'manager', 'branch_manager', 'head_office_manager'])->count(),
        ];

        $users = $query->latest()->paginate(15);
        $branches = Branch::where('status', 'active')->get();
        
        $roles = [
            'admin' => 'System Administrator (Full Access)',
            'head_office_manager' => 'Head Office Operations Manager',
            'manager' => 'Store / Branch Manager',
            'cashier' => 'Front Desk Cashier',
            'inventory_staff' => 'Inventory Controller / Stock Keeper',
            'warehouse_manager' => 'Central Warehouse Manager',
            'accountant' => 'Financial Accountant',
        ];

        $permissionsGuide = [
            'admin' => [
                'title' => 'System Administrator',
                'description' => 'Unrestricted access to all multi-branch settings, financial ledgers, staff user accounts, master catalog, and POS billing.',
                'features' => ['All Terminal POS Actions', 'Staff & User Roles Management', 'Branch & Counter Setup', 'Master Inventory & Catalogs', 'Financial Ledgers & Audits'],
            ],
            'head_office_manager' => [
                'title' => 'Head Office Manager',
                'description' => 'Central operations oversight across all branches. Catalog pricing, purchase orders, inventory distributions, and high-level analytics.',
                'features' => ['All Branches Overview', 'Master Price & Attribute Management', 'Supplier Purchase Orders & GRN', 'Inter-Branch Stock Transfers', 'Consolidated Sales Reports'],
            ],
            'manager' => [
                'title' => 'Store / Branch Manager',
                'description' => 'Complete store-level operational control including shifts, daily cash, price discounts, stock transfers, and store expenses.',
                'features' => ['POS Cashier Register & Shift Audits', 'Daily Petty Cash Expenses', 'Branch Stock & Expiry Tracking', 'Customer CRM & Loyalty Points', 'Branch Sales Invoices & Refunds'],
            ],
            'cashier' => [
                'title' => 'Front Desk Cashier',
                'description' => 'Dedicated front-line point-of-sale checkout operator. Fast barcode scanning, bill printing, and customer rewards.',
                'features' => ['Barcode Scanner & Quick Billing', 'Cash, Card & Split Payments', 'Receipt & Invoice Printing', 'Customer Registration & Points', 'Daily Shift Opening & Closing'],
            ],
            'inventory_staff' => [
                'title' => 'Inventory Controller',
                'description' => 'Stock keeper responsible for warehouse storage, goods receipt verification (GRN), expiry date monitoring, and stock transfers.',
                'features' => ['Goods Received Notes (GRN)', 'Batch Numbers & Expiry Date Alerts', 'Stock Transfers & Receptions', 'Physical Stock Counts & Adjustments', 'Product Catalog Price Lookup'],
            ],
            'warehouse_manager' => [
                'title' => 'Central Warehouse Manager',
                'description' => 'Manages bulk supplier procurements, central distribution dispatching to retail branches, and vendor purchase orders.',
                'features' => ['Purchase Orders & Backorders', 'Vendor Management & Supply Records', 'Warehouse Batch Management', 'Bulk Inter-Branch Transfers', 'Stock Replenishments'],
            ],
            'accountant' => [
                'title' => 'Financial Accountant',
                'description' => 'Handles financial reporting, petty cash reconciliation, supplier credit invoices, and store profitability.',
                'features' => ['Daily Store Expense Records', 'Supplier Payment Vouchers', 'Sales Ledger & Cash Flow Reports', 'Stock Valuation & Margins', 'Shift Financial Reports'],
            ],
        ];

        return view('users.index', compact('users', 'branches', 'roles', 'metrics', 'permissionsGuide'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Staff management is restricted to system administrators.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,super_admin,head_office_manager,manager,branch_manager,cashier,inventory_staff,warehouse_manager,accountant',
            'branch_id' => 'nullable|exists:branches,id',
            'phone' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive',
        ], [
            'username.unique' => 'This username is already taken by another staff member.',
            'email.unique' => 'This email address is already in use.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('status', 'Staff member ' . $validated['name'] . ' registered successfully.');
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Staff management is restricted to system administrators.');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => 'required|string|in:admin,super_admin,head_office_manager,manager,branch_manager,cashier,inventory_staff,warehouse_manager,accountant',
            'branch_id' => 'nullable|exists:branches,id',
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:6',
        ], [
            'username.unique' => 'This username is already taken by another staff member.',
            'email.unique' => 'This email address is already in use.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Prevent self-deactivation or demotion of current user
        if ($user->id === auth()->id() && $validated['status'] !== 'active') {
            return redirect()->route('users.index')->with('error', 'You cannot deactivate your own administrative account.');
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('status', 'Staff details for ' . $user->name . ' updated successfully.');
    }

    public function resetPassword(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized. Staff management is restricted to system administrators.');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'new_password.min' => 'Password must be at least 6 characters long.',
            'new_password.confirmed' => 'The password confirmation does not match.',
        ]);

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return redirect()->route('users.index')
            ->with('status', 'Password for ' . $user->name . ' has been reset successfully.');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $user = User::findOrFail($id);
        
        // Prevent disabling yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot deactivate your own account.');
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return redirect()->route('users.index')
            ->with('status', 'Staff account status updated for ' . $user->name . ' (' . ucfirst($user->status) . ').');
    }

    public function destroy($id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own administrative account.');
        }

        // Check if user has active cashier shift or sales records
        if ($user->shifts()->where('status', 'open')->exists()) {
            return redirect()->route('users.index')->with('error', 'Cannot delete user ' . $user->name . ' because they currently have an open cashier shift. Close the shift first.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('status', 'Staff user ' . $userName . ' removed successfully.');
    }

    public function downloadBackup()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $backupData = [
            'generated_at' => now()->toIso8601String(),
            'app_version' => 'PSI ONE POS v2.4 SaaS',
            'branches' => Branch::all(),
            'users' => User::select('id', 'name', 'username', 'email', 'role', 'phone', 'status', 'created_at')->get(),
            'categories' => \App\Models\Category::all(),
            'brands' => \App\Models\Brand::all(),
            'products' => \App\Models\Product::with(['variations.stocks'])->get(),
            'customers' => \App\Models\Customer::with('payments')->get(),
            'suppliers' => \App\Models\Supplier::all(),
            'sales' => \App\Models\Sale::with(['items', 'payments'])->latest()->take(500)->get(),
            'expenses' => \App\Models\Expense::all(),
        ];

        $json = json_encode($backupData, JSON_PRETTY_PRINT);
        $fileName = 'psi_pos_backup_' . date('Y_m_d_His') . '.json';

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
