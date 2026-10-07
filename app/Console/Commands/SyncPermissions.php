<?php

namespace App\Console\Commands;

use App\Services\TenantProvisioningService;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Re-apply the permission catalogue and default role grants to every tenant';

    public function handle(TenantProvisioningService $provisioner): int
    {
        $count = $provisioner->syncAllTenants();

        $this->info("Permissions and default roles synced for {$count} tenant(s).");

        return self::SUCCESS;
    }
}
