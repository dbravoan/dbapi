<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Blogging\Category\Application\Response\CategoryResponse;
use Dbapi\Blogging\Category\Domain\Category;
use Dbapi\Blogging\Category\Domain\CategoryId;
use Dbapi\Blogging\Category\Domain\CategoryName;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class CategoryRouteTest extends TestCase
{
    private const CATEGORY_ID = '550e8400-e29b-41d4-a716-446655440002';

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
            'app_id'           => 'app_blog',
            'name'             => 'Blog',
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

    public function test_find_category_returns_404_when_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(null);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/categories/' . self::CATEGORY_ID);

        $response->assertStatus(404);
    }

    public function test_find_category_returns_200_when_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $category = Category::create(
            new CategoryId(self::CATEGORY_ID),
            new CategoryName('Tech'),
        );
        $categoryResponse = CategoryResponse::fromAggregate($category);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn($categoryResponse);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/categories/' . self::CATEGORY_ID);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', self::CATEGORY_ID);
        $response->assertJsonPath('data.name', 'Tech');
    }

    public function test_create_category_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_blog/v1/categories', [
            'id'   => self::CATEGORY_ID,
            'name' => 'Tech',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_category_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_blog/v1/categories', []);

        $response->assertStatus(422);
    }

    public function test_create_category_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['todolist']]));

        $response = $this->postJson('/app_blog/v1/categories', [
            'id'   => self::CATEGORY_ID,
            'name' => 'Tech',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_category_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->putJson('/app_blog/v1/categories/' . self::CATEGORY_ID, [
            'name' => 'Technology',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_category_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->putJson('/app_blog/v1/categories/' . self::CATEGORY_ID, []);

        $response->assertStatus(422);
    }

    public function test_update_category_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['todolist']]));

        $response = $this->putJson('/app_blog/v1/categories/' . self::CATEGORY_ID, [
            'name' => 'Technology',
        ]);

        $response->assertStatus(403);
    }
}
