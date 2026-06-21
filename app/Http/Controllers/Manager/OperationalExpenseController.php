<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\OperationalExpense;

class OperationalExpenseController extends Controller
{
    public function index()
    {
        return view('manager.operational-expenses.index', [
            'title' => 'Operational Expenses',
            'operationalExpenses' => OperationalExpense::with(['outlet', 'recorder'])->latest('expense_date')->paginate(15),
        ]);
    }
}
