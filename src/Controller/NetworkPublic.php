<?php

namespace App\Controller;

use App\Util\GSEvent;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NetworkPublic extends AbstractController
{
    public function __invoke()
    {
        GSEvent::handle('test', ['foobar']);
        return $this->render('network/public.html.twig', []);
    }
}
