<?php

namespace Animizer\Laravel;

use Animizer\Scraper;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->app->singleton('animizer', function ($app) {
            return new Scraper();
        });

        $this->app->alias('animizer', Scraper::class);
    }

    public function register()
    {
    }
}