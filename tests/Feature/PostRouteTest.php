<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Blogging\Post\Application\Response\PostResponse;
use Dbapi\Blogging\Post\Domain\Post;
use Dbapi\Blogging\Post\Domain\PostContent;
use Dbapi\Blogging\Post\Domain\PostId;
use Dbapi\Blogging\Post\Domain\PostLanguage;
use Dbapi\Blogging\Post\Domain\PostName;
use Dbapi\Blogging\Post\Domain\PostSlug;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class PostRouteTest extends TestCase
{
    private const POST_ID = '550e8400-e29b-41d4-a716-446655440001';

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

    public function test_find_post_returns_404_when_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(null);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/posts/' . self::POST_ID);

        $response->assertStatus(404);
    }

    public function test_find_post_returns_200_when_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $post = Post::create(
            new PostId(self::POST_ID),
            new PostName('Test Post'),
            new PostSlug('test-post'),
            new PostContent('Content'),
            new PostLanguage('en'),
            null, null, null, null, null, null, null, null,
        );
        $postResponse = PostResponse::fromAggregate($post);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn($postResponse);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/posts/' . self::POST_ID);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', self::POST_ID);
    }

    public function test_create_post_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_blog/v1/posts', [
            'id'       => self::POST_ID,
            'title'    => 'Test Post',
            'content'  => 'Content',
            'language' => 'en',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_post_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_blog/v1/posts', []);

        $response->assertStatus(422);
    }

    public function test_create_post_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['todolist']]));

        $response = $this->postJson('/app_blog/v1/posts', [
            'id'       => self::POST_ID,
            'title'    => 'Test Post',
            'content'  => 'Content',
            'language' => 'en',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_post_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->putJson('/app_blog/v1/posts/' . self::POST_ID, [
            'language' => 'en',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_post_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->putJson('/app_blog/v1/posts/' . self::POST_ID, []);

        $response->assertStatus(422);
    }

    public function test_update_post_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['todolist']]));

        $response = $this->putJson('/app_blog/v1/posts/' . self::POST_ID, [
            'language' => 'en',
        ]);

        $response->assertStatus(403);
    }
}
