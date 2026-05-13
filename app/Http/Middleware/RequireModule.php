<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gates a route group behind a module being enabled for the resolved tenant.
 *
 * Usage in routes:
 *   Route::middleware('require.module:blog')->group(...)
 *   Route::middleware('require.module:todolist')->group(...)
 */
final class RequireModule
{
    public function __construct(private readonly TenantResolverInterface $tenantResolver) {}

    public function handle(Request $request, Closure $next, string $module): mixed
    {
        $tenant = $this->tenantResolver->tenant();

        if ($tenant === null) {
            // RequireTenant should have already blocked this, but be defensive
            return response()->json([
                'success' => false,
                'message' => 'Tenant context is required',
            ], 400);
        }

        if (!$tenant->hasModule($module)) {
            return $this->moduleNotEnabledResponse($module);
        }

        return $next($request);
    }

    private function moduleNotEnabledResponse(string $module): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "Module '{$module}' is not enabled for this tenant",
            'errors' => [
                'module' => ["Contact support to enable the '{$module}' module."],
            ],
        ], 403);
    }
}
