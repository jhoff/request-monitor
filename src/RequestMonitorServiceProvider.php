<?php

namespace Jhoff\RequestMonitor;

use Illuminate\Support\ServiceProvider;
use Jhoff\RequestMonitor\RequestMonitor;
use Stripe\ApiRequestor as StripeApiRequestor;
use Stripe\HttpClient\CurlClient as StripeCurlClient;

class RequestMonitorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->runningInConsole() && RequestMonitor::initialized()) {
            RequestMonitor::registerWith(app());
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
