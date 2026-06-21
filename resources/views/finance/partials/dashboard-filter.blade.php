<form action="{{ $action }}" method="get" class="row g-2 align-items-end mb-4">
    <div class="col-md-3">
        <label for="date" class="form-label">Tanggal</label>
        <input type="date" name="date" id="date" value="{{ request('date', $summary['date']) }}" class="form-control">
    </div>
    <div class="col-md-2">
        <label for="month" class="form-label">Bulan</label>
        <input type="number" min="1" max="12" name="month" id="month" value="{{ request('month', $summary['month']) }}" class="form-control">
    </div>
    <div class="col-md-2">
        <label for="year" class="form-label">Tahun</label>
        <input type="number" min="2000" max="2100" name="year" id="year" value="{{ request('year', $summary['year']) }}" class="form-control">
    </div>
    <div class="col-md-3">
        <label for="outlet_id" class="form-label">Outlet</label>
        <select name="outlet_id" id="outlet_id" class="form-control">
            <option value="">Semua Outlet</option>
            @foreach($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected((string) request('outlet_id') === (string) $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>
