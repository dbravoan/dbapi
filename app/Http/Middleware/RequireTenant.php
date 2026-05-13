<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Dbapi\Shared\Infrastructure\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RequireTenant
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if ($this->tenantContext->appId() === null) {
            return $this->missingTenantResponse();
        }

        return $next($request);
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Tenant context is required',
            'errors' => [
                'X-App-ID' => [
                    'Provide the X-App-ID header for tenant-scoped endpoints.',
                ],
            ],
        ], 400);
    }
}
