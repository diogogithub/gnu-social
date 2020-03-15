<?php

use App\Controller\NetworkPublic;

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('network.public', '/main/all')
           ->controller(NetworkPublic::class);
};
