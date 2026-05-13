<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Blogging\Tag\Application\Response\TagResponse;
use Dbapi\Blogging\Tag\Domain\Tag;
use Dbapi\Blogging\Tag\Domain\TagId;
use Dbapi\Blogging\Tag\Domain\TagName;
use Dbapi\Blogging\Tag\Domain\TagSlug;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class TagRouteTest extends TestCase
{
    private const TAG_ID = '550e8400-e29b-41d4-a716-446655440003';

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

    public function test_find_tag_returns_404_when_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(null);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/tags/' . self::TAG_ID);

        $response->assertStatus(404);
    }

    public function test_find_tag_returns_200_when_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $tag = Tag::create(
            new TagId(self::TAG_ID),
            new TagName('Laravel'),
            new TagSlug('laravel'),
        );
        $tagResponse = TagResponse::fromAggregate($tag);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn($tagResponse);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/tags/' . self::TAG_ID);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', self::TAG_ID);
        $response->assertJsonPath('data.name', 'Laravel');
    }

    public function test_create_tag_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_blog/v1/tags', [
            'id'   => self::TAG_ID,
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_tag_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_blog/v1/tags', []);

        $response->assertStatus(422);
    }

    public function test_create_tag_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['todolist']]));

        $response = $this->postJson('/app_blog/v1/tags', [
            'id'   => self::TAG_ID,
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_tag_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->putJson('/app_blog/v1/tags/' . self::TAG_ID, [
            'name' => 'PHP',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_tag_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->putJson('/app_blog/v1/tags/' . self::TAG_ID, []);

        $response->assertStatus(422);
    }

    public function test_update_tag_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['todolist']]));

        $response = $this->putJson('/app_blog/v1/tags/' . self::TAG_ID, [
            'name' => 'PHP',
        ]);

        $response->assertStatus(403);
    }
}
