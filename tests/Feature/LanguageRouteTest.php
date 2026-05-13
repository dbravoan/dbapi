<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Language\Language\Application\FindAll\LanguageListResponse;
use Dbapi\Language\Language\Application\Response\LanguageResponse;
use Dbapi\Language\Language\Domain\Language;
use Dbapi\Language\Language\Domain\LanguageCode;
use Dbapi\Language\Language\Domain\LanguageId;
use Dbapi\Language\Language\Domain\LanguageIsActive;
use Dbapi\Language\Language\Domain\LanguageIsDefault;
use Dbapi\Language\Language\Domain\LanguageName;
use Dbapi\Language\Language\Domain\LanguageNativeName;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class LanguageRouteTest extends TestCase
{
    private const LANGUAGE_ID = '550e8400-e29b-41d4-a716-446655440006';

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
            'app_id'           => 'app_lang',
            'name'             => 'Lang',
            'type'             => 'blog',
            'status'           => 'active',
            'enabled_modules'  => ['languages'],
            'allowed_versions' => null,
            'placement'        => 'pooled',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('api.supported_versions', ['v1']);
    }

    public function test_find_language_returns_404_when_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(null);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_lang/v1/languages/' . self::LANGUAGE_ID);

        $response->assertStatus(404);
    }

    public function test_find_language_returns_200_when_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $language = Language::create(
            new LanguageId(self::LANGUAGE_ID),
            new LanguageCode('en'),
            new LanguageName('English'),
            new LanguageNativeName('English'),
            new LanguageIsDefault(true),
            new LanguageIsActive(true),
        );
        $languageResponse = LanguageResponse::fromAggregate($language);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn($languageResponse);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_lang/v1/languages/' . self::LANGUAGE_ID);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', self::LANGUAGE_ID);
    }

    public function test_find_language_blocked_when_module_not_enabled(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        $response = $this->getJson('/app_lang/v1/languages/' . self::LANGUAGE_ID);

        $response->assertStatus(403);
    }

    public function test_find_all_languages_returns_200(): void
    {
        $this->mockResolver($this->makeTenant());

        $language = Language::create(
            new LanguageId(self::LANGUAGE_ID),
            new LanguageCode('en'),
            new LanguageName('English'),
            new LanguageNativeName('English'),
            new LanguageIsDefault(true),
            new LanguageIsActive(true),
        );
        $languageResponse = LanguageResponse::fromAggregate($language);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')
            ->with(\Mockery::type(\Dbapi\Language\Language\Application\FindAll\FindAllLanguagesQuery::class))
            ->andReturn(new LanguageListResponse([$languageResponse]));
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_lang/v1/languages');

        $response->assertStatus(200);
    }

    public function test_find_all_languages_blocked_when_module_not_enabled(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        $response = $this->getJson('/app_lang/v1/languages');

        $response->assertStatus(403);
    }

    public function test_create_language_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_lang/v1/languages', [
            'id'          => self::LANGUAGE_ID,
            'code'        => 'en',
            'name'        => 'English',
            'native_name' => 'English',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_language_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_lang/v1/languages', []);

        $response->assertStatus(422);
    }

    public function test_create_language_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        $response = $this->postJson('/app_lang/v1/languages', [
            'id'          => self::LANGUAGE_ID,
            'code'        => 'en',
            'name'        => 'English',
            'native_name' => 'English',
        ]);

        $response->assertStatus(403);
    }
}
