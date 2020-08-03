<?php

namespace Plugin\Test;

use App\Core\Module;
use App\Core\Router\RouteLoader;
use Plugin\Test\Controller\TestController;

class Test extends Module
{
    public function onTest(string $foo)
    {
        var_dump('Event handled: ' . $foo);
    }

    public function onAddRoute(RouteLoader $r)
    {
        $r->connect('test_foo', '/foo', TestController::class);
    }
}
