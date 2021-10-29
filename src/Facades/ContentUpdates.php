<?php

namespace RTNatePHP\LaravelFileContentUpdates\Facades;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use RTNatePHP\LaravelFileContentUpdates\Contracts\ContentUpdates as ContractsContentUpdates;

/**
 * @see RTNatePHP\LaravelFileContentUpdates\Contracts\ContentUpdates
 */
class ContentUpdates extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ContractsContentUpdates::class;
    }
}
