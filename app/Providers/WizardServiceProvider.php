<?php

namespace App\Providers;

use App\Services\Files\TempFileService;
use App\Services\Wizard\WizardState;
use Illuminate\Support\ServiceProvider;

/*
|--------------------------------------------------------------------------
| WizardServiceProvider
|--------------------------------------------------------------------------
| Binds WizardState as a singleton wired to the active session, so the
| controller (and later, validation/services) can type-hint it directly.
|
| Register in bootstrap/providers.php:
|   App\Providers\WizardServiceProvider::class,
*/

class WizardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WizardState::class, function ($app) {
            return new WizardState($app['session.store']);
        });

        $this->app->singleton(TempFileService::class, function () {
            return TempFileService::fromConfig();
        });
    }

    public function boot(): void
    {
        //
    }
}
