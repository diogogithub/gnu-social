<?php

namespace App\Controller;

use App\Util\GSEvent as Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NetworkPublic extends AbstractController
{
    public function __invoke()
    {
        Event::handle('Test', ['foobar']);

        return $this->render('network/public.html.twig', []);
    }
}
