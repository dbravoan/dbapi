<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(\Dbapi\Shared\Infrastructure\TenantContext::class);
        $this->app->singleton(\Dbapi\Shared\Infrastructure\TenantResolver::class);
        $this->app->singleton(
            \Dbapi\Shared\Infrastructure\TenantResolverInterface::class,
            fn ($app) => $app->make(\Dbapi\Shared\Infrastructure\TenantResolver::class)
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
