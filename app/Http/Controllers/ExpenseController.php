<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('status', 'active')->get();
        $categories = ExpenseCategory::all();

        $query = Expense::with(['category', 'branch', 'user']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $expenses = $query->latest()->paginate(15);
        $totalExpense = (clone $query)->sum('amount');

        return view('expenses.index', compact('expenses', 'branches', 'categories', 'totalExpense'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        Expense::create($validated);

        return redirect()->route('expenses.index')
            ->with('status', 'Expense voucher recorded successfully.');
    }
}

