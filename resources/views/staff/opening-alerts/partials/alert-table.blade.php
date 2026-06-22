<div class="card mb-3">
    <div class="card-body">
        <h5>{{ $title }}</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Kategori</th>
                        <th>Stok Saat Ini</th>
                        <th>Minimum Stock</th>
                        <th>Satuan</th>
                        <th>Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td>{{ $alert['stock']->item->name ?? '-' }}</td>
                            <td><span class="badge bg-warning text-dark">{{ $alert['category'] }}</span></td>
                            <td>{{ number_format((float) $alert['stock']->current_stock, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) ($alert['stock']->item->min_stock ?? 0), 2, ',', '.') }}</td>
                            <td>{{ $alert['stock']->unit ?? $alert['stock']->item->base_unit ?? '-' }}</td>
                            <td>{{ $alert['recommendation'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Tidak ada alert.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
