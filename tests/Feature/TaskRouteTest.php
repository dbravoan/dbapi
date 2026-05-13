<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dbapi\TodoList\Task\Application\Response\TaskResponse;
use Dbapi\TodoList\Task\Domain\Task;
use Dbapi\TodoList\Task\Domain\TaskId;
use Dbapi\TodoList\Task\Domain\TaskPriority;
use Dbapi\TodoList\Task\Domain\TaskStatus;
use Dbapi\TodoList\Task\Domain\TaskTitle;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class TaskRouteTest extends TestCase
{
    private const TASK_ID = '550e8400-e29b-41d4-a716-446655440000';

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
            'app_id'           => 'my_app',
            'name'             => 'My App',
            'type'             => 'todolist',
            'status'           => 'active',
            'enabled_modules'  => ['todolist'],
            'allowed_versions' => null,
            'placement'        => 'pooled',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('api.supported_versions', ['v1']);
    }

    public function test_find_task_returns_404_when_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(null);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/my_app/v1/tasks/' . self::TASK_ID);

        $response->assertStatus(404);
    }

    public function test_find_task_returns_200_when_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $task = Task::create(
            new TaskId(self::TASK_ID),
            new TaskTitle('Buy milk'),
            TaskStatus::pending(),
            TaskPriority::low(),
            null,
        );
        $taskResponse = TaskResponse::fromAggregate($task);

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn($taskResponse);
        $this->app->instance(QueryBus::class, $queryBus);

        $response = $this->getJson('/my_app/v1/tasks/' . self::TASK_ID);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', self::TASK_ID);
        $response->assertJsonPath('data.title', 'Buy milk');
    }

    public function test_create_task_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/my_app/v1/tasks', [
            'id'       => self::TASK_ID,
            'title'    => 'Buy milk',
            'status'   => 'pending',
            'priority' => 'low',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_task_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/my_app/v1/tasks', []);

        $response->assertStatus(422);
    }

    public function test_create_task_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        $response = $this->postJson('/my_app/v1/tasks', [
            'id'       => self::TASK_ID,
            'title'    => 'Buy milk',
            'status'   => 'pending',
            'priority' => 'low',
        ]);

        $response->assertStatus(403);
    }
}
