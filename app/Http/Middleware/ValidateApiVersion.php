<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Validates the {version} route parameter.
 *
 * Resolution order:
 *   1. Tenant record's `allowed_versions` column (DB) — if set and non-empty
 *   2. `api.tenant_supported_versions` config map (env-based fallback)
 *   3. `api.supported_versions` global config
 */
final class ValidateApiVersion
{
    public function __construct(private readonly TenantResolverInterface $tenantResolver) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = strtolower((string) $request->route('tenant'));
        $version = strtolower((string) $request->route('version'));
        $supportedVersions = $this->resolveSupportedVersions($tenant);

        if ($version === '' || !in_array($version, $supportedVersions, true)) {
            return $this->unsupportedVersionResponse($tenant, $supportedVersions);
        }

        return $next($request);
    }

    private function resolveSupportedVersions(string $tenant): array
    {
        $globalVersions = $this->normalizeVersions(config('api.supported_versions', ['v1']));

        // 1. Check DB record (already resolved by IdentifyTenant — no extra query)
        $tenantRecord = $this->tenantResolver->tenant();
        if ($tenantRecord !== null) {
            $dbVersions = $this->normalizeVersions($tenantRecord->allowedVersions() ?? []);
            if ($dbVersions !== []) {
                return $dbVersions;
            }
        }

        // 2. Env/config map fallback
        if ($tenant !== '') {
            $tenantVersionsMap = config('api.tenant_supported_versions', []);
            if (is_array($tenantVersionsMap)) {
                $configVersions = $this->normalizeVersions(Arr::get($tenantVersionsMap, $tenant, []));
                if ($configVersions !== []) {
                    return $configVersions;
                }
            }
        }

        // 3. Global fallback
        return $globalVersions;
    }

    private function normalizeVersions(mixed $versions): array
    {
        if (!is_array($versions)) {
            return [];
        }

        $normalized = [];
        foreach ($versions as $version) {
            if (!is_string($version)) {
                continue;
            }
            $trimmed = strtolower(trim($version));
            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function unsupportedVersionResponse(string $tenant, array $supportedVersions): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unsupported API version',
            'tenant' => $tenant,
            'errors' => [
                'version' => ['Use one of: ' . implode(', ', $supportedVersions)],
            ],
        ], 400);
    }
}
