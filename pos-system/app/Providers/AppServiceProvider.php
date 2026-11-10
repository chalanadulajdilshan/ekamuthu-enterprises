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
        // migrate:fresh and db:wipe drop EVERY table in the database - on live that is
        // all sales, stock and customer history. Always blocked; to rebuild a test DB
        // use `php artisan migrate:reset` (runs each migration's down()).
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
