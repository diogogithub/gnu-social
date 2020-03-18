<?php

require_once 'bootstrap.php';

use App\Kernel;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$entityManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

return ConsoleRunner::createHelperSet($entityManager);
