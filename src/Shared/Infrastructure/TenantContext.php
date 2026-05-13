<?php

declare(strict_types=1);

namespace Dbapi\Shared\Infrastructure;

final class TenantContext
{
    private ?string $appId = null;

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
        $this->applyContext();
    }

    public function appId(): ?string
    {
        return $this->appId;
    }

    private function applyContext(): void
    {
        if (null === $this->appId) {
            return;
        }

        // Dynamic logic to change database connection or table prefix
        // For now, we'll set a config value that models can use
        config(['database.tenant.app_id' => $this->appId]);
        
        // Example: Dynamic connection switching
        // if (config("database.connections.{$this->appId}")) {
        //     config(['database.default' => $this->appId]);
        // }
    }
}
