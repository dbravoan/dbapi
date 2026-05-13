<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Identity\User\Application\Response\UserResponse;
use Dbapi\Identity\User\Domain\User;
use Dbapi\Identity\User\Domain\UserId;
use Dbapi\Identity\User\Domain\UserName;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class UserRouteTest extends TestCase
{
    private const USER_ID = '550e8400-e29b-41d4-a716-446655440005';

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
            'app_id'           => 'app_users',
            'name'             => 'Users',
            'type'             => 'blog',
            'status'           => 'active',
            'enabled_modules'  => [],
            'allowed_versions' => null,
            'placement'        => 'pooled',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('api.supported_versions', ['v1']);
    }

    public function test_find_user_returns_404_when_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(null);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_users/v1/users/' . self::USER_ID);

        $response->assertStatus(404);
    }

    public function test_find_user_returns_200_when_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $user = User::create(
            new UserId(self::USER_ID),
            new UserName('John Doe'),
        );
        $userResponse = UserResponse::fromAggregate($user);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn($userResponse);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/app_users/v1/users/' . self::USER_ID);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', self::USER_ID);
        $response->assertJsonPath('data.name', 'John Doe');
    }

    public function test_create_user_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_users/v1/users', [
            'id'   => self::USER_ID,
            'name' => 'John Doe',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_user_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_users/v1/users', []);

        $response->assertStatus(422);
    }

    public function test_update_user_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->putJson('/app_users/v1/users/' . self::USER_ID, [
            'name' => 'Jane Doe',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_user_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->putJson('/app_users/v1/users/' . self::USER_ID, []);

        $response->assertStatus(422);
    }
}
