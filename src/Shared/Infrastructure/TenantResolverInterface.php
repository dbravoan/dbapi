<?php

declare(strict_types=1);

namespace Dbapi\Shared\Infrastructure;

use App\Models\Tenant;

interface TenantResolverInterface
{
    public function resolve(string $appId): ?Tenant;

    public function tenant(): ?Tenant;

    public function reset(): void;
}
