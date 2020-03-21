<?php

namespace App\Routes;

use App\Util\RouteLoader;

abstract class Main
{
    public static function load(RouteLoader $r): void
    {
        $r->connect('main_all', '/main/all', \App\Controller\NetworkPublic::class);
    }
}
