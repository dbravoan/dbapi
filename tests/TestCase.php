<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // CI runner has no .env. The encrypter is a boot-time singleton so
        // APP_KEY must be in the environment *before* parent::setUp() boots.
        $envPath = dirname(__DIR__) . '/.env';
        if (!file_exists($envPath)) {
            copy(dirname(__DIR__) . '/.env.example', $envPath);
        }
        putenv('APP_KEY=base64:EF9ew1LqPrKSZJuat8QQJt/WL6ggXPkvie89ozhb5XI=');

        parent::setUp();

        if (!file_exists(storage_path('oauth-private.key'))) {
            $this->artisan('passport:keys', ['--force' => true, '--quiet' => true]);
        }
    }
}
