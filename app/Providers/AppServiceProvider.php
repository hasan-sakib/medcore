<?php

namespace App\Providers;

use App\Support\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);
    }

    public function boot(): void
    {
        // Strict mode in non-production to surface N+1, lazy-loading, mass-assignment issues early
        Model::shouldBeStrict(! $this->app->isProduction());

        // Prevent destructive commands in production
        DB::prohibitDestructiveCommands($this->app->isProduction());

        Vite::prefetch(concurrency: 3);
    }
}
