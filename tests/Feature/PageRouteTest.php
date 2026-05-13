<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\PageManagement\Page\Application\Response\PageResponse;
use Dbapi\PageManagement\Page\Domain\Page;
use Dbapi\PageManagement\Page\Domain\PageId;
use Dbapi\PageManagement\Page\Domain\PageStatus;
use Dbapi\PageManagement\Page\Domain\PageTranslation;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class PageRouteTest extends TestCase
{
    private const PAGE_ID = '550e8400-e29b-41d4-a716-446655440004';

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
            'app_id'           => 'app_pages',
            'name'             => 'Pages',
            'type'             => 'blog',
            'status'           => 'active',
            'enabled_modules'  => ['pages'],
            'allowed_versions' => null,
            'placement'        => 'pooled',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('api.supported_versions', ['v1']);
    }

    public function test_find_page_returns_404_when_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(null);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_pages/v1/pages/' . self::PAGE_ID);

        $response->assertStatus(404);
    }

    public function test_find_page_returns_200_when_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $page = Page::create(
            new PageId(self::PAGE_ID),
            PageStatus::published(),
            new PageTranslation(
                languageCode: 'en',
                slug: 'about-us',
                title: 'About Us',
                content: [],
                seoTitle: null,
                seoDescription: null,
                seoKeywords: null,
                canonicalUrl: null,
                ogTitle: null,
                ogDescription: null,
                ogImage: null,
                structuredData: null,
            ),
        );
        $pageResponse = PageResponse::fromAggregate($page);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn($pageResponse);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_pages/v1/pages/' . self::PAGE_ID);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', self::PAGE_ID);
    }

    public function test_create_page_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_pages/v1/pages', [
            'id'            => self::PAGE_ID,
            'status'        => 'draft',
            'language_code' => 'en',
            'slug'          => 'about-us',
            'title'         => 'About Us',
            'content'       => [],
        ]);

        $response->assertStatus(401);
    }

    public function test_create_page_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_pages/v1/pages', []);

        $response->assertStatus(422);
    }

    public function test_create_page_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        $response = $this->postJson('/app_pages/v1/pages', [
            'id'            => self::PAGE_ID,
            'status'        => 'draft',
            'language_code' => 'en',
            'slug'          => 'about-us',
            'title'         => 'About Us',
            'content'       => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_update_page_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->putJson('/app_pages/v1/pages/' . self::PAGE_ID, [
            'language_code' => 'en',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_page_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->putJson('/app_pages/v1/pages/' . self::PAGE_ID, []);

        $response->assertStatus(422);
    }

    public function test_update_page_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        $response = $this->putJson('/app_pages/v1/pages/' . self::PAGE_ID, [
            'language_code' => 'en',
        ]);

        $response->assertStatus(403);
    }
}
