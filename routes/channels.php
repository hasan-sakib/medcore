<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| All private channels are tenant-scoped — the user's tenant_id must match
| the {tenantId} segment, and they must hold the required permission.
*/

Broadcast::channel('tenant.{tenantId}.beds', function (User $user, int $tenantId): bool {
    return (int) $user->tenant_id === $tenantId && $user->can('beds.view');
});

Broadcast::channel('tenant.{tenantId}.or', function (User $user, int $tenantId): bool {
    return (int) $user->tenant_id === $tenantId && $user->can('operating-rooms.view');
});
