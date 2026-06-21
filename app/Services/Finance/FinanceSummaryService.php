<?php

namespace App\Services\Finance;

use App\Models\OperationalExpense;
use App\Models\Payment;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class FinanceSummaryService
{
    private const PAID_STATUSES = ['paid', 'settled', 'confirmed'];

    public function summary(?string $date = null, ?int $month = null, ?int $year = null, ?int $outletId = null): array
    {
        $date = $date ?: now()->toDateString();
        $month = $month ?: (int) now()->format('m');
        $year = $year ?: (int) now()->format('Y');
        $cogsPercentage = max(0, (float) config('services.finance.default_cogs_percentage', 40));

        $salesToday = $this->paidSalesQuery($outletId)
            ->whereDate('orders.created_at', $date)
            ->sum('orders.total_price');

        $salesMonth = $this->paidSalesQuery($outletId)
            ->whereMonth('orders.created_at', $month)
            ->whereYear('orders.created_at', $year)
            ->sum('orders.total_price');

        $dailyTarget = $this->targetQuery($outletId)
            ->where('period_type', 'daily')
            ->whereDate('target_date', $date)
            ->sum('target_amount');

        $monthlyTarget = $this->targetQuery($outletId)
            ->where('period_type', 'monthly')
            ->where('target_month', $month)
            ->where('target_year', $year)
            ->sum('target_amount');

        $operationalExpense = OperationalExpense::query()
            ->when($outletId, fn (Builder $query) => $query->where('outlet_id', $outletId))
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->where('status', '!=', 'rejected')
            ->sum('amount');

        $discountTotal = 0;
        $refundTotal = 0;
        $netSales = (float) $salesMonth - $discountTotal - $refundTotal;
        $estimatedCogs = $netSales * $cogsPercentage / 100;
        $grossProfit = $netSales - $estimatedCogs;
        $netProfit = $grossProfit - (float) $operationalExpense;

        return [
            'date' => Carbon::parse($date)->toDateString(),
            'month' => $month,
            'year' => $year,
            'outlet_id' => $outletId,
            'sales_today' => (float) $salesToday,
            'daily_target' => (float) $dailyTarget,
            'daily_target_achievement' => $dailyTarget > 0 ? (float) $salesToday / (float) $dailyTarget * 100 : 0,
            'sales_month' => (float) $salesMonth,
            'monthly_target' => (float) $monthlyTarget,
            'monthly_target_achievement' => $monthlyTarget > 0 ? (float) $salesMonth / (float) $monthlyTarget * 100 : 0,
            'gross_sales' => (float) $salesMonth,
            'discount_total' => $discountTotal,
            'refund_total' => $refundTotal,
            'net_sales' => $netSales,
            'cogs_percentage' => $cogsPercentage,
            'estimated_cogs' => $estimatedCogs,
            'gross_profit' => $grossProfit,
            'operational_expense' => (float) $operationalExpense,
            'net_profit' => $netProfit,
            'net_profit_margin' => $netSales > 0 ? $netProfit / $netSales * 100 : 0,
        ];
    }

    private function paidSalesQuery(?int $outletId)
    {
        return Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->whereIn('payments.payment_status', self::PAID_STATUSES)
            ->when($outletId, fn ($query) => $query->where('orders.outlet_id', $outletId));
    }

    private function targetQuery(?int $outletId)
    {
        return SalesTarget::query()
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId));
    }
}
