<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Services\GreetingService;
use Flare;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Flare::determineVersionUsing(function () {
            $versionFile = base_path('VERSION');

            if (! File::exists($versionFile)) {
                return __('Unknown');
            }

            return str_replace("\n", '', File::get($versionFile));
        });

        $this->app->singleton(GreetingService::class, function ($app) {
            return new GreetingService;
        });

        $this->app->alias(GreetingService::class, 'Greeting');
    }

    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });
    }
}
