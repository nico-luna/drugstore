<?php

namespace App\Providers;

use App\Tenancy\CurrentTenant;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentTenant::class, fn () => new CurrentTenant());
    }

    public function boot(): void
    {
        //
    }
}
