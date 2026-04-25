<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Pagination\Paginator;

use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (str_contains(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }
        Paginator::useBootstrapFive();
        view()->share('activeTemplateTrue', 'assets/xaxino/');
    }
}
