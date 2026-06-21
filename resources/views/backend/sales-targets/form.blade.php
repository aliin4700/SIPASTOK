@csrf
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label" for="outlet_id">Outlet</label>
        <select name="outlet_id" id="outlet_id" class="form-control">
            <option value="">Semua Outlet</option>
            @foreach($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $salesTarget->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="period_type">Period Type</label>
        <select name="period_type" id="period_type" class="form-control" required>
            @foreach(\App\Models\SalesTarget::PERIOD_TYPES as $type)
                <option value="{{ $type }}" @selected(old('period_type', $salesTarget->period_type) === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="target_date">Target Date</label>
        <input type="date" name="target_date" id="target_date" value="{{ old('target_date', optional($salesTarget->target_date)->format('Y-m-d')) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="target_month">Target Month</label>
        <input type="number" min="1" max="12" name="target_month" id="target_month" value="{{ old('target_month', $salesTarget->target_month) }}" class="form-control">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="target_year">Target Year</label>
        <input type="number" min="2000" max="2100" name="target_year" id="target_year" value="{{ old('target_year', $salesTarget->target_year ?? now()->year) }}" class="form-control">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="target_amount">Target Amount</label>
        <input type="number" min="0" step="1000" name="target_amount" id="target_amount" value="{{ old('target_amount', $salesTarget->target_amount) }}" class="form-control" required>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="notes">Notes</label>
        <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $salesTarget->notes) }}</textarea>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<button type="submit" class="btn btn-primary">Simpan</button>
<a href="{{ route('backend.sales-targets.index') }}" class="btn btn-secondary">Batal</a>
