<?php

namespace App\Jobs;

use App\Models\MedicineBatch;
use App\Models\Tenant;
use App\Services\InventoryService;
use App\Support\TenantManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Daily sweep: flips active batches past their expiry date to 'expired' so they are
 * never dispensed, and logs near-expiry / low-stock counts per tenant.
 */
class ExpireMedicineBatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $alertDays = 30) {}

    public function handle(InventoryService $inventory, TenantManager $tenants): void
    {
        foreach (Tenant::withoutGlobalScopes()->where('status', 'active')->get() as $tenant) {
            // Re-establish tenant context so the scoped queries below only see this tenant.
            $tenants->setCurrent($tenant);

            try {
                $expired = MedicineBatch::where('status', 'active')
                    ->where('expiry_date', '<', now()->toDateString())
                    ->update(['status' => 'expired']);

                $nearExpiry = $inventory->getExpiryAlerts($this->alertDays)->count();
                $lowStock = $inventory->getLowStockAlerts()->count();

                if ($expired || $nearExpiry || $lowStock) {
                    Log::warning('Pharmacy stock sweep', [
                        'tenant_id' => $tenant->id,
                        'newly_expired_batches' => $expired,
                        'expiring_within_days' => [$this->alertDays => $nearExpiry],
                        'low_stock_medicines' => $lowStock,
                    ]);
                }
            } finally {
                $tenants->clearCurrent();
            }
        }
    }
}
