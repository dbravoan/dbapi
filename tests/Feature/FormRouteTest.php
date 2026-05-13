<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Dbapi\Forms\Form\Application\Response\FormResponse;
use Dbapi\Forms\Form\Application\Submit\SubmitFormCommand;
use Dbapi\Forms\Form\Domain\FormInactiveException;
use Dbapi\Forms\Form\Domain\FormNotFoundException;
use Dbapi\Forms\Form\Domain\FormValidationFailedException;
use Dbapi\Shared\Infrastructure\TenantResolverInterface;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class FormRouteTest extends TestCase
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
            'app_id'           => 'app_forms',
            'name'             => 'Forms',
            'type'             => 'blog',
            'status'           => 'active',
            'enabled_modules'  => ['forms'],
            'allowed_versions' => null,
            'placement'        => 'pooled',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('api.supported_versions', ['v1']);
    }

    public function test_submit_form_returns_403_when_honeypot_field_is_filled(): void
    {
        $this->mockResolver($this->makeTenant());

        // Bus should never be called when honeypot trips.
        $commandBus = \Mockery::mock(CommandBus::class);
        $commandBus->shouldNotReceive('dispatch');
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_forms/v1/forms/contact-us/submit', [
            'name' => 'Bot',
            'honeypot' => 'iam-a-bot',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Spam detected by honeypot.');
    }

    public function test_submit_form_also_honors_legacy_hp_field(): void
    {
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $commandBus->shouldNotReceive('dispatch');
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_forms/v1/forms/contact-us/submit', [
            'name' => 'Bot',
            '_hp' => 'iam-a-bot',
        ]);

        $response->assertStatus(403);
    }

    public function test_submit_form_returns_404_when_form_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $commandBus->shouldReceive('dispatch')
            ->with(\Mockery::type(SubmitFormCommand::class))
            ->andThrow(FormNotFoundException::forKey('missing'));
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_forms/v1/forms/missing/submit', [
            'name' => 'John',
        ]);

        $response->assertStatus(404);
    }

    public function test_submit_form_returns_403_when_form_inactive(): void
    {
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $commandBus->shouldReceive('dispatch')
            ->andThrow(FormInactiveException::forKey('paused'));
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_forms/v1/forms/paused/submit', [
            'name' => 'John',
        ]);

        $response->assertStatus(403);
    }

    public function test_submit_form_returns_422_when_validation_fails(): void
    {
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $commandBus->shouldReceive('dispatch')
            ->andThrow(new FormValidationFailedException(
                'The email field is required.',
                ['email' => ['The email field is required.']],
            ));
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_forms/v1/forms/contact-us/submit', [
            'name' => 'John',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The email field is required.');
    }

    public function test_submit_form_returns_202_on_success(): void
    {
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $commandBus->shouldReceive('dispatch')
            ->with(\Mockery::type(SubmitFormCommand::class))
            ->once();
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_forms/v1/forms/contact-us/submit', [
            'name' => 'John',
            'email' => 'j@example.com',
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('success', true);
    }

    public function test_submit_form_blocked_when_forms_module_not_enabled(): void
    {
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        $response = $this->postJson('/app_forms/v1/forms/contact-us/submit', [
            'name' => 'John',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_form_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->postJson('/app_forms/v1/forms', [
            'name' => 'Contact',
            'key' => 'contact',
            'fields' => [
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_create_form_validates_required_fields(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant());

        $commandBus = \Mockery::mock(CommandBus::class);
        $this->app->instance(CommandBus::class, $commandBus);

        $response = $this->postJson('/app_forms/v1/forms', []);

        $response->assertStatus(422);
    }

    public function test_find_form_returns_404_when_not_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(null);
        $this->app->instance(QueryBus::class, $queryBus);

        $this->withoutMiddleware(Authenticate::class);
        $response = $this->getJson('/app_forms/v1/forms/1');

        $response->assertStatus(404);
    }

    public function test_find_form_returns_200_when_found(): void
    {
        $this->mockResolver($this->makeTenant());

        $formResponse = new FormResponse(
            id: 1,
            name: 'Contact',
            key: 'contact',
            recipientEmail: null,
            active: true,
            fields: [],
        );

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn($formResponse);
        $this->app->instance(QueryBus::class, $queryBus);

        $this->withoutMiddleware(Authenticate::class);
        $response = $this->getJson('/app_forms/v1/forms/1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonPath('data.name', 'Contact');
    }

    public function test_find_form_requires_authentication(): void
    {
        $this->mockResolver($this->makeTenant());

        $response = $this->getJson('/app_forms/v1/forms/1');

        $response->assertStatus(401);
    }

    public function test_find_form_blocked_when_module_not_enabled(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->mockResolver($this->makeTenant(['enabled_modules' => ['blog']]));

        $response = $this->getJson('/app_forms/v1/forms/1');

        $response->assertStatus(403);
    }
}
