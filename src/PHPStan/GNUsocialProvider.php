<?php

namespace App\PHPStan;

use App\Core\Event;
use App\Core\GNUsocial;
use App\Kernel;
use Functional as F;

class GNUsocialProvider
{
    private ?GNUsocial $gnu_social = null;
    private ?Kernel $kernel        = null;

    public function __construct()
    {
        if (isset($_ENV['PHPSTAN_BOOT_KERNEL'])) {
            $this->kernel = require __DIR__ . '/../../config/phpstan-bootstrap.php';
            $container    = $this->kernel->getContainer()->get('test.service_container');
            $services     = F\map(
                (new \ReflectionClass(GNUsocial::class))->getMethod('__construct')->getParameters(),
                fn ($p) => $container->get((string) $p->getType())
            );
            $this->gnu_social = new GNUsocial(...$services);
            $this->gnu_social->initialize();
            Event::handle('InitializeModule');
        }
    }

    public function getGNUsocial(): GNUsocial
    {
        return $this->gnu_social;
    }
}
