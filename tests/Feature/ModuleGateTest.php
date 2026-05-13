<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

/**
 * Tests the require.module middleware gating blog and todolist routes.
 */
class ModuleGateTest extends TestCase
{
    private function mockResolver(?Tenant $tenant): void
    {
        $resolver = \Mockery::mock(TenantResolverInterface::class);
        $resolver->shouldReceive('resolve')->andReturn($tenant);
        $resolver->shouldReceive('tenant')->andReturn($tenant);
        $this->app->instance(TenantResolverInterface::class, $resolver);
    }

    private function makeTenant(array $overrides = []): Tenant
    {
        return new Tenant(array_merge([
            'app_id'           => 'app_demo',
            'name'             => 'Demo',
            'type'             => 'blog',
            'status'           => 'active',
            'enabled_modules'  => ['blog'],
            'allowed_versions' => null,
            'placement'        => 'pooled',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('api.supported_versions', ['v1']);
    }

    // -------------------------------------------------------------------------
    // Blog module gate
    // -------------------------------------------------------------------------

    public function test_blog_route_accessible_when_module_enabled(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        // Reaches auth gate → 401 (module gate passed)
        $response = $this->getJson('/app_demo/v1/posts/550e8400-e29b-41d4-a716-446655440000');
        // No auth required for GET — will hit the handler (likely 500 without DB, but not 403)
        $this->assertNotEquals(403, $response->status());
    }

    public function test_blog_route_blocked_when_module_disabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['todolist']])); // blog NOT enabled

        $response = $this->getJson('/app_demo/v1/posts/550e8400-e29b-41d4-a716-446655440000');

        $response
            ->assertStatus(403)
            ->assertJsonPath('message', "Module 'blog' is not enabled for this tenant");
    }

    public function test_blog_write_blocked_when_module_disabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => []]));

        $response = $this->postJson('/app_demo/v1/posts', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Test', 'content' => 'Body', 'language' => 'en', 'tag_names' => [],
        ]);

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // TodoList module gate
    // -------------------------------------------------------------------------

    public function test_todolist_route_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']])); // todolist NOT enabled

        $response = $this->getJson('/app_demo/v1/tasks/550e8400-e29b-41d4-a716-446655440000');

        $response
            ->assertStatus(403)
            ->assertJsonPath('message', "Module 'todolist' is not enabled for this tenant");
    }

    public function test_todolist_route_accessible_when_module_enabled(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog', 'todolist']]));

        $response = $this->getJson('/app_demo/v1/tasks/550e8400-e29b-41d4-a716-446655440000');

        // Passes module gate — not 403
        $this->assertNotEquals(403, $response->status());
    }

    // -------------------------------------------------------------------------
    // Users are NOT gated by any module
    // -------------------------------------------------------------------------

    public function test_users_route_accessible_regardless_of_modules(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => []])); // no modules

        $response = $this->getJson('/app_demo/v1/users/550e8400-e29b-41d4-a716-446655440000');

        $this->assertNotEquals(403, $response->status());
    }
}
