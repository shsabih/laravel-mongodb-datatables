<?php

namespace Shexpert\MDatatable\Tests;

use Shexpert\MDatatable\DatatableServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            DatatableServiceProvider::class,
        ];
    }
}
