<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The POS shares the MAIN Ekamuthu database. migrate:fresh and db:wipe drop
        // EVERY table in it (not just pos_*), which would destroy the main system.
        // Always blocked. Use `php artisan migrate:reset` to remove only the POS tables.
        FreshCommand::prohibit();
        WipeCommand::prohibit();

        // Fail loudly on N+1 queries during development.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Paginator::defaultView('partials.pagination');

        View::composer('*', function ($view) {
            static $settings;
            $settings ??= Setting::allValues();
            $view->with('settings', $settings);
        });
    }
}
