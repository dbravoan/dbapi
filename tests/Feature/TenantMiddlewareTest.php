<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

/**
 * Tests the full middleware pipeline:
 *   IdentifyTenant -> ValidateApiVersion -> RequireTenant -> (auth gate)
 *
 * TenantResolver is mocked so no DB connection is required.
 */
class TenantMiddlewareTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Build a Tenant model stub without touching the DB. */
    private function makeTenant(array $overrides = []): Tenant
    {
        $defaults = [
            'app_id'           => 'app_demo',
            'name'             => 'Demo App',
            'type'             => 'blog',
            'status'           => 'active',
            'enabled_modules'  => ['blog'],
            'allowed_versions' => null,
            'placement'        => 'pooled',
        ];

        $tenant = new Tenant(array_merge($defaults, $overrides));

        return $tenant;
    }

    /** Bind a fake TenantResolver that returns the given tenant (or null). */
    private function mockResolver(?Tenant $tenant): void
    {
        $resolver = \Mockery::mock(TenantResolverInterface::class);
        $resolver->shouldReceive('resolve')->andReturn($tenant);
        $resolver->shouldReceive('tenant')->andReturn($tenant);
        $this->app->instance(TenantResolverInterface::class, $resolver);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('api.supported_versions', ['v1']);
        config()->set('api.tenant_supported_versions', [
            'legacy_co' => ['v1', 'v2'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Format validation (happens before DB lookup)
    // -------------------------------------------------------------------------

    public function test_invalid_tenant_slug_returns_400(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $response = $this->postJson('/INVALID TENANT/v1/posts', []);

        $response
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid tenant identifier')
            ->assertJsonPath('success', false);
    }

    // -------------------------------------------------------------------------
    // Registry: unknown / suspended / archived
    // -------------------------------------------------------------------------

    public function test_unknown_tenant_returns_404(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver(null);

        $response = $this->postJson('/ghost_tenant/v1/posts', []);

        $response
            ->assertStatus(404)
            ->assertJsonPath('message', 'Not Found');
    }

    public function test_suspended_tenant_returns_403(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['status' => 'suspended', 'app_id' => 'bad_co']));

        $response = $this->postJson('/bad_co/v1/posts', []);

        $response
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tenant account is suspended');
    }

    public function test_archived_tenant_returns_404(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['status' => 'archived', 'app_id' => 'old_co']));

        $response = $this->postJson('/old_co/v1/posts', []);

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Active tenant: version validation
    // -------------------------------------------------------------------------

    public function test_active_tenant_with_valid_version_reaches_auth_gate(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_demo/v1/posts', [
            'id'       => '550e8400-e29b-41d4-a716-446655440000',
            'title'    => 'Test',
            'content'  => 'Body',
            'language' => 'en',
            'tag_names' => [],
        ]);

        // Reaches auth gate → 401
        $response->assertStatus(401);
    }

    public function test_unsupported_version_returns_400(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_demo/v3/posts', []);

        $response
            ->assertStatus(400)
            ->assertJsonPath('message', 'Unsupported API version')
            ->assertJsonPath('tenant', 'app_demo');
    }

    public function test_version_from_tenant_db_record_overrides_global_config(): void
    {
        // Tenant DB record explicitly allows only v2
        $tenant = $this->makeTenant(['allowed_versions' => ['v2']]);
        $this->mockResolver($tenant);

        // v2 is NOT in global config but IS in tenant record → should pass to auth gate
        $response = $this->postJson('/app_demo/v2/posts', [
            'id'       => '550e8400-e29b-41d4-a716-446655440000',
            'title'    => 'Test',
            'content'  => 'Body',
            'language' => 'en',
            'tag_names' => [],
        ]);

        $response->assertStatus(401); // reaches auth gate
    }

    public function test_version_not_in_tenant_db_record_is_rejected(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        // Tenant DB record explicitly allows only v2 — v1 should fail
        $tenant = $this->makeTenant(['allowed_versions' => ['v2']]);
        $this->mockResolver($tenant);

        $response = $this->postJson('/app_demo/v1/posts', []);

        $response
            ->assertStatus(400)
            ->assertJsonPath('message', 'Unsupported API version');
    }

    public function test_write_route_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_demo/v1/posts', [
            'id'       => '550e8400-e29b-41d4-a716-446655440000',
            'title'    => 'Test',
            'content'  => 'Body',
            'language' => 'en',
            'tag_names' => [],
        ]);

        $response->assertStatus(401);
    }
}
