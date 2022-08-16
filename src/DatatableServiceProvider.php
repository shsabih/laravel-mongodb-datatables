<?php

namespace Shexpert\MDatatable;

use Illuminate\Support\ServiceProvider;
use Shexpert\MDatatable\ShexpertDatatable;

class DatatableServiceProvider extends ServiceProvider
{

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/shexpert-datatable.php' => config_path('shexpert-datatable.php'),
        ], 'shexpert-datatable-config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('shexpert_datatable', function($app) {
            return new ShexpertDatatable();
        });

        $this->mergeConfigFrom(
            __DIR__ . '/../config/shexpert-datatable.php',
            'shexpert-datatable'
        );
    }
}
