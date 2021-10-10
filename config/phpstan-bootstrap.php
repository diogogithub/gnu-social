<?php

declare(strict_types = 1);

require_once 'bootstrap.php';

use App\Kernel;

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
return $kernel;
