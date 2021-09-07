<?php

require_once 'bootstrap.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
return $kernel->getContainer()->get('doctrine')->getManager();
