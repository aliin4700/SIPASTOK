@php
    $money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $percent = fn ($value) => number_format((float) $value, 1, ',', '.') . '%';
@endphp

<div class="row g-3 mb-4">
    @foreach([
        'Sales Today' => $money($summary['sales_today']),
        'Daily Target Achievement' => $percent($summary['daily_target_achievement']),
        'Sales This Month' => $money($summary['sales_month']),
        'Monthly Target Achievement' => $percent($summary['monthly_target_achievement']),
        'Estimated COGS (' . $summary['cogs_percentage'] . '%)' => $money($summary['estimated_cogs']),
        'Operational Expense' => $money($summary['operational_expense']),
        'Gross Profit' => $money($summary['gross_profit']),
        'Net Profit' => $money($summary['net_profit']),
        'Net Profit Margin' => $percent($summary['net_profit_margin']),
    ] as $label => $value)
        <div class="col-md-4 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="h5 mb-0">{{ $value }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
