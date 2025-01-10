<?php

namespace Santosdave\Sabre\Facades;

use Illuminate\Support\Facades\Facade;

class Sabre extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sabre';
    }
}