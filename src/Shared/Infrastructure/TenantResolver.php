<?php

declare(strict_types=1);

namespace Dbapi\Shared\Infrastructure;

use App\Models\Tenant;

/**
 * Resolves and caches a Tenant record by app_id for the current request lifecycle.
 *
 * Injected as a singleton; each HTTP request gets a fresh instance via the
 * singleton registered in AppServiceProvider.
 */
final class TenantResolver implements TenantResolverInterface
{
    private ?Tenant $resolved = null;
    private bool $attempted = false;

    /**
     * Look up a tenant by app_id.
     * Returns null if not found.
     * Caches the result for the duration of the request.
     */
    public function resolve(string $appId): ?Tenant
    {
        if ($this->attempted) {
            return $this->resolved;
        }

        $this->attempted = true;
        $this->resolved = Tenant::where('app_id', $appId)->first();

        return $this->resolved;
    }

    /**
     * Returns the already-resolved tenant (or null if not yet resolved / not found).
     */
    public function tenant(): ?Tenant
    {
        return $this->resolved;
    }

    /**
     * Reset state — useful for testing.
     */
    public function reset(): void
    {
        $this->resolved = null;
        $this->attempted = false;
    }
}
