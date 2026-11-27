<div class="card">
    <div class="card-body">
        <h5>Material / BOM</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Waste %</th>
                        <th>Effective Qty</th>
                        <th>Unit Cost</th>
                        <th>Estimated Cost</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalCost = 0;
                    @endphp
                    @foreach($recipe->items as $recipeItem)
                        @php
                            $unitCost = $recipeItem->item?->last_purchase_price;
                            $estimatedCost = is_numeric($unitCost) ? $recipeItem->effective_qty * (float) $unitCost : null;
                            if ($estimatedCost !== null) {
                                $totalCost += $estimatedCost;
                            }
                        @endphp
                        <tr>
                            <td>{{ $recipeItem->item->name ?? '-' }}</td>
                            <td>{{ number_format((float) $recipeItem->qty, 4, ',', '.') }} {{ $recipeItem->unit }}</td>
                            <td>{{ number_format((float) $recipeItem->waste_percentage, 2, ',', '.') }}%</td>
                            <td>{{ number_format($recipeItem->effective_qty, 4, ',', '.') }} {{ $recipeItem->unit }}</td>
                            <td>{{ is_numeric($unitCost) ? 'Rp ' . number_format((float) $unitCost, 0, ',', '.') : 'cost belum tersedia' }}</td>
                            <td>{{ $estimatedCost !== null ? 'Rp ' . number_format($estimatedCost, 0, ',', '.') : '-' }}</td>
                            <td>{{ $recipeItem->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <th colspan="5" class="text-right">Total Estimated Cost</th>
                        <th>Rp {{ number_format($totalCost, 0, ',', '.') }}</th>
                        <th></th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
