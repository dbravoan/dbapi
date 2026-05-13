<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Dbapi\Shared\Infrastructure\TenantContext;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Resolves the tenant from the URL path, then validates it against the registry.
 *
 * Pipeline:
 *   1. Extract app_id from route {tenant} parameter (normalised to lowercase)
 *   2. Validate format (regex)  → 400 on invalid format
 *   3. Look up in tenants table → 404 if not found
 *   4. Check status            → 403 if suspended or archived
 *   5. Store in TenantContext  → downstream middleware / handlers read from there
 */
final class IdentifyTenant
{
    private const APP_ID_PATTERN = '/^[a-z0-9][a-z0-9_-]{1,61}[a-z0-9]$/';

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantResolverInterface $tenantResolver,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $appId = $this->normalizeAppId($request->route('tenant'));

        if ($appId === null) {
            $appId = $this->normalizeAppId($request->header('X-App-ID'));
        }

        if ($appId === null) {
            return $next($request);
        }

        if (!$this->isValidAppId($appId)) {
            return $this->invalidTenantResponse();
        }

        // Registry lookup
        $tenant = $this->tenantResolver->resolve($appId);

        if ($tenant === null) {
            return $this->notFoundResponse();
        }

        if ($tenant->isSuspended()) {
            return $this->suspendedResponse();
        }

        if ($tenant->isArchived()) {
            return $this->archivedResponse();
        }

        $this->tenantContext->setAppId($appId);

        return $next($request);
    }

    private function normalizeAppId(mixed $appId): ?string
    {
        if (!is_string($appId)) {
            return null;
        }

        $trimmed = trim($appId);
        if ($trimmed === '') {
            return null;
        }

        return strtolower($trimmed);
    }

    private function isValidAppId(string $appId): bool
    {
        return (bool) preg_match(self::APP_ID_PATTERN, $appId);
    }

    private function invalidTenantResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Invalid tenant identifier',
            'errors' => [
                'tenant' => ['Use 3-63 chars: lowercase letters, numbers, hyphen or underscore.'],
            ],
        ], 400);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Not Found',
        ], 404);
    }

    private function suspendedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Tenant account is suspended',
            'errors' => [
                'tenant' => ['This tenant has been suspended. Contact support.'],
            ],
        ], 403);
    }

    private function archivedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Tenant account is archived',
            'errors' => [
                'tenant' => ['This tenant no longer exists.'],
            ],
        ], 404);
    }
}
