<?php

namespace Santosdave\SabreWrapper\Facades;

use Illuminate\Support\Facades\Facade;

class Sabre extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sabre';
    }
}
