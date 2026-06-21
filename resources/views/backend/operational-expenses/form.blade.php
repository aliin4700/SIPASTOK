@csrf
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label" for="outlet_id">Outlet</label>
        <select name="outlet_id" id="outlet_id" class="form-control">
            <option value="">Semua Outlet</option>
            @foreach($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $operationalExpense->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="expense_date">Expense Date</label>
        <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', optional($operationalExpense->expense_date)->format('Y-m-d')) }}" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="category">Category</label>
        <select name="category" id="category" class="form-control" required>
            @foreach(\App\Models\OperationalExpense::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(old('category', $operationalExpense->category) === $category)>{{ str_replace('_', ' ', $category) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="amount">Amount</label>
        <input type="number" min="0" step="1000" name="amount" id="amount" value="{{ old('amount', $operationalExpense->amount) }}" class="form-control" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="payment_method">Payment Method</label>
        <input type="text" name="payment_method" id="payment_method" value="{{ old('payment_method', $operationalExpense->payment_method) }}" class="form-control">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="status">Status</label>
        <select name="status" id="status" class="form-control" required>
            @foreach(\App\Models\OperationalExpense::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $operationalExpense->status) === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="description">Description</label>
        <input type="text" name="description" id="description" value="{{ old('description', $operationalExpense->description) }}" class="form-control">
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="notes">Notes</label>
        <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $operationalExpense->notes) }}</textarea>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<button type="submit" class="btn btn-primary">Simpan</button>
<a href="{{ route('backend.operational-expenses.index') }}" class="btn btn-secondary">Batal</a>
