<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant from the request subdomain and binds it into
 * TenantManager so every subsequent Eloquent query is automatically scoped.
 *
 * Central/admin domains bypass tenant binding (TenantManager::bypass()).
 * Unknown subdomains return 404.
 */
class IdentifyTenant
{
    public function __construct(private TenantManager $manager) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host      = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        // Central domains — Super Admin panel, platform-level API
        if ($this->isCentralDomain($subdomain)) {
            $this->manager->bypass();
            return $next($request);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::withoutGlobalScopes()
            ->where('slug', $subdomain)
            ->first();

        if (! $tenant) {
            abort(404, "Tenant '{$subdomain}' not found.");
        }

        if ($tenant->status !== 'active') {
            abort(403, "Tenant '{$subdomain}' is suspended.");
        }

        $this->manager->setCurrent($tenant);

        return $next($request);
    }

    private function extractSubdomain(string $host): string
    {
        // Strip port if present
        $host = strtolower(explode(':', $host)[0]);

        // For localhost or bare IP, treat as central
        if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            return '__central__';
        }

        $baseDomain  = config('app.base_domain', 'medcore.local');
        $parts       = explode('.', $host);
        $baseParts   = explode('.', $baseDomain);

        if (count($parts) <= count($baseParts)) {
            return '__central__';
        }

        return $parts[0];
    }

    private function isCentralDomain(string $subdomain): bool
    {
        return in_array($subdomain, ['admin', '__central__', 'www']);
    }
}
