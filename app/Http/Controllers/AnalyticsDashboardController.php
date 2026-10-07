<?php

namespace App\Http\Controllers;

use App\Support\TenantManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $tenantId = app(TenantManager::class)->current()->id;
        $cacheKey = "analytics:dashboard:{$tenantId}";

        $data = Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            return [
                'monthly_revenue' => $this->monthlyRevenue($tenantId),
                'invoice_summary' => $this->invoiceSummary($tenantId),
                'encounter_types' => $this->encounterTypes($tenantId),
                'bed_occupancy' => $this->bedOccupancy($tenantId),
                'top_medicines' => $this->topMedicines($tenantId),
                'claims_summary' => $this->claimsSummary($tenantId),
            ];
        });

        return Inertia::render('Analytics/Dashboard', $data);
    }

    private function monthlyRevenue(int $tenantId): array
    {
        $rows = DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('YEAR(paid_at) as yr, MONTH(paid_at) as mo, SUM(amount) as total')
            ->groupByRaw('YEAR(paid_at), MONTH(paid_at)')
            ->orderByRaw('YEAR(paid_at), MONTH(paid_at)')
            ->get()
            ->keyBy(fn ($r) => $r->yr.'-'.str_pad($r->mo, 2, '0', STR_PAD_LEFT));

        // Fill all 12 months so the chart has no gaps
        $result = [];
        for ($i = 11; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $key = $d->format('Y-m');
            $result[] = [
                'month' => $d->format('M Y'),
                'total' => (float) ($rows[$key]->total ?? 0),
            ];
        }

        return $result;
    }

    private function invoiceSummary(int $tenantId): array
    {
        $rows = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('status')
            ->get();

        $summary = ['total_outstanding' => 0, 'total_collected' => 0, 'by_status' => []];
        foreach ($rows as $r) {
            $summary['by_status'][$r->status] = ['count' => $r->count, 'total' => (float) $r->total];
            if (in_array($r->status, ['sent', 'partially_paid'])) {
                $summary['total_outstanding'] += $r->total;
            }
            if ($r->status === 'paid') {
                $summary['total_collected'] += $r->total;
            }
        }

        return $summary;
    }

    private function encounterTypes(int $tenantId): array
    {
        return DB::table('encounters')
            ->where('tenant_id', $tenantId)
            ->where('encounter_date', '>=', now()->subDays(30))
            ->selectRaw('encounter_type, COUNT(*) as count')
            ->groupBy('encounter_type')
            ->get()
            ->map(fn ($r) => ['type' => $r->encounter_type, 'count' => $r->count])
            ->toArray();
    }

    private function bedOccupancy(int $tenantId): array
    {
        $total = DB::table('beds')->where('tenant_id', $tenantId)->where('is_active', true)->count();
        $occupied = DB::table('beds')->where('tenant_id', $tenantId)->where('status', 'occupied')->count();
        $wards = DB::table('beds')
            ->join('wards', 'beds.ward_id', '=', 'wards.id')
            ->where('beds.tenant_id', $tenantId)
            ->where('beds.is_active', true)
            ->selectRaw('wards.name, COUNT(*) as total, SUM(beds.status = "occupied") as occupied')
            ->groupBy('wards.id', 'wards.name')
            ->get()
            ->map(fn ($r) => [
                'ward' => $r->name,
                'total' => $r->total,
                'occupied' => $r->occupied,
                'pct' => $r->total > 0 ? round($r->occupied / $r->total * 100, 1) : 0,
            ])
            ->toArray();

        return compact('total', 'occupied', 'wards');
    }

    private function topMedicines(int $tenantId): array
    {
        return DB::table('dispense_records')
            ->join('medicines', 'dispense_records.medicine_id', '=', 'medicines.id')
            ->where('dispense_records.tenant_id', $tenantId)
            ->where('dispense_records.dispensed_at', '>=', now()->subDays(30))
            ->selectRaw('medicines.name, SUM(quantity_dispensed) as qty')
            ->groupBy('medicines.id', 'medicines.name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'qty' => (int) $r->qty])
            ->toArray();
    }

    private function claimsSummary(int $tenantId): array
    {
        return DB::table('claims')
            ->where('tenant_id', $tenantId)
            ->selectRaw('status, COUNT(*) as count, SUM(amount_claimed) as claimed, COALESCE(SUM(amount_paid), 0) as paid')
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status,
                'count' => $r->count,
                'claimed' => (float) $r->claimed,
                'paid' => (float) $r->paid,
            ])
            ->toArray();
    }
}
