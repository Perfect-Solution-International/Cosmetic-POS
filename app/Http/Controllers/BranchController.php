<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Register;
use App\Models\Subscription;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $branches = Branch::with(['registers', 'users', 'owner'])->get();

        $activeSub = Subscription::whereIn('status', ['trial', 'active', 'grace_period'])
            ->when($user && $user->isShopOwner(), fn($q) => $q->where('shop_owner_id', $user->id))
            ->latest()
            ->first();

        $allocatedBranches = $activeSub?->allocated_branches ?? max(5, $branches->count());
        $usedBranches = $branches->where('status', 'active')->count();

        return view('branches.index', compact('branches', 'activeSub', 'allocatedBranches', 'usedBranches'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Enforce Subscription Multi-Outlet Quota
        $activeSub = Subscription::whereIn('status', ['trial', 'active', 'grace_period'])
            ->when($user && $user->isShopOwner(), fn($q) => $q->where('shop_owner_id', $user->id))
            ->latest()
            ->first();

        if ($activeSub && $activeSub->allocated_branches) {
            $currentActiveCount = Branch::where('status', 'active')
                ->when($user && $user->isShopOwner(), fn($q) => $q->where('owner_id', $user->id))
                ->count();

            if ($currentActiveCount >= $activeSub->allocated_branches) {
                return back()->withErrors([
                    'branch_quota' => "Branch Quota Limit Reached! Your plan allows {$activeSub->allocated_branches} outlets (Currently active: {$currentActiveCount}). Please upgrade your subscription to add more branches."
                ])->withInput();
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'type' => 'required|in:retail_store,central_warehouse,franchise',
            'receipt_header' => 'nullable|string',
            'receipt_footer' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        if ($user && $user->isShopOwner()) {
            $validated['owner_id'] = $user->id;
        } elseif (empty($validated['owner_id']) && $activeSub?->shop_owner_id) {
            $validated['owner_id'] = $activeSub->shop_owner_id;
        }

        Branch::create($validated);

        return redirect()->route('branches.index')
            ->with('status', 'Branch ' . $validated['name'] . ' created successfully.');
    }

    public function storeRegister(Request $request, $branchId)
    {
        $branch = Branch::findOrFail($branchId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:registers,code',
        ]);

        $branch->registers()->create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'status' => 'closed',
        ]);

        return redirect()->route('branches.index')
            ->with('status', 'POS Register Counter created for ' . $branch->name . '.');
    }
}


