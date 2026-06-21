<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\OperationalExpense;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationalExpenseController extends Controller
{
    public function index()
    {
        return view('backend.operational-expenses.index', [
            'operationalExpenses' => OperationalExpense::with(['outlet', 'recorder'])->latest('expense_date')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('backend.operational-expenses.create', [
            'operationalExpense' => new OperationalExpense(['expense_date' => now()->toDateString(), 'status' => 'recorded']),
            'outlets' => Outlet::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['recorded_by'] = auth()->id();
        $data['approved_by'] = $data['status'] === 'approved' ? auth()->id() : null;
        $data['expense_month'] = (int) date('m', strtotime($data['expense_date']));
        $data['expense_year'] = (int) date('Y', strtotime($data['expense_date']));

        OperationalExpense::create($data);

        return redirect()->route('backend.operational-expenses.index')->with('success', 'Operational expense berhasil dibuat.');
    }

    public function edit(OperationalExpense $operationalExpense)
    {
        return view('backend.operational-expenses.edit', [
            'operationalExpense' => $operationalExpense,
            'outlets' => Outlet::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, OperationalExpense $operationalExpense)
    {
        $data = $this->validated($request);
        $data['approved_by'] = $data['status'] === 'approved' ? auth()->id() : null;
        $data['expense_month'] = (int) date('m', strtotime($data['expense_date']));
        $data['expense_year'] = (int) date('Y', strtotime($data['expense_date']));

        $operationalExpense->update($data);

        return redirect()->route('backend.operational-expenses.index')->with('success', 'Operational expense berhasil diperbarui.');
    }

    public function destroy(OperationalExpense $operationalExpense)
    {
        $operationalExpense->delete();

        return redirect()->route('backend.operational-expenses.index')->with('success', 'Operational expense berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'expense_date' => ['required', 'date'],
            'category' => ['required', Rule::in(OperationalExpense::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(OperationalExpense::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
