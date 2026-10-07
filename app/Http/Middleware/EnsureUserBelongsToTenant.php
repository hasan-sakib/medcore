<?php

namespace App\Http\Middleware;

use App\Support\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The session cookie is shared across *.medcore.local, so a user signed in to hospital A
 * still arrives authenticated when they open hospital B's subdomain. Routes that carry no
 * permission middleware (dashboard, profile, ...) would then render B's data to A's user.
 *
 * If the signed-in user belongs to a different tenant than the host, sign them out and send
 * them to this host's login. Super admins (tenant_id NULL) are not tenant-bound.
 */
class EnsureUserBelongsToTenant
{
    public function __construct(private TenantManager $manager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $this->manager->hasCurrent()
            && $user->tenant_id !== null
            && (int) $user->tenant_id !== (int) $this->manager->current()->id
        ) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
