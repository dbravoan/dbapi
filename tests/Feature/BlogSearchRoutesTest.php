<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Blogging\Category\Application\Response\CategoriesResponse;
use Dbapi\Blogging\Category\Application\Response\CategoryResponse;
use Dbapi\Blogging\Category\Application\SearchByCriteria\CountCategoriesByCriteriaQuery;
use Dbapi\Blogging\Category\Application\SearchByCriteria\CountCategoriesByCriteriaResponse;
use Dbapi\Blogging\Category\Application\SearchByCriteria\SearchCategoriesByCriteriaQuery;
use Dbapi\Blogging\Post\Application\Response\PostsResponse;
use Dbapi\Blogging\Post\Application\SearchByCriteria\CountPostsByCriteriaQuery;
use Dbapi\Blogging\Post\Application\SearchByCriteria\CountPostsByCriteriaResponse;
use Dbapi\Blogging\Post\Application\SearchByCriteria\SearchPostsByCriteriaQuery;
use Dbapi\Blogging\Tag\Application\Response\TagResponse;
use Dbapi\Blogging\Tag\Application\Response\TagsResponse;
use Dbapi\Blogging\Tag\Application\SearchByCriteria\CountTagsByCriteriaQuery;
use Dbapi\Blogging\Tag\Application\SearchByCriteria\CountTagsByCriteriaResponse;
use Dbapi\Blogging\Tag\Application\SearchByCriteria\SearchTagsByCriteriaQuery;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Tests\TestCase;

class BlogSearchRoutesTest extends TestCase
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

    public function test_search_tags_returns_200_with_meta(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')
            ->with(\Mockery::type(SearchTagsByCriteriaQuery::class))
            ->andReturn(new TagsResponse(new TagResponse('id-1', 'php', 'php')));
        $queryBus->shouldReceive('ask')
            ->with(\Mockery::type(CountTagsByCriteriaQuery::class))
            ->andReturn(new CountTagsByCriteriaResponse(1));
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/tags');

        $response->assertStatus(200);
        $response->assertJsonPath('data.data.0.id', 'id-1');
        $response->assertJsonPath('data.meta.filtered_records', 1);
    }

    public function test_search_tags_blocked_when_blog_module_disabled(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => []]));

        $response = $this->getJson('/app_blog/v1/tags');

        $response->assertStatus(403);
    }

    public function test_search_categories_returns_200_with_meta(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')
            ->with(\Mockery::type(SearchCategoriesByCriteriaQuery::class))
            ->andReturn(new CategoriesResponse(new CategoryResponse('cat-1', 'Tech')));
        $queryBus->shouldReceive('ask')
            ->with(\Mockery::type(CountCategoriesByCriteriaQuery::class))
            ->andReturn(new CountCategoriesByCriteriaResponse(1));
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/categories');

        $response->assertStatus(200);
        $response->assertJsonPath('data.data.0.name', 'Tech');
        $response->assertJsonPath('message', 'Categories searched successfully');
    }

    public function test_search_categories_blocked_when_blog_module_disabled(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => []]));

        $response = $this->getJson('/app_blog/v1/categories');

        $response->assertStatus(403);
    }

    public function test_search_posts_returns_200_with_meta(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')
            ->with(\Mockery::type(SearchPostsByCriteriaQuery::class))
            ->andReturn(new PostsResponse());
        $queryBus->shouldReceive('ask')
            ->with(\Mockery::type(CountPostsByCriteriaQuery::class))
            ->andReturn(new CountPostsByCriteriaResponse(0));
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_blog/v1/posts');

        $response->assertStatus(200);
        $response->assertJsonPath('data.meta.filtered_records', 0);
    }

    public function test_search_posts_blocked_when_blog_module_disabled(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => []]));

        $response = $this->getJson('/app_blog/v1/posts');

        $response->assertStatus(403);
    }
}
