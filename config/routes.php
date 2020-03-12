<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use App\Controller\NetworkPublic;

return function (RoutingConfigurator $routes) {
    $routes->add('network.public', '/main/all')
           ->controller(NetworkPublic::class);
};
