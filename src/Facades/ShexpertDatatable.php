<?php

namespace Shexpert\MDatatable;

use Illuminate\Support\Facades\Facade;

class ShexpertDatatable extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'shexpert_datatable';
    }
}
