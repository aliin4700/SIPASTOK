<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\SalesTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SalesTargetController extends Controller
{
    public function index()
    {
        return view('backend.sales-targets.index', [
            'salesTargets' => SalesTarget::with(['outlet', 'creator'])->latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return view('backend.sales-targets.create', [
            'salesTarget' => new SalesTarget(),
            'outlets' => Outlet::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();

        SalesTarget::create($data);

        return redirect()->route('backend.sales-targets.index')->with('success', 'Sales target berhasil dibuat.');
    }

    public function edit(SalesTarget $salesTarget)
    {
        return view('backend.sales-targets.edit', [
            'salesTarget' => $salesTarget,
            'outlets' => Outlet::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SalesTarget $salesTarget)
    {
        $salesTarget->update($this->validated($request));

        return redirect()->route('backend.sales-targets.index')->with('success', 'Sales target berhasil diperbarui.');
    }

    public function destroy(SalesTarget $salesTarget)
    {
        $salesTarget->delete();

        return redirect()->route('backend.sales-targets.index')->with('success', 'Sales target berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'period_type' => ['required', Rule::in(SalesTarget::PERIOD_TYPES)],
            'target_date' => ['nullable', 'date'],
            'target_month' => ['nullable', 'integer', 'between:1,12'],
            'target_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'target_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['period_type'] === 'daily' && empty($data['target_date'])) {
            throw ValidationException::withMessages(['target_date' => 'Tanggal wajib diisi untuk target harian.']);
        }

        if (in_array($data['period_type'], ['monthly', 'yearly'], true) && empty($data['target_year'])) {
            throw ValidationException::withMessages(['target_year' => 'Tahun wajib diisi untuk target bulanan/tahunan.']);
        }

        if ($data['period_type'] === 'monthly' && empty($data['target_month'])) {
            throw ValidationException::withMessages(['target_month' => 'Bulan wajib diisi untuk target bulanan.']);
        }

        return $data;
    }
}
